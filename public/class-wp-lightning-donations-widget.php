<?php

// If this file is called directly, abort.
defined('WPINC') || die;


class LNP_DonationsWidget {

    function __construct( $plugin ) {

        $this->plugin = $plugin;
    }



    /**
     * Load HTML template file
     * Allow user to override default template filename with filter
     * 
     * @return [type] [description]
     */
    public function get_donation_template() {

        /**
         * Example usage:
         * 
         * add_filter('lnp_alby_donate_template' function( $filename ) {
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
            untrailingslashit(get_stylesheet_directory()) . '/lnp-alby/' . $filename,
            WP_LN_ROOT_PATH . '/public/templates/donation/' . $filename,
        );

        foreach ( $template_names as $template )
        {
            if ( file_exists($template) )
            {
                return $template;
            }
        }
    }




    /**
     * Append and/or prepend donation box to all post types
     * Where in options is this option enabled
     */
    public function set_donation_box( $content )
    {
        global $post;

        $post_type = get_post_type($post);
        $placement = $this->is_enabled_for_post_type($post_type);
        $template  = $this->get_donation_template();

        // Nothing to incldue
        if ( empty($placement) || ! $template )
        {
            return $content;
        }

        $wplnp = $this->get_donation_box_options();

        ob_start();

        // Include above content
        if ( in_array('above', $placement) )
        {
            do_action( 'before_lnp_alby_donation_widget', 'above' );

            require $template;

            do_action( 'after_lnp_alby_donation_widget', 'above' );
        }

        // WP_Post content
        echo $content;

        // Include below content
        if ( in_array('below', $placement) )
        {
            do_action( 'before_lnp_alby_donation_widget', 'below' );

            require $template;

            do_action( 'after_lnp_alby_donation_widget', 'below' );
        }

        // Include Js/CSS
        $this->load_scripts();
        
        // Return post content
        return ob_get_clean();
    }




    /**
     * Append and/or prepend donation box to all post types
     * Where in options is this option enabled
     */
    public function get_donation_block_html()
    {
        $template = $this->get_donation_template();
        $wplnp    = $this->get_donation_box_options();

        ob_start();

        require $template;

        // Include Js/CSS
        $this->load_scripts();
        
        // Return post content
        return ob_get_clean();
    }



    /**
     * Get options for donations box
     * 
     * @var $option string option_name
     * 
     * @return mixed  plugin options from 'Donations' settings page
     */
    public function get_donation_box_options( string $option = '' )
    {
        $options = ( property_exists($this->plugin, 'donation_options') )
            ? $this->plugin->getDonationOptions()
            : array();


        /**
         * Include LND Payment options
         */
        $options['lnd_client'] = $this->plugin->getLightningClientType();

        // Return specifc option
        if ( $option && isset($options[ $option ]) )
        {
            return $options[ $option ];
        }

        // Return all if not specified
        return $options;
    }




    /**
     * Check if donation box
     * @param  string  $post_type [description]
     * @return boolean            [description]
     */
    public function is_enabled_for_post_type( string $post_type )
    {
        $options   = $this->get_donation_box_options();
        $placement = ( isset($options['donations_autoadd']) )
            ? array_keys($options['donations_autoadd'])
            : array();

        // If no placement is selected don't include
        if ( ! $placement )
            return array();

        // Post types enabled in options
        $post_types = ( isset($options['donations_enabled_for']) )
            ? array_keys($options['donations_enabled_for'])
            : array();

        // Check if current WP_Post post_type is in array of allowed ones
        // and return placement
        return ( in_array( $post_type, $post_types ) )
            ? $placement
            : array();
    }



    /**
     * Include scripts required for donation widget to work
     * Scripts will be included async
     * 
     * @return [type] [description]
     */
    public function load_scripts()
    {
        // Enqueue script for Donation widget
        echo wp_get_script_tag(
            array(
                'src'   => WP_LN_ROOT_URI . '/assets/js/components/donations.js',
                'async' => true,
                'defer' => true,
            )
        );
    }
}