<?php

require_once 'SettingsPage.php';

class PaywallPage implements SettingsPage
{
    private $settings_path = 'lnp_settings_paywall';

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->options = get_option('lnp');
    }

    public function initFields()
    {
        register_setting('lnp', $this->settings_path);
        add_settings_section('paywall', 'Paywall Config', null, $this->settings_path);
        add_settings_field('paywall_text', 'Text', array($this, 'field_paywall_text'), $this->settings_path, 'paywall');
        add_settings_field('paywall_button_text', 'Button', array($this, 'field_paywall_button_text'), $this->settings_path, 'paywall');
        add_settings_field('paywall_amount', 'Amount', array($this, 'field_paywall_amount'), $this->settings_path, 'paywall');
        add_settings_field('paywall_total', 'Total', array($this, 'field_paywall_total'), $this->settings_path, 'paywall');
        add_settings_field('paywall_timeout', 'Timeout', array($this, 'field_paywall_timeout'), $this->settings_path, 'paywall');
        add_settings_field('paywall_timein', 'Timein', array($this, 'field_paywall_timein'), $this->settings_path, 'paywall');
        add_settings_field('paywall_all_amount', 'Amount for all', array($this, 'field_paywall_all_amount'), $this->settings_path, 'paywall');
        add_settings_field('paywall_all_period', 'Days available', array($this, 'field_paywall_all_days'), $this->settings_path, 'paywall');
        add_settings_field('paywall_all_confirmation', 'Confirmation text', array($this, 'field_paywall_all_confirmation'), $this->settings_path, 'paywall');
        add_settings_field('paywall_lnurl_rss', 'Add LNURL to RSS items', array($this, 'field_paywall_lnurl_rss'), $this->settings_path, 'paywall');
        add_settings_field('paywall_disable_in_rss', 'Disable paywall in RSS?', array($this, 'field_paywall_disable_in_rss'), $this->settings_path, 'paywall');
    }


    public function initPage(){
        add_submenu_page('lnp_settings', 'Lightning Paywall Balances', 'Paywall', 'manage_options', 'lnp_paywall', array($this, 'renderer'));
    }

    public function renderer()
    {
?>
        <div class="wrap">
            <h1>Lightning Paywall Settings</h1>
        </div>
        <form method="post" action="options.php">
            <?php
            settings_fields($this->settings_path);
            do_settings_sections($this->settings_path);
            submit_button();
            ?>
        </form>
<?php
    }


    public function field_paywall_text()
    {
        printf(
            '<input type="text" name="lnp[paywall_text]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['paywall_text']),
            'Paywall text (use %s for the amount)'
        );
    }

    public function field_paywall_button_text()
    {
        printf(
            '<input type="text" name="lnp[button_text]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['button_text']),
            'Button text'
        );
    }
    public function field_paywall_amount()
    {
        printf(
            '<input type="number" name="lnp[amount]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['amount']),
            'Amount in sats per article'
        );
    }
    public function field_paywall_total()
    {
        printf(
            '<input type="number" name="lnp[total]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['total']),
            'Total amount to collect. After that amount the article will be free'
        );
    }
    public function field_paywall_timeout()
    {
        printf(
            '<input type="number" name="lnp[timeout]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['timeout']),
            'Make the article free X days after it is published'
        );
    }
    public function field_paywall_timein()
    {
        printf(
            '<input type="number" name="lnp[timein]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['timein']),
            'Enable the paywall x days after the article is published'
        );
    }
    public function field_paywall_all_amount()
    {
        printf(
            '<input type="number" name="lnp[all_amount]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['all_amount']),
            'Amount for all articles'
        );
    }
    public function field_paywall_all_days()
    {
        printf(
            '<input type="number" name="lnp[all_days]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['all_days']),
            'How many days should all articles be available'
        );
    }
    public function field_paywall_all_confirmation()
    {
        printf(
            '<input type="text" name="lnp[all_confirmation]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['all_confirmation']),
            'Confirmation text for all article payments'
        );
    }
    public function field_paywall_lnurl_rss()
    {
        printf(
            '<input type="checkbox" name="lnp[lnurl_rss]" value="1" %s/><br><label>%s</label>',
            empty($this->options['lnurl_rss']) ? '' : 'checked',
            'Add lightning payment details to RSS items'
        );
    }
    public function field_paywall_disable_in_rss()
    {
        printf(
            '<input type="checkbox" name="lnp[disable_paywall_in_rss]" value="1" %s/><br><label>%s</label>',
            empty($this->options['disable_paywall_in_rss']) ? '' : 'checked',
            'Disable paywall in RSS items / show full content in RSS.'
        );
    }
}
