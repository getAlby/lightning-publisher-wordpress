<?php

 use \tkijewski\lnurl;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WP_Lightning
 * @subpackage WP_Lightning/admin
 */
class WP_Lightning_Admin
{

    /**
     * Main Plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    WP_Lightning    $plugin    The main plugin object.
     */
    private $plugin;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param WP_Lightning $plugin The main plugin object.
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_styles()
    {

        wp_enqueue_style($this->plugin->get_plugin_name(), plugin_dir_url(__FILE__) . 'css/wp-lightning-admin.css', array(), $this->plugin->get_version(), 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts()
    {

        wp_enqueue_script($this->plugin->get_plugin_name(), plugin_dir_url(__FILE__) . 'js/wp-lightning-admin.js', array( 'jquery' ), $this->plugin->get_version(), true);
    }

    /**
     * Admin Page
     */
    public function lightning_menu()
    {
        add_menu_page(
            'LN Publisher',
            'LN Publisher',
            'manage_options',
            'lnp_settings',
            null,
            'dashicons-superhero'
        );
    }


    /**
     * Add Block
     *
     * @return [type] [description]
     */
    public function init_donation_block()
    {

        // Gutenberg is not active.
        if (! function_exists('register_block_type') ) {
            return;
        }

        register_block_type(dirname(__DIR__, 1) . '/blocks/donate/block.json');
        register_block_type(
            dirname(__DIR__, 1) . '/blocks/paywall/block.json',
            array(
                'render_callback' => [$this, 'render_paywall_shortcode'],
            )
        );

        // The JS block script
        $twentyuno_block_js_path = sprintf(
            '%s/blocks/twentyuno/block.js',
            untrailingslashit(WP_LN_ROOT_URI)
        );
        wp_register_script(
            'alby-twentyuno-block-script-edit',
            $twentyuno_block_js_path,
            ['wp-blocks', 'wp-i18n', 'wp-element'], // Required scripts for the block
            filemtime(dirname(__DIR__, 1) . '/blocks/twentyuno/block.js')
        );
        wp_register_script("twentyuno-widget-script", "https://embed.twentyuno.net/js/app.js");
        $twentyuno_block_editor_css_path = sprintf(
            '%s/blocks/twentyuno/editor.css',
            untrailingslashit(WP_LN_ROOT_URI)
        );
        wp_register_style(
            'alby-twentyuno-block-css-edit',
            $twentyuno_block_editor_css_path
        );

        register_block_type(
            dirname(__DIR__, 1) . '/blocks/twentyuno/block.json',
            array(
                'render_callback' => [$this, 'render_twentyuno_widget_block'],
            )
        );

        // Path to Js that handles block functionality
        wp_register_script(
            'alby/donate-js',
            sprintf(
                '%s/assets/js/blocks/donation/donation.js',
                untrailingslashit(WP_LN_ROOT_URI)
            )
        );

        wp_register_style(
            'alby/donate-css',
            sprintf(
                '%s/assets/css/blocks/donation.css',
                untrailingslashit(WP_LN_ROOT_URI)
            )
        );



        register_block_type(
            'alby/donate', array(
            'api_version'     => 2,
            'title'           => 'Alby: Bitcoin Donation',
            'category'        => 'common',
            'description'     => 'Learning in progress',
            'icon'            => 'icon-alby',
            'editor_script'   => 'alby/donate-js',
            'editor_style'    => 'alby/donate-css',
            'attributes'      => [
                'amount'      => [
                    'type'    => 'number'
                ]
            ],
            'render_callback' => (array($this, 'render_gutenberg')),
            )
        );
    }

    public function render_twentyuno_widget_block( $attrs)
    {
        $name = !empty($attrs['name']) ? strip_tags($attrs["name"]) : '';
        $color = !empty($attrs['color']) ? strip_tags($attrs["color"]) : '';
        $image = !empty($attrs['image']) ? strip_tags($attrs["image"]) : '';
        $lnurl = lnurl\encodeUrl(get_rest_url(null, '/lnp-alby/v1/lnurlp'));

        return '<div class="wp-lnp-twentyuno-widget">
            <lightning-widget
                name="'. $name . '"
                accent="'. $color . '"
                to="'. $lnurl .'"
                image="'. $color . '"
            />
          </div>';
    }

    function render_paywall_shortcode( $attributes, $content )
    {
        $sanitized_attributes = array_map(
            function ($key, $value) {
                return strval($key) . '="' . esc_html(strval($value)) . '"';
            }, array_keys($attributes), array_values($attributes)
        );
        $shortcode_attributes = implode(" ", $sanitized_attributes);
        return "[lnpaywall " . $shortcode_attributes . " ]";
    }

    public function render_gutenberg( $atts )
    {
        return 'nop';
        $atts = shortcode_atts(
            array(
            'pay_block'     => 'true',
            'btc_format'    => '',
            'currency'      => '',
            'price'         => '',
            'duration_type' => '',
            'duration'      => '',
            ), $atts
        );

        return do_shortcode("[alby_donation_block]");
    }

    function widget_init()
    {
        $lnurl = lnurl\encodeUrl(get_rest_url(null, '/lnp-alby/v1/lnurlp'));
        $widget = new TwentyunoWidget(["lnurl" => $lnurl]);
        register_widget($widget);
    }

    /**
     * Reset the existing values in the wallet settings options
     * before saving the current wallet options
     */
    function reset_wallet_on_update($new_value, $old_value, $option)
    {
        // Get the difference between the 2 array
        $diff = array_diff($new_value, $old_value);
        // If any difference is found (new wallet used), reset all the values and only use the new ones
        if (!empty($diff)) {
            $empty_settings = array_fill_keys(array_keys($old_value), "");
            $new_value = array_merge($empty_settings, $diff);
        }
        return $new_value;
    }
}
