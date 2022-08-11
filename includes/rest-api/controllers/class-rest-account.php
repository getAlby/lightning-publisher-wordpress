<?php

use \Firebase\JWT;

// Exit if accessed directly
defined('WPINC') || die;


/**
 * @file
 * REST API Endpoint to create/login to an Alby account
 */
class LNP_AccountController extends \WP_REST_Controller
{

    public function register_routes()
    {

        $this->namespace = 'lnp-alby/v1';

        register_rest_route(
            $this->namespace,
            'account',
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_alby_account'),
                    'permission_callback' => array($this, 'check_permission')
                ),
            )
        );
    }

    public function check_permission()
    {
        if (current_user_can('manage_options') || current_user_can('administrator')) {
            return true;
        }
        return new \WP_Error('rest_forbidden', __('Invalid Request, Missing permissions', 'lnp-alby'), array( 'status' => 401 ));
    }

    /**
     * Create Alby Account
     */
    public function create_alby_account($request)
    {
        ob_start();
        $plugin = $this->get_plugin();

        $email    = $request->get_param('email');
        $password = $request->get_param('password');
        if (empty($password) || empty($email)) {
            ob_end_clean();
            return new \WP_Error(__('Invalid Request, Missing required parameters', 'lnp-alby'));
        }

        try {
            $account = LNDHub\Client::createAlbyWallet($email, $password);
            if (!empty($account['lndhub']) && !empty($account['lndhub']['login'])) {
                // update node keysend settings
                $lnp_general = get_option('lnp_general');
                if (empty($lnp_general['v4v_node_key'])) {
                    $lnp_general['v4v_node_key'] = $account['keysend_pubkey'];
                    $lnp_general['v4v_custom_key'] = $account['keysend_custom_key'];
                    $lnp_general['v4v_custom_value'] = $account['keysend_custom_value'];
                    update_option('lnp_general', $lnp_general);
                }
                ob_end_clean();
                wp_send_json($account, 200);
            } else {
                ob_end_clean();
                wp_send_json($account, 422);
            }
        }catch(\Exception $e) {
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
    public function get_endpoint_args_for_item_schema( $method = \WP_REST_Server::CREATABLE )
    {

        $params = array();
        return $params;
    }
}
