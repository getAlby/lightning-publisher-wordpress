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
                'label'       => __( 'Text', 'lnp-alby' ),
                'name'        => 'paywall_text',
                'description' => __( 'Paywall text (use %s for the amount)', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'pricing',
            'field'   => array(
                'label' => __( 'Button Label', 'lnp-alby' ),
                'name'  => 'paywall_button_text',
            ),
        );

        $fields[] = array(
            'tab'     => 'pricing',
            'field'   => array(
                'label'       => __( 'Amount', 'lnp-alby' ),
                'type'        => 'number',
                'name'        => 'paywall_amount',
                'description' => __( 'Amount in SATS per article', 'lnp-alby'),
            ),
        );    

        $fields[] = array(
            'tab'     => 'pricing',
            'field'   => array(
                'label'       => __( 'Total', 'lnp-alby' ),
                'name'        => 'paywall_total',
                'type'        => 'number',
                'description' => __( 'Total amount to collect. After that amount the article will be free', 'lnp-alby'),
            ),
        );
        

        /**
         * Fields for section: Pricing
         */
        $fields[] = array(
            'tab'     => 'restrictions',
            'field'   => array(
                'label'       => __( 'Timeout', 'lnp-alby' ),
                'name'        => 'paywall_timeout',
                'description' => __( 'Make the article free X days after it is published', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'restrictions',
            'field'   => array(
                'label'       => __( 'Timein', 'lnp-alby' ),
                'name'        => 'paywall_timein',
                'description' => __( 'Enable the paywall N days after the article is published', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'restrictions',
            'field'   => array(
                'label'       => __( 'Days available', 'lnp-alby' ),
                'name'        => 'paywall_all_period',
                'description' => __( 'How many days should all articles be available', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'restrictions',
            'field'   => array(
                'label'       => __( 'Confirmation text', 'lnp-alby' ),
                'name'        => 'paywall_all_confirmation',
                'description' => __( 'Confirmation text for all article payments', 'lnp-alby'),
            ),
        );


        /**
         * Fields for section: Pricing
         */
        $fields[] = array(
            'tab'     => 'integrations',
            'field'   => array(
                'label'       => __( 'Add LNURL to RSS items', 'lnp-alby' ),
                'name'        => 'paywall_lnurl_rss',
                'description' => __( 'Add lightning payment details to RSS items', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'integrations',
            'field'   => array(
                'label'       => __( 'Disable paywall in RSS?', 'lnp-alby' ),
                'name'        => 'paywall_disable_in_rss',
                'description' => __( 'Disable paywall in RSS items / show full content in RSS.', 'lnp-alby'),
            ),
        );

        // Save Form fields to class
        $this->form_fields = $fields;
    }
}
