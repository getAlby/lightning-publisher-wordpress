<?php

use \tkijewski\lnurl;
use \Firebase\JWT;

class WP_Lightning
{
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WP_Lightning_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The lightning client.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WP_Lightning_Client_Interface   $lightningClient    The lightning client.
	 */
	protected $lightningClient;

	/**
	 * The lightning client type.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $lightningClient    The lightning client type.
	 */
	protected $lightningClientType;

	/**
	 * The database handler.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      LNP_DatabaseHandler    $database_handler    The database handler.
	 */
	protected $database_handler;

	/**
	 * The connection options.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $connection_options    The connection options.
	 */
	protected $connection_options;

	/**
	 * The paywall options.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $paywall_options    The paywall options.
	 */
	protected $paywall_options;

	/**
	 * The donation options.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $donation_options    The donation options.
	 */
	protected $donation_options;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		define('WP_LN_PAYWALL_JWT_KEY', hash_hmac('sha256', 'lnp-alby', AUTH_KEY));
		define('WP_LN_PAYWALL_JWT_ALGORITHM', 'HS256');
		define('WP_LN_ROOT_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
		define('WP_LN_ROOT_URI', untrailingslashit(plugin_dir_url(__FILE__)));

		if (defined('WP_LIGHTNING_VERSION')) {
			$this->version = WP_LIGHTNING_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wp-lightning';
		$this->load_dependencies();
		$this->set_locale();
		$this->read_database_options();
		$this->setup_client();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_ajax_hooks();

		// // admin
		// add_action('admin_menu', array($this, 'admin_menu'));
		// // initializing admin pages
		// new LNP_Dashboard($this, 'lnp_settings');
		// new LNP_BalancePage($this, 'lnp_settings', $this->database_handler);

		// $paywall_page    = new LNP_PaywallPage($this, 'lnp_settings');
		// $connection_page = new LNP_ConnectionPage($this, 'lnp_settings');
		// $donation_page   = new LNP_DonationPage($this, 'lnp_settings');

		// new LNP_HelpPage($this, 'lnp_settings');

		// // get page options
		// $this->connection_options = $connection_page->options;
		// $this->paywall_options    = $paywall_page->options;
		// $this->donation_options   = $donation_page->options;

		// // Init admin only stuff
		// new LNP_Admin($this);

		// // Anything that goes on frontend
		// new LNP_DonationsWidget($this);

		// /**
		//  * Init REST API Server
		//  */
		// $server = LNP_RESTServer::instance();
		// $server->init();
		// $server->set_plugin_instance($this);

		// add_action('widgets_init', array($this, 'widget_init'));
		// // feed
		// // https://code.tutsplus.com/tutorials/extending-the-default-wordpress-rss-feed--wp-27935
		// if (!empty($this->paywall_options['lnurl_rss'])) {
		//     add_action('init', array($this, 'add_lnurl_endpoints'));
		//     add_action('template_redirect', array($this, 'lnurl_endpoints'));
		//     add_action('rss2_item', array($this, 'add_lnurl_to_rss_item_filter'));
		// }

		// add_action('admin_enqueue_scripts', array($this, 'load_custom_wp_admin_style'));
		// add_action('wp_head', array($this, 'hook_meta_tags'));
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WP_Lightning_Loader. Orchestrates the hooks of the plugin.
	 * - WP_Lightning_i18n. Defines internationalization functionality.
	 * - WP_Lightning_Admin. Defines all hooks for the admin area.
	 * - WP_Lightning_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		// Composer dependencies
		require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

		// Custom Tables
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/db/database-handler.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/db/transactions.php';

		// REST API Server
		// Server class includes controllers
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/rest-api/class-rest-server.php';

		// Admin only stuff
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-init.php';

		// Settings 
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-abstract-settings.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-dashboard.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-balance.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-donation.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-paywall.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-connections.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-help.php';

		// Admin stuff
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/widgets/lnp-widget.php';

		// Public facing
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/includes/class-donations-widget.php';

		// Includes
		require_once plugin_dir_path(dirname(__FILE__)) . 'lightning-address.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-lightning-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-lightning-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-lightning-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wp-lightning-public.php';

		/**
		 * The class responsible for defining all ajax endpoints
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-lightning-ajax.php';
		
		/**
		 * The lightning client classes.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/interface-wp-lightning-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/abstract-class-wp-lightning-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-wp-lightning-btcpay-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-wp-lightning-lnaddress-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-wp-lightning-lnbits-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-wp-lightning-lnd-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-wp-lightning-lndhub-client.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-lightning-paywall.php';

		$this->loader = new WP_Lightning_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WP_Lightning_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new WP_Lightning_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Read the options from the database.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function read_database_options()
	{

		$this->database_handler = new LNP_DatabaseHandler();

		$paywall_page    = new LNP_PaywallPage($this, 'lnp_settings');
		$connection_page = new LNP_ConnectionPage($this, 'lnp_settings');
		$donation_page   = new LNP_DonationPage($this, 'lnp_settings');

		// get page options
		$this->connection_options = $connection_page->options;
		$this->paywall_options    = $paywall_page->options;
		$this->donation_options   = $donation_page->options;
	}

	/**
	 * Setup the lightning client
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function setup_client()
	{

		$this->lightningClient = null;
		$this->lightningClientType = null;

		if (!$this->lightningClient) {
			if (!empty($this->connection_options['lnd_address'])) {
				$this->lightningClientType = 'lnd';
				$this->lightningClient = new WP_Lightning_LND_Client($this->connection_options);
			} elseif (!empty($this->connection_options['lnbits_apikey'])) {
				$this->lightningClientType = 'lnbits';
				$this->lightningClient = new WP_Lightning_LNBits_Client($this->connection_options);
			} elseif (!empty($this->connection_options['lnaddress_address']) || !empty($this->connection_options['lnaddress_lnurl'])) {
				$this->lightningClientType = 'lnaddress';
				$this->lightningClient = new WP_Lightning_LNAddress_Client($this->connection_options);
			} elseif (!empty($this->connection_options['btcpay_host'])) {
				$this->lightningClientType = 'btcpay';
				$this->lightningClient = new WP_Lightning_BTCPay_Client($this->connection_options);
			} elseif (!empty($this->connection_options['lndhub_url']) && !empty($this->connection_options['lndhub_login']) && !empty($this->connection_options['lndhub_password'])) {
				$this->lightningClientType = 'lndhub';
				$this->lightningClient = new WP_Lightning_LNDHub_Client($this->connection_options);
			}
		}
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new WP_Lightning_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new WP_Lightning_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		$this->loader->add_filter('the_content', $plugin_public, 'ln_paywall_filter');
	}

	/**
	 * Register all of the hooks related to ajax endpoints.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_ajax_hooks()
	{

		$plugin_ajax = new WP_Lightning_Ajax($this);

		$this->loader->add_action('wp_ajax_lnp_invoice', $plugin_ajax, 'ajax_make_invoice');
		$this->loader->add_action('wp_ajax_nopriv_lnp_invoice', $plugin_ajax, 'ajax_make_invoice');
		
		// TODO: Missing Implementation of ajax_make_invoice_all
		// $this->loader->add_action('wp_ajax_lnp_invoice_all', $plugin_ajax, 'ajax_make_invoice_all');
		// $this->loader->add_action('wp_ajax_nopriv_lnp_invoice_all', $plugin_ajax, 'ajax_make_invoice_all');

		$this->loader->add_action('wp_ajax_lnp_check_payment', $plugin_ajax, 'ajax_check_payment');
		$this->loader->add_action('wp_ajax_nopriv_lnp_check_payment', $plugin_ajax, 'ajax_check_payment');
		
		// TODO: Missing Implementation of ajax_check_payment_all
		// $this->loader->add_action('wp_ajax_lnp_check_payment_all', $plugin_ajax, 'ajax_check_payment_all');
		// $this->loader->add_action('wp_ajax_nopriv_lnp_check_payment_all', $plugin_ajax, 'ajax_check_payment_all');

		$this->loader->add_action('wp_ajax_create_lnp_hub_account', $plugin_ajax, 'create_lnp_hub_account');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WP_Lightning_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}

	/**
	 * Get the lightning client
	 */
	public function getLightningClient()
	{
		return $this->lightningClient;
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
	 * Get the donation options
	 */
	public function getDonationOptions()
	{
		return $this->donation_options;
	}

	//     public function add_lnurl_to_rss_item_filter()
	//     {
	//         global $post;
	//         $pay_url = add_query_arg([
	//             'lnurl' => 'pay',
	//             'lnurl_post_id' => $post->ID
	//         ], get_site_url());
	//         $lnurl = lnurl\encodeUrl($pay_url);
	//         echo '<payment:lnurl>' . $lnurl . '</payment:lnurl>';
	//     }

	//     public static function splitPublicProtected($content)
	//     {
	//         return preg_split('/(<p>)?\[ln.+\](<\/p>)?/', $content, 2);
	//     }

	//     /**
	//      * Store the post_id in an cookie to remember the payment
	//      * and increment the paid amount on the post
	//      * must only be called once (can be exploited currently)
	//      */
	//     public static function save_as_paid($post_id, $amount_paid = 0)
	//     {
	//         $paid_post_ids = self::get_paid_post_ids();
	//         if (!in_array($post_id, $post_ids)) {
	//             $amount_received = get_post_meta($post_id, '_lnp_amount_received', true);
	//             if (is_numeric($amount_received)) {
	//                 $amount = $amount_received + $amount_paid;
	//             } else {
	//                 $amount = $amount_paid;
	//             }
	//             update_post_meta($post_id, '_lnp_amount_received', $amount);

	//             array_push($paid_post_ids, $post_id);
	//         }
	//         $jwt = JWT\JWT::encode(array('post_ids' => $paid_post_ids), WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM);
	//         setcookie('wplnp', $jwt, time() + time() + 60 * 60 * 24 * 180, '/');
	//     }

	//     public static function save_paid_all($days)
	//     {
	//         $paid_post_ids = self::get_paid_post_ids();
	//         $jwt = JWT\JWT::encode(array('all_until' => time() + $days * 24 * 60 * 60, 'post_ids' => $paid_post_ids), WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM);
	//         setcookie('wplnp', $jwt, time() + time() + 60 * 60 * 24 * 180, '/');
	//     }

	//     public static function has_paid_for_all()
	//     {
	//         $wplnp = null;
	//         if (isset($_COOKIE['wplnp'])) {
	//             $wplnp = $_COOKIE['wplnp'];
	//         } elseif (isset($_GET['wplnp'])) {
	//             $wplnp = $_GET['wplnp'];
	//         }
	//         if (empty($wplnp)) return false;
	//         try {
	//             $jwt = JWT\JWT::decode($wplnp, new JWT\Key(WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM));
	//             return $jwt->{'all_until'} > time();
	//         } catch (Exception $e) {
	//             //setcookie("wplnp", "", time() - 3600, '/'); // delete invalid JWT cookie
	//             return false;
	//         }
	//     }

	//     public static function get_paid_post_ids()
	//     {
	//         $wplnp = null;
	//         if (isset($_COOKIE['wplnp'])) {
	//             $wplnp = $_COOKIE['wplnp'];
	//         } elseif (isset($_GET['wplnp'])) {
	//             $wplnp = $_GET['wplnp'];
	//         }
	//         if (empty($wplnp)) return [];
	//         try {
	//             $jwt = JWT\JWT::decode($wplnp, new JWT\Key(WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM));
	//             $paid_post_ids = $jwt->{'post_ids'};
	//             if (!is_array($paid_post_ids)) return [];

	//             return $paid_post_ids;
	//         } catch (Exception $e) {
	//             //setcookie("wplnp", "", time() - 3600, '/'); // delete invalid JWT cookie
	//             return [];
	//         }
	//     }

	//     public static function has_paid_for_post($post_id)
	//     {
	//         $paid_post_ids = self::get_paid_post_ids();
	//         return in_array($post_id, $paid_post_ids);
	//     }

	//     /**
	//      * Register scripts and styles
	//      */
	//     public function enqueue_script()
	//     {

	//         wp_enqueue_style(
	//             'wpln/admin-css',
	//             WP_LN_ROOT_URI . '/assets/css/admin.css'
	//         );

	//         wp_enqueue_script(
	//             'wpln/webln-js',
	//             WP_LN_ROOT_URI . '/assets/js/webln.min.js'
	//         );

	//         wp_enqueue_script(
	//             'wpln/paywall-js',
	//             WP_LN_ROOT_URI . '/assets/js/publisher.js'
	//         );

	//         wp_enqueue_script(
	//             'wpln/paywall-js',
	//             WP_LN_ROOT_URI . '/assets/css/publisher.css'
	//         );

	//         wp_localize_script('wpln/paywall-js', 'LN_Paywall', array(
	//             'ajax_url'  => admin_url('admin-ajax.php'),
	//             'rest_base' => get_rest_url(null, '/lnp-alby/v1')
	//         ));
	//     }



	//     /**
	//      * AJAX endpoint to check if an invoice is settled
	//      * returns the protected content if the invoice is settled
	//      */
	//     public function ajax_check_payment()
	//     {
	//         if (empty($_POST['token'])) {
	//             return wp_send_json(['settled' => false], 404);
	//         }
	//         try {
	//             $jwt = JWT\JWT::decode($_POST['token'], new JWT\Key(WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM));
	//         } catch (Exception $e) {
	//             return wp_send_json(['settled' => false], 404);
	//         }

	//         // if we get a preimage we can check if the preimage matches the payment hash and accept it.
	//         if (!empty($_POST['preimage']) && hash('sha256', hex2bin($_POST['preimage']), false) == $jwt->{"r_hash"}) {
	//             $invoice = ['settled' => true];
	//             // if ew do not have a preimage we must check with the LN node if the invoice was paid.
	//         } else {
	//             $invoice_id = $jwt->{'invoice_id'};
	//             $invoice = $this->getLightningClient()->getInvoice($invoice_id);
	//         }

	//         // TODO check amount?
	//         if ($invoice && $invoice['settled']) { // && (int)$invoice['value'] == (int)$jwt->{'amount'}) {
	//             $post_id = $jwt->{'post_id'};
	//             $this->database_handler->update_invoice_state($jwt->{'r_hash'}, 'settled');
	//             if (!empty($post_id)) {
	//                 $content = get_post_field('post_content', $post_id);
	//                 list($public, $protected) = self::splitPublicProtected($content);
	//                 self::save_as_paid($post_id, $amount);
	//                 wp_send_json($protected, 200);
	//             } elseif (!empty($jwt->{'all'})) {
	//                 self::save_paid_all($this->paywall_options['all_days']);
	//                 wp_send_json($this->paywall_options['all_confirmation'], 200);
	//             }
	//         } else {
	//             wp_send_json(['settled' => false], 402);
	//         }
	//     }

	//     protected static function extract_ln_shortcode($content)
	//     {
	//         if (!preg_match('/\[ln(.+)\]/i', $content, $m)) {
	//             return;
	//         }
	//         return shortcode_parse_atts($m[1]);
	//     }

	//     public function get_paywall_options_for($postId, $content)
	//     {
	//         $ln_shortcode_data = self::extract_ln_shortcode($content);
	//         if (!$ln_shortcode_data && !is_array($ln_shortcode_data)) {
	//             return null;
	//         }


	//         return [
	//             'paywall_text' => array_key_exists('text', $ln_shortcode_data) ? $ln_shortcode_data['text'] : $this->paywall_options['paywall_text'] ?? null,
	//             'button_text'  => array_key_exists('button', $ln_shortcode_data) ? $ln_shortcode_data['button'] : $this->paywall_options['button_text'] ?? null,
	//             'amount'       => array_key_exists('amount', $ln_shortcode_data) ? (int)$ln_shortcode_data['amount'] : (int)($this->paywall_options['amount'] ?? null),
	//             'total'        => array_key_exists('total', $ln_shortcode_data) ? (int)$ln_shortcode_data['total'] : (int)($this->paywall_options['total'] ?? null),
	//             'timeout'      => array_key_exists('timeout', $ln_shortcode_data) ? (int)$ln_shortcode_data['timeout'] : (int)($this->paywall_options['timeout'] ?? null),
	//             'timein'       => array_key_exists('timein', $ln_shortcode_data) ? (int)$ln_shortcode_data['timein'] : (int)($this->paywall_options['timein'] ?? null),
	//             'disable_in_rss' => array_key_exists('disable_in_rss', $ln_shortcode_data) ? true : $this->paywall_options['disable_paywall_in_rss'] ?? [] ?? null,
	//         ];
	//     }

	//     /**
	//      * Format display for paid post
	//      */
	//     protected static function format_paid($post_id, $ln_shortcode_data, $public, $protected)
	//     {
	//         return sprintf('%s%s', $public, $protected);
	//     }

	//     /**
	//      * Format display for unpaid post. Injects the payment request HTML
	//      */
	//     protected static function format_unpaid($post_id, $options, $public)
	//     {
	//         $text   = '<p>' . sprintf(empty($options['paywall_text']) ? 'To continue reading the rest of this post, please pay <em>%s Sats</em>.' : $options['paywall_text'], $options['amount']) . '</p>';
	//         $button = sprintf('<button class="wp-lnp-btn">%s</button>', empty($options['button_text']) ? 'Pay now' : $options['button_text']);
	//         // $autopay = '<p><label><input type="checkbox" value="1" class="wp-lnp-autopay" />Enable autopay<label</p>';
	//         return sprintf('%s<div id="wp-lnp-wrapper" class="wp-lnp-wrapper" data-lnp-postid="%d">%s%s</div>', $public, $post_id, $text, $button);
	//     }

	//     function widget_init()
	//     {
	//         $has_paid = self::has_paid_for_all();
	//         register_widget(new LnpWidget($has_paid, $this->paywall_options));
	//     }

	//     // endpoint idea from: https://webdevstudios.com/2015/07/09/creating-simple-json-endpoint-wordpress/
	//     public function add_lnurl_endpoints()
	//     {
	//         add_rewrite_tag('%lnurl%', '([^&]+)');
	//         add_rewrite_tag('%lnurl_post_id%', '([^&]+)');
	//         add_rewrite_tag('%amount%', '([^&]+)');
	//         //add_rewrite_rule( 'lnurl/([^&]+)/?', 'index.php?lnurl=$matches[1]', 'top' );
	//     }

	//     public function lnurl_endpoints()
	//     {
	//         global $wp_query;
	//         $lnurl = $wp_query->get('lnurl');
	//         $post_id = $wp_query->get('lnurl_post_id');

	//         if (!$lnurl) {
	//             return;
	//         }

	//         $description = get_bloginfo('name');
	//         if (!empty($post_id)) {
	//             $description = $description . ' - ' . get_the_title($post_id);
	//         }

	//         if ($lnurl == 'pay') {
	//             $callback_url = home_url(add_query_arg('lnurl', 'cb'));
	//             wp_send_json([
	//                 'callback' => $callback_url,
	//                 'minSendable' => 1000 * 1000, // millisatoshi
	//                 'maxSendable' => 1000000 * 1000, // millisatoshi
	//                 'tag' => 'payRequest',
	//                 'metadata' => '[["text/plain", "' . $description . '"]]'
	//             ]);
	//         } elseif ($lnurl == 'cb') {
	//             $amount = $_GET['amount'];
	//             if (empty($amount)) {
	//                 wp_send_json(['status' => 'ERROR', 'reason' => 'amount missing']);
	//                 return;
	//             }
	//             $description_hash = base64_encode(hash('sha256', '[["text/plain", "' . $description . '"]]', true));
	//             $invoice = $this->getLightningClient()->addInvoice([
	//                 'memo' => substr($description, 0, 64),
	//                 'description_hash' => $description_hash,
	//                 'value' => $amount,
	//                 'expiry' => 1800,
	//                 'private' => true
	//             ]);
	//             wp_send_json(['pr' => $invoice['payment_request'], 'routes' => []]);
	//         }
	//     }

	//     /**
	//      * Admin
	//      */
	//     public function admin_menu()
	//     {
	//         add_menu_page(
	//             'Lightning Paywall',
	//             'Lightning Paywall',
	//             'manage_options',
	//             'lnp_settings',
	//             null,
	//             'dashicons-superhero'
	//         );
	//     }

	//     public function create_lnp_hub_account()
	//     {
	//         $account = LNDHub\Client::createWallet("https://ln.getalby.com", "bluewallet");
	//         wp_send_json($account);
	//     }


	//     public function hook_meta_tags()
	//     {
	//         if (!empty($this->paywall_options['lnurl_meta_tag']) && $this->paywall_options['lnurl_meta_tag']) {
	//             $url = get_site_url(null, '/?lnurl=pay');
	//             echo '<meta name="lightning" content="lnurlp:' . $url . '" />';
	//         }
	//     }


	//     public function load_custom_wp_admin_style($hook)
	//     {
	//         // $hook is string value given add_menu_page function.
	//         // if ($hook != 'toplevel_page_mypluginname') {
	//         //   return;
	//         // }
	//         wp_enqueue_style(
	//             'wpln/admin-css',
	//             WP_LN_ROOT_URI . '/assets/css/admin.css'
	//         );

	//         wp_enqueue_script(
	//             'wpln/admin-js',
	//             WP_LN_ROOT_URI . '/assets/js/admin.js'
	//         );
	//     }

	//     public function get_file_url($path)
	//     {
	//         return plugins_url($path, __FILE__);
	//     }
}
