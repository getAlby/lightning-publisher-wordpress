<?php

require_once 'SettingsPage.php';

class BalancePage implements SettingsPage
{
    private $settings_path = 'lnp_settings_balances';

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->options = get_option('lnp');
    }

    public function initFields()
    {
        register_setting('lnp', $this->settings_path);
    }

    public function initPage()
    {
        add_submenu_page('lnp_settings', 'Lightning Paywall Balances', 'Balance', 'manage_options', 'lnp_balances', array($this, 'renderer'));
    }

    public function renderer()
    {
?>
        <div class="wrap">
            <h1>Lightning Paywall Balances</h1>
        </div>
<?php
    }
}
