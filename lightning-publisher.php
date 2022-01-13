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

require_once 'lightning_address.php';

require_once 'LnpWidget.php';
require_once 'admin/dashboard.php';
require_once 'admin/balance.php';
require_once 'admin/paywall.php';
require_once 'admin/connections.php';
require_once 'admin/help.php';
require_once 'database-handler.php';

use \tkijewski\lnurl;
use \Firebase\JWT\JWT;

define('WP_LN_PAYWALL_JWT_KEY', hash_hmac('sha256', 'wp-lightning-paywall', AUTH_KEY));

class WP_LN_Paywall
{
  public function __construct()
  {
    $this->lightningClient = null;
    $this->lightningClientType = null;

    $this->database_handler = new DatabaseHandler();

    add_action('init', array($this->database_handler, 'init'));
    // frontend
    add_action('wp_enqueue_scripts', array($this, 'enqueue_script'));
    add_filter('the_content',        array($this, 'ln_paywall_filter'));

    // ajax
    add_action('wp_ajax_lnp_invoice',        array($this, 'ajax_make_invoice'));
    add_action('wp_ajax_nopriv_lnp_invoice', array($this, 'ajax_make_invoice'));

    add_action('wp_ajax_lnp_invoice_all',        array($this, 'ajax_make_invoice_all'));
    add_action('wp_ajax_nopriv_lnp_invoice_all', array($this, 'ajax_make_invoice_all'));

    add_action('wp_ajax_lnp_check_payment', array($this, 'ajax_check_payment'));
    add_action('wp_ajax_nopriv_lnp_check_payment', array($this, 'ajax_check_payment'));

    add_action('wp_ajax_lnp_check_payment_all', array($this, 'ajax_check_payment_all'));
    add_action('wp_ajax_nopriv_lnp_check_payment_all', array($this, 'ajax_check_payment_all'));

    add_action('wp_ajax_create_lnp_hub_account', array($this, 'create_lnp_hub_account'));

    // admin
    add_action('admin_menu', array($this, 'admin_menu'));
    // initializing admin pages
    new LNP_Dashboard($this, 'lnp_settings');
    new BalancePage($this, 'lnp_settings', $this->database_handler);
    $paywall_page = new PaywallPage($this, 'lnp_settings');
    $connection_page = new ConnectionPage($this, 'lnp_settings');
    new HelpPage($this, 'lnp_settings');

    // get page options
    $this->connection_options = $connection_page->options;
    $this->paywall_options = $paywall_page->options ?  $paywall_page->options : [];

    add_action('widgets_init', array($this, 'widget_init'));
    // feed
    // https://code.tutsplus.com/tutorials/extending-the-default-wordpress-rss-feed--wp-27935
    if (!empty($this->paywall_options['lnurl_rss'])) {
      add_action('init', array($this, 'add_lnurl_endpoints'));
      add_action('template_redirect', array($this, 'lnurl_endpoints'));
      add_action('rss2_item', array($this, 'add_lnurl_to_rss_item_filter'));
    }
    add_action('admin_enqueue_scripts', array($this, 'load_custom_wp_admin_style'));
  }

  public function getLightningClient()
  {
    if ($this->lightningClient) {
      return $this->lightningClient;
    }

    if (!empty($this->connection_options['lnd_address'])) {
      $this->lightningClientType = 'lnd';
      $this->lightningClient = new LND\Client();
      $this->lightningClient->setAddress(trim($this->connection_options['lnd_address']));
      $this->lightningClient->setMacarronHex(trim($this->connection_options['lnd_macaroon']));
      if (!empty($this->connection_options['lnd_cert'])) {
        $certPath = tempnam(sys_get_temp_dir(), "WPLNP");
        file_put_contents($certPath, hex2bin($this->connection_options['lnd_cert']));
        $this->lightningClient->setTlsCertificatePath($certPath);
      }
    } elseif (!empty($this->connection_options['lnbits_apikey'])) {
      $this->lightningClientType = 'lnbits';
      $this->lightningClient = new LNbits\Client($this->connection_options['lnbits_apikey'], $this->connection_options['lnbits_host']);
    } elseif (!empty($this->connection_options['lnaddress_address'])) {
      $this->lightningClientType = 'lnaddress';
      $this->lightningClient = new LightningAddress();
      $this->lightningClient->setAddress($this->connection_options['lnaddress_address']);
    } elseif (!empty($this->connection_options['btcpay_host'])) {
      $this->lightningClientType = 'btcpay';
      $this->lightningClient = new BTCPay\Client($this->connection_options['btcpay_host'], $this->connection_options['btcpay_apikey'], $this->connection_options['btcpay_store_id']);
      $this->lightningClient->init();
    } elseif (!empty($this->connection_options['lnaddress_lnurl'])) {
      $this->lightningClientType = 'lnaddress';
      $this->lightningClient = new LightningAddress();
      $this->lightningClient->setLnurl($this->connection_options['lnaddress_lnurl']);
    } elseif (!empty($this->connection_options['lndhub_url']) && !empty($this->connection_options['lndhub_login']) && !empty($this->connection_options['lndhub_password'])) {
      $this->lightningClientType = 'lndhub';
      $this->lightningClient = new LNDHub\Client($this->connection_options['lndhub_url'], $this->connection_options['lndhub_login'], $this->connection_options['lndhub_password']);
      $this->lightningClient->init();
    }
    return $this->lightningClient;
  }

  /**
   * filter ln shortcodes and inject payment request HTML
   */
  public function ln_paywall_filter($content)
  {
    $post_id = get_the_ID();
    $paywall_options = $this->get_paywall_options_for($post_id, $content);

    if (!$paywall_options) {
      return $content;
    }

    list($public, $protected) = self::splitPublicProtected($content);

    if ($paywall_options['disable_in_rss'] && is_feed()) {
      return self::format_paid($post_id, $paywall_options, $public, $protected);
    }

    if (!empty($paywall_options['timeout']) && time() > get_post_time('U') + $paywall_options['timeout'] * 24 * 60 * 60) {
      return self::format_paid($post_id, $paywall_options, $public, $protected);
    }
    if (!empty($paywall_options['timein']) && time() < get_post_time('U') + $paywall_options['timein'] * 24 * 60 * 60) {
      return self::format_paid($post_id, $paywall_options, $public, $protected);
    }

    $amount_received = get_post_meta($post_id, '_lnp_amount_received', true);
    if (!empty($paywall_options['total']) && $amount_received >= $paywall_options['total']) {
      return self::format_paid($post_id, $paywall_options, $public, $protected);
    }

    if (self::has_paid_for_all()) {
      return self::format_paid($post_id, $paywall_options, $public, $protected);
    }

    if (self::has_paid_for_post($post_id)) {
      return self::format_paid($post_id, $paywall_options, $public, $protected);
    }

    return self::format_unpaid($post_id, $paywall_options, $public);
  }

  public function add_lnurl_to_rss_item_filter()
  {
    global $post;
    $pay_url = add_query_arg([
      'lnurl' => 'pay',
      'lnurl_post_id' => $post->ID
    ], get_site_url());
    $lnurl = lnurl\encodeUrl($pay_url);
    echo '<payment:lnurl>' . $lnurl . '</payment:lnurl>';
  }

  public static function splitPublicProtected($content)
  {
    return preg_split('/(<p>)?\[ln.+\](<\/p>)?/', $content, 2);
  }

  /**
   * Store the post_id in an cookie to remember the payment
   * and increment the paid amount on the post
   * must only be called once (can be exploited currently)
   */
  public static function save_as_paid($post_id, $amount_paid = 0)
  {
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
    setcookie('wplnp', $jwt, time() + time() + 60 * 60 * 24 * 180, '/');
  }

  public static function save_paid_all($days)
  {
    $paid_post_ids = self::get_paid_post_ids();
    $jwt = JWT::encode(array('all_until' => time() + $days * 24 * 60 * 60, 'post_ids' => $paid_post_ids), WP_LN_PAYWALL_JWT_KEY);
    setcookie('wplnp', $jwt, time() + time() + 60 * 60 * 24 * 180, '/');
  }

  public static function has_paid_for_all()
  {
    $wplnp = null;
    if (isset($_COOKIE['wplnp'])) {
      $wplnp = $_COOKIE['wplnp'];
    } elseif (isset($_GET['wplnp'])) {
      $wplnp = $_GET['wplnp'];
    }
    if (empty($wplnp)) return false;
    try {
      $jwt = JWT::decode($wplnp, WP_LN_PAYWALL_JWT_KEY, array('HS256'));
      return $jwt->{'all_until'} > time();
    } catch (Exception $e) {
      //setcookie("wplnp", "", time() - 3600, '/'); // delete invalid JWT cookie
      return false;
    }
  }

  public static function get_paid_post_ids()
  {
    $wplnp = null;
    if (isset($_COOKIE['wplnp'])) {
      $wplnp = $_COOKIE['wplnp'];
    } elseif (isset($_GET['wplnp'])) {
      $wplnp = $_GET['wplnp'];
    }
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

  public static function has_paid_for_post($post_id)
  {
    $paid_post_ids = self::get_paid_post_ids();
    return in_array($post_id, $paid_post_ids);
  }

  /**
   * Register scripts and styles
   */
  public function enqueue_script()
  {
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
  public function ajax_make_invoice()
  {
    if (!empty($_POST['post_id'])) {
      $post_id = (int)$_POST['post_id'];
      $paywall_options = $this->get_paywall_options_for($post_id, get_post_field('post_content', $post_id));
      if (!$paywall_options) {
        return wp_send_json(['error' => 'invalid post'], 404);
      }
      $memo = get_bloginfo('name') . ' - ' . get_the_title($post_id);
      $amount = $paywall_options['amount'];
      $response_data = ['post_id' => $post_id, 'amount' => $amount];
    } elseif (!empty($_POST['all'])) {
      $memo = get_bloginfo('name');
      $amount = $this->paywall_options['all_amount'];
      $response_data = ['all' => true, 'amount' => $amount];
    } else {
      return wp_send_json(['error' => 'invalid post'], 404);
    }

    $memo = substr($memo, 0, 64);
    $memo = preg_replace('/[^\w_ ]/', '', $memo);
    $invoice_params = [
      'memo' => $memo,
      'value' => $amount, // in sats
      'expiry' => 1800,
      'private' => true
    ];

    $invoice = $this->getLightningClient()->addInvoice($invoice_params);
    $this->database_handler->store_invoice($post_id, $invoice['r_hash'], $invoice['payment_request'], $amount, '', 0);

    $jwt_data = array_merge($response_data, ['invoice_id' => $invoice['r_hash'], 'r_hash' => $invoice['r_hash'], 'exp' => time() + 60 * 10]);
    $jwt = JWT::encode($jwt_data, WP_LN_PAYWALL_JWT_KEY,  'HS256');

    $response = array_merge($response_data, ['token' => $jwt, 'payment_request' => $invoice['payment_request']]);
    //wp_send_json([ 'post_id' => $post_id, 'token' => $jwt, 'amount' => $paywall_options['amount'], 'payment_request' => $invoice['payment_request']]);
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
      $jwt = JWT::decode($_POST['token'], WP_LN_PAYWALL_JWT_KEY, array('HS256'));
    } catch (Exception $e) {
      return wp_send_json(['settled' => false], 404);
    }

    // if we get a preimage we can check if the preimage matches the payment hash and accept it.
    if (!empty($_POST['preimage']) && hash('sha256', hex2bin($_POST['preimage']), false) == $jwt->{"r_hash"}) {
      $invoice = ['settled' => true];
      // if ew do not have a preimage we must check with the LN node if the invoice was paid.
    } else {
      $invoice_id = $jwt->{'invoice_id'};
      $invoice = $this->getLightningClient()->getInvoice($invoice_id);
    }

    // TODO check amount?
    if ($invoice && $invoice['settled']) { // && (int)$invoice['value'] == (int)$jwt->{'amount'}) {
      $post_id = $jwt->{'post_id'};
      $this->database_handler->update_invoice_state($jwt->{'r_hash'}, 'settled');
      if (!empty($post_id)) {
        $content = get_post_field('post_content', $post_id);
        list($public, $protected) = self::splitPublicProtected($content);
        self::save_as_paid($post_id, $amount);
        wp_send_json($protected, 200);
      } elseif (!empty($jwt->{'all'})) {
        self::save_paid_all($this->paywall_options['all_days']);
        wp_send_json($this->paywall_options['all_confirmation'], 200);
      }
    } else {
      wp_send_json(['settled' => false], 402);
    }
  }
  protected static function extract_ln_shortcode($content)
  {
    if (!preg_match('/\[ln(.+)\]/i', $content, $m)) {
      return;
    }
    return shortcode_parse_atts($m[1]);
  }

  public function get_paywall_options_for($postId, $content)
  {
    $ln_shortcode_data = self::extract_ln_shortcode($content);
    if (!$ln_shortcode_data && !is_array($ln_shortcode_data)) {
      return null;
    }


    return [
      'paywall_text' => array_key_exists('text', $ln_shortcode_data) ? $ln_shortcode_data['text'] : $this->paywall_options['paywall_text'] ?? null,
      'button_text'  => array_key_exists('button', $ln_shortcode_data) ? $ln_shortcode_data['button'] : $this->paywall_options['button_text'] ?? null,
      'amount'       => array_key_exists('amount', $ln_shortcode_data) ? (int)$ln_shortcode_data['amount'] : (int)($this->paywall_options['amount'] ?? null),
      'total'        => array_key_exists('total', $ln_shortcode_data) ? (int)$ln_shortcode_data['total'] : (int)($this->paywall_options['total'] ?? null),
      'timeout'      => array_key_exists('timeout', $ln_shortcode_data) ? (int)$ln_shortcode_data['timeout'] : (int)($this->paywall_options['timeout'] ?? null),
      'timein'       => array_key_exists('timein', $ln_shortcode_data) ? (int)$ln_shortcode_data['timein'] : (int)($this->paywall_options['timein'] ?? null),
      'disable_in_rss' => array_key_exists('disable_in_rss', $ln_shortcode_data) ? true : $this->paywall_options['disable_paywall_in_rss'] ?? [] ?? null,
    ];
  }

  /**
   * Format display for paid post
   */
  protected static function format_paid($post_id, $ln_shortcode_data, $public, $protected)
  {
    return sprintf('%s%s', $public, $protected);
  }

  /**
   * Format display for unpaid post. Injects the payment request HTML
   */
  protected static function format_unpaid($post_id, $options, $public)
  {
    $text   = '<p>' . sprintf(empty($options['paywall_text']) ? 'To continue reading the rest of this post, please pay <em>%s Sats</em>.' : $options['paywall_text'], $options['amount']) . '</p>';
    $button = sprintf('<button class="wp-lnp-btn">%s</button>', empty($options['button_text']) ? 'Pay now' : $options['button_text']);
    // $autopay = '<p><label><input type="checkbox" value="1" class="wp-lnp-autopay" />Enable autopay<label</p>';
    return sprintf('%s<div id="wp-lnp-wrapper" class="wp-lnp-wrapper" data-lnp-postid="%d">%s%s</div>', $public, $post_id, $text, $button);
  }

  function widget_init()
  {
    $has_paid = self::has_paid_for_all();
    register_widget(new LnpWidget($has_paid, $this->paywall_options));
  }

  // endpoint idea from: https://webdevstudios.com/2015/07/09/creating-simple-json-endpoint-wordpress/
  public function add_lnurl_endpoints()
  {
    add_rewrite_tag('%lnurl%', '([^&]+)');
    add_rewrite_tag('%lnurl_post_id%', '([^&]+)');
    add_rewrite_tag('%amount%', '([^&]+)');
    //add_rewrite_rule( 'lnurl/([^&]+)/?', 'index.php?lnurl=$matches[1]', 'top' );
  }

  public function lnurl_endpoints()
  {
    global $wp_query;
    $lnurl = $wp_query->get('lnurl');
    $post_id = $wp_query->get('lnurl_post_id');

    if (!$lnurl) {
      return;
    }

    $description = get_bloginfo('name');
    if (!empty($post_id)) {
      $description = $description . ' - ' . get_the_title($post_id);
    }

    if ($lnurl == 'pay') {
      $callback_url = home_url(add_query_arg('lnurl', 'cb'));
      wp_send_json([
        'callback' => $callback_url,
        'minSendable' => 1000 * 1000, // millisatoshi
        'maxSendable' => 1000000 * 1000, // millisatoshi
        'tag' => 'payRequest',
        'metadata' => '[["text/plain", "' . $description . '"]]'
      ]);
    } elseif ($lnurl == 'cb') {
      $amount = $_GET['amount'];
      if (empty($amount)) {
        wp_send_json(['status' => 'ERROR', 'reason' => 'amount missing']);
        return;
      }
      $description_hash = base64_encode(hash('sha256', '[["text/plain", "' . $description . '"]]', true));
      $invoice = $this->getLightningClient()->addInvoice([
        'memo' => substr($description, 0, 64),
        'description_hash' => $description_hash,
        'value' => $amount,
        'expiry' => 1800,
        'private' => true
      ]);
      wp_send_json(['pr' => $invoice['payment_request'], 'routes' => []]);
    }
  }

  /**
   * Admin
   */
  public function admin_menu()
  {
    add_menu_page('Lighting Paywall', 'Lighting Paywall', 'manage_options', 'lnp_settings');
  }

  public function create_lnp_hub_account()
  {
    $account = LNDHub\Client::createWallet("https://wallets.getalby.com", "bluewallet");
    wp_send_json($account);
  }

  public function load_custom_wp_admin_style($hook)
  {
    // $hook is string value given add_menu_page function.
    // if ($hook != 'toplevel_page_mypluginname') {
    //   return;
    // }
    wp_enqueue_style('custom_wp_admin_css', plugins_url('css/admin.css', __FILE__));
  }

  public function get_file_url($path)
  {
    return plugins_url($path, __FILE__);
  }
}

new WP_LN_Paywall();
