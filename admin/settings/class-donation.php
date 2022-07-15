<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class LNP_DonationPage extends LNP_SettingsPage
{
    protected $settings_path = 'lnp_settings_donation';
    protected $option_name   = 'lnp_donation';
    protected $template_html = 'settings/page-donation.php';

    public function init_fields()
    {
        // Tabs
        $this->tabs   = array(
            'integrations' => array(
                'title' => __('Integrations', 'lnp-alby'),
            ),
            'widget' => array(
                'title' => __('Donation Widget', 'lnp-alby'),
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
        $this->page_title = __('Donations Settings', 'lnp-alby');
        $this->menu_title = __('Donations', 'lnp-alby');
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
         * Fields for section: Integrations
         */
        $fields[] = array(
            'tab'     => 'integrations',
            'field'   => array(
                'type'        => 'checkbox_group',
                'name'        => 'donations_enabled_for',
                'options'     => $this->get_post_types(),
                'label'       => __('Auto add donation box', 'lnp-alby'),
                'description' => __('Enable this option to automatically append the donation block to the end of each post, for selected post type. You can still manually add the donation box with shortcode or Gutenberg block', 'lnp-alby'),
            ),
        );


        /**
         * Values for field: Placement
         * Make it readable
         */
        $options   = array();
        $options[] = array(
            'value' => 'above',
            'label' => __('Above content', 'lnp-alby'),
        );

        $options[] = array(
            'value' => 'below',
            'label' => __('Below content', 'lnp-alby'),
        );

        $fields[] = array(
            'tab'     => 'integrations',
            'field'   => array(
                'type'        => 'checkbox_group',
                'name'        => 'donations_autoadd',
                'options'     => $options,
                'label'       => __('Placement', 'lnp-alby'),
                'description' => __('Where to add the donation box, if not selected the donation box will not be inserted automatically', 'lnp-alby'),
            ),
        );


        $fields[] = array(
            'tab'     => 'widget',
            'field'   => array(
                'type'    => 'number',
                'name'    => 'widget_amount',
                'default' => 100000,
                'label'   => __('Default amount in sats', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'widget',
            'field'   => array(
                'type'    => 'text',
                'name'    => 'widget_title',
                'default' => 'Show some love',
                'label'   => __('Widget title', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'widget',
            'field'   => array(
                'type'    => 'text',
                'name'    => 'widget_description',
                'default' => 'Support us by donating sats to keep us going',
                'label'   => __('Widget description', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'widget',
            'field'   => array(
                'type'        => 'text',
                'name'        => 'widget_thankyou',
                'default'     => 'Woow, you are awesome! Thank you for your support!',
                'label'       => __('Thank you message', 'lnp-alby'),
                'description' => __('Will be displayed after payment is processed', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'widget',
            'field'   => array(
                'type'    => 'text',
                'name'    => 'widget_button_label',
                'default' => 'Donate now',
                'label'   => __('Widget button label', 'lnp-alby'),
            ),
        );

        // Save Form fields to class
        $this->form_fields = $fields;
    }


    /**
     * Get all registered post types
     * This will populate checbox group where user selects where to
     * automatically prepend or append donation box
     *
     * @return [array] array('post_type' => 'Label');
     */
    private function get_post_types()
    {

        /**
         * Docs:
         *
         * @link https://developer.wordpress.org/reference/functions/get_post_types/
         */
        $types   = get_post_types(array('public' => true), 'objects');
        $options = array();

        foreach ( $types as $type )
        {
            $options[] = array(
                'label' => $type->label,
                'value' => $type->name,
            );
        }

        return $options;
    }
}
