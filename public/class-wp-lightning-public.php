<?php

use Firebase\JWT;

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

	public function add_lnurl_to_rss_item_filter()
	{
		global $post;
		$pay_url = get_rest_url(null, '/lnp-alby/v1/lnurlp');
		$lnurl = preg_replace('/^https?:\/\//', 'lnurlp://', $pay_url);
		echo '<payment:lnurl>' . $lnurl . '</payment:lnurl>';
	}

	public function hook_meta_tags()
	{
		if (!empty($this->plugin->getPaywallOptions()['lnurl_meta_tag']) && $this->plugin->getPaywallOptions()['lnurl_meta_tag']) {
			$lnurl = get_rest_url(null, '/lnp-alby/v1/lnurlp');
			echo '<meta name="lightning" content="lnurlp:' . $lnurl . '" />';
		}
	}

	public function sc_alby_donation_block() {

        $donationWidget = new LNP_DonationsWidget($this->plugin);

        return $donationWidget->get_donation_block_html();
    }
}
