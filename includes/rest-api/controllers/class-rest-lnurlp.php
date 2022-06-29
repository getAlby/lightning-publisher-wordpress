<?php

// Exit if accessed directly
defined('WPINC') || die;


/**
 * @file
 * REST API Endpoint to handle LNURL pay requests
 */
class LNP_LnurlpController extends \WP_REST_Controller
{

    public function register_routes()
    {

        $this->namespace = 'lnp-alby/v1';

        register_rest_route(
            $this->namespace,
            'lnurlp',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'process_lnurlp_request'),
                    'permission_callback' => '__return_true',
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            'lnurlp/callback',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'process_lnurlp_callback_request'),
                    'permission_callback' => '__return_true',
                ),
            )
        );
    }

    /**
     * LNURL request 1
     */
    public function process_lnurlp_request($request)
    {
        ob_start();
        $plugin = $this->get_plugin();
        $logger = $plugin->get_logger();


        $description = get_bloginfo('name');
        //$post_id = $wp_query->get('lnurl_post_id');
        //if (!empty($post_id)) {
        //  $description = $description . ' - ' . get_the_title($post_id);
        //}

        $callback_url = get_rest_url(null, '/lnp-alby/v1/lnurlp/callback');
        $response = [
          'status' => 'OK',
          'callback' => $callback_url,
          'minSendable' => 10 * 1000, // millisatoshi
          'maxSendable' => 1000000 * 1000, // millisatoshi
          'tag' => 'payRequest',
          'metadata' => '[["text/identifier", "' . site_url() .'"]["text/plain", "' . $description . '"]]'
        ];
        ob_end_clean();
        wp_send_json($response, 200);
    }

    /**
     * LNURL request 1
     */
    public function process_lnurlp_callback_request($request)
    {
        ob_start();
        $plugin = $this->get_plugin();
        $logger = $plugin->get_logger();

        $description = get_bloginfo('name');
        //$post_id = intval( $request->get_param('amount') );
        //if (!empty($post_id)) {
        //  $description = $description . ' - ' . get_the_title($post_id);
        //}

        $amount = intval($request->get_param('amount'));
        if (empty($amount)) {
            wp_send_json(['status' => 'ERROR', 'reason' => 'amount missing']);
            return;
        }
        $description_hash = base64_encode(hash('sha256', '[["text/identifier", "' . site_url() .'"]["text/plain", "' . $description . '"]]', true));

        $invoice = $this->plugin->getLightningClient()->addInvoice(
            [
            'memo' => substr($description, 0, 64),
            'description_hash' => $description_hash,
            'value' => $amount,
            'expiry' => 1800,
            'private' => true
            ]
        );

        $response = ['pr' => $invoice['payment_request'], 'routes' => []];
        ob_end_clean();
        wp_send_json($response, 200);
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
        $params['amount'] = array(
            'default'           => 0,
            'description'       => __('Invoice amount', 'lnp-alby'),
            'type'              => 'integer',
            'sanitize_callback' => 'intval',
            'validate_callback' => 'rest_validate_request_arg',
        );

        return $params;
    }
}
