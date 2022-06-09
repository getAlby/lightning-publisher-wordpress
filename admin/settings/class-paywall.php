<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class LNP_PaywallPage extends LNP_SettingsPage
{
    protected $settings_path = 'lnp_settings_paywall';
    protected $template_html = 'settings/page-paywall.php';
    protected $option_name   = 'lnp_paywall';
  
    public function init_fields()
    {
        // Tabs
        $this->tabs   = array(
            'pricing' => array(
                'title' => __( 'Pricing', 'lnp-alby' ),
            ),
            'restrictions' => array(
                'title' => __( 'Restrictions', 'lnp-alby' ),
            ),
            'integrations' => array(
                'title' => __( 'Integrations', 'lnp-alby' ),
            ),
        );

        parent::init_fields();
    }


    /**
     * Make menu item/page title translatable
     */
    protected function set_translations()
    {
        // Menu Item label
        $this->page_title = __( 'Paywall Settings', 'lnp-alby' );
        $this->menu_title = __( 'Paywall Settings', 'lnp-alby' );
    }


    /**
     * Array of form fields available on this page
     */
    public function set_form_fields() {

        /**
         * Fields 
         */
        $fields = array();

        /**
         * Fields for section: Pricing
         */
        $fields[] = array(
            'tab'     => 'pricing',
            'field'   => array(
                'name'        => 'paywall_text',
                'label'       => __( 'Text', 'lnp-alby' ),
                'description' => __( 'Paywall text (use %s for the amount)', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'pricing',
            'field'   => array(
                'name'  => 'paywall_button_text',
                'label' => __( 'Button Label', 'lnp-alby' ),
            ),
        );

        $fields[] = array(
            'tab'     => 'pricing',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'paywall_amount',
                'label'       => __( 'Amount', 'lnp-alby' ),
                'description' => __( 'Amount in SATS per article', 'lnp-alby'),
            ),
        );    

        $fields[] = array(
            'tab'     => 'pricing',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'paywall_total',
                'label'       => __( 'Total', 'lnp-alby' ),                
                'description' => __( 'Total amount to collect. After that amount the article will be free', 'lnp-alby'),
            ),
        );
        

        /**
         * Fields for section: Pricing
         */
        $fields[] = array(
            'tab'     => 'restrictions',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'paywall_timeout',
                'label'       => __( 'Timeout', 'lnp-alby' ),
                'description' => __( 'Make the article free N days after it is published', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'restrictions',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'paywall_timein',
                'label'       => __( 'Timein', 'lnp-alby' ),
                'description' => __( 'Enable the paywall N days after the article is published', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'restrictions',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'paywall_all_period',
                'label'       => __( 'Days available', 'lnp-alby' ),
                'description' => __( 'How many days should all articles be available', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'restrictions',
            'field'   => array(
                'name'        => 'paywall_all_confirmation',
                'label'       => __( 'Confirmation text', 'lnp-alby' ),
                'description' => __( 'Confirmation text for all article payments', 'lnp-alby'),
            ),
        );


        /**
         * Fields for section: Pricing
         */
        $fields[] = array(
            'tab'     => 'integrations',
            'field'   => array(
                'name'        => 'paywall_lnurl_rss',
                'label'       => __( 'Add LNURL to RSS items', 'lnp-alby' ),
                'description' => __( 'Add lightning payment details to RSS items', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'integrations',
            'field'   => array(
                'type'        => 'checkbox',
                'name'        => 'paywall_disable_in_rss',
                'label'       => __( 'Disable paywall in RSS?', 'lnp-alby' ),
                'description' => __( 'Disable paywall in RSS items / show full content in RSS.', 'lnp-alby'),
            ),
        );

        // Save Form fields to class
        $this->form_fields = $fields;
    }
}
