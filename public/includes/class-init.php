<?php

// If this file is called directly, abort.
defined('WPINC') || die;


class LNP_Public {


    function __construct( $plugin ) {

    }



    /**
     * Load HTML template file
     * Allow user to override default template filename with filter
     *
     * @var $settings array of settings from options page 'Donations'
     * 
     * @return [type] [description]
     */
    public function get_donation_template( $settings = array() ) {

        /**
         * 
         *
         * Example usage:
         * 
         * add_filter('lnp_alby_donate_template' function() {
         *     return 'my-custom-template-filename.php';
         * });
         * 
         *
         */
        $filename = apply_filters( 'lnp_alby_donate_template', 'default.php', 10, 1 );

        /**
         * Allow user to override default template filename with filter
         * Custom template should be saved in currently active theme root
         *
         * "my-theme-name/lnp-alby/my-custom-template-filename.php"
         *
         * Fallback is always to default template located in plugin folder
         * 
         */
        $template_names = array(
            untrailingslashit(get_theme_root()) . '/lnp-alby/' . $filename,
            WP_LN_ROOT_PATH . '/public/templates/donation/' . $filename,
        );

        
        /**
         * Include and display HTML template
         */
        locate_template( $template_names, false, true, $settings );
    }
}