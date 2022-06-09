<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class LNP_Admin
{
    public function __construct($plugin = false)
    {
        $this->plugin = $plugin;

        add_action('init', array($this, 'init_donation_block') );
        add_shortcode('alby_donation_block', array($this, 'sc_alby_donation_block') );

    }


    /**
     * Add Block
     * @return [type] [description]
     */
    public function init_donation_block() {

        // Gutenberg is not active.
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

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


        register_block_type( 'alby/donate', array(
            'api_version'     => 2,
            'title'           => 'Alby: Bitcoin Donation',
            'category'        => 'common',
            'description'     => 'Learning in progress',
            'icon'            => 'icon-alby',
            'editor_script'   => 'alby/donate-js',
            'editor_style'    => 'alby/donate-css',
            'render_callback' => (array($this, 'render_gutenberg')),
        ));
    }


    public function render_gutenberg( $atts )
    {
        $atts = shortcode_atts(array(
            'pay_block'     => 'true',
            'btc_format'    => '',
            'currency'      => '',
            'price'         => '',
            'duration_type' => '',
            'duration'      => '',
        ), $atts);

        return do_shortcode("[alby_donation_block]");
    }


    public function sc_alby_donation_block() {

        $donationWidget = new LNP_DonationsWidget($this->plugin);

        return $donationWidget->get_donation_block_html();
    }
}