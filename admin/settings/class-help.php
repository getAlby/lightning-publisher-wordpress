<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class LNP_HelpPage extends LNP_SettingsPage
{
    protected $settings_path = 'lnp_settings_help';
    protected $option_name   = 'lnp_paywall';
    protected $template_html = 'settings/page-help.php';

    /**
     * Make menu item/page title translatable
     */
    protected function set_translations()
    {
        // Menu Item label
        $this->page_title = __('Help', 'lnp-alby');
        $this->menu_title = __('Help', 'lnp-alby');
    }
}
