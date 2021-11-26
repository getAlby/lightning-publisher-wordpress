<?php

require_once 'SettingsPage.php';

class BalancePage extends SettingsPage
{
    protected $settings_path = 'lnp_settings_balances';
    protected $option_name = 'lnp_paywall';

    protected $page_title = 'Lightning Paywall Transactions';
    protected $menu_title = 'Transactions';

    public function renderer()
    {
?>
        <div class="wrap">
            <h1>Lightning Transactions</h1>
        </div>
<?php
    }
}
