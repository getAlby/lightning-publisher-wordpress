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

define('WP_LN_PAYWALL_JWT_KEY', hash_hmac('sha256', 'wp-lightning-paywall', AUTH_KEY));

class WP_LN_Paywall {
  public function __construct() {
    $this->options = get_option('lnp');

    // frontend
    add_action('wp_enqueue_scripts', array($this, 'enqueue_script'));
    add_filter('the_content',        array($this, 'ln_paywall_filter'));

    // ajax
    add_action('wp_ajax_lnp_invoice',           array($this, 'ajax_make_invoice'));
    add_action('wp_ajax_nopriv_lnp_invoice',   array($this, 'ajax_make_invoice'));

    add_action('wp_ajax_ln[_check_payment',  array($this, 'ajax_check_payment'));
    add_action('wp_ajax_nopriv_lnp_check_payment',  array($this, 'ajax_check_payment'));

    // admin
    add_action('admin_init', array($this, 'admin_init'));
    add_action('admin_menu', array($this, 'admin_menu'));
  }

  protected function getLNDClient() {
    if (!$this->lnd) {
      $this->lnd = new LND\Client();
      $this->lnd->setAddress(trim($this->options['lnd_address']));
      $this->lnd->setMacarronHex(trim($this->options['lnd_macaroon']));
      if (!empty($this->options['lnd_cert'])) {
        $certPath = tempnam(sys_get_temp_dir(), "WPLNP");
        file_put_contents($certPath, hex2bin($this->options['lnd_cert']));
        $this->lnd->setTlsCertificatePath($certPath);
      }
    }
    return $this->lnd;
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

  public static function get_paid_post_ids() {
    if (empty($_COOKIE["wplnp"])) return [];
    try {
      $jwt = JWT::decode($_COOKIE["wplnp"], WP_LN_PAYWALL_JWT_KEY, array('HS256'));
      $paid_post_ids = $jwt->{'post_ids'};
      if (!is_array($paid_post_ids)) return [];

      return $paid_post_ids;
    } catch (Exception $e) {
      //setcookie("wplnp", "", time() - 3600, '/'); // delete invalid JWT cookie
      return [];
    }
  }

  public static function has_paid_for_post($post_id) {
    $paid_post_ids = self::get_paid_post_ids();
    return in_array($post_id, $paid_post_ids);
  }
  /**
   * Register scripts and styles
   */
  public function enqueue_script() {
    wp_enqueue_script('webln', 'https://unpkg.com/webln@0.2.0/dist/webln.min.js'); // TODO bundle
    wp_enqueue_script('ln-paywall', plugins_url('js/publisher.js', __FILE__), array('jquery'));
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

    if (!$ln_shortcode_data) return status_header(404);

    $invoice = $this->getLNDClient()->addInvoice([
      'memo' => 'POST ID' . $post_id,
      'value' => $ln_shortcode_data['amount'] // in sats
    ]);

    $jwt = JWT::encode(array('post_id' => $post_id, 'r_hash' => $invoice->{'r_hash'}, exp => time() + 60*5), WP_LIGHTNING_JWT_KEY);

    wp_send_json([ 'post_id' => $post_id, 'token' => $jwt, 'payment_request' => $invoice->{'payment_request'}]);
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
    $r_hash_str = $jwt->{'r_hash'};
    $post_id = $jwt->{'post_id'};

    $invoice = $this->getLNDClient()->getInvoice($r_hash_str);
    $amount = (int)$invoice->{'value'};
    if ($invoice && $invoice->{'settled'}) {
      $content = get_post_field('post_content', $post_id);
      list($public, $protected) = self::splitPublicProtected($content);
      self::save_as_paid($post_id, $amount);
      wp_send_json($protected);
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
    $text   = '<p>' . sprintf(!isset($ln_shortcode_data['text']) ? 'To continue reading the rest of this post, please pay <em>%s Satoshi</em>.' : $ln_shortcode_data['text'], $ln_shortcode_data['amount']).'</p>';
    $button = sprintf('<a class="wp-lnp-btn" href="#" data-lnp-postid="%d">%s</a>', $post_id, !isset($ln_shortcode_data['button']) ? 'Pay to continue reading' : $ln_shortcode_data['button']);
    $autopay = '<p><label><input type="checkbox" value="1" class="wp-lnp-autopay" id="wp-lnp-autopay" />Enable autopay<label</p>';
    return sprintf('%s<div id="wp-lnp-wrapper" class="wp-lnp-wrapper">%s%s%s</div>', $public, $text, $button, $autopay);
  }

  /**
   * Admin
   */
  public function admin_menu() {
    add_options_page('Lighting Paywall Settings', 'Lightning', 'manage_options', 'lnp', array($this, 'admin_page'));
  }

  public function admin_init() {
    register_setting('lnp', 'lnp');
    add_settings_section('lnd', 'LND Config', null, 'lnp');

    add_settings_field('lnd_address', 'Address', array($this, 'field_lnd_address'), 'lnp', 'lnd');
    add_settings_field('lnd_macaroon', 'Macaroon', array($this, 'field_lnd_macaroon'), 'lnp', 'lnd');
    add_settings_field('lnd_cert', 'TLS Cert', array($this, 'field_lnd_cert'), 'lnp', 'lnd');
  }
  public function admin_page() {
    ?>
    <div class="wrap">
        <h1>Lightning Paywall Settings</h1>
        <div class="node-info">
          <?php
            try {
              if ($this->getLNDClient()->isConnectionValid()) {
                $node_info = $this->getLNDClient()->getInfo();
                echo "Connected to: " . $node_info->{'alias'} . ' - ' . $node_info->{'identity_pubkey'};
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
  public function field_lnd_address(){
    printf('<input type="text" name="lnp[lnd_address]" value="%s" />',
      esc_attr($this->options['lnd_address']),
      'http://localhost');
  }
  public function field_lnd_macaroon(){
    printf('<input type="text" name="lnp[lnd_macaroon]" value="%s" /><br><label>%s</label>',
      esc_attr($this->options['lnd_macaroon']),
      'Invoices macaroon in HEX format');
  }
  public function field_lnd_cert(){
    printf('<input type="text" name="lnp[lnd_cert]" value="%s" /><br><label>%s</label>',
      esc_attr($this->options['lnd_cert']),
      'TLS Certificate');
  }
}

new WP_LN_Paywall();
