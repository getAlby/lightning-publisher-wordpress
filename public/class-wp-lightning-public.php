<?php

use Firebase\JWT;
use \tkijewski\lnurl;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    WP_Lightning
 * @subpackage WP_Lightning/public
 */
class WP_Lightning_Public {

	/**
     * Main Plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      WP_Lightning    $plugin    The main plugin object.
     */
    private $plugin;

	/**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    WP_Lightning    $plugin       The main plugin object.
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WP_Lightning_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WP_Lightning_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin->get_plugin_name(), plugin_dir_url( __FILE__ ) . 'css/wp-lightning-public.css', array(), $this->plugin->get_version(), 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WP_Lightning_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WP_Lightning_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin->get_plugin_name(), plugin_dir_url( __FILE__ ) . 'js/wp-lightning-public.js', array( 'jquery' ), $this->plugin->get_version(), true );

		wp_enqueue_script( 'wpln/webln-js', plugin_dir_url( __FILE__ ) . 'js/webln.min.js', array(), $this->plugin->get_version(), true );

		wp_localize_script($this->plugin->get_plugin_name(), 'LN_Paywall', array(
            'ajax_url'  => admin_url('admin-ajax.php'),
            'rest_base' => get_rest_url(null, '/lnp-alby/v1')
        ));
	}

	/**
     * filter ln shortcodes and inject payment request HTML
     */
    public function ln_paywall_filter($content)
    {
		$paywall = new WP_Lightning_Paywall($this->plugin, $content);
		return $paywall->getContent();
    }

	/**
     * AJAX endpoint to create new invoices
     */
    public function ajax_make_invoice()
    {
        if (!empty($_POST['post_id'])) {
            $post_id = (int)$_POST['post_id'];
			$paywall = new WP_Lightning_Paywall($this->plugin, get_post_field('post_content', $post_id));
            $paywall_options = $paywall->getOptions();
            if (!$paywall_options) {
                // return wp_send_json(['error' => 'invalid post'], 404);
            }
            $memo = get_bloginfo('name') . ' - ' . get_the_title($post_id);
            $amount = $paywall_options['amount'];
            $response_data = ['post_id' => $post_id, 'amount' => $amount];
        } elseif (!empty($_POST['all'])) {
            $memo = get_bloginfo('name');
            $amount = $this->paywall_options['all_amount'];
            $response_data = ['all' => true, 'amount' => $amount];
        } else {
            return wp_send_json(['error' => 'invalid post'], 404);
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

        $invoice = $this->plugin->getLightningClient()->addInvoice($invoice_params);
        $this->plugin->getDatabaseHandler()->store_invoice($post_id, $invoice['r_hash'], $invoice['payment_request'], $amount, '', 0);

        $jwt_data = array_merge($response_data, ['invoice_id' => $invoice['r_hash'], 'r_hash' => $invoice['r_hash'], 'exp' => time() + 60 * 10]);
        $jwt = JWT\JWT::encode($jwt_data, WP_LN_PAYWALL_JWT_KEY,  WP_LN_PAYWALL_JWT_ALGORITHM);

        $response = array_merge($response_data, ['token' => $jwt, 'payment_request' => $invoice['payment_request']]);
        //wp_send_json([ 'post_id' => $post_id, 'token' => $jwt, 'amount' => $paywall_options['amount'], 'payment_request' => $invoice['payment_request']]);
        wp_send_json($response);
    }

	// endpoint idea from: https://webdevstudios.com/2015/07/09/creating-simple-json-endpoint-wordpress/
	public function add_lnurl_endpoints()
	{
		add_rewrite_tag('%lnurl%', '([^&]+)');
		add_rewrite_tag('%lnurl_post_id%', '([^&]+)');
		add_rewrite_tag('%amount%', '([^&]+)');
		//add_rewrite_rule( 'lnurl/([^&]+)/?', 'index.php?lnurl=$matches[1]', 'top' );
	}

	public function lnurl_endpoints()
	{
		global $wp_query;
		$lnurl = $wp_query->get('lnurl');
		$post_id = $wp_query->get('lnurl_post_id');

		if (!$lnurl) {
			return;
		}

		$description = get_bloginfo('name');
		if (!empty($post_id)) {
			$description = $description . ' - ' . get_the_title($post_id);
		}

		if ($lnurl == 'pay') {
			$callback_url = home_url(add_query_arg('lnurl', 'cb'));
			wp_send_json([
				'callback' => $callback_url,
				'minSendable' => 1000 * 1000, // millisatoshi
				'maxSendable' => 1000000 * 1000, // millisatoshi
				'tag' => 'payRequest',
				'metadata' => '[["text/plain", "' . $description . '"]]'
			]);
		} elseif ($lnurl == 'cb') {
			$amount = $_GET['amount'];
			if (empty($amount)) {
				wp_send_json(['status' => 'ERROR', 'reason' => 'amount missing']);
				return;
			}
			$description_hash = base64_encode(hash('sha256', '[["text/plain", "' . $description . '"]]', true));
			$invoice = $this->plugin->getLightningClient()->addInvoice([
				'memo' => substr($description, 0, 64),
				'description_hash' => $description_hash,
				'value' => $amount,
				'expiry' => 1800,
				'private' => true
			]);
			wp_send_json(['pr' => $invoice['payment_request'], 'routes' => []]);
		}
	}

	public function add_lnurl_to_rss_item_filter()
	{
		global $post;
		$pay_url = add_query_arg([
			'lnurl' => 'pay',
			'lnurl_post_id' => $post->ID
		], get_site_url());
		$lnurl = lnurl\encodeUrl($pay_url);
		echo '<payment:lnurl>' . $lnurl . '</payment:lnurl>';
	}

	public function hook_meta_tags()
	{
		if (!empty($this->plugin->getPaywallOptions()['lnurl_meta_tag']) && $this->plugin->getPaywallOptions()['lnurl_meta_tag']) {
			$url = get_site_url(null, '/?lnurl=pay');
			echo '<meta name="lightning" content="lnurlp:' . $url . '" />';
		}
	}
}
