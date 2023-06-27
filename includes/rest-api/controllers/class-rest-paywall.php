<?php

use \Firebase\JWT;

// Exit if accessed directly
defined('WPINC') || die;


/**
 * @file
 * REST API Endpoint that handles Paywall
 */
class LNP_PaywallController extends \WP_REST_Controller
{

    public function register_routes()
    {

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

    }



    /**
     * Process paywall payment request
     *
     * @param  object $request WP_REST_Request
     * @return array           Invoice data or error message
     */
    public function process_paywall_payment_request( $request )
    {
        $plugin = $this->get_plugin();
        $post_id = intval($request->get_param('post_id'));

        if (empty($post_id)) {
            return new \WP_Error(__('Invalid Request, Missing required parameters', 'lnp-alby'));
        }

        $post = get_post($post_id);
        // get the content of the post and apply the blocks
        // this adds the "shortcode" to the content if the Gutenberg block is used
        // the shortcode is then parsed in BLN_Publisher_Paywall with a regex
        $content = do_blocks($post->post_content);
        $paywall = new BLN_Publisher_Paywall($plugin, ['content' => $content, 'post_id' => $post_id]);
        $paywall_options = $paywall->get_options();
        if (!$paywall_options) {
            return wp_send_json(['error' => 'invalid post'], 404);
        }
        $memo = get_bloginfo('name') . ' - ' . get_the_title($post_id);
        $amount = $paywall_options['amount'];
        $currency = strtolower($paywall_options['currency']);

        if ($currency != 'btc') {
            $exchange_rate = $plugin->get_current_exchange_rate($currency);
            $amount = $plugin->convert_to_sats($amount, $currency, $exchange_rate);
        } else {
            $exchange_rate = 1;
        }
        $response_data = ['post_id' => $post_id, 'amount' => $amount];

        $memo = substr($memo, 0, 64);
        $memo = preg_replace('/[^\w_ ]/', '', $memo);
        $invoice_params = [
            'memo' => $memo,
            'value' => $amount, // in sats
            'expiry' => 1800,
            'private' => true
        ];
        $invoice = $plugin->getLightningClient($post_id)->addInvoice($invoice_params);
        $plugin->getDatabaseHandler()->store_invoice(
            [
            "post_id" => $post_id,
            "invoice_type" => "paywall",
            "payment_hash" => $invoice['r_hash'],
            "payment_request" => $invoice['payment_request'],
            "amount_in_satoshi" => $amount,
            "exchange_currency" => $currency,
            "exchange_rate" => $exchange_rate
            ]
        );

        // if the invoice includes a custom invoice_id we use that
        // otherwise we default to the r_hash
        // this is currently mainly used in the lightning address
        $invoice_id = empty($invoice['id']) ? $invoice['r_hash'] : $invoice['id'];
        $jwt_data = array_merge($response_data, ['invoice_id' => $invoice_id, 'amount' => $amount, 'r_hash' => $invoice['r_hash'], 'exp' => time() + 60 * 10]);
        $jwt = JWT\JWT::encode($jwt_data, BLN_PUBLISHER_PAYWALL_JWT_KEY,  BLN_PUBLISHER_PAYWALL_JWT_ALGORITHM);

        $response = array_merge($response_data, ['token' => $jwt, 'payment_request' => $invoice['payment_request']]);
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
        $plugin = $this->get_plugin();
        $token    = $request->get_param('token');
        $preimage = $request->get_param('preimage');
        if (empty($token)) {
            return wp_send_json(['settled' => false, 'error' => 'missing token'], 404);
        }
        try {
            $jwt = JWT\JWT::decode($token, new JWT\Key(BLN_PUBLISHER_PAYWALL_JWT_KEY, BLN_PUBLISHER_PAYWALL_JWT_ALGORITHM));
        } catch (\Exception $e) {
            return wp_send_json(['settled' => false, 'error' => 'token decode error'], 404);
        }

        // if we get a preimage we can check if the preimage matches the payment hash and accept it.
        if (!empty($preimage) && hash('sha256', hex2bin($preimage), false) == $jwt->{"r_hash"}) {
            $invoice = ['settled' => true, 'amount' => $jwt->{"amount"}];
            // if we do not have a preimage we must check with the LN node if the invoice was paid.
        } else {
            $post_id = $jwt->{'post_id'};
            $invoice_id = $jwt->{'invoice_id'};
            $invoice = $plugin->getLightningClient($post_id)->getInvoice($invoice_id);
        }

        // TODO check amount?
        if ($invoice && $invoice['settled']) { // && (int)$invoice['value'] == (int)$jwt->{'amount'}) {
            $post_id = $jwt->{'post_id'};
            $plugin->getDatabaseHandler()->update_invoice_state($jwt->{'r_hash'}, 'settled');

            $post = get_post($post_id);
            // get the content of the post and apply the blocks
            // this adds the "shortcode" to the content if the Gutenberg block is used
            // the shortcode is then parsed in BLN_Publisher_Paywall with a regex
            $content = do_blocks($post->post_content);
            $content = do_shortcode($content);
            $paywall = new BLN_Publisher_Paywall($plugin, ['content' => $content, 'post_id' => $post_id]);
            $protected = $paywall->get_protected_content();

            // fallback to use either value or amount from the invoice response
            // the connections are inconsitent there
            if (array_key_exists("value", $invoice)) {
                $amount = $invoice["value"];
            } elseif (array_key_exists("amount", $invoice)) {
                $amount = $invoice["amount"];
            } else {
                $amount = $jwt->{"amount"}; // fallback to the jwt data
            }
            $plugin->save_as_paid($post_id, $amount);
            wp_send_json($protected, 200);
        } else {
            wp_send_json(['settled' => false], 402);
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
    public function get_endpoint_args_for_item_schema( $method = \WP_REST_Server::CREATABLE )
    {

        $params = array();

        $params['post_id'] = array(
            'default'           => 0,
            'description'       => __('ID of the post that is requested for payment', 'lnp-alby'),
            'type'              => 'integer',
            'sanitize_callback' => 'intval',
            'validate_callback' => 'rest_validate_request_arg',
        );

        return $params;
    }
}
