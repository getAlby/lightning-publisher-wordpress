<?php

require_once 'SettingsPage.php';
require_once __DIR__ . '/' . '../tables/transactions.php';
class BalancePage extends SettingsPage
{
    protected $settings_path = 'lnp_settings_balances';
    protected $option_name = 'lnp_paywall';

    protected $page_title = 'Lightning Paywall Transactions';
    protected $menu_title = 'Transactions';

    protected $database_handler;

    public function __construct($plugin, $page, $database_handler)
    {
        parent::__construct($plugin, $page);
        $this->database_handler = $database_handler;
    }

    public function renderer()
    {
        $table = new TransactionsTable($this->database_handler);
        $table->prepare_items();
?>
        <div class="wrap">
            <h1>Lightning Transactions</h1>
            <div>
                <?php
                $table->display();
                ?>
            </div>
        </div>
<?php
    }
}
