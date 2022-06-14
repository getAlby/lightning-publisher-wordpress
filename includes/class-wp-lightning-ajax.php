<?php

use \Firebase\JWT;

/**
 * Ajax endpoints of the plugin
 *
 * @package    WP_Lightning
 * @subpackage WP_Lightning/includes
 */
class WP_Lightning_Ajax
{

    /**
     * Main Plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      WP_Lightning    $plugin    The main plugin object.
     */
    private $plugin;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    WP_Lightning    $plugin       The main plugin object.
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Create Invoice.
     */
    public function ajax_make_invoice()
    {
        if (!empty($_POST['post_id'])) {
            $post_id = (int)$_POST['post_id'];
            $paywall = new WP_Lightning_Paywall(get_post_field('post_content', $post_id));
            $paywall_options = $paywall->getOptions();
            if (!$paywall_options) {
                return wp_send_json(['error' => 'invalid post'], 404);
            }
            $memo = get_bloginfo('name') . ' - ' . get_the_title($post_id);
            $amount = $paywall_options['amount'];
            $response_data = ['post_id' => $post_id, 'amount' => $amount];
        } elseif (!empty($_POST['all'])) {
            $memo = get_bloginfo('name');
            $database_options = $this->plugin->getPaywallOptions();
            $amount = $database_options['all_amount'];
            $response_data = ['all' => true, 'amount' => $amount];
        } else {
            return wp_send_json(['error' => 'invalid post'], 404);
        }

        if (!$amount) {
            $amount = 1000;
        }

        $memo = substr($memo, 0, 64);
        $memo = preg_replace('/[^\w_ ]/', '', $memo);
        $invoice_params = [
            'memo' => $memo,
            'value' => $amount, // in sats
            'expiry' => 1800,
            'private' => true
        ];

        $invoice = $this->plugin->getLightningClient()->addInvoice($invoice_params);
        $this->plugin->getDatabaseHandler()->store_invoice($post_id, $invoice['r_hash'], $invoice['payment_request'], $amount, '', 0);

        $jwt_data = array_merge($response_data, ['invoice_id' => $invoice['r_hash'], 'r_hash' => $invoice['r_hash'], 'exp' => time() + 60 * 10]);
        $jwt = JWT\JWT::encode($jwt_data, WP_LN_PAYWALL_JWT_KEY,  WP_LN_PAYWALL_JWT_ALGORITHM);

        $response = array_merge($response_data, ['token' => $jwt, 'payment_request' => $invoice['payment_request']]);
        wp_send_json($response);
    }

    /**
     * AJAX endpoint to check if an invoice is settled
     * returns the protected content if the invoice is settled
     */
    public function ajax_check_payment()
    {
        if (empty($_POST['token'])) {
            return wp_send_json(['settled' => false], 404);
        }
        try {
            $jwt = JWT\JWT::decode($_POST['token'], new JWT\Key(WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM));
        } catch (Exception $e) {
            return wp_send_json(['settled' => false], 404);
        }

        // if we get a preimage we can check if the preimage matches the payment hash and accept it.
        if (!empty($_POST['preimage']) && hash('sha256', hex2bin($_POST['preimage']), false) == $jwt->{"r_hash"}) {
            $invoice = ['settled' => true];
            // if ew do not have a preimage we must check with the LN node if the invoice was paid.
        } else {
            $invoice_id = $jwt->{'invoice_id'};
            $invoice = $this->plugin->getLightningClient()->getInvoice($invoice_id);
        }

        // TODO check amount?
        if ($invoice && $invoice['settled']) { // && (int)$invoice['value'] == (int)$jwt->{'amount'}) {
            $post_id = $jwt->{'post_id'};
            $this->plugin->getDatabaseHandler()->update_invoice_state($jwt->{'r_hash'}, 'settled');
            if (!empty($post_id)) {
                $content = get_post_field('post_content', $post_id);
                $paywall = new WP_Lightning_Paywall($content);
                $protected = $paywall->getProtectedContent();
                WP_Lightning_Paywall::save_as_paid($post_id, $invoice['value']);
                wp_send_json($protected, 200);
            } elseif (!empty($jwt->{'all'})) {
                WP_Lightning_Paywall::save_paid_all($this->plugin->getPaywallOptions()['all_days']);
                wp_send_json($this->plugin->getPaywallOptions()['all_confirmation'], 200);
            }
        } else {
            wp_send_json(['settled' => false], 402);
        }
    }

    /**
     * Create LNPHub Account
     */
    public function create_lnp_hub_account()
    {
      $account = LNDHub\Client::createWallet("https://ln.getalby.com", "bluewallet");
      wp_send_json($account);
    }
}
