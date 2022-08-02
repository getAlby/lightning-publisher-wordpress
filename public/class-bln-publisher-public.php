<?php

use Firebase\JWT;

// If this file is called directly, abort.
defined('WPINC') || die;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    BLN_Publisher
 * @subpackage BLN_Publisher/public
 */
class BLN_Publisher_Public
{

    /**
     * Main Plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    BLN_Publisher    $plugin    The main plugin object.
     */
    private $plugin;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param BLN_Publisher $plugin The main plugin object.
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in BLN_Publisher_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The BLN_Publisher_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin->get_plugin_name(), plugin_dir_url(__FILE__) . 'css/bln-publisher-public.css', array(), $this->plugin->get_version(), 'all');

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in BLN_Publisher_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The BLN_Publisher_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin->get_plugin_name(), plugin_dir_url(__FILE__) . 'js/bln-publisher-public.js', $this->plugin->get_version(), true);

        wp_localize_script(
            $this->plugin->get_plugin_name(), 'LN_Paywall', array(
            'rest_base' => get_rest_url(null, '/lnp-alby/v1')
            )
        );
    }

    /**
     * filter ln shortcodes and inject payment request HTML
     */
    public function ln_paywall_filter($content)
    {
        $paywall = new BLN_Publisher_Paywall($this->plugin, [ "content" => $content, "post_id" => get_the_ID()]);
        return $paywall->get_content();
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
        if (
            (!empty($this->plugin->getGeneralOptions()['lnurl_meta_tag']) && $this->plugin->getGeneralOptions()['lnurl_meta_tag']) ||
            (!empty($this->plugin->getGeneralOptions()['lnurl_meta_tag_lnurlp']) && $this->plugin->getGeneralOptions()['lnurl_meta_tag_lnurlp'])
        ) {
            if (!empty($this->plugin->getGeneralOptions()['lnurl_meta_tag_lnurlp'])) {
                $lnurl = $this->plugin->getGeneralOptions()['lnurl_meta_tag_lnurlp'];
            } else {
                $lnurl = get_rest_url(null, '/lnp-alby/v1/lnurlp');
            }
            $lnurl_without_protocol = preg_replace('/^https?:\/\//', '', $lnurl);
            echo '<meta name="lightning" content="lnurlp:' . $lnurl_without_protocol . '" />';
        }
    }

    public function sc_alby_donation_block()
    {

        $donationWidget = new LNP_DonationsWidget($this->plugin);

        return $donationWidget->get_donation_block_html();
    }

    public function add_v4v_rss_ns_tag()
    {
        echo 'xmlns:podcast="https://podcastindex.org/namespace/1.0"';
    }

    public function add_v4v_rss_tag()
    {
        $address = $this->plugin->getGeneralOptions()['v4v_node_key'];
        $custom_key = $this->plugin->getGeneralOptions()['v4v_custom_key'];
        $custom_value = $this->plugin->getGeneralOptions()['v4v_custom_value'];

        $tag = array();
        $tag[] = '<podcast:value type="lightning" method="keysend">';
        $tag[] = '<podcast:valueRecipient name="' . get_bloginfo('name') .'" type="node" address="' . $address . '"';
        if (!empty($custom_key)) {
            $tag[] = ' customKey="' . $custom_key . '"';
        }
        if (!empty($custom_value)) {
            $tag[] = ' customValue="' . $custom_value . '"';
        }
        $tag[] = ' split="100" />';
        $tag[] = '</podcast:value>';
        echo join(' ', $tag);
    }
}
