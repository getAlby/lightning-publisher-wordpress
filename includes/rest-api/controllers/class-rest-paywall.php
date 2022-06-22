<?php

use \Firebase\JWT;

// Exit if accessed directly
defined( 'WPINC' ) || die;


/**
 * @file
 * REST API Endpoint that handles Paywall
 */
class LNP_PaywallController extends \WP_REST_Controller {

    /**
     * Register route for file uploads from remote repository
     */
    public function register_routes() {

        $this->namespace = 'lnp-alby/v1';

        register_rest_route(
            $this->namespace,
            "paywall/pay",
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'process_paywall_payment_request'),
                    'permission_callback' => '__return_true',
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            'paywall/verify',
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'process_paywall_verify_request'),
                    'permission_callback' => '__return_true',
                ),
            )
        );
        
        register_rest_route(
            $this->namespace,
            'account',
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_lnp_hub_account'),
                    'permission_callback' => '__return_true',
                ),
            )
        );
    }



    /**
     * Process paywall payment request
     * 
     * @param  object $request WP_REST_Request
     * @return array           Invoice data or error message
     */
    public function process_paywall_payment_request( $request ) {
        ob_start();
        $plugin = $this->get_plugin();
        $logger = $plugin->get_logger();
        $post_id = intval( $request->get_param('post_id') );
        $all = intval( $request->get_param('all') );
        if (!empty($post_id)) {
            $paywall = new WP_Lightning_Paywall($plugin, get_post_field('post_content', $post_id));
            $paywall_options = $paywall->getOptions();
            if (!$paywall_options) {
                $logger->error('Paywall options not found', ['post_id' => $post_id]);
                ob_end_clean();
                return wp_send_json(['error' => 'invalid post'], 404);
            }
            $memo = get_bloginfo('name') . ' - ' . get_the_title($post_id);
            $amount = $paywall_options['amount'];
            $response_data = ['post_id' => $post_id, 'amount' => $amount];
        } elseif (!empty($all)) {
            $memo = get_bloginfo('name');
            $database_options = $plugin->getPaywallOptions();
            $amount = $database_options['all_amount'];
            $response_data = ['all' => true, 'amount' => $amount];
        } else {
            $logger->error('Invalid post');
            ob_end_clean();
            return new \WP_Error(__('Invalid Request, Missing required parameters', 'lnp-alby'));
        }

        if (!$amount) {
            $amount = 1000;
        }

        $memo = substr($memo, 0, 64);
        $memo = preg_replace('/[^\w_ ]/', '', $memo);
        $invoice_params = [
            'memo' => $memo,
            'value' => $amount, // in sats
            'expiry' => 1800,
            'private' => true
        ];
        $invoice = $plugin->getLightningClient()->addInvoice($invoice_params);
        $plugin->getDatabaseHandler()->store_invoice($post_id, $invoice['r_hash'], $invoice['payment_request'], $amount, '', 0);

        $jwt_data = array_merge($response_data, ['invoice_id' => $invoice['r_hash'], 'r_hash' => $invoice['r_hash'], 'exp' => time() + 60 * 10]);
        $jwt = JWT\JWT::encode($jwt_data, WP_LN_PAYWALL_JWT_KEY,  WP_LN_PAYWALL_JWT_ALGORITHM);

        $response = array_merge($response_data, ['token' => $jwt, 'payment_request' => $invoice['payment_request']]);
        $logger->info('Invoice created successfully', $response);
        ob_end_clean();
        return rest_ensure_response($response);
    }



    /**
     * Verify has invoice been paid
     * 
     * @param  object $request WP_REST_Request
     * @return array           Invoice data or error message
     */
    public function process_paywall_verify_request( $request )
    {   
        ob_start();
        $plugin = $this->get_plugin();
        $logger = $plugin->get_logger();
        $token    = $request->get_param('token');
        $preimage = $request->get_param('preimage');
        if (empty($token)) {
            $logger->error('Token not provided');
            ob_end_clean();
            return wp_send_json(['settled' => false], 404);
        }
        try {
            $jwt = JWT\JWT::decode($token, new JWT\Key(WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM));
        } catch (Exception $e) {
            $logger->error('Unable to decode token');
            ob_end_clean();
            return wp_send_json(['settled' => false], 404);
        }

        // if we get a preimage we can check if the preimage matches the payment hash and accept it.
        if (!empty($preimage) && hash('sha256', hex2bin($preimage), false) == $jwt->{"r_hash"}) {
            $invoice = ['settled' => true];
            // if ew do not have a preimage we must check with the LN node if the invoice was paid.
        } else {
            $invoice_id = $jwt->{'invoice_id'};
            $invoice = $plugin->getLightningClient()->getInvoice($invoice_id);
        }

        // TODO check amount?
        if ($invoice && $invoice['settled']) { // && (int)$invoice['value'] == (int)$jwt->{'amount'}) {
            $post_id = $jwt->{'post_id'};
            $plugin->getDatabaseHandler()->update_invoice_state($jwt->{'r_hash'}, 'settled');
            if (!empty($post_id)) {
                $content = get_post_field('post_content', $post_id);
                $paywall = new WP_Lightning_Paywall($plugin, $content);
                $protected = $paywall->getProtectedContent();
                WP_Lightning::save_as_paid($post_id, $invoice['value']);
                $logger->info('Payment saved successfully', ['post_id'=> $post_id, 'invoice' => $invoice]);
                ob_end_clean();
                wp_send_json($protected, 200);
            } elseif (!empty($jwt->{'all'})) {
                WP_Lightning::save_paid_all($plugin->getPaywallOptions()['all_days']);
                $logger->info('Payment saved successfully', ['post_id'=> 'all', 'invoice' => $invoice]);
                ob_end_clean();
                wp_send_json($plugin->getPaywallOptions()['all_confirmation'], 200);
            }
        } else {
            $logger->error('Payment couldn\'t be saved', ['invoice' => $invoice]);
            ob_end_clean();
            wp_send_json(['settled' => false], 402);
        }
    }

    /**
     * Create LNPHub Account
     */
    public function create_lnp_hub_account()
    {
        ob_start();
        $plugin = $this->get_plugin();
        $logger = $plugin->get_logger();
        try {
            $account = LNDHub\Client::createWallet("https://ln.getalby.com", "bluewallet");
            $logger->info('LNDHub Wallet Created', ['account' => $account]);
            ob_end_clean();
            wp_send_json($account, 200);
        }catch(Exception $e) {
            ob_end_clean();
            wp_send_json($e, 500);
        }
    }


    /**
     * Main plugin instance
     * 
     * @return object
     */
    private function get_plugin()
    {
        return $this->plugin;
    }


    /**
     * Main plugin instance
     * This will provide access to LND Client
     * 
     * @param object $plugin
     */
    public function set_plugin_instance( &$plugin )
    {
        $this->plugin = $plugin;
    }


    /**
     * Attributes
     */
    public function get_endpoint_args_for_item_schema( $method = \WP_REST_Server::CREATABLE ) {

        $params = array();

        $params['post_id'] = array(
            'default'           => 0,
            'description'       => __( 'Post where paywall payment was made', 'lnp-alby' ),
            'type'              => 'integer',
            'sanitize_callback' => 'intval',
            'validate_callback' => 'rest_validate_request_arg',
        );

        return $params;
    }
}
