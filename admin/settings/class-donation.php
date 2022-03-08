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
                'values'      => $this->get_post_types(),
                'label'       => __( 'Auto add donation box', 'lnp-alby' ),
                'description' => __( 'Enable this option to automatically append donation block to end of each post, for selected post type', 'lnp-alby'),
            ),
        );


        /**
         * Values for field: Placement
         * Make it readable
         */
        $values   = array();
        $values[] = array(
            'value' => 'above',
            'label' => __( 'Above content', 'lnp-alby' ),
        );

        $values[] = array(
            'value' => 'below',
            'label' => __( 'Below content', 'lnp-alby' ),
        );

        $fields[] = array(
            'tab'     => 'integrations',
            'field'   => array(
                'type'        => 'checkbox_group',
                'name'        => 'donations_autoadd',
                'values'      => $values,
                'label'       => __( 'Placement', 'lnp-alby' ),
                'description' => __( 'Where to add donation box', 'lnp-alby'),
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
    private function get_post_types() {

        /**
         * Docs:
         * @link https://developer.wordpress.org/reference/functions/get_post_types/
         */
        $types  = get_post_types(array('public' => true), 'objects');
        $values = array();

        foreach ( $types as $type )
        {
            $values[] = array(
                'label' => $type->label,
                'value' => $type->name,
            );
        }

        return $values;
    }
}
