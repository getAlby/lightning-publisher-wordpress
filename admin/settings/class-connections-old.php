<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class LNP_ConnectionPageOld extends LNP_SettingsPage
{
    protected $settings_path = 'lnp_settings_connections';
    protected $template_html = 'settings/page-connections-old.php';
    protected $option_name   = 'lnp_connection';

    public function init_fields()
    {
        parent::init_fields();

        $this->lnd_section();

        $this->lndbits_section();

        $this->btcpay_section();

        $this->lnaddress_section();

        $this->lndhub_section();

        $this->lndhub_create_account_section();

        $this->navigation();
    }

    protected function input_field($name, $label, $type = 'text', $autocomplete = false)
    {

        printf(
            '<input id="%s" type="%s" autocomplete="%s" name="%s" value="%s" />',
            $name,
            $type,
            $autocomplete ? 'on' : 'off',
            $this->get_field_name($name),
            $this->get_field_value($name)
        );
        if ($type !== 'hidden') {
            printf(
                '<br><label>%s</label>',
                $label
            );
        }
    }

    public function create_input_field($args = [])
    {
        $this->input_field($args['key'], $args['label'], $args['type'] ?? 'text', $args['autocomplete'] ?? false);
    }

    protected function add_section($data = [])
    {
        add_settings_section($data['key'], $data['title'], null, $this->settings_path);
    }

    protected function add_input_field($data = [])
    {
        add_settings_field($data['key'], $data['name'], array($this, 'create_input_field'), $this->settings_path, $data['section'], $data);
    }

    protected function add_custom_field($data = [], $callback, $args = [])
    {
        add_settings_field($data['key'], $data['name'], $callback, $this->settings_path, $data['section'], $args);
    }

    protected function add_custom_input_field($data = [], $callback)
    {
        add_settings_field($data['key'], $data['name'], $callback, $this->settings_path, $data['section'], $data);
    }

    /**
     * Make menu item/page title translatable
     */
    protected function set_translations()
    {
        // Menu Item label
        $this->page_title = __( 'Wallet Settings', 'lnp-alby' );
        $this->menu_title = __( 'Wallet Settings', 'lnp-alby' );
    }


    public function navigation()
    {
        $section = 'connection-types';
        $this->add_section([
            'key' => $section,
            'title' => ''
        ]);

        $this->add_custom_field([
            'key' => 'connection_type_lnd',
            'name' => null,
            'section' => $section
        ], array($this, 'field_connection_types'));
    }

    // Done
    public function lnd_section()
    {
        $section = 'lnd';

        $this->add_section([
            'key' => $section,
            'title' => 'LND Config'
        ]);

        $this->add_input_field([
            'key' => 'lnd_address',
            'name' => 'Address',
            'label' => 'e.g. https://127.0.0.1:8080 - or <a href="#" id="load_from_lndconnect">click here to load details from a lndconnect</a>',
            'section' => $section,
            'type' => 'url'
        ]);

        $this->add_input_field([
            'key' => 'lnd_macaroon',
            'name' => 'Macaroon',
            'label' => 'Invoices macaroon in HEX format',
            'section' => $section
        ]);

        $this->add_input_field([
            'key' => 'lnd_cert',
            'name' => 'TLS Cert',
            'label' => 'TLS Certificate',
            'section' => $section
        ]);
    }

    public function lndbits_section()
    {
        $section = 'lnbits';

        $this->add_section([
            'key' => $section,
            'title' => 'LNbits Config'
        ]);


        $this->add_input_field([
            'key' => 'lnbits_apikey',
            'name' => 'API Key',
            'label' => 'LNbits Invoice/read key',
            'section' => $section,
        ]);

        $this->add_input_field([
            'key' => 'lnbits_host',
            'name' => 'Host',
            'label' => 'LNbits host (e.g. https://legend.lnbits.com)',
            'section' => $section,
            'type' => 'url'
        ]);
    }

    public function btcpay_section()
    {
        $section = 'btcpay';

        $this->add_section([
            'key' => $section,
            'title' => 'BTC Payer'
        ]);

        $this->add_input_field([
            'key' => 'btcpay_host',
            'name' => 'Host',
            'label' => 'BtcPay Host',
            'section' =>  $section,
            'type' => 'url'
        ]);

        $this->add_input_field([
            'key' => 'btcpay_apikey',
            'name' => 'ApiKey',
            'label' => 'BtcPay Api Key',
            'section' => $section
        ]);

        $this->add_input_field([
            'key' => 'btcpay_store_id',
            'name' => 'Store Id',
            'label' => 'BtcPay Store Id',
            'section' => $section
        ]);
    }

    public function lnaddress_section()
    {
        $section = 'lnaddress';

        $this->add_section([
            'key' => $section,
            'title' => 'Lightning Address Config'
        ]);

        $this->add_input_field([
            'key' => 'lnaddress_address',
            'name' => 'Lightning Address',
            'label' => 'Lightning Address (e.g. you@payaddress.co) - only works if the vistor supports WebLN!',
            'section' => $section,
        ]);
    }

    public function lndhub_section()
    {
        $section = 'lndhub';

        $this->add_section([
            'key' => $section,
            'title' => 'LndHub Config'
        ]);

        $this->add_input_field([
            'key' => 'lndhub_url',
            'name' => 'LndHub Url',
            'label' => 'LndHub Host',
            'section' => $section,
            'type' => 'url'
        ]);

        $this->add_input_field([
            'key' => 'lndhub_login',
            'name' => 'LndHub Login',
            'label' => '',
            'section' => $section
        ]);

        $this->add_input_field([
            'key' => 'lndhub_password',
            'name' => 'LndHub Password',
            'label' => '',
            'section' => $section,
            'type' => 'password'
        ]);
    }

    public function lndhub_create_account_section()
    {
        $section = 'lndhub_create_account';

        $this->add_section([
            'key' => $section,
            'title' => 'LndHub Config'
        ]);

        $this->add_input_field([
            'key' => 'lndhub_url',
            'name' => 'LndHub Url',
            'label' => 'LndHub Host',
            'section' => $section,
            'type' => 'hidden',
        ]);

        $this->add_input_field([
            'key' => 'lndhub_login',
            'name' =>   'LndHub Login',
            'type' => 'hidden',
            'label' => '',
            'section' => $section
        ]);

        $this->add_input_field([
            'key' => 'lndhub_password',
            'name' => 'LndHub Password',
            'label' => '',
            'section' => $section,
            'type' => 'hidden',
        ]);

        $this->add_custom_input_field([
            'key' => 'lndhub_create_button',
            'name' => 'LndHub Create Wallet',
            'label' => 'Create Wallet',
            'section' => $section,
        ], array($this, 'field_create_account_section'));
    }

    public function field_create_account_section($args)
    {
        printf(
            '<div>
                <button id="%s" type="button" class="button button-danger">Create wallet</button>
             </div>',
            'create_lndhub_account',
            $args['label']
        );
    }

    public function field_connection_types()
    {
        echo '<div class="wp-lnp-card__container">';
        
        $this->connection_card('lnd', $this->plugin->get_file_url('assets/img/lnd.png'), 'Lnd', 'Connect using lndhub');

        $this->connection_card('lndhub', $this->plugin->get_file_url('assets/img/lndhub.png'), 'LndHub (BlueWallet)', 'Connect using lndhub');

        $this->connection_card('lnbits', $this->plugin->get_file_url('assets/img/lnbits.png'), 'LNbits', 'Connect to your LNbits account');

        $this->connection_card('lnaddress', $this->plugin->get_file_url('assets/img/satsymbol-black.png'), 'Lightning Address Config', 'Connect using Lightning Address Config');

        $this->connection_card('btcpay', $this->plugin->get_file_url('assets/img/BTCPay_Icon_with_background.png'), 'BTC Pay', 'Connect using BTCPay Server');

        $this->connection_card('lndhub_create_account', $this->plugin->get_file_url('assets/img/alby.png'), 'Create a new wallet', 'We create and manage a lightning wallet for you');

        echo '</div>';
    }


    public function connection_card($id, $image, $title, $subtitle)
    {
        printf(
            '<div data-section="%s" class="wp-lnp-card">
                <div class="wp-lnp-card__header">
                    <img src="%s" class="wp-lnp-card__image" />
                </div>
                <div class="wp-lnp-card__body">
                    <h4 class="wp-lnp-card__title">%s</h4>
                    <p class="wp-lnp-card__text">%s</p>
                </div>
            </div>',
            $id,
            $image,
            $title,
            $subtitle
        );
    }

    public function render_current_section($page)
    {
        global $wp_settings_sections;

        if (!isset($wp_settings_sections[$page])) {
            return;
        }

        $type = $this->plugin->lightningClientType;
        $i = 0;
        foreach ((array) $wp_settings_sections[$page] as $section) {

            if (!isset($type) && $i !== 0) continue;

            if (isset($type) && $type !== $section['id']) continue;

            $this->render_section($page, $section, false);
            break;
        }
    }

    public function do_settings_sections($page)
    {
        global $wp_settings_sections, $wp_settings_fields;

        if (!isset($wp_settings_sections[$page])) {
            return;
        }


        foreach ((array) $wp_settings_sections[$page] as $section)
        {
            // Tabs with icons
            if ($section['id'] === 'connection-types')
                continue;

            // Render fields
            $this->render_section($page, $section, true);
        }
    }

    public function render_section($page, $section, $hide)
    {
        global $wp_settings_fields;
        $class = $hide ? 'wp-lnp-section__hidden' : '';

        echo "<div id='{$section['id']}' class='wp-lnp-section {$class}'>";

        if ($section['title']) {
            echo "<h2>{$section['title']}</h2>\n";
        }

        if ($section['callback']) {
            call_user_func($section['callback'], $section);
        }

        if (!isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section['id']])) {
            return;
        }

        if ($section['id'] === 'connection-types' || $section['id'] === 'lndhub_create_account') {
            echo '<div>';
            $this->do_settings_fields($page, $section['id']);
            echo '</div>';
        } else {
            echo '<table class="form-table" role="presentation">';
            do_settings_fields($page, $section['id']);
            echo '</table>';
        }
        echo '</div>';
    }

    public function do_settings_fields($page, $section)
    {
        global $wp_settings_fields;

        if (!isset($wp_settings_fields[$page][$section])) {
            return;
        }
        foreach ((array) $wp_settings_fields[$page][$section] as $field) {
            call_user_func($field['callback'], $field['args']);
        }
    }
}
