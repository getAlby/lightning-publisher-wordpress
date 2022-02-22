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
                'title'       => __('LNDhub', 'lnp-alby' ),
                'description' => __('Connect using LNDhub', 'lnp-alby'),
            ),
            'lndhub' => array(
                'title'       => __('LndHub (BlueWallet)', 'lnp-alby' ),
                'description' => __('Connect using LNDhub', 'lnp-alby'),
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
                'label'       => __( 'Address', 'lnp-alby' ),
                'name'        => 'lnd_address',
                'type'        => 'url',
                'description' => __( 'e.g. https://127.0.0.1:8080 - or <a href="#" id="load_from_lndconnect">click here to load details from a lndconnect</a>', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lnd',
            'field'   => array(
                'label'       => __( 'Macaroon', 'lnp-alby' ),
                'name'        => 'lnd_macaroon',
                'description' => __( 'Invoices macaroon in HEX format', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lnd',
            'field'   => array(
                'label'       => __( 'TLS Certificate', 'lnp-alby' ),
                'name'        => 'lnd_cert',
                'description' => __( 'TLS Certificate', 'lnp-alby'),
            ),
        );


        /**
         * Fields for section: LND Hub (Blue wallet)
         */
        $fields[] = array(
            'tab'     => 'lndhub',
            'field'   => array(
                'label'       => __( 'LndHub Url', 'lnp-alby' ),
                'name'        => 'lndhub_url',
                'type'        => 'url',
                'description' => __( 'LndHub Host', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lndhub',
            'field'   => array(
                'label'       => __( 'Login', 'lnp-alby' ),
                'name'        => 'lndhub_login',
                'description' => __( 'LndHub Login', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lndhub',
            'field'   => array(
                'label'       => __( 'Password', 'lnp-alby' ),
                'name'        => 'lndhub_password',
                'type'        => 'password',
                'description' => __( 'LndHub Password', 'lnp-alby'),
            ),
        );

        /**
         * LNBits
         */
        $fields[] = array(
            'tab'     => 'lnbits',
            'field'   => array(
                'label'       => __( 'API Key', 'lnp-alby' ),
                'name'        => 'lnbits_apikey',
                'description' => __( 'LNbits Invoice/read key', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lnbits',
            'field'   => array(
                'label'       => __( 'Host', 'lnp-alby' ),
                'name'        => 'lnbits_host',
                'type'        => 'url',
                'description' => __( 'LNbits host (e.g. https://legend.lnbits.com)', 'lnp-alby'),
            ),
        );


        /**
         * BTC Pay
         */
        $fields[] = array(
            'tab'     => 'btcpay',
            'field'   => array(
                'label'       => __( 'Host', 'lnp-alby' ),
                'name'        => 'btcpay_host',
                'type'        => 'url',
                'description' => __( 'BtcPay Host', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'btcpay',
            'field'   => array(
                'label'       => __( 'API Key', 'lnp-alby' ),
                'name'        => 'btcpay_apikey',
                'description' => __( 'BtcPay Api Key', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'btcpay',
            'field'   => array(
                'label'       => __( 'Store ID', 'lnp-alby' ),
                'name'        => 'btcpay_store_id',
                'description' => __( 'BtcPay Store ID', 'lnp-alby'),
            ),
        );


        /**
         * LN Address
         */
        $fields[] = array(
            'tab'     => 'lnaddress',
            'field'   => array(
                'label'       => __( 'Lightning Address', 'lnp-alby' ),
                'name'        => 'lnaddress_address',
                'description' => __( 'Lightning Address (e.g. you@payaddress.co) - only works if the vistor supports WebLN!', 'lnp-alby'),
            ),
        );

        // Save Form fields to class
        $this->form_fields = $fields;
    }



    public function get_lnd_address_node_info() {

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
                $message = __('Not connected', 'lnp-alby');
            }
        }
        catch (Exception $e) {

            $type    = 'error';
            $message = sprintf(
                '%s %s',
                __('Not connected', 'lnp-alby'),
                $e
            );
        }

        $this->add_admin_notice($message, $type);
    }
}
