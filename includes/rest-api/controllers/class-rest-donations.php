<?php

use \Firebase\JWT;

// Exit if accessed directly
defined( 'WPINC' ) || die;


/**
 * @file
 * REST API Endpoint that handles donations
 */
class LNP_DonationsController extends \WP_REST_Controller {

    /**
     * Register route for file uploads from remote repository
     */
    public function register_routes() {

        $this->namespace = 'lnp-alby/v1';
        $this->rest_base = 'donate';

        register_rest_route(
            trailingslashit( $this->namespace ),
            $this->rest_base,
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'process_donate_request'),
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

        // Amount user is trying to donate
        $amount  = intval( $request->get_param('amount') );
        $post_id = intval( $request->get_param('post_id') );

        // Don't allow less than 100 SATS
        if ( $amount < 100 )
        {
            return new \WP_Error(__('Mimimum domation amount is 100 SATS', 'lnp-alby'));
        }

        // Don't allow less than 100 SATS
        if ( ! $post_id )
        {
            return new \WP_Error(__('Invalid Request, post_id missing', 'lnp-alby'));
        }

        $invoice  = $this->create_invoice($post_id, $amount);
        //$resposne =  

        // error_log( print_r($request, true) );
        return rest_ensure_response($invoice);
    }



    /**
     * Create Invoice
     * 
     * @param  object $request WP_REST_Request
     * @return [type]          [description]
     */
    private function create_invoice( int $post_id, int $amount )
    {
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
        $plugin->database_handler->store_invoice($post_id, $invoice['r_hash'], $invoice['payment_request'], $amount, '', 0);

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
