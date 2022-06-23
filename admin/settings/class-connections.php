<?php

// If this file is called directly, abort.
defined('WPINC') || die;

class LNP_ConnectionPage extends LNP_SettingsPage
{
    protected $settings_path = 'lnp_settings_connections';
    protected $template_html = 'settings/page-connections.php';
    protected $option_name   = 'lnp_connection';

    /**
     * Make menu item/page title translatable
     */
    protected function set_translations()
    {
        // Menu Item label
        $this->page_title = __( 'Wallet Settings', 'lnp-alby' );
        $this->menu_title = __( 'Wallet Settings', 'lnp-alby' );

        add_action('admin_notices', array($this, 'get_lnd_address_node_info'));
        add_action('lnp_tab_before_alby', array($this, 'render_tab_alby_wallet'));
    }


    /**
     * Register Tabs if any
     * @return [type] [description]
     */
    public function init_fields()
    {
        // Tabs
        $this->tabs = array(
            'lnd' => array(
                'title'       => __('LND', 'lnp-alby' ),
                'description' => __('Connect using LND', 'lnp-alby'),
            ),
            'lndhub' => array(
                'title'       => __('LNDHub', 'lnp-alby' ),
                'description' => __('Connect using LNDHub', 'lnp-alby'),
            ),
            'lnbits' => array(
                'title'       => __('LNbits', 'lnp-alby' ),
                'description' => __('Connect to your LNbits account', 'lnp-alby'),
            ),
            'btcpay' => array(
                'title'       => __('BTC Pay', 'lnp-alby' ),
                'description' => __('Connect using BTCPay Server', 'lnp-alby'),
            ),
            'lnaddress' => array(
                'title'       => __('LN Address', 'lnp-alby' ),
                'description' => __('Connect using Lightning Address Config', 'lnp-alby'),
            ),
            'alby' => array(
                'title'       => __('Alby Wallet', 'lnp-alby' ),
                'description' => __('We create and manage a lightning wallet for you', 'lnp-alby'),
            ),
        );

        parent::init_fields();
    }


    /**
     * Array of form fields available on this page
     */
    public function set_form_fields() {

        /**
         * Fields
         */
        $fields = array();

        /**
         * Fields for section: LND Hub
         */
        $fields[] = array(
            'tab'     => 'lnd',
            'field'   => array(
                'type'        => 'url',
                'name'        => 'lnd_address',
                'label'       => __( 'Address', 'lnp-alby' ),
                'description' => __( 'e.g. https://127.0.0.1:8080 - or <a href="#" id="load_from_lndconnect">click here to load details from a lndconnect</a>', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lnd',
            'field'   => array(
                'name'        => 'lnd_macaroon',
                'label'       => __( 'Macaroon', 'lnp-alby' ),
                'description' => __( 'Invoices macaroon in HEX format', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lnd',
            'field'   => array(
                'name'        => 'lnd_cert',
                'label'       => __( 'TLS Certificate', 'lnp-alby' ),
                'description' => __( 'TLS Certificate', 'lnp-alby'),
            ),
        );


        /**
         * Fields for section: LND Hub (Blue wallet)
         */
        $fields[] = array(
            'tab'     => 'lndhub',
            'field'   => array(
                'type'        => 'url',
                'name'        => 'lndhub_url',
                'label'       => __( 'LndHub Url', 'lnp-alby' ),
                'description' => __( 'LndHub Host', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lndhub',
            'field'   => array(
                'name'        => 'lndhub_login',
                'label'       => __( 'Login', 'lnp-alby' ),
                'description' => __( 'LndHub Login', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lndhub',
            'field'   => array(
                'type'        => 'password',
                'name'        => 'lndhub_password',
                'label'       => __( 'Password', 'lnp-alby' ),
                'description' => __( 'LndHub Password', 'lnp-alby'),
            ),
        );


        /**
         * LNBits
         */
        $fields[] = array(
            'tab'     => 'lnbits',
            'field'   => array(
                'name'        => 'lnbits_apikey',
                'label'       => __( 'API Key', 'lnp-alby' ),
                'description' => __( 'LNbits Invoice/read key', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lnbits',
            'field'   => array(
                'type'        => 'url',
                'name'        => 'lnbits_host',
                'label'       => __( 'Host', 'lnp-alby' ),
                'description' => __( 'LNbits host (e.g. https://legend.lnbits.com)', 'lnp-alby'),
            ),
        );


        /**
         * BTC Pay
         */
        $fields[] = array(
            'tab'     => 'btcpay',
            'field'   => array(
                'type'        => 'url',
                'name'        => 'btcpay_host',
                'label'       => __( 'Host', 'lnp-alby' ),
                'description' => __( 'BtcPay Host', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'btcpay',
            'field'   => array(
                'name'        => 'btcpay_apikey',
                'label'       => __( 'API Key', 'lnp-alby' ),
                'description' => __( 'BtcPay Api Key', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'btcpay',
            'field'   => array(
                'name'        => 'btcpay_store_id',
                'label'       => __( 'Store ID', 'lnp-alby' ),
                'description' => __( 'BtcPay Store ID', 'lnp-alby'),
            ),
        );


        /**
         * LN Address
         */
        $fields[] = array(
            'tab'     => 'lnaddress',
            'field'   => array(
                'type'        => 'email',
                'name'        => 'lnaddress_address',
                'label'       => __( 'Lightning Address', 'lnp-alby' ),
                'description' => __( 'Lightning Address (e.g. you@payaddress.co) - only works if the vistor supports WebLN!', 'lnp-alby'),
            ),
        );

        // Save Form fields to class
        $this->form_fields = $fields;
    }



    /**
     * Generate Alby
     */
    public function render_tab_alby_wallet() {

        printf(
            '<div>
                <table class="form-table" role="presentation"><tbody>
                    <tr>
                        <th scope="row">%s</th><td><input type="email" class="regular-text" id="alby_email" value="" placeholder="" autocomplete="off"></td>
                    </tr>
                    <tr>
                        <th scope="row">%s</th><td><input type="password" class="regular-text" id="alby_password" value="" placeholder="" autocomplete="off"></td>
                    </tr>
                    <tr>
                        <th scope="row"></th><td>
                        <button id="create_alby_account" type="button" class="button button-secondary">%s</button>
                        </td>
                    </tr>
                </table>
             </div>',
            __('Email', 'lnp-alby'),
            __('Password', 'lnp-alby'),
            __('Use Alby Wallet', 'lnp-alby')
        );
    }



    public function get_lnd_address_node_info() {

        // Don't run check on other settings pages
        if ( ! $this->is_current_page() )
        {
            return;

        }
        try {

            if (
                $this->plugin->getLightningClient()
                && $this->plugin->getLightningClient()->isConnectionValid()
            ) {
                $node_info = $this->plugin->getLightningClient()->getInfo();

                $type    = 'notice';
                $message = sprintf(
                    '%s %s - %s',
                    __('Connected to:', 'lnp-alby'),
                    $node_info['alias'],
                    $node_info['identity_pubkey']
                );
            }
            else {

                $type    = 'error';
                $message = __('Wallet not connected', 'lnp-alby');
            }
        }
        catch (Exception $e) {

            $type    = 'error';
            $message = sprintf(
                '%s %s',
                __('Connection Error, please check log for details', 'lnp-alby'),
                $e
            );
        }

        $this->add_admin_notice($message, $type);
    }
}
