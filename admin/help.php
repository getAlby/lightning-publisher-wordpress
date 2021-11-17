<?php

require_once 'SettingsPage.php';

class HelpPage implements SettingsPage
{
    private $settings_path = 'lnp_settings_help';

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
        add_submenu_page('lnp_settings', 'Lightning Paywall Help', 'Help', 'manage_options', 'lnp_help', array($this, 'renderer'));
    }

    public function renderer()
    {
?>
<div class="wrap">
            <h1>Lightning Paywall Help</h1>
        </div>
        <div class="wrap">
        <h3>Shortcodes</h3>
            <p>
                To configure each article the following shortcode attributes are available:
            </p>
            <ul>
                <li>amount</li>
                <li>total</li>
                <li>timein</li>
                <li>timeout</li>
            </ul>
            
        </div>
<?php
    }
}
