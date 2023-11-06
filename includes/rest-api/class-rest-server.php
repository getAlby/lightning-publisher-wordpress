<?php

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 * Using singleton pattern
 */
class LNP_RESTServer
{
    /**
     * The single instance of the class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Main Plugin.
     *
     * @var BLN_Publisher
     */
    protected $plugin;

    /**
     * Constructor
     *
     * @return void
     */
    protected function __construct()
    {
    }


    /**
     * Get class instance.
     *
     * @return object Instance.
     */
    final public static function instance()
    {
        if (null === static::$instance ) {
            static::$instance = new static();
        }
        return static::$instance;
    }


    /**
     * Do stuff when initially loading class
     *
     * @param [type] $plugin [description]
     */
    public function init()
    {
        /**
         * Instead of $this, just in case because this is a singleton
         * with protected constructor
         */
        $server = self::instance();

        add_action('rest_api_init', array($server, 'register_rest_routes'));
        add_action('rest_api_init', array($server, 'rest_send_cors_headers'));
    }


    /**
     * Set plugin instance in case we need it later
     *
     * @param [type] $plugin [description]
     */
    public function set_plugin_instance( $plugin )
    {
        $this->plugin = $plugin;
    }


    /**
     * Get plugin instance
     *
     * @param [type] $plugin
     */
    public function get_plugin_instance()
    {
        return $this->plugin;
    }


    /**
     * Register REST API routes.
     */
    public function register_rest_routes()
    {
        $plugin = $this->get_plugin_instance();

        /**
         * filename => class name
         */
        $classes = array(
            'class-rest-donations' => 'LNP_DonationsController',
            'class-rest-paywall' => 'LNP_PaywallController',
            'class-rest-invoices' => 'LNP_InvoicesController',
            'class-rest-lnurlp' => 'LNP_LnurlpController'
        );

        foreach ( $classes as $file => $controller_class )
        {
            // Include file
            include_once "controllers/{$file}.php";

            $class = new $controller_class; // Init class
            $class->register_routes(); // Register route
            $class->set_plugin_instance($plugin);
        }
    }

    /**
     * Enable CORS if needed
     */
    public function rest_send_cors_headers()
    {

        // Remove previous settings
        remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');

        // Add all subdomains
        add_filter(
            'rest_pre_serve_request', function ( $served, $result, $request ) {

                $headers = $request->get_headers();
                $origin = isset($headers['origin'])
                ? $headers['origin'][0]
                : 'https://' . $headers['host'][0];
                /**
                 * List of allowed hosts
                 * Who is allowed to send requests to REST API Endpoints
                 */
                $allowed = array(
                site_url(),
                );

                if (in_array(untrailingslashit($origin), $allowed) ) {
                    header('Referrer-Policy: origin');
                    header('Access-Control-Allow-Origin: ' . $origin);
                    header('Access-Control-Allow-Methods: POST, OPTIONS');
                    header('Access-Control-Allow-Credentials: true');
                    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-WP-Nonce, Content-Disposition, Application, Content-Length');
                }

                return $served;

            }, 1001, 3
        );
    }

    /**
     * Prevent cloning.
     */
    private function __clone()
    {
    }


    /**
     * Prevent unserializing.
     */
    final public function __wakeup()
    {
        throw new Exception(__('Unserializing instances of this class is forbidden.', 'lnp-alby'));
    }
}
