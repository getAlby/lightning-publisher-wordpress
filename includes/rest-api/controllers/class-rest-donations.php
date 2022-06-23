<?php

use \Firebase\JWT;

// Exit if accessed directly
defined( 'WPINC' ) || die;


/**
 * @file
 * REST API Endpoint that handles donations
 */
class LNP_DonationsController extends \WP_REST_Controller {

    public function register_routes() {

        $this->namespace = 'lnp-alby/v1';
        $this->rest_base = 'donate';

        register_rest_route(
            $this->namespace,
            $this->rest_base,
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'process_donate_request'),
                    'permission_callback' => '__return_true',
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            'verify',
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'process_verify_request'),
                    'permission_callback' => '__return_true',
                ),
            )
        );
    }



    /**
     * Process donation request
     *
     * @param  object $request WP_REST_Request
     * @return array           Invoice data or error message
     */
    public function process_donate_request( $request ) {
        ob_start();
        // Amount user is trying to donate
        $amount  = intval( $request->get_param('amount') );
        $post_id = intval( $request->get_param('post_id') );

        // Don't allow less than 100 SATS
        if ( $amount < 100 )
        {
            ob_end_clean();
            return new \WP_Error(__('Mimimum domation amount is 100 SATS', 'lnp-alby'));
        }

        // Don't allow less than 100 SATS
        if ( ! $post_id )
        {
            ob_end_clean();
            return new \WP_Error(__('Invalid Request, post_id missing', 'lnp-alby'));
        }

        $invoice  = $this->create_invoice($post_id, $amount);
        //$resposne =

        // error_log( print_r($request, true) );
        ob_end_clean();
        return rest_ensure_response($invoice);
    }



    /**
     * Verify has invoice been paid
     *
     * @param  object $request WP_REST_Request
     * @return array           Invoice data or error message
     */
    public function process_verify_request( $request )
    {
        ob_start();
        $token    = $request->get_param('token');
        $amount   = $request->get_param('amount');
        $preimage = $request->get_param('preimage');
        $plugin   = $this->get_plugin();
        $settled  = false;
        $invoice  = false;

        // Default response
        $response = array(
            'settled' => false
        );

        // No token
        if ( empty($token) )
        {
            ob_end_clean();
            return wp_send_json_error($response, 404);
        }


        try {
            $jwt = JWT\JWT::decode(
                $token,
                new JWT\Key(WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM)
            );
        }
        catch (Exception $e) {
            ob_end_clean();
            return wp_send_json_error($response, 404);
        }

        // if we get a preimage we can check if the preimage matches the payment hash and accept it.
        if (
            ( ! empty($preimage) )
            && hash('sha256', hex2bin($preimage), false) == $jwt->{"r_hash"}
            )
        {
            $settled  = true;
            $response = array('settled' => true);
        }
        // if ew do not have a preimage we must check with the LN node if the invoice was paid.
        else
        {
            $invoice_id = $jwt->{'invoice_id'};
            $invoice    = $plugin->getLightningClient()->getInvoice($invoice_id);
        }


        if ($invoice && $settled)
        {
            $post_id = $jwt->{'post_id'};
            $plugin->getDatabaseHandler()->update_invoice_state($jwt->{'r_hash'}, 'settled');

            if (!empty($post_id))
            {
                $plugin->save_as_paid($post_id, $amount);
                ob_end_clean();
                wp_send_json_success($response, 200);
            }
        }

        ob_end_clean();
        return wp_send_json_error($response, 402);
    }



    /**
     * Create Invoice
     *
     * @param  object $request WP_REST_Request
     * @return [type]          [description]
     */
    private function create_invoice( int $post_id, int $amount )
    {
        ob_start();
        $memo = get_bloginfo('name') . ' - ' . get_the_title($post_id);
        $memo = substr($memo, 0, 64);
        $memo = preg_replace('/[^\w_ ]/', '', $memo);

        $invoice_params = array(
            'memo'    => $memo,
            'value'   => $amount, // in sats
            'expiry'  => 1800,
            'private' => true
        );

        $response_data = array(
            'post_id' => $post_id,
            'amount'  => $amount
        );

        $plugin  = $this->get_plugin();
        $invoice = $plugin->getLightningClient()->addInvoice($invoice_params);
        $plugin->getDatabaseHandler()->store_invoice($post_id, $invoice['r_hash'], $invoice['payment_request'], $amount, '', 0);

        $jwt_data = array_merge($response_data,
            array(
                'invoice_id' => $invoice['r_hash'],
                'r_hash'     => $invoice['r_hash'],
                'exp'        => time() + 60 * 10
        ));

        $jwt = JWT\JWT::encode($jwt_data, WP_LN_PAYWALL_JWT_KEY,  WP_LN_PAYWALL_JWT_ALGORITHM);

        $response = array_merge($response_data,
            array(
                'token'           => $jwt,
                'payment_request' => $invoice['payment_request']
            )
        );
        ob_end_clean();
        return $response;
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

        $params['amount'] = array(
            'default'           => 0,
            'description'       => __( 'Amount in SATS user wants to donate', 'lnp-alby' ),
            'type'              => 'integer',
            'sanitize_callback' => 'intval',
            'validate_callback' => 'rest_validate_request_arg',
        );

        $params['post_id'] = array(
            'default'           => 0,
            'description'       => __( 'Post where donation was made', 'lnp-alby' ),
            'type'              => 'integer',
            'sanitize_callback' => 'intval',
            'validate_callback' => 'rest_validate_request_arg',
        );

        return $params;
    }
}
