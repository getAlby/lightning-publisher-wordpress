<?php

require_once 'init.php';

class HelpPage extends SettingsPage
{
    protected $settings_path = 'lnp_settings_help';
    protected $option_name = 'lnp_paywall';

    protected $page_title = 'Help';
    protected $menu_title = 'Help';

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
            <blockquote>
                <ul>
                    <li>amount</li>
                    <li>total</li>
                    <li>timein</li>
                    <li>timeout</li>
                </ul>
            </blockquote>

            <h3>Usage</h3>
            <blockquote>
                <p>[lnd-SHORT_CODE] eg: [lnd-amount]</p>
            </blockquote>

        </div>
<?php
    }
}
