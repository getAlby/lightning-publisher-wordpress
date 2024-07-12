<?php

use \Firebase\JWT;

// If this file is called directly, abort.
defined('WPINC') || die;

class BLN_Publisher
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since  1.0.0
     * @access protected
     * @var    BLN_Publisher_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since  1.0.0
     * @access protected
     * @var    string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since  1.0.0
     * @access protected
     * @var    string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The lightning client.
     *
     * @since  1.0.0
     * @access protected
     * @var    BLN_Publisher_Client_Interface   $lightningClient    The lightning client.
     */
    protected $lightningClient;

    /**
     * The lightning client type.
     *
     * @since  1.0.0
     * @access protected
     * @var    string    $lightningClient    The lightning client type.
     */
    protected $lightningClientType;

    /**
     * The database handler.
     *
     * @since  1.0.0
     * @access protected
     * @var    LNP_DatabaseHandler    $database_handler    The database handler.
     */
    protected $database_handler;

    /**
     * The connection options.
     *
     * @since  1.0.0
     * @access protected
     * @var    array    $connection_options    The connection options.
     */
    protected $connection_options;

    /**
     * The paywall options.
     *
     * @since  1.0.0
     * @access protected
     * @var    array    $paywall_options    The paywall options.
     */
    protected $paywall_options;

    /**
     * The general options.
     *
     * @since  1.0.0
     * @access protected
     * @var    array    $general_options    The general options.
     */
    protected $general_options;


    /**
     * The donation options.
     *
     * @since  1.0.0
     * @access protected
     * @var    array    $donation_options    The donation options.
     */
    protected $donation_options;

    /**
     * The Plugin Admin Class
     */
    protected $plugin_admin;

    /**
     * The Plugin Public Class
     */
    protected $plugin_public;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        if (defined('BLN_PUBLISHER_VERSION')) {
            $this->version = BLN_PUBLISHER_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'bln-publisher';
        $this->load_dependencies();
        $this->initialize_loader();
        $this->set_locale();
        $this->read_database_options();
        $this->setup_client();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_shortcodes();
        $this->initialize_rest_api();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - BLN_Publisher_Loader. Orchestrates the hooks of the plugin.
     * - BLN_Publisher_i18n. Defines internationalization functionality.
     * - BLN_Publisher_Admin. Defines all hooks for the admin area.
     * - BLN_Publisher_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since  1.0.0
     * @access private
     */
    private function load_dependencies()
    {

        // Composer dependencies
        include_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

        // Custom Tables
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/db/database-handler.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/db/transactions.php';

        // REST API Server
        // Server class includes controllers
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/rest-api/class-rest-server.php';

        // Settings
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-abstract-settings.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-dashboard.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-balance.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-donation.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-paywall.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-connections.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-general.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-help.php';

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-bln-publisher-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-bln-publisher-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-bln-publisher-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'public/class-bln-publisher-public.php';

        /**
         * The lightning client classes.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/lightning-address.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/interface-bln-publisher-client.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/abstract-class-bln-publisher-client.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-bln-publisher-alby-client.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-bln-publisher-nwc-client.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-bln-publisher-btcpay-client.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-bln-publisher-lnaddress-client.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-bln-publisher-lnbits-client.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-bln-publisher-lnd-client.php';
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-bln-publisher-lndhub-client.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-bln-publisher-paywall.php';

        /**
         * The class responsible for REST API.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/rest-api/class-rest-server.php';

        $this->plugin_admin = new BLN_Publisher_Admin($this);
        $this->plugin_public = new BLN_Publisher_Public($this);
    }

    /**
     * Initialize the loader which registers all actions and filters for the plugin
     */
    private function initialize_loader()
    {
        $this->loader = new BLN_Publisher_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the BLN_Publisher_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since  1.0.0
     * @access private
     */
    private function set_locale()
    {

        $plugin_i18n = new BLN_Publisher_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Read the options from the database.
     *
     * @since  1.0.0
     * @access private
     */
    private function read_database_options()
    {

        $this->database_handler = new LNP_DatabaseHandler();

        $dashboard_page = new LNP_Dashboard($this, 'lnp_settings');
        $balance_page = new LNP_BalancePage($this, 'lnp_settings', $this->database_handler);
        $paywall_page    = new LNP_PaywallPage($this, 'lnp_settings');
        $connection_page = new LNP_ConnectionPage($this, 'lnp_settings');
        //$donation_page   = new LNP_DonationPage($this, 'lnp_settings');
        $general_page   = new LNP_GeneralPage($this, 'lnp_settings');
        $help_page = new LNP_HelpPage($this, 'lnp_settings');

        // get page options
        $this->connection_options = $connection_page->options;
        $this->paywall_options    = $paywall_page->options;
        $this->general_options    = $general_page->options;
        //$this->donation_options   = $donation_page->options;
    }

    /**
     * Setup the lightning client
     *
     * @since  1.0.0
     * @access private
     */
    private function setup_client()
    {

        $this->lightningClient = null;
        $this->lightningClientType = null;

        if (!$this->lightningClient)
        {
            try
            {
                if (!empty($this->connection_options['alby_access_token']))
                {
                    $this->lightningClientType = 'alby';
                    $this->lightningClient = new BLN_Publisher_Alby_Client($this->connection_options);
                }
                elseif (!empty($this->connection_options['nwc_connection_uri']))
                {
                    $this->lightningClientType = 'nwc';
                    $this->lightningClient = new BLN_Publisher_NWC_Client($this->connection_options);
                }
                elseif (!empty($this->connection_options['lnaddress_address']) || !empty($this->connection_options['lnaddress_lnurl']))
                {
                    $this->lightningClientType = 'lnaddress';
                    $this->lightningClient = new BLN_Publisher_LNAddress_Client($this->connection_options);
                }
                elseif (!empty($this->connection_options['lnd_address']))
                {
                    $this->lightningClientType = 'lnd';
                    $this->lightningClient = new BLN_Publisher_LND_Client($this->connection_options);
                }
                elseif (!empty($this->connection_options['lnbits_apikey'])) {
                    $this->lightningClientType = 'lnbits';
                    $this->lightningClient = new BLN_Publisher_LNBits_Client($this->connection_options);
                }
                elseif (!empty($this->connection_options['btcpay_host']))
                {
                    $this->lightningClientType = 'btcpay';
                    $this->lightningClient = new BLN_Publisher_BTCPay_Client($this->connection_options);
                }
                elseif (!empty($this->connection_options['lndhub_url']) && !empty($this->connection_options['lndhub_login']) && !empty($this->connection_options['lndhub_password']))
                {
                    $this->lightningClientType = 'lndhub';
                    $this->lightningClient = new BLN_Publisher_LNDHub_Client($this->connection_options);
                }
            } catch (\Exception $e) {
                echo "Faild to connect to Lightning Wallet: " . $e->getMessage();
            }
        }
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since  1.0.0
     * @access private
     */
    private function define_admin_hooks()
    {
        // Load the css styles for admin section
        $this->loader->add_action('admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles');
        // Load the js scripts for admin section
        $this->loader->add_action('admin_enqueue_scripts', $this->plugin_admin, 'enqueue_scripts');
        // Add the menu to the Wordpress Dashboard
        $this->loader->add_action('admin_menu', $this->plugin_admin, 'lightning_menu');
        // Register the donation block
        $this->loader->add_action('init', $this->plugin_admin, 'init_gutenberg_blocks');
        // Register custom field in user profile for custom address
        $this->loader->add_filter('user_contactmethods', $this->plugin_admin, 'add_user_lnp_address');
        $this->loader->add_filter('plugin_action_links', $this->plugin_admin, 'add_plugin_link', 10, 2);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since  1.0.0
     * @access private
     */
    private function define_public_hooks()
    {
        // Load the css styles for frotnend
        $this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue_styles');
        // Load the js scripts for frotnend
        $this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue_scripts');
        // Add the lightning meta tag to the head of the page
        $this->loader->add_action('wp_head', $this->plugin_public, 'hook_meta_tags');

        $this->loader->add_filter('script_loader_tag', $this->plugin_public, 'add_module_script_type_attribute' , 10, 3);

        // For RSS Feed
        if (!empty($this->paywall_options['lnurl_rss'])) {
            // Add URL as RSS Item
            $this->loader->add_action('rss2_item', $this->plugin_public, 'add_lnurl_to_rss_item_filter');
        }

        if (!empty($this->general_options['add_v4v_rss_tag'])) {
            $this->loader->add_action('rss2_head', $this->plugin_public, 'add_v4v_rss_tag');
            if (empty($this->general_options['disable_add_v4v_rss_ns_tag']) ) {
                $this->loader->add_action('rss2_ns', $this->plugin_public, 'add_v4v_rss_ns_tag');
            }
        }

        // Apply Paywall to the content
        $this->loader->add_filter('no_texturize_shortcodes', $this->plugin_public, 'shortcodes_to_exempt_from_wptexturize', 8); // try to avoid wptexturize from texturizing the short codes
        $this->loader->add_filter('the_content', $this->plugin_public, 'ln_paywall_filter', 999999999); // then number is the priority. Elementor uses a high number and does loads of things in the `the_content` filter so we need to run afterwards
    }

    /**
     * Register all of the shortcodes.
     *
     * @since  1.0.0
     * @access private
     */
    private function define_shortcodes()
    {
        // Register shortcode for donation block
        $this->loader->add_shortcode('ln_v4v', $this->plugin_public, 'render_webln_v4v_donation_button');
        $this->loader->add_shortcode('ln_simple_boost', $this->plugin_public, 'render_webln_v4v_simple_boost');
    }

    /**
     * Initialize REST API.
     *
     * @since  1.0.0
     * @access private
     */
    private function initialize_rest_api()
    {
        $server = LNP_RESTServer::instance();
        $server->set_plugin_instance($this);
        $server->init();
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since 1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since  1.0.0
     * @return string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since  1.0.0
     * @return BLN_Publisher_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since  1.0.0
     * @return string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Get the lightning client
     */
    public function getLightningClient($post_id = null)
    {
        // Default to post author LNaddress
        // if specified in user profile
        if ( $post_id )
        {
            $post = get_post($post_id);
            $address = get_user_meta( $post->post_author, '_lnp_ln_address', true );
            if ( $address )
            {
              $connection_options = $this->connection_options;
              $connection_options['lnaddress_address'] = $address;

              return new BLN_Publisher_LNAddress_Client($connection_options);
            }
        }
        return $this->lightningClient;
    }

    /**
     * Get the lightning client type
     */
    public function getLightningClientType()
    {
        return $this->lightningClientType;
    }

    /**
     * Get the database handler
     */
    public function getDatabaseHandler()
    {
        return $this->database_handler;
    }

    /**
     * Get the connection options
     */
    public function getConnectionOptions()
    {
        return $this->connection_options;
    }

    /**
     * Get the paywall options
     */
    public function getPaywallOptions()
    {
        return $this->paywall_options;
    }

    /**
     * Get the general options
     */
    public function getGeneralOptions()
    {
        return $this->general_options;
    }

    /**
     * Get the donation options
     */
    public function getDonationOptions()
    {
        return $this->donation_options;
    }

    public static function get_paid_post_ids()
    {
        $wplnp = null;
        if (isset($_COOKIE['wplnp'])) {
            $wplnp = sanitize_text_field($_COOKIE['wplnp']);
        } elseif (isset($_GET['wplnp'])) {
            $wplnp = sanitize_text_field($_GET['wplnp']);
        }
        if (empty($wplnp)) { return [];
        }
        try {
            $jwt = JWT\JWT::decode($wplnp, new JWT\Key(BLN_PUBLISHER_PAYWALL_JWT_KEY, BLN_PUBLISHER_PAYWALL_JWT_ALGORITHM));
            $paid_post_ids = $jwt->{'post_ids'};
            if (!is_array($paid_post_ids)) { return [];
            }

            return $paid_post_ids;
        } catch (\Exception $e) {
            //setcookie("wplnp", "", time() - 3600, '/'); // delete invalid JWT cookie
            return [];
        }
    }

    public function has_paid_for_post($post_id)
    {
        $paid_post_ids = BLN_Publisher::get_paid_post_ids();
        return in_array($post_id, $paid_post_ids);
    }

    /**
     * Store the post_id in an cookie to remember the payment
     * and increment the paid amount on the post
     * must only be called once (can be exploited currently)
     */
    public function save_as_paid($post_id, $amount_paid = 0)
    {
        $paid_post_ids = BLN_Publisher::get_paid_post_ids();
        if (!in_array($post_id, $paid_post_ids)) {
            $amount_received = get_post_meta($post_id, '_bln_amount_received', true);
            if (is_numeric($amount_received)) {
                $amount = $amount_received + $amount_paid;
            } else {
                $amount = $amount_paid;
            }
            update_post_meta($post_id, '_bln_amount_received', $amount);

            array_push($paid_post_ids, $post_id);
        }
        $jwt = JWT\JWT::encode(array('post_ids' => $paid_post_ids), BLN_PUBLISHER_PAYWALL_JWT_KEY, BLN_PUBLISHER_PAYWALL_JWT_ALGORITHM);
        if (!empty($this->general_options["cookie_timeframe_days"])) {
            $days = intval($this->general_options["cookie_timeframe_days"]);
        } else {
            $days = 180;
        }
        setcookie('wplnp', $jwt, time() + 60 * 60 * 24 * $days, '/');
    }

    public function get_current_exchange_rate($currency)
    {
        $transient_key = 'alby_current_rate_' . strtolower($currency);
        $alby_rate_url = 'https://getalby.com/api/rates/' . strtolower($currency);

        // Check for transient, if none, update the rate
        if ( false === ($data = get_transient($transient_key)) ) {
            // Get remote HTML file
            $response = wp_remote_get($alby_rate_url);
            // Check for error
            if ( is_wp_error($response) ) {
                return;
            }
            // Parse remote HTML file
            $data = wp_remote_retrieve_body($response);
            // Check for error
            if ( is_wp_error( $data ) ) {
                return;
            }

            // Store rate in transient, expire after 10 minutes
            set_transient($transient_key, $data, 10 * MINUTE_IN_SECONDS );
        }
        $data = json_decode($data);
        return $data->{'rate'};
    }

    public function convert_to_sats($amount_in_cents, $currency, $rate = null)
    {
        if (strtolower($currency) == 'btc') {
            return $amount_in_cents;
        }
        if (empty($rate)) {
            $rate = $this->get_current_exchange_rate($amount_in_cents, $currency);
        }
        $current_price_in_cents = floatval($rate) * 100.0;
        $price_per_satoshi = 100000000.0 / $current_price_in_cents;
        return ceil($amount_in_cents * $price_per_satoshi);
    }

}
