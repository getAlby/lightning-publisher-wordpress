<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class PaywallPage extends SettingsPage
{
    protected $settings_path = 'lnp_settings_paywall';
    protected $template_html = 'settings/page-paywall.php';
    protected $option_name   = 'lnp_paywall';

    protected $page_title;
    protected $menu_title;

    public function init_fields()
    {
        // parent::init_fields();

        // Tabbed sections
        $this->sections = array(
            'pricing'      => __( 'Pricing', 'wp-lightning-paywall' ),
            'restrictions' => __( 'Restrictions', 'wp-lightning-paywall' ),
            'integrations' => __( 'Integrations', 'wp-lightning-paywall' ),
        );

        // Register Tabbed sections
        foreach( $this->sections as $id => $label )
        {
            add_settings_section(
                'paywall_' . $id,
                $label,
                null,
                'wpln_paywall_' . $id
            );
        }

        $this->init_form_fields();
    }


    /**
     * Make menu item/page title translatable
     */
    protected function set_translations()
    {
        $this->page_title = __( 'Paywall Settings', 'wp-lightning-paywall' );
        $this->menu_title = __( 'Paywall Settings', 'wp-lightning-paywall' );
    }


    /**
     * Array of form fields available on this page
     */
    public function init_form_fields() {

        /**
         * Fields 
         */
        $fields = array();

        /**
         * Fields for section: Pricing
         */
        $fields[] = array(
            'page'    => 'wpln_paywall_pricing',
            'section' => 'paywall_pricing',
            'field'   => array(
                'label'       => __( 'Text', 'wp-lightning-paywall' ),
                'name'        => 'paywall_text',
                'description' => __( 'Paywall text (use %s for the amount)', 'wp-lightning-paywall'),
            ),
        );

        $fields[] = array(
            'page'    => 'wpln_paywall_pricing',
            'section' => 'paywall_pricing',
            'field'   => array(
                'label' => __( 'Button Label', 'wp-lightning-paywall' ),
                'name'  => 'paywall_button_text',
            ),
        );

        $fields[] = array(
            'page'    => 'wpln_paywall_pricing',
            'section' => 'paywall_pricing',
            'field'   => array(
                'label'       => __( 'Amount', 'wp-lightning-paywall' ),
                'name'        => 'paywall_amount',
                'description' => __( 'Amount in SATS per article', 'wp-lightning-paywall'),
            ),
        );    

        $fields[] = array(
            'page'    => 'wpln_paywall_pricing',
            'section' => 'paywall_pricing',
            'field'   => array(
                'label'       => __( 'Total', 'wp-lightning-paywall' ),
                'name'        => 'paywall_total',
                'description' => __( 'Total amount to collect. After that amount the article will be free', 'wp-lightning-paywall'),
            ),
        );
        

        /**
         * Fields for section: Pricing
         */
        $fields[] = array(
            'page'    => 'wpln_paywall_restrictions',
            'section' => 'paywall_restrictions',
            'field'   => array(
                'label'       => __( 'Timeout', 'wp-lightning-paywall' ),
                'name'        => 'paywall_timeout',
                'description' => __( 'Make the article free X days after it is published', 'wp-lightning-paywall'),
            ),
        );

        $fields[] = array(
            'page'    => 'wpln_paywall_restrictions',
            'section' => 'paywall_restrictions',
            'field'   => array(
                'label'       => __( 'Timein', 'wp-lightning-paywall' ),
                'name'        => 'paywall_timein',
                'description' => __( 'Enable the paywall N days after the article is published', 'wp-lightning-paywall'),
            ),
        );

        $fields[] = array(
            'page'    => 'wpln_paywall_restrictions',
            'section' => 'paywall_restrictions',
            'field'   => array(
                'label'       => __( 'Days available', 'wp-lightning-paywall' ),
                'name'        => 'paywall_all_period',
                'description' => __( 'How many days should all articles be available', 'wp-lightning-paywall'),
            ),
        );

        $fields[] = array(
            'page'    => 'wpln_paywall_restrictions',
            'section' => 'paywall_restrictions',
            'field'   => array(
                'label'       => __( 'Confirmation text', 'wp-lightning-paywall' ),
                'name'        => 'paywall_all_confirmation',
                'description' => __( 'Confirmation text for all article payments', 'wp-lightning-paywall'),
            ),
        );


        /**
         * Fields for section: Pricing
         */
        $fields[] = array(
            'page'    => 'wpln_paywall_integrations',
            'section' => 'paywall_integrations',
            'field'   => array(
                'label'       => __( 'Add LNURL to RSS items', 'wp-lightning-paywall' ),
                'name'        => 'paywall_lnurl_rss',
                'description' => __( 'Add lightning payment details to RSS items', 'wp-lightning-paywall'),
            ),
        );

        $fields[] = array(
            'page'    => 'wpln_paywall_integrations',
            'section' => 'paywall_integrations',
            'field'   => array(
                'label'       => __( 'Disable paywall in RSS?', 'wp-lightning-paywall' ),
                'name'        => 'paywall_disable_in_rss',
                'description' => __( 'Disable paywall in RSS items / show full content in RSS.', 'wp-lightning-paywall'),
            ),
        );

        // Save Form fields to class
        $this->form_fields = $fields;
    }
}
