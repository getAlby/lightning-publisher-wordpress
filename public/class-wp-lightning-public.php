<?php

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
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-lightning-public.css', array(), $this->version, 'all' );

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-lightning-public.js', array( 'jquery' ), $this->version, true );

		wp_enqueue_script( 'wpln/webln-js', plugin_dir_url( __FILE__ ) . 'js/webln.min.js', array(), $this->version, true );

		wp_localize_script($this->plugin_name, 'LN_Paywall', array(
            'ajax_url'  => admin_url('admin-ajax.php'),
            'rest_base' => get_rest_url(null, '/lnp-alby/v1')
        ));
	}

	/**
     * filter ln shortcodes and inject payment request HTML
     */
    public function ln_paywall_filter($content)
    {
		$paywall = new WP_Lightning_Paywall($content);
		return $paywall->getContent();
    }

	/**
     * AJAX endpoint to create new invoices
     */
    public function ajax_make_invoice()
    {
        if (!empty($_POST['post_id'])) {
            $post_id = (int)$_POST['post_id'];
            $paywall_options = $this->get_paywall_options_for($post_id, get_post_field('post_content', $post_id));
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

        $invoice = $this->getLightningClient()->addInvoice($invoice_params);
        $this->database_handler->store_invoice($post_id, $invoice['r_hash'], $invoice['payment_request'], $amount, '', 0);

        $jwt_data = array_merge($response_data, ['invoice_id' => $invoice['r_hash'], 'r_hash' => $invoice['r_hash'], 'exp' => time() + 60 * 10]);
        $jwt = JWT\JWT::encode($jwt_data, WP_LN_PAYWALL_JWT_KEY,  WP_LN_PAYWALL_JWT_ALGORITHM);

        $response = array_merge($response_data, ['token' => $jwt, 'payment_request' => $invoice['payment_request']]);
        //wp_send_json([ 'post_id' => $post_id, 'token' => $jwt, 'amount' => $paywall_options['amount'], 'payment_request' => $invoice['payment_request']]);
        wp_send_json($response);
    }
}
