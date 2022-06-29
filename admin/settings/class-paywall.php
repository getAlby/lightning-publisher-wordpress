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
            'paywall' => array(
                'title' => __('Paywall', 'lnp-alby'),
            ),
            'advanced' => array(
                'title' => __('Advanced', 'lnp-alby'),
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
        $this->page_title = __('Paywall Settings', 'lnp-alby');
        $this->menu_title = __('Paywall Settings', 'lnp-alby');
    }


    /**
     * Array of form fields available on this page
     */
    public function set_form_fields()
    {

        /**
         * Fields
         */
        $fields = array();

        /**
         * Fields for section: Pricing
         */
         /*
        $fields[] = array(
            'tab'     => 'paywall',
            'field'   => array(
                'name'        => 'paywall_text',
                'label'       => __( 'Text', 'lnp-alby' ),
                'description' => __( 'Paywall text (use %s for the amount)', 'lnp-alby'),
            ),
        );
        */

        $fields[] = array(
            'tab'     => 'paywall',
            'field'   => array(
                'name'  => 'paywall_button_text',
                'label' => __('Button Label', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'paywall',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'paywall_amount',
                'label'       => __('Default Amount', 'lnp-alby'),
                'description' => __('Amount in SATS per article', 'lnp-alby'),
            ),
        );




        /**
         * Fields for section: Pricing
         */
        $fields[] = array(
            'tab'     => 'advanced',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'paywall_timeout',
                'label'       => __('Timeout', 'lnp-alby'),
                'description' => __('Make the article free X hours after it is published and enable the paywall after that', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'advanced',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'paywall_timein',
                'label'       => __('Timein', 'lnp-alby'),
                'description' => __('Enable the paywall X hours after the article is published', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'advanced',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'paywall_total',
                'label'       => __('Total', 'lnp-alby'),
                'description' => __('Total amount to collect. After that amount the article will be free', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'advanced',
            'field'   => array(
                'type'        => 'checkbox',
                'name'        => 'paywall_disable_in_rss',
                'label'       => __('Disable paywall in RSS?', 'lnp-alby'),
                'description' => __('Disable paywall in RSS items / show full content in RSS.', 'lnp-alby'),
            ),
        );


        /*
        $fields[] = array(
            'tab'     => 'integrations',
            'field'   => array(
                'name'        => 'paywall_lnurl_rss',
                'label'       => __( 'Add LNURL to RSS items', 'lnp-alby' ),
                'description' => __( 'Add lightning payment details to RSS items', 'lnp-alby'),
            ),
        );
        */


        // Save Form fields to class
        $this->form_fields = $fields;
    }
}
