<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class PaywallPage extends SettingsPage
{
    protected $settings_path = 'lnp_settings_paywall';
    protected $option_name   = 'lnp_paywall';

    protected $page_title;
    protected $menu_title;

    public function init_fields()
    {
        parent::init_fields();

        // Tabbed sections
        $this->sections = array(
            'pricing'      => __( 'Pricing', 'wp-lightning-paywall' ),
            'restrictions' => __( 'Restrictions', 'wp-lightning-paywall' ),
            'integrations' => __( 'Integrations', 'wp-lightning-paywall' ),
        );

        // Register Tabbed sections
        foreach( $this->sections as $id => $label )
        {
            add_settings_section(
                'paywall_' . $id,
                $label,
                null,
                'wpln_paywall_' . $id
            );
        }

        /**
         * Section: Pricing
         * 
         * page: wpln_paywall_pricing
         * section: paywall_pricing
         */
        add_settings_field(
            'paywall_text',
            __( 'Text', 'wp-lightning-paywall' ),
            array($this, 'field_paywall_text'),
            'wpln_paywall_pricing',
            'paywall_pricing'
        );

        add_settings_field(
            'paywall_button_text',
            __( 'Button Label', 'wp-lightning-paywall' ),
            array($this, 'field_paywall_button_text'),
            'wpln_paywall_pricing',
            'paywall_pricing'
        );

        add_settings_field(
            'paywall_amount',
            __( 'Amount', 'wp-lightning-paywall' ),
            array($this, 'field_paywall_amount'),
            'wpln_paywall_pricing',
            'paywall_pricing'
        );

        add_settings_field(
            'paywall_total',
            __( 'Total', 'wp-lightning-paywall' ),
            array($this, 'field_paywall_total'),
            'wpln_paywall_pricing',
            'paywall_pricing'
        );

        
        /**
         * Section: Restrictions
         * 
         * page: wpln_paywall_restrictions
         * section: paywall_restrictions
         */
        add_settings_field(
            'paywall_timeout',
            __( 'Timeout', 'wp-lightning-paywall' ),
            array($this, 'field_paywall_timeout'),
            'wpln_paywall_restrictions',
            'paywall_restrictions'
        );


        add_settings_field(
            'paywall_timein',
            __( 'Timein', 'wp-lightning-paywall' ),
            array($this, 'field_paywall_timein'),
            'wpln_paywall_restrictions',
            'paywall_restrictions'
        );
        
        add_settings_field(
            'paywall_all_period',
            'Days available',
            array($this, 'field_paywall_all_days'),
            $this->settings_path, 'paywall'
        );
        add_settings_field('paywall_all_confirmation', 'Confirmation text', array($this, 'field_paywall_all_confirmation'), $this->settings_path, 'paywall');


        /**
         * Section: Integrations
         * 
         * page: wpln_paywall_integrations
         * section: paywall_integrations
         */
        add_settings_field(
            'paywall_lnurl_rss',
            'Add LNURL to RSS items',
            array($this, 'field_paywall_lnurl_rss'),
            'wpln_paywall_integrations',
            'paywall_integrations'
        );

        add_settings_field(
            'paywall_disable_in_rss',
            'Disable paywall in RSS?',
            array($this, 'field_paywall_disable_in_rss'),
            'wpln_paywall_integrations',
            'paywall_integrations'
        );
    }

    /**
     * Make menu item/page title translatable
     */
    protected function set_translations() {
        $this->page_title = __( 'Paywall Settings', 'wp-lightning-paywall' );
        $this->menu_title = __( 'Paywall Settings', 'wp-lightning-paywall' );
    }

    public function renderer()
    {
        include $this->get_template_path('settings/page-paywall.php');
    }


    public function field_paywall_text()
    {
        printf(
            '<input type="text" class="regular-text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('paywall_text'),
            $this->get_field_value('paywall_text'),
            'Paywall text (use %s for the amount)'
        );
    }

    public function field_paywall_button_text()
    {
        printf(
            '<input type="text"class="regular-text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('button_text'),
            $this->get_field_value('button_text'),
            'Button text'
        );
    }
    public function field_paywall_amount()
    {
        printf(
            '<input type="number" class="regular-text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('amount'),
            $this->get_field_value('amount'),
            'Amount in sats per article'
        );
    }
    public function field_paywall_total()
    {
        printf(
            '<input type="number" class="regular-text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('total'),
            $this->get_field_value('total'),
            'Total amount to collect. After that amount the article will be free'
        );
    }
    public function field_paywall_timeout()
    {
        printf(
            '<input type="number" class="regular-text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('timeout'),
            $this->get_field_value('timeout'),
            'Make the article free X days after it is published'
        );
    }
    public function field_paywall_timein()
    {
        printf(
            '<input type="number" class="regular-text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('timein'),
            $this->get_field_value('timein'),
            'Enable the paywall x days after the article is published'
        );
    }
    public function field_paywall_all_amount()
    {
        printf(
            '<input type="number" class="regular-text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('all_amount'),
            $this->get_field_value('all_amount'),
            'Amount for all articles'
        );
    }
    public function field_paywall_all_days()
    {
        printf(
            '<input type="number" class="regular-text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('all_days'),
            $this->get_field_value('all_days'),
            'How many days should all articles be available'
        );
    }
    public function field_paywall_all_confirmation()
    {
        printf(
            '<input type="text" class="regular-text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('all_confirmation'),
            $this->get_field_value('all_confirmation'),
            'Confirmation text for all article payments'
        );
    }
    public function field_paywall_lnurl_rss()
    {
        printf(
            '<label><input type="checkbox" name="%s" value="1" %s/> %s</label>',
            $this->get_field_name('lnurl_rss'),
            empty($this->get_field_value('lnurl_rss')) ? '' : 'checked',
            'Add lightning payment details to RSS items'
        );
    }
    public function field_paywall_disable_in_rss()
    {
        printf(
            '<label><input type="checkbox" name="%s" value="1" %s/> %s</label>',
            $this->get_field_name('disable_paywall_in_rss'),
            empty($this->get_field_value('disable_paywall_in_rss')) ? '' : 'checked',
            'Disable paywall in RSS items / show full content in RSS.'
        );
    }
}
