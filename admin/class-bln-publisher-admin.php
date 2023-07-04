<?php

 use \tkijewski\lnurl;

// If this file is called directly, abort.
 defined('WPINC') || die;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    BLN_Publisher
 * @subpackage BLN_Publisher/admin
 */
class BLN_Publisher_Admin
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
     * Register the stylesheets for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_styles()
    {

        wp_enqueue_style($this->plugin->get_plugin_name(), plugin_dir_url(__FILE__) . 'css/bln-publisher-admin.css', array(), $this->plugin->get_version(), 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts()
    {

        wp_enqueue_script($this->plugin->get_plugin_name(), plugin_dir_url(__FILE__) . 'js/bln-publisher-admin.js', array( 'jquery' ), $this->plugin->get_version(), true);
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
     * Add Gutenberg Blocks
     *
     */
    public function init_gutenberg_blocks()
    {

        // Gutenberg is not active.
        if (! function_exists('register_block_type') ) {
            return;
        }

        //register_block_type(dirname(__DIR__, 1) . '/blocks/donate/block.json');
        register_block_type(
            dirname(__DIR__, 1) . '/blocks/paywall/block.json',
            array(
                'render_callback' => [$this, 'render_paywall_shortcode'],
            )
        );

        register_block_type(
            dirname(__DIR__, 1) . '/blocks/twentyuno/block.json',
            array(
                'render_callback' => [$this, 'render_twentyuno_widget_block'],
            )
        );

        wp_enqueue_script("twentyuno-widget-script.js",  sprintf(
            '%s/public/js/twentyuno.js',
            untrailingslashit(BLN_PUBLISHER_ROOT_URI)
        ));

        //register_block_type(dirname(__DIR__, 1) . '/blocks/donate/block.json');
        register_block_type(
            dirname(__DIR__, 1) . '/blocks/webln-button/block.json',
            array(
                'render_callback' => [$this, 'render_webln_donation_button'],
            )
        );

        /*
        // Path to Js that handles block functionality
        wp_register_script(
            'alby/donate-js',
            sprintf(
                '%s/assets/js/blocks/donation/donation.js',
                untrailingslashit(BLN_PUBLISHER_ROOT_URI)
            )
        );

        wp_register_style(
            'alby/donate-css',
            sprintf(
                '%s/assets/css/blocks/donation.css',
                untrailingslashit(BLN_PUBLISHER_ROOT_URI)
            )
        );
        */
    }

    function render_twentyuno_widget_block( $attrs)
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
                image="'. $image . '"
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

    function render_webln_donation_button($attributes)
    {
        $amount = !empty($attributes['amount']) ? esc_attr($attributes["amount"]) : '';
        $currency = !empty($attributes['currency']) ? esc_attr($attributes["currency"]) : '';
        $button_text = !empty($attributes['button_text']) ? strip_tags($attributes["button_text"]) : '';
        $success_message = !empty($attributes['success_message']) ? esc_attr($attributes["success_message"]) : '';

        return '<div class="wp-lnp-webln-button-wrapper">
            <button class="wp-lnp-webln-button" data-amount="' . $amount . '" data-currency="' . $currency . '" data-success="' . $success_message . '">'. $button_text .'</button>
            </div>';
    }

    /**
     * Add a Bitcoin Lightning address field to WordPress user profile page
     * 
     * @param  array $methods
     * @return array
     *
     * @link https://developer.wordpress.org/reference/hooks/user_contactmethods/
     */
    function add_user_lnp_address( $methods )
    {
        $methods['_lnp_ln_address'] = __('Lightning Address', 'lnp-alby');
        return $methods;
    }
    
    /**
     * Add settings link to plugin actions
     *
     * @param  array  $plugin_actions
     * @param  string $plugin_file
     * @since  1.0
     * @return array
     *
     * WordPress Docs:
     * @link https://developer.wordpress.org/reference/hooks/plugin_action_links/
     */
    function add_plugin_link( $plugin_actions, $plugin_file ) {

        $new_actions = array();

        if ( 'lightning-publisher-wordpress/bln-publisher.php' === $plugin_file )
        {
            $new_actions['cl_settings'] = sprintf(
                __( '<a href="%s">Settings</a>', 'lnp-alby' ),
                esc_url( admin_url( 'admin.php?page=lnp_settings' ) )
            );
        }

        return array_merge( $new_actions, $plugin_actions );
    }    
}
