<?php

// If this file is called directly, abort.
defined('WPINC') || die;

require_once 'init.php';

class LNP_Dashboard extends SettingsPage
{
    protected $settings_path = 'lnp_settings';
    protected $option_name = 'lnp_dashboard';

    protected $page_title = 'Dashboard';
    protected $menu_title = 'Dashboard';

    public function renderer()
    {
?>
<div class="wrap">
            <h1>Lightning Dashboard</h1>
        </div>
        <div class="wrap">
        </div>
<?php
    }
}
