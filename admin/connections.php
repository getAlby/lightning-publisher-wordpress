<?php

require_once 'SettingsPage.php';

class ConnectionPage extends SettingsPage
{
    protected $settings_path = 'lnp_settings_connections';
    protected $option_name = 'lnp_connection';

    protected $page_title = 'Wallet Settings';
    protected $menu_title = 'Wallet settings';

    public function init_fields()
    {
        parent::init_fields();
        add_settings_section('lnd', 'LND Config', null, $this->settings_path);
        add_settings_field('lnd_address', 'Address', array($this, 'field_lnd_address'), $this->settings_path, 'lnd');
        add_settings_field('lnd_macaroon', 'Macaroon', array($this, 'field_lnd_macaroon'), $this->settings_path, 'lnd');
        add_settings_field('lnd_cert', 'TLS Cert', array($this, 'field_lnd_cert'), $this->settings_path, 'lnd');

        add_settings_section('lnbits', 'LNbits Config', null, $this->settings_path);
        add_settings_field('lnbits_apikey', 'API Key', array($this, 'field_lnbits_apikey'), $this->settings_path, 'lnbits');
        add_settings_field('lnbits_host', 'Host', array($this, 'field_lnbits_host'), $this->settings_path, 'lnbits');

        add_settings_section('lnaddress', 'Lightning Address Config', null, $this->settings_path);
        add_settings_field('lnaddress_address', 'Lightning Address', array($this, 'field_lnaddress_address'), $this->settings_path, 'lnaddress');


        add_settings_section('lndhub', 'LndHub Config', null, $this->settings_path);
        add_settings_field('lndhub_url', 'LndHub Url', array($this, 'field_lndhub_url'), $this->settings_path, 'lndhub');
        add_settings_field('lndhub_login', 'LndHub Login', array($this, 'field_lndhub_login'), $this->settings_path, 'lndhub');
        add_settings_field('lndhub_password', 'LndHub Password', array($this, 'field_lndhub_password'), $this->settings_path, 'lndhub');
        if (!$this->plugin->getLightningClient() || !$this->plugin->getLightningClient()->isConnectionValid()) {
            add_settings_field('lndhub_generate', "Don't have a wallet ?", array($this, 'field_lndhub_generate'), $this->settings_path, 'lndhub');
        }
    }

    public function renderer()
    {

?>
        <div class="wrap">
            <h1>Lightning Wallet Settings</h1>
            <div class="node-info">
                <?php
                try {
                    if ($this->plugin->getLightningClient() && $this->plugin->getLightningClient()->isConnectionValid()) {
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
            <form id="wallet_settings_form" method="post" action="options.php">
                <script>
                    window.addEventListener("DOMContentLoaded", function() {
                        document.getElementById('load_from_lndconnect').addEventListener('click', function(e) {
                            e.preventDefault();
                            var lndConnectUrl = prompt('Please enter your lndconnect string (e.g. run: lndconnect --url --port=8080)');
                            if (!lndConnectUrl) {
                                return;
                            }
                            var url = new URL(lndConnectUrl);
                            document.getElementById('lnp_lnd_address').value = 'https:' + url.pathname;
                            document.getElementById('lnp_lnd_macaroon').value = url.searchParams.get('macaroon');
                            document.getElementById('lnp_lnd_cert').value = url.searchParams.get('cert');
                        });

                        if (!document.getElementById('lndhub_create_account')) return;
                        
                        document.getElementById('lndhub_create_account').addEventListener('click', function(e) {
                            e.preventDefault();
                            const button = e.target;
                            button.innerHTML = "Generating.....";
                            button.disabled = true;
                            const data = new FormData();

                            data.append('action', 'create_lnp_hub_account');

                            fetch("<?php echo get_site_url(); ?>/wp-admin/admin-ajax.php", {
                                    method: "POST",
                                    credentials: 'same-origin',
                                    body: data
                                })
                                .then((response) => response.json())
                                .then((response) => {
                                    console.log(response);
                                    document.getElementById('lndhub_url').value = response.url;
                                    document.getElementById('lndhub_login').value = response.login;
                                    document.getElementById('lndhub_password').value = response.password;
                                    button.innerHTML = "Generated";
                                    document.getElementById("submit").click();
                                }).catch((e) => console.error(e))
                        });
                    });
                </script>
                <?php
                settings_fields($this->settings_path);
                do_settings_sections($this->settings_path);
                submit_button();
                ?>
            </form>
        </div>
<?php
    }

    public function field_lnd_address()
    {
        $help = 'e.g. https://127.0.0.1:8080 - or <a href="#" id="load_from_lndconnect">click here to load details from a lndconnect</a>';
        printf(
            '<input type="text" name="%s" id="lnp_lnd_address" value="%s" autocomplete="off" /><br>%s',
            $this->get_field_name('lnd_address'),
            $this->get_field_value('lnd_address'),
            $help
        );
    }
    public function field_lnd_macaroon()
    {
        printf(
            '<input type="text" name="%s" id="lnp_lnd_macaroon" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('lnd_macaroon'),
            $this->get_field_value('lnd_macaroon'),
            'Invoices macaroon in HEX format'
        );
    }
    public function field_lnd_cert()
    {
        printf(
            '<input type="text" name="%s" value="%s" id="lnp_lnd_cert" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('lnd_macaroon'),
            $this->get_field_value('lnd_cert'),
            'TLS Certificate'
        );
    }
    public function field_lnbits_apikey()
    {
        printf(
            '<input type="text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('lnbits_apikey'),
            $this->get_field_value('lnbits_apikey'),
            'LNbits Invoice/read key'
        );
    }
    public function field_lnbits_host()
    {
        printf(
            '<input type="text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('lnbits_host'),
            $this->get_field_value('lnbits_host'),
            'LNbits host (e.g. https://legend.lnbits.com)'
        );
    }
    public function field_lnaddress_address()
    {
        printf(
            '<input type="text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('lnaddress_address'),
            $this->get_field_value('lnaddress_address'),
            'Lightning Address (e.g. you@payaddress.co) - only works if the vistor supports WebLN!'
        );
    }
    public function field_lndhub_url()
    {
        printf(
            '<input id="lndhub_url" type="text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('lndhub_url'),
            $this->get_field_value('lndhub_url'),
            'LndHub Host'
        );
    }
    public function field_lndhub_login()
    {
        printf(
            '<input id="lndhub_login" type="text" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('lndhub_login'),
            $this->get_field_value('lndhub_login'),
            'LndHub Login'
        );
    }
    public function field_lndhub_password()
    {
        printf(
            '<input id="lndhub_password" type="password" name="%s" value="%s" autocomplete="off" /><br><label>%s</label>',
            $this->get_field_name('lndhub_password'),
            $this->get_field_value('lndhub_password'),
            'LndHub Password'
        );
    }
    public function field_lndhub_generate()
    {
        printf(
            '<button id="lndhub_create_account" class="button button-primary" type="button">%s</button>',
            'Generate Wallet'
        );
    }
}
