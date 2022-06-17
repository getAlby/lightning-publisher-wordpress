<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class LNP_LogsPage extends LNP_SettingsPage
{
    protected $settings_path = 'lnp_settings_logs';
    protected $option_name   = 'lnp_paywall';
    protected $template_html = 'settings/page-logs.php';

    /**
     * Make menu item/page title translatable
     */
    protected function set_translations()
    {
        // Menu Item label
        $this->page_title = __( 'Logs', 'lnp-alby' );
        $this->menu_title = __( 'Logs', 'lnp-alby' );
    }
}
