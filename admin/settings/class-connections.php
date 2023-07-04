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
        $this->page_title = __('Wallet Settings', 'lnp-alby');
        $this->menu_title = __('Wallet Settings', 'lnp-alby');

        add_action('admin_notices', array($this, 'get_ln_node_info'));
        add_action('lnp_tab_before_alby', array($this, 'render_tab_alby_wallet'));
    }


    /**
     * Register Tabs if any
     *
     * @return [type] [description]
     */
    public function init_fields()
    {
        // Tabs
        $this->tabs = array(
            'alby' => array(
                'title'       => __('Alby Wallet', 'lnp-alby'),
                'description' => __('Create or connect a getalby.com account. Alby manages a Lightning wallet for you.', 'lnp-alby'),
            ),
            'lnd' => array(
                'title'       => __('LND', 'lnp-alby'),
                'description' => __('Connect your LND node.', 'lnp-alby'),
            ),
            'lndhub' => array(
                'title'       => __('LNDHub', 'lnp-alby'),
                'description' => __('Connect to an LNDHub account (for example Alby).', 'lnp-alby'),
            ),
            'lnbits' => array(
                'title'       => __('LNbits', 'lnp-alby'),
                'description' => __('Connect to your LNbits account.', 'lnp-alby'),
            ),
            'btcpay' => array(
                'title'       => __('BTC Pay', 'lnp-alby'),
                'description' => __('Connect to a BTCPay Server.', 'lnp-alby'),
            ),
            'lnaddress' => array(
                'title'       => __('LN Address', 'lnp-alby'),
                'description' => __('Connect using a Lightning Address.', 'lnp-alby'),
            ),
        );

        parent::init_fields();
    }


    /**
     * Array of form fields available on this page
     */
    public function set_form_fields()
    {

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
                'label'       => __('REST Address (with port)', 'lnp-alby'),
                'description' => __('e.g. https://127.0.0.1:8080', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lnd',
            'field'   => array(
                'name'        => 'lnd_macaroon',
                'label'       => __('Macaroon', 'lnp-alby'),
                'description' => __('Invoices macaroon in HEX format', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lnd',
            'field'   => array(
                'name'        => 'lnd_cert',
                'label'       => __('TLS Certificate', 'lnp-alby'),
                'description' => __('TLS Certificate in HEX format', 'lnp-alby'),
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
                'label'       => __('LndHub Host', 'lnp-alby'),
                'description' => __('LndHub Host', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lndhub',
            'field'   => array(
                'name'        => 'lndhub_login',
                'label'       => __('Login', 'lnp-alby'),
                'description' => __('LndHub Login', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lndhub',
            'field'   => array(
                'type'        => 'password',
                'name'        => 'lndhub_password',
                'label'       => __('Password', 'lnp-alby'),
                'description' => __('LndHub Password', 'lnp-alby'),
            ),
        );


        /**
         * LNBits
         */
        $fields[] = array(
            'tab'     => 'lnbits',
            'field'   => array(
                'name'        => 'lnbits_apikey',
                'label'       => __('API Key', 'lnp-alby'),
                'description' => __('LNbits Invoice/read key', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'lnbits',
            'field'   => array(
                'type'        => 'url',
                'name'        => 'lnbits_host',
                'label'       => __('Host', 'lnp-alby'),
                'description' => __('LNbits host (e.g. https://legend.lnbits.com)', 'lnp-alby'),
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
                'label'       => __('Host', 'lnp-alby'),
                'description' => __('BTCPay Server Host (Greenfield API)', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'btcpay',
            'field'   => array(
                'name'        => 'btcpay_apikey',
                'label'       => __('API Key', 'lnp-alby'),
                'description' => __('BTCPay Api Key. - requires permission to "Create lightning invoice" (btcpay.store.cancreatelightninginvoice)', 'lnp-alby'),
            ),
        );

        $fields[] = array(
            'tab'     => 'btcpay',
            'field'   => array(
                'name'        => 'btcpay_store_id',
                'label'       => __('Store ID', 'lnp-alby'),
                'description' => __('BTCPay Store ID', 'lnp-alby'),
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
                'label'       => __('Lightning Address', 'lnp-alby'),
                'description' => __('Lightning Address (e.g. you@getalby.com) - currently only works if the vistor supports WebLN or with a @getalby.com lightning address.', 'lnp-alby'),
            ),
        );

        // Save Form fields to class
        $this->form_fields = $fields;
    }



    /**
     * Generate Alby
     */
    public function render_tab_alby_wallet()
    {

        printf(
            '<div>
                <table class="form-table" role="presentation"><tbody>
                    <tr>
                        <th scope="row">%s</th><td><input type="email" class="regular-text" id="alby_email" name="lnp_connection[alby_email]" value="" placeholder="" autocomplete="username"></td>
                    </tr>
                    <tr>
                        <th scope="row">%s</th><td><input type="password" class="regular-text" id="alby_password" name="lnp_connection[alby_password]" value="" placeholder="" autocomplete="new-password"></td>
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



    public function get_ln_node_info()
    {

        // Don't run check on other settings pages
        if (! $this->is_current_page() ) {
            return;

        }
        try {

            if ($this->plugin->getLightningClient()
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
        catch (\Exception $e) {

            $type    = 'error';
            $message = sprintf(
                '%s %s',
                __('Connection Error, please check log for details', 'lnp-alby'),
                $e
            );
        }

        $this->add_admin_notice($message, $type);
    }

    /**
     * Get the active tab based on the wallet setting saved in the database
     * Overrides the parent method
     */
    public function get_active_tab_id()
    {
        $connection_options = $this->plugin->getConnectionOptions();
        if (!empty($connection_options['lnd_address'])) {
            return 'lnd';
        } elseif (!empty($connection_options['lnbits_apikey'])) {
            return 'lnbits';
        } elseif (!empty($connection_options['lnaddress_address']) || !empty($connection_options['lnaddress_lnurl'])) {
            return 'lnaddress';
        } elseif (!empty($connection_options['btcpay_host'])) {
            return 'btcpay';
        } elseif (!empty($connection_options['lndhub_url']) && !empty($connection_options['lndhub_login']) && !empty($connection_options['lndhub_password'])) {
            return 'lndhub';
        }else {
            return isset($_GET['tab'])
                ? sanitize_text_field($_GET['tab'])
                : key($this->tabs);
        }
    }
}
