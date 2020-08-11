<?php
/*
    Plugin Name: Lightning Paywall
    Version:     0.0.1
    Plugin URI:  
    Description: Wordpress content paywall using the lightning network. Directly connected to an LND node
    Author:      
    Author URI:  

    Fork of: https://github.com/ElementsProject/wordpress-lightning-publisher
*/

if (!defined('ABSPATH')) exit;

require_once 'vendor/autoload.php';

use \Firebase\JWT\JWT;

define('WP_LIGHTNING_JWT_KEY', hash_hmac('sha256', 'wp-lightning-paywall', AUTH_KEY));

class WP_LN_Paywall {
  public function __construct() {
    $this->options = get_option('lnp');
    $this->lightningClient = null;

    // frontend
    add_action('wp_enqueue_scripts', array($this, 'enqueue_script'));
    add_filter('the_content',        array($this, 'ln_paywall_filter'));

    // ajax
    add_action('wp_ajax_lnp_invoice',        array($this, 'ajax_make_invoice'));
    add_action('wp_ajax_nopriv_lnp_invoice', array($this, 'ajax_make_invoice'));

    add_action('wp_ajax_lnp_check_payment', array($this, 'ajax_check_payment'));
    add_action('wp_ajax_nopriv_lnp_check_payment', array($this, 'ajax_check_payment'));

    // admin
    add_action('admin_init', array($this, 'admin_init'));
    add_action('admin_menu', array($this, 'admin_menu'));
  }

  protected function getLightningClient() {
    if ($this->lightningClient) {
      return $this->lightningClient;
    }

    if (!empty($this->options['lnd_address'])) {
      $this->lightningClient = new LND\Client();
      $this->lightningClient->setAddress(trim($this->options['lnd_address']));
      $this->lightningClient->setMacarronHex(trim($this->options['lnd_macaroon']));
      if (!empty($this->options['lnd_cert'])) {
        $certPath = tempnam(sys_get_temp_dir(), "WPLNP");
        file_put_contents($certPath, hex2bin($this->options['lnd_cert']));
        $this->lightningClient->setTlsCertificatePath($certPath);
      }
    } elseif (!empty($this->options['lnbits_apikey'])) {
      $this->lightningClient = new LNbits\Client($this->options['lnbits_apikey']);
    }
    return $this->lightningClient;
  }
  /**
   * filter ln shortcodes and inject payment request HTML
   */
  public function ln_paywall_filter($content) {
    $ln_shortcode_data = self::extract_ln_shortcode($content);
    if (!$ln_shortcode_data) return $content;

    $post_id = get_the_ID();

    list($public, $protected) = self::splitPublicProtected($content);
    $amount_received = get_post_meta($post_id, '_lnp_amount_received', true);
    if (self::has_paid_for_post($post_id)) {
      return self::format_paid($post_id, $ln_shortcode_data, $public, $protected);
    }
    if ($ln_shortcode_data['total'] && (int)$ln_shortcode_data['total'] < $amount_received) {
      return self::format_paid($post_id, $ln_shortcode_data, $public, $protected);
    }

    return self::format_unpaid($post_id, $ln_shortcode_data, $public);
  }

  public static function splitPublicProtected($content) {
    return preg_split('/(<p>)?\[ln.+\](<\/p>)?/', $content, 2);
  }

  /**
   * Store the post_id in an cookie to remember the payment
   * and increment the paid amount on the post
   * must only be called once (can be exploited currently)
   */
  public static function save_as_paid($post_id, $amount_paid = 0) {
    $paid_post_ids = self::get_paid_post_ids();
    if (!in_array($post_id, $post_ids)) {
      $amount_received = get_post_meta($post_id, '_lnp_amount_received', true);
      if (is_numeric($amount_received)) {
        $amount = $amount_received + $amount_paid;
      } else {
        $amount = $amount_paid;
      }
      update_post_meta($post_id, '_lnp_amount_received', $amount);

      array_push($paid_post_ids, $post_id);
    }
    $jwt = JWT::encode(array('post_ids' => $paid_post_ids), WP_LN_PAYWALL_JWT_KEY);
    setcookie('wplnp', $jwt, time() + time() + 60*60*24*180, '/');
  }

  public static function has_paid_for_all() {
    $wplnp = $_COOKIE['wplnp'] || $_GET['wplnp'];
    if (empty($wplnp)) return false;
    try {
      $jwt = JWT::decode($wplnp, WP_LN_PAYWALL_JWT_KEY, array('HS256'));
      return $jwt->{'all_until'} > time();
    } catch (Exception $e) {
      //setcookie("wplnp", "", time() - 3600, '/'); // delete invalid JWT cookie
      return false;
    }
  }

  public static function get_paid_post_ids() {
    $wplnp = $_COOKIE['wplnp'] || $_GET['wplnp'];
    if (empty($wplnp)) return [];
    try {
      $jwt = JWT::decode($wplnp, WP_LN_PAYWALL_JWT_KEY, array('HS256'));
      $paid_post_ids = $jwt->{'post_ids'};
      if (!is_array($paid_post_ids)) return [];

      return $paid_post_ids;
    } catch (Exception $e) {
      //setcookie("wplnp", "", time() - 3600, '/'); // delete invalid JWT cookie
      return [];
    }
  }

  public static function has_paid_for_post($post_id) {
    if (self::has_paid_for_all()) {
      return true;
    }
    $paid_post_ids = self::get_paid_post_ids();
    return in_array($post_id, $paid_post_ids);
  }
  /**
   * Register scripts and styles
   */
  public function enqueue_script() {
    wp_enqueue_script('webln', plugins_url('js/webln.min.js', __FILE__));
    wp_enqueue_script('ln-paywall', plugins_url('js/publisher.js', __FILE__));
    wp_enqueue_style('ln-paywall', plugins_url('css/publisher.css', __FILE__));
    wp_localize_script('ln-paywall', 'LN_Paywall', array(
      'ajax_url'   => admin_url('admin-ajax.php'),
    ));
  }

  /**
   * AJAX endpoint to create new invoices
   */
  public function ajax_make_invoice() {
    $post_id = (int)$_POST['post_id'];
    $ln_shortcode_data = self::extract_ln_shortcode(get_post_field('post_content', $post_id));

    if (!$ln_shortcode_data) {
      return wp_send_json([ 'error' => 'invalid post' ], 404);
    }

    $memo = get_bloginfo('name') . ' - ' . get_the_title($post_id);

    $invoice = $this->getLightningClient()->addInvoice([
      'memo' => substr($memo, 0, 64),
      'value' => $ln_shortcode_data['amount'], // in sats
      'expiry' => 1800
    ]);

    $jwt = JWT::encode(array('post_id' => $post_id, 'invoice_id' => $invoice['r_hash'], 'exp' => time() + 60*5, 'amount' => $ln_shortcode_data['amount']), WP_LIGHTNING_JWT_KEY);

    wp_send_json([ 'post_id' => $post_id, 'token' => $jwt, 'payment_request' => $invoice['payment_request']]);
  }

  /**
   * AJAX endpoint to check if an invoice is settled
   * returns the protected content if the invoice is settled
   */
  public function ajax_check_payment() {
    try {
      $jwt = JWT::decode($_POST['token'], WP_LIGHTNING_JWT_KEY, array('HS256'));
    } catch(Exception $e) {
      return wp_send_json([ 'settled' => false ], 404);
    }
    $invoice_id = $jwt->{'invoice_id'};
    $post_id = $jwt->{'post_id'};

    $invoice = $this->getLightningClient()->getInvoice($invoice_id);
    $amount = (int)$invoice['value'] || (int)$jwt->{'amount'}; // TODO: invoice LNbits does not return the amount. needs to be added to lnbits
    if ($invoice && $invoice['settled']) {
      $content = get_post_field('post_content', $post_id);
      list($public, $protected) = self::splitPublicProtected($content);
      self::save_as_paid($post_id, $amount);
      wp_send_json($protected, 200);
    } else {
      wp_send_json([ 'settled' => false ], 402);
    }
  }

  /**
   * Parse [ln] tags and return as structured data
   * Expected format: [ln key=val]
   * @param string $content
   * @return array
   */
  protected static function extract_ln_shortcode($content) {
    if (!preg_match('/\[ln(.+)\]/i', $content, $m)) return;
    return shortcode_parse_atts($m[1]);
  }

  /**
   * Format display for paid post
   */
  protected static function format_paid($post_id, $ln_shortcode_data, $public, $protected) {
    return sprintf('%s%s', $public, $protected);
  }

  /**
   * Format display for unpaid post. Injects the payment request HTML
   */
  protected static function format_unpaid($post_id, $ln_shortcode_data, $public) {
    $text   = '<p>' . sprintf(!isset($ln_shortcode_data['text']) ? 'To continue reading the rest of this post, please pay <em>%s sats</em>.' : $ln_shortcode_data['text'], $ln_shortcode_data['amount']).'</p>';
    $button = sprintf('<button class="wp-lnp-btn">%s</button>', !isset($ln_shortcode_data['button']) ? 'Pay now' : $ln_shortcode_data['button']);
    $autopay = '<p><label><input type="checkbox" value="1" class="wp-lnp-autopay" />Enable autopay<label</p>';
    return sprintf('%s<div id="wp-lnp-wrapper" class="wp-lnp-wrapper" data-lnp-postid="%d">%s%s%s</div>', $public, $post_id, $text, $button, $autopay);
  }

  /**
   * Admin
   */
  public function admin_menu() {
    add_menu_page('Lighting Paywall Settings', 'Lighting Paywall', 'manage_options', 'lnp_settings');
    add_submenu_page('lnp_settings','Lighting Paywall Settings', 'Settings', 'manage_options', 'lnp_settings',array($this, 'settings_page'));
    add_submenu_page('lnp_settings', 'Lightning Paywall Balances','Balances', 'manage_options', 'lnp_balances', array($this, 'balances_page'));
  }

  public function admin_init() {
    register_setting('lnp', 'lnp');
    add_settings_section('lnd', 'LND Config', null, 'lnp');

    add_settings_field('lnd_address', 'Address', array($this, 'field_lnd_address'), 'lnp', 'lnd');
    add_settings_field('lnd_macaroon', 'Macaroon', array($this, 'field_lnd_macaroon'), 'lnp', 'lnd');
    add_settings_field('lnd_cert', 'TLS Cert', array($this, 'field_lnd_cert'), 'lnp', 'lnd');

    add_settings_section('lnbits', 'LNbits', null, 'lnp');
    add_settings_field('lnbits_apikey', 'API Key', array($this, 'field_lnbits_apikey'), 'lnp', 'lnbits');
  }

  public function settings_page() {
    ?>
    <div class="wrap">
        <h1>Lightning Paywall Settings</h1>
        <div class="node-info">
          <?php
            try {
              if ($this->getLightningClient()->isConnectionValid()) {
                $node_info = $this->getLightningClient()->getInfo();
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
        <?php
            settings_fields('lnp');
            do_settings_sections('lnp');
            submit_button();
        ?>
        </form>
    </div>
    <?php
  }

  public function balances_page() {
    ?>
    <div class="wrap">
        <h1>Lightning Paywall Balances</h1>
    </div>
    <?php
  }
  public function field_lnd_address(){
    printf('<input type="text" name="lnp[lnd_address]" value="%s" autocomplete="off" />',
      esc_attr($this->options['lnd_address']),
      'http://localhost');
  }
  public function field_lnd_macaroon(){
    printf('<input type="text" name="lnp[lnd_macaroon]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['lnd_macaroon']),
      'Invoices macaroon in HEX format');
  }
  public function field_lnd_cert(){
    printf('<input type="text" name="lnp[lnd_cert]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['lnd_cert']),
      'TLS Certificate');
  }
  public function field_lnbits_apikey(){
    printf('<input type="text" name="lnp[lnbits_apikey]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['lnbits_apikey']),
      'LNbits API Key.');
  }
}

new WP_LN_Paywall();
