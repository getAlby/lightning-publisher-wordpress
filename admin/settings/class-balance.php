<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class LNP_BalancePage extends LNP_SettingsPage
{
    protected $settings_path = 'lnp_settings_balances';
    protected $option_name   = 'lnp_paywall';
    protected $template_html = 'settings/page-balance.php';

    protected $database_handler;

    public function __construct($plugin, $page, $database_handler)
    {
        parent::__construct($plugin, $page);
        $this->database_handler = $database_handler;
    }

    /**
     * Make menu item/page title translatable
     */
    protected function set_translations()
    {
        // Menu Item label
        $this->page_title = __('Transactions', 'lnp-alby');
        $this->menu_title = __('Transactions', 'lnp-alby');
    }
}
