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
          'commentAllowed' => 128,
          'payerData' => ["name" => ["mandatory" => false]],
          'metadata' => $this->get_lnurlp_metadata()
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


        // TODO: can we somehow use this as invoice memo, too?
        $memo = "";
        $payerData = $request->get_param('payerdata');
        if ($payerData) {
            $name = json_decode($payerData)->{'name'};
            $memo = $memo . $name . ": ";
        }
        // set the comment as memo or default to the blog name
        $comment = $request->get_param('comment');
        if ($comment) {
            $memo = $memo . $comment;
        }
        $amount = intval($request->get_param('amount'));
        if (empty($amount)) {
            wp_send_json(['status' => 'ERROR', 'reason' => 'amount missing']);
            return;
        }
        $amount = ceil($amount / 1000); // amounts are sent in milli sats
        $unhashed_description = $this->get_lnurlp_metadata($payerData);
        $description_hash = hash('sha256', $unhashed_description, false);

        $post_id = intval($request->get_param('post_id'));
        $invoice = $this->plugin->getLightningClient($post_id)->addInvoice(
            [
            'memo' => $memo, // not supported when setting a description hash
            'description_hash' => $description_hash,
            'unhashed_description' => $unhashed_description,
            'value' => $amount,
            'expiry' => 1800,
            'private' => true
            ]
        );
        $plugin->getDatabaseHandler()->store_invoice([
            "payment_hash" => $invoice['r_hash'],
            "invoice_type" => "lnurl",
            "payment_request" => $invoice['payment_request'],
            "amount_in_satoshi" => $amount,
            "comment" => $memo,
        ]);

        $response = ['pr' => $invoice['payment_request'], 'routes' => []];
        //ob_end_clean();
        wp_send_json($response, 200);
    }

    private function get_lnurlp_metadata($payerData = null) {
        $description = get_bloginfo('name');
        $identifier = site_url();
        $metadata = json_encode([
            ["text/identifier", $identifier],
            ["text/plain", $description]
        ]);
        if ($payerData) {
            $metadata = $metadata . $payerData;
        }
        return $metadata;
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
