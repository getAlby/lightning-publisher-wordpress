<?php

require_once 'SettingsPage.php';

class PaywallPage extends SettingsPage
{
    protected $settings_path = 'lnp_settings_paywall';
    protected $option_name = 'lnp_paywall';

    protected $page_title = 'Paywall Settings';
    protected $menu_title = 'Paywall settings';

    public function init_fields()
    {
        parent::init_fields();
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
        add_settings_field('paywall_lnurl_meta_tag', 'Add LNURL Meta tag', array($this, 'field_paywall_lnurl_meta_tag'), $this->settings_path, 'paywall');
        add_settings_field('paywall_disable_in_rss', 'Disable paywall in RSS?', array($this, 'field_paywall_disable_in_rss'), $this->settings_path, 'paywall');
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
            '<input type="text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('paywall_text'),
            $this->get_field_value('paywall_text'),
            'Paywall text (use %s for the amount)'
        );
    }

    public function field_paywall_button_text()
    {
        printf(
            '<input type="text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('button_text'),
            $this->get_field_value('button_text'),
            'Button text'
        );
    }
    public function field_paywall_amount()
    {
        printf(
            '<input type="number" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('amount'),
            $this->get_field_value('amount'),
            'Amount in sats per article'
        );
    }
    public function field_paywall_total()
    {
        printf(
            '<input type="number" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('total'),
            $this->get_field_value('total'),
            'Total amount to collect. After that amount the article will be free'
        );
    }
    public function field_paywall_timeout()
    {
        printf(
            '<input type="number" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('timeout'),
            $this->get_field_value('timeout'),
            'Make the article free X days after it is published'
        );
    }
    public function field_paywall_timein()
    {
        printf(
            '<input type="number" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('timein'),
            $this->get_field_value('timein'),
            'Enable the paywall x days after the article is published'
        );
    }
    public function field_paywall_all_amount()
    {
        printf(
            '<input type="number" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('all_amount'),
            $this->get_field_value('all_amount'),
            'Amount for all articles'
        );
    }
    public function field_paywall_all_days()
    {
        printf(
            '<input type="number" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('all_days'),
            $this->get_field_value('all_days'),
            'How many days should all articles be available'
        );
    }
    public function field_paywall_all_confirmation()
    {
        printf(
            '<input type="text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('all_confirmation'),
            $this->get_field_value('all_confirmation'),
            'Confirmation text for all article payments'
        );
    }
    public function field_paywall_lnurl_meta_tag()
    {
        printf(
            '<input type="checkbox" name="%s" value="1" %s/><br><label>%s</label>',
            $this->get_field_name('lnurl_meta_tag'),
            empty($this->get_field_value('lnurl_meta_tag')) ? '' : 'checked',
            'Add lightning meta tag'
        );
    }
    public function field_paywall_lnurl_rss()
    {
        printf(
            '<input type="checkbox" name="%s" value="1" %s/><br><label>%s</label>',
            $this->get_field_name('lnurl_rss'),
            empty($this->get_field_value('lnurl_rss')) ? '' : 'checked',
            'Add lightning payment details to RSS items'
        );
    }
    public function field_paywall_disable_in_rss()
    {
        printf(
            '<input type="checkbox" name="%s" value="1" %s/><br><label>%s</label>',
            $this->get_field_name('disable_paywall_in_rss'),
            empty($this->get_field_value('disable_paywall_in_rss')) ? '' : 'checked',
            'Disable paywall in RSS items / show full content in RSS.'
        );
    }
}
