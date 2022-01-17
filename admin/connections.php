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

        $this->navigation();
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

    public function field_connection_types()
    {
        echo '<div class="wp-lnp-card__container">';
        $this->connection_card('lnd', $this->plugin->get_file_url('img/lnd.png'), 'Lnd', 'Connect using lndhub');
        $this->connection_card('lndhub', $this->plugin->get_file_url('img/lndhub.png'), 'LndHub (BlueWallet)', 'Connect using lndhub');
        $this->connection_card('lnbits', $this->plugin->get_file_url('img/lnbits.png'), 'LNbits', 'Connect to your LNbits account');
        $this->connection_card('lnaddress', $this->plugin->get_file_url('img/satsymbol-black.png'), 'Lightning Address Config', 'Connect using Lightning Address Config');
        $this->connection_card('btcpay', $this->plugin->get_file_url('img/BTCPay_Icon_with_background.png'), 'BTC Pay', 'Connect using BTCPay Server');
        $this->connection_card('lndhub_create_account', $this->plugin->get_file_url('img/alby.png'), 'Create a new wallet', 'We create and manage a lightning wallet for you');
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

    function do_settings_sections($page)
    {
        global $wp_settings_sections, $wp_settings_fields;

        if (!isset($wp_settings_sections[$page])) {
            return;
        }


        foreach ((array) $wp_settings_sections[$page] as $section) {
            if ($section['id'] === 'connection-types') continue;
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

        if ($section['id'] === 'connection-types') {
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
                    let generating_lndhub_account = false;

                    function lndhubCreateAccount(card) {

                        if (generating_lndhub_account) return;

                        generating_lndhub_account = true;

                        const image = card.getElementsByTagName('img')[0];
                        image.classList.add('wp-lnp-card__image--grayscale');
                        const title = card.getElementsByTagName('h4')[0];
                        const subtitle = card.getElementsByTagName('p')[0];
                        title.innerHTML = "Generating.....";
                        subtitle.innerHTML = "We are generating an account for you";
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
                                title.innerHTML = "Generated";
                                subtitle.innerHTML = "Your account has been generated";
                                document.getElementById("submit").click();
                                image.classList.remove('wp-lnp-card--disabled');
                                image.classList.remove('wp-lnp-card__image--grayscale');
                            }).catch((e) => console.error(e))
                    }

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

                        const list = document.querySelectorAll('.wp-lnp-card');
                        const sections = document.querySelectorAll('.wp-lnp-section__hidden');
                        const fieldsContainer = document.getElementById('wp-lnp-fields');
                        const sectionsContent = {};
                        sections.forEach(section => {
                            sectionsContent[section.id] = section.innerHTML;
                            section.remove();
                        });
                        list.forEach(el => el.addEventListener('click', e => {
                            e.preventDefault();
                            const current = el.attributes['data-section'].value;
                            if (current === 'lndhub_create_account') {
                                fieldsContainer.innerHTML = sectionsContent['lndhub'];
                                lndhubCreateAccount(el);
                                return;
                            }
                            fieldsContainer.innerHTML = sectionsContent[current];
                        }));


                    });
                </script>
                <?php
                settings_fields($this->settings_path);

                echo '<div id="wp-lnp-fields">';

                $this->render_current_section($this->settings_path);

                echo '</div>';
                submit_button();
                ?>
            </form>
            <?php
            $this->do_settings_sections($this->settings_path);
            $this->do_settings_fields($this->settings_path, 'connection-types');
            ?>
        </div>
<?php
    }
}
