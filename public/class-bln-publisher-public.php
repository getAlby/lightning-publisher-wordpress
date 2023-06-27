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

        wp_enqueue_script($this->plugin->get_plugin_name(), plugin_dir_url(__FILE__) . 'js/bln-publisher-public.js', $this->plugin->get_version(), true);

        wp_enqueue_script('bln-webln-button.js', plugin_dir_url(__FILE__) . 'js/bln-webln-button.js', $this->plugin->get_version(), true);

        wp_localize_script(
            $this->plugin->get_plugin_name(), 'LN_Paywall', array(
            'rest_base' => get_rest_url(null, '/lnp-alby/v1'),
            'post_id'   => get_the_ID(),
            )
        );
    }

   /**
    * Script loader filter to add type="module" where required
    *
    * @since 1.0.0
    */
    public function add_module_script_type_attribute($tag, $handle, $src)
    {
        // if it is a module add type module to the tag
        // we need this for the simple-boost button
        if (str_contains($handle, 'bln-js-modules')) {
            $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
        }
        return $tag;
    }

    /**
     * filter ln shortcodes and inject payment request HTML
     */
    public function ln_paywall_filter($content)
    {
        $paywall = new BLN_Publisher_Paywall($this->plugin, [ "content" => $content, "post_id" => get_the_ID()]);
        return $paywall->get_content();
    }

    public function shortcodes_to_exempt_from_wptexturize()
    {
      return array("lnpaywall", "ln_v4v", "ln_simple_boost");
    }

    public function add_lnurl_to_rss_item_filter()
    {
        global $post;
        $pay_url = get_rest_url(null, '/lnp-alby/v1/lnurlp');
        $lnurl = preg_replace('/^https?:\/\//', 'lnurlp://', $pay_url);
        echo '<payment:lnurl>' . esc_attr($lnurl) . '</payment:lnurl>';
    }

    public function hook_meta_tags()
    {
        // All options
        $options = $this->plugin->getGeneralOptions();

        // In case options not saved or disabled
        if ( empty($options['lnurl_meta_tag']) )
        {
            return;
        }
        
        // LN Address
        $lnurl = ( empty($options['lnurl_meta_tag_lnurlp']) )
            ? get_rest_url(null, '/lnp-alby/v1/lnurlp') // Default
            : $options['lnurl_meta_tag_lnurlp']; // Custom option


        $post_id = get_the_ID();
        // In case of WP_Post use authors lightning address
        if ( $post_id )
        {
            $post = get_post($post_id);
            $address = get_user_meta( $post->post_author, '_lnp_ln_address', true );
            if ( $address )
            {
                $lnurl = $address;
            }
        }
        
        /**
         * Filter to enable programmatic update 
         * Usage: 
         *
         * add_filter( 'lnurl_meta_tag_lnurlp', function( $lnurl ) {
         *
         *     if ( is_page('about-us') )
         *     {
         *         $lnurl = 'me@you.io';
         *     }
         * 
         *     return $lnurl;
         * });
         */
        $lnurl = apply_filters( 'lnurl_meta_tag_lnurlp', $lnurl );

        // Strip protocol
        $lnurl_without_protocol = preg_replace('/^https?:\/\//', '', $lnurl);

        // Echo value
        printf(
            '<meta name="lightning" content="lnurlp:%s" />',
            esc_attr($lnurl_without_protocol)
        );
    }

    public function add_v4v_rss_ns_tag()
    {
        echo ' xmlns:podcast="https://podcastindex.org/namespace/1.0" ';
    }

    public function add_v4v_rss_tag()
    {
        $address = $this->plugin->getGeneralOptions()['v4v_node_key'];
        $custom_key = $this->plugin->getGeneralOptions()['v4v_custom_key'];
        $custom_value = $this->plugin->getGeneralOptions()['v4v_custom_value'];

        ?>
        <podcast:value type="lightning" method="keysend">
            <podcast:valueRecipient type="node" split="100" name="<?php echo esc_attr(get_bloginfo('name')); ?>" address="<?php echo esc_attr($address); ?>" <?php if (!empty($custom_key)) { ?>customKey="<?php echo esc_attr($custom_key); ?>"<?php } ?> <?php if (!empty($custom_value)) { ?>customValue="<?php echo esc_attr($custom_value); ?>"<?php } ?> />
        </podcast:value>
        <?php
    }

    public function render_webln_v4v_donation_button($attributes, $content)
    {
        $attributes = shortcode_atts( array(
            'amount' => '1000',
            'currency' => 'btc',
            'success_message' => 'Thanks!',
        ), $attributes, 'ln_v4v' );
        if (empty($content)) {
            $content = 'Like with sats';
        }
        $amount = !empty($attributes['amount']) ? esc_attr($attributes["amount"]) : '';
        $currency = !empty($attributes['currency']) ? esc_attr($attributes["currency"]) : '';
        $success_message = !empty($attributes['success_message']) ? esc_attr($attributes["success_message"]) : '';

        return '<div class="wp-lnp-webln-button-wrapper">
            <button class="wp-lnp-webln-button" data-amount="' . $amount . '" data-currency="' . $currency . '" data-success="' . $success_message . '">'. wp_kses_post($content) .'</button>
            </div>';
    }

    public function render_webln_v4v_simple_boost($attributes, $content)
    {
        wp_enqueue_script('bln-js-modules/simple-boost.bundled.js', plugin_dir_url(__FILE__) . 'js/bln-js-modules/simple-boost.bundled.js', $this->plugin->get_version(), true);

        $lnurl = get_rest_url(null, '/lnp-alby/v1/lnurlp');
        $attributes = shortcode_atts( array(
            'amount' => '1000',
            'currency' => 'btc',
            'class' => 'gumroad',
        ), $attributes, 'ln_simple_boost' );
        $amount = esc_attr($attributes['currency'] == 'btc' ? intval($attributes['amount']) : intval($attributes['amount'])/100);
        $currency = strtolower($attributes['currency']) == 'btc' ? 'sats' : esc_attr($attributes['currency']);
        $klass = esc_attr($attributes['class']);
        return '<simple-boost
          amount="'. $amount .'"
          currency="' . $currency . '"
          class="' . $klass . '"
          address="' . esc_attr($lnurl) .'"
        >' . wp_kses_post($content) . '</simple-boost>';
    }
}
