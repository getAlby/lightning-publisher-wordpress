<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class LNP_Dashboard extends LNP_SettingsPage
{
    protected $settings_path = 'lnp_settings';
    protected $option_name   = 'lnp_dashboard';
    protected $template_html = 'settings/page-dashboard.php';

    /**
     * Make menu item/page title translatable
     */
    protected function set_translations()
    {
        // Menu Item label
        $this->page_title = __( 'Dashboard', 'lnp-alby' );
        $this->menu_title = __( 'Dashboard', 'lnp-alby' );
    }
}
