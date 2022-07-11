<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class LNP_GeneralPage extends LNP_SettingsPage
{
    protected $settings_path = 'lnp_settings_general';
    protected $template_html = 'settings/page-general.php';
    protected $option_name   = 'lnp_general';

    public function init_fields()
    {
        // Tabs
        $this->tabs   = array(
            'value4value' => array(
                'title' => __('Value 4 Value', 'lnp-alby'),
            ),
            'general' => array(
                'title' => __('General', 'lnp-alby'),
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
        $this->page_title = __('General Settings', 'lnp-alby');
        $this->menu_title = __('General Settings', 'lnp-alby');
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

        $fields[] = array(
            'tab'     => 'value4value',
            'field'   => array(
                'type'  => 'checkbox',
                'name'  => 'lnurl_meta_tag',
                'value' => 'on',
                'label' => __('Enable Value4Value lightning meta tag', 'lnp-alby'),
                'description' => __('Enable the lightning metatag which allows visitors to send sats to your page', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'value4value',
            'field'   => array(
                'name'  => 'lnurl_meta_tag_lnurlp',
                'label' => __('Custom recipient for the lightning meta tag', 'lnp-alby'),
                'description' => __('By default the connected wallet is used to generate the meta tag. You can overwrite this here for example with your lightning address', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'value4value',
            'field'   => array(
                'type'  => 'checkbox',
                'name'  => 'add_v4v_rss_tag',
                'value' => 'on',
                'label' => __('Enable Value4Value tag', 'lnp-alby'),
                'description' => __('Add the podcast:value tag to your RSS feed. Configure the node key (and custom key/value if needed)', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'value4value',
            'field'   => array(
                'name'  => 'v4v_node_key',
                'label' => __('Node key', 'lnp-alby'),
                'description' => __('Node address - the lightning node public key', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'value4value',
            'field'   => array(
                'name'  => 'v4v_custom_key',
                'label' => __('Custom Key', 'lnp-alby'),
            ),
        );
        $fields[] = array(
            'tab'     => 'value4value',
            'field'   => array(
                'name'  => 'v4v_custom_value',
                'label' => __('Custom Value', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'general',
            'field'   => array(
                'name'  => 'cookie_timeframe_days',
                'label' => __('Cookie timeframe', 'lnp-alby'),
                'description' => __('Paid articles are saved in a cookie. How many days should these cookie be valid? (default: 180)', 'lnp-alby'),
            ),
        );

        // Save Form fields to class
        $this->form_fields = $fields;
    }
}
