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

        $this->lnd_section();

        $this->lndbits_section();

        $this->btcpay_section();

        $this->lnaddress_section();

        $this->lndhub_section();
    }

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


        if (!$this->plugin->getLightningClient() || !$this->plugin->getLightningClient()->isConnectionValid()) {

            $this->add_custom_field([
                'key' => 'ndhub_generate',
                'name' => 'Don\'t have a wallet ?',
                'section' => $section
            ], array($this, 'field_lndhub_generate'));
        }
    }


    public function field_lndhub_generate()
    {
        printf(
            '<button id="lndhub_create_account" class="button button-primary" type="button">%s</button>',
            'Generate Wallet'
        );
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
}
