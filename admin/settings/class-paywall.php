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
                'name'  => 'button_text',
                'label' => __('Button Label', 'lnp-alby'),
                'description' => __('You can use %{formatted_amount}, %{length}, %{currency}, %{amount}', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'paywall',
            'field'   => array(
                'name'  => 'description',
                'label' => __('Description', 'lnp-alby'),
                'description' => __('You can use %{formatted_amount}, %{length}, %{currency}, %{amount}', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'paywall',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'amount',
                'label'       => __('Default Amount', 'lnp-alby'),
                'description' => __('Amount in smallest unit (e.g. cents/sats) per article', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'paywall',
            'field'   => array(
                'name'        => 'currency',
                'label'       => __('Currency', 'lnp-alby'),
                'description' => __('eur, usd, gbp (default is btc)', 'lnp-alby'),
            ),
        );


        /**
         * Fields for section: Pricing
         */
        $fields[] = array(
            'tab'     => 'advanced',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'timeout',
                'label'       => __('Timeout', 'lnp-alby'),
                'description' => __('Remove paywall and make the article free X hours after it is published', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'advanced',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'timein',
                'label'       => __('Timein', 'lnp-alby'),
                'description' => __('Remove paywall and make the article free for X hours. Then after X hours enable the paywall.', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'advanced',
            'field'   => array(
                'type'        => 'number',
                'name'        => 'total',
                'label'       => __('Total', 'lnp-alby'),
                'description' => __('Total amount to collect. After that amount is reached the paywall will be removed.', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'advanced',
            'field'   => array(
                'type'        => 'checkbox',
                'name'        => 'disable_in_rss',
                'value'       => 'on',
                'label'       => __('Disable paywall in RSS', 'lnp-alby'),
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
