<?php

require_once 'SettingsPage.php';

class ConnectionPage implements SettingsPage
{
    private $settings_path = 'lnp_settings_connection';

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->options = get_option('lnp');
    }

    public function initFields()
    {
        register_setting('lnp', $this->settings_path);
        add_settings_section('lnd', 'LND Config', null, $this->settings_path);
        add_settings_field('lnd_address', 'Address', array($this, 'field_lnd_address'), $this->settings_path, 'lnd');
        add_settings_field('lnd_macaroon', 'Macaroon', array($this, 'field_lnd_macaroon'), $this->settings_path, 'lnd');
        add_settings_field('lnd_cert', 'TLS Cert', array($this, 'field_lnd_cert'), $this->settings_path, 'lnd');

        add_settings_section('lnbits', 'LNbits Config', null, $this->settings_path);
        add_settings_field('lnbits_apikey', 'API Key', array($this, 'field_lnbits_apikey'), $this->settings_path, 'lnbits');

        add_settings_section('lnaddress', 'Lightning Address Config', null, $this->settings_path);
        add_settings_field('lnaddress_address', 'Lightning Address', array($this, 'field_lnaddress_address'), $this->settings_path, 'lnaddress');


        add_settings_section('lndhub', 'LNDHub Config', null, $this->settings_path);
        add_settings_field('lndhub_url', 'Lndhub url', array($this, 'field_lndhub_url'), $this->settings_path, 'lndhub');
        add_settings_field('lndhub_login', 'Lndhub Login', array($this, 'field_lndhub_login'), $this->settings_path, 'lndhub');
        add_settings_field('lndhub_password', 'Lndhub Password', array($this, 'field_lndhub_password'), $this->settings_path, 'lndhub');
    }

    public function initPage()
    {
        add_submenu_page('lnp_settings', 'Lighting Paywall Settings', 'Connection', 'manage_options', 'lnp_settings', array($this, 'renderer'));
    }

    public function renderer()
    {
?>
        <div class="wrap">
            <h1>Lightning Connection Settings</h1>
            <div class="node-info">
                <?php
                try {
                    if ($this->plugin->getLightningClient()->isConnectionValid()) {
                        $node_info = $this->plugin->getLightningClient()->getInfo();
                        echo "Connected to: " . $node_info['alias'] . ' - ' . $node_info['identity_pubkey'];
                    } else {
                        echo 'Not connected';
                    }
                } catch (Exception $e) {
                    echo "Failed to connect: " . $e;
                }
                ?>
            </div>
            <form method="post" action="options.php">
                <script>
                    window.addEventListener("DOMContentLoaded", function() {
                                document.getElementById('load_from_lndconnect').addEventListener('click', function(e) {
                                        e.preventDefault();
                                        var lndConnectUrl = prompt('Please enter your lndconnect string (e.g. run: lndconnect --url --port=8080)');
                                        if (!lndConnectUrl) {
                                            return ' }
                                            var url = new URL(lndConnectUrl);
                                            document.getElementById('lnp_lnd_address').value = 'https:' + url.pathname;
                                            document.getElementById('lnp_lnd_macaroon').value = url.searchParams.get('macaroon');
                                            document.getElementById('lnp_lnd_cert').value = url.searchParams.get('cert');
                                        });
                                });
                </script>
                <?php
                settings_fields($this->settings_path);
                do_settings_sections($this->settings_path);
                submit_button();
                ?>
            </form>
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

    public function field_lnd_address()
    {
        $help = 'e.g. https://127.0.0.1:8080 - or <a href="#" id="load_from_lndconnect">click here to load details from a lndconnect</a>';
        printf(
            '<input type="text" name="lnp[lnd_address]" id="lnp_lnd_address" value="%s" autocomplete="off" /><br>%s',
            esc_attr($this->options['lnd_address']),
            $help
        );
    }
    public function field_lnd_macaroon()
    {
        printf(
            '<input type="text" name="lnp[lnd_macaroon]" id="lnp_lnd_macaroon" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['lnd_macaroon']),
            'Invoices macaroon in HEX format'
        );
    }
    public function field_lnd_cert()
    {
        printf(
            '<input type="text" name="lnp[lnd_cert]" value="%s" id="lnp_lnd_cert" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['lnd_cert']),
            'TLS Certificate'
        );
    }
    public function field_lnbits_apikey()
    {
        printf(
            '<input type="text" name="lnp[lnbits_apikey]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['lnbits_apikey']),
            'LNbits Invoice/read key'
        );
    }
    public function field_lnaddress_address()
    {
        printf(
            '<input type="text" name="lnp[lnaddress_address]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['lnaddress_address']),
            'Lightning Address (e.g. you@payaddress.co) - only works if the vistor supports WebLN!'
        );
    }
    public function field_lndhub_url()
    {
        printf(
            '<input type="text" name="lnp[lndhub_url]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['lndhub_url']),
            'Lndhub Url'
        );
    }
    public function field_lndhub_login()
    {
        printf(
            '<input type="text" name="lnp[lndhub_login]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['lndhub_login']),
            'Lndhub Login'
        );
    }
    public function field_lndhub_password()
    {
        printf(
            '<input type="password" name="lnp[lndhub_password]" value="%s" autocomplete="off" /><br><label>%s</label>',
            esc_attr($this->options['lndhub_password']),
            'Lndhub Password'
        );
    }
}
