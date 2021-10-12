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

use \tkijewski\lnurl;
use \Firebase\JWT\JWT;

define('WP_LN_PAYWALL_JWT_KEY', hash_hmac('sha256', 'wp-lightning-paywall', AUTH_KEY));

class WP_LN_Paywall
{
  public function __construct()
  {
    $this->options = get_option('lnp');
    $this->lightningClient = null;

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

    // admin
    add_action('admin_init', array($this, 'admin_init'));
    add_action('admin_menu', array($this, 'admin_menu'));

    add_action('widgets_init', array($this, 'widget_init'));
    // feed
    // https://code.tutsplus.com/tutorials/extending-the-default-wordpress-rss-feed--wp-27935
    if (!empty($this->options['lnurl_rss'])) {
      add_action('init', array($this, 'add_lnurl_endpoints'));
      add_action('template_redirect', array($this, 'lnurl_endpoints'));
      add_action('rss2_item', array($this, 'add_lnurl_to_rss_item_filter'));
    }
  }

  protected function getLightningClient()
  {
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
    } elseif (!empty($this->options['lnaddress_address'])) {
      $this->lightningClient = new LightningAddress();
      $this->lightningClient->setAddress($this->options['lnaddress_address']);
    } elseif (!empty($this->options['lnaddress_lnurl'])) {
      $this->lightningClient = new LightningAddress();
      $this->lightningClient->setLnurl($this->options['lnaddress_lnurl']);
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
    if (empty($wplnp)) return false;
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
      $amount = $this->options['all_amount'];
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

    $jwt_data = array_merge($response_data, ['invoice_id' => $invoice['r_hash'], 'r_hash' => $invoice['r_hash'], 'exp' => time() + 60 * 10]);
    $jwt = JWT::encode($jwt_data, WP_LN_PAYWALL_JWT_KEY);

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
      if (!empty($post_id)) {
        $content = get_post_field('post_content', $post_id);
        list($public, $protected) = self::splitPublicProtected($content);
        self::save_as_paid($post_id, $amount);
        wp_send_json($protected, 200);
      } elseif (!empty($jwt->{'all'})) {
        self::save_paid_all($this->options['all_days']);
        wp_send_json($this->options['all_confirmation'], 200);
      }
    } else {
      wp_send_json(['settled' => false], 402);
    }
  }

  protected static function extract_ln_shortcode($content)
  {
    if (!preg_match('/\[ln(.+)\]/i', $content, $m)) return;
    return shortcode_parse_atts($m[1]);
  }

  public function get_paywall_options_for($postId, $content)
  {
    $ln_shortcode_data = self::extract_ln_shortcode($content);
    if (!$ln_shortcode_data) return null;

    return [
      'paywall_text' => $ln_shortcode_data['text'] ? $ln_shortcode_data['text'] : $this->options['paywall_text'],
      'button_text'  => $ln_shortcode_data['button'] ? $ln_shortcode_data['button'] : $this->options['button_text'],
      'amount'       => $ln_shortcode_data['amount'] ? (int)$ln_shortcode_data['amount'] : (int)$this->options['amount'],
      'total'        => $ln_shortcode_data['total'] ? (int)$ln_shortcode_data['total'] : (int)$this->options['total'],
      'timeout'      => $ln_shortcode_data['timeout'] ? (int)$ln_shortcode_data['timeout'] : (int)$this->options['timeout'],
      'timein'       => $ln_shortcode_data['timein'] ? (int)$ln_shortcode_data['timein'] : (int)$this->options['timein'],
      'disable_in_rss' => $ln_shortcode_data['disable_in_rss'] ? true : $this->options['disable_paywall_in_rss'],
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
    return sprintf('%s<div id="wp-lnp-wrapper" class="wp-lnp-wrapper" data-lnp-postid="%d">%s%s%s</div>', $public, $post_id, $text, $button, $autopay);
  }

  function widget_init()
  {
    $has_paid = self::has_paid_for_all();
    register_widget(new LnpWidget($has_paid, $this->options));
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
    add_menu_page('Lighting Paywall Settings', 'Lighting Paywall', 'manage_options', 'lnp_settings');
    add_submenu_page('lnp_settings', 'Lighting Paywall Settings', 'Connection', 'manage_options', 'lnp_settings', array($this, 'settings_page'));
    add_submenu_page('lnp_settings', 'Lightning Paywall Balances', 'Paywall', 'manage_options', 'lnp_balances', array($this, 'balances_page'));
  }

  public function admin_init()
  {
    register_setting('lnp', 'lnp');
    add_settings_section('lnd', 'LND Config', null, 'lnp');

    add_settings_field('lnd_address', 'Address', array($this, 'field_lnd_address'), 'lnp', 'lnd');
    add_settings_field('lnd_macaroon', 'Macaroon', array($this, 'field_lnd_macaroon'), 'lnp', 'lnd');
    add_settings_field('lnd_cert', 'TLS Cert', array($this, 'field_lnd_cert'), 'lnp', 'lnd');

    add_settings_section('lnbits', 'LNbits Config', null, 'lnp');
    add_settings_field('lnbits_apikey', 'API Key', array($this, 'field_lnbits_apikey'), 'lnp', 'lnbits');

    add_settings_section('lnaddress', 'Lightning Address Config', null, 'lnp');
    add_settings_field('lnaddress_address', 'Lightning Address', array($this, 'field_lnaddress_address'), 'lnp', 'lnaddress');


    add_settings_section('lndhub', 'LNDHub Config', null, 'lnp');
    add_settings_field('lndhub_url', 'Lndhub url', array($this, 'field_lndhub_url'), 'lnp', 'lndhub');
    add_settings_field('lndhub_login', 'Lndhub Login', array($this, 'field_lndhub_login'), 'lnp', 'lndhub');
    add_settings_field('lndhub_password', 'Lndhub Password', array($this, 'field_lndhub_password'), 'lnp', 'lndhub');

    add_settings_section('paywall', 'Paywall Config', null, 'lnp');
    add_settings_field('paywall_text', 'Text', array($this, 'field_paywall_text'), 'lnp', 'paywall');
    add_settings_field('paywall_button_text', 'Button', array($this, 'field_paywall_button_text'), 'lnp', 'paywall');
    add_settings_field('paywall_amount', 'Amount', array($this, 'field_paywall_amount'), 'lnp', 'paywall');
    add_settings_field('paywall_total', 'Total', array($this, 'field_paywall_total'), 'lnp', 'paywall');
    add_settings_field('paywall_timeout', 'Timeout', array($this, 'field_paywall_timeout'), 'lnp', 'paywall');
    add_settings_field('paywall_timein', 'Timein', array($this, 'field_paywall_timein'), 'lnp', 'paywall');
    add_settings_field('paywall_all_amount', 'Amount for all', array($this, 'field_paywall_all_amount'), 'lnp', 'paywall');
    add_settings_field('paywall_all_period', 'Days available', array($this, 'field_paywall_all_days'), 'lnp', 'paywall');
    add_settings_field('paywall_all_confirmation', 'Confirmation text', array($this, 'field_paywall_all_confirmation'), 'lnp', 'paywall');
    add_settings_field('paywall_lnurl_rss', 'Add LNURL to RSS items', array($this, 'field_paywall_lnurl_rss'), 'lnp', 'paywall');
    add_settings_field('paywall_disable_in_rss', 'Disable paywall in RSS?', array($this, 'field_paywall_disable_in_rss'), 'lnp', 'paywall');
  }

  public function settings_page()
  {
?>
    <div class="wrap">
      <h1>Lightning Connection Settings</h1>
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
        <script>
          window.addEventListener("DOMContentLoaded", function() {
                document.getElementById('load_from_lndconnect').addEventListener('click', function(e) {
                    e.preventDefault();
                    var lndConnectUrl = prompt('Please enter your lndconnect string (e.g. run: lndconnect --url --port=8080)');
                    if (!lndConnectUrl) {
                      return ' }
                      var url = new URL(lndConnectUrl);
                      document.getElementById('lnp_lnd_address').value = 'https:' + url.pathname;
                      document.getElementById('lnp_lnd_macaroon').value = url.searchParams.get('macaroon');
                      document.getElementById('lnp_lnd_cert').value = url.searchParams.get('cert');
                    });
                });
        </script>
        <?php
        settings_fields('lnp');
        do_settings_sections('lnp');
        submit_button();
        ?>
      </form>
      <h3>Shortcodes</h3>
      <p>
        To configure each article the following shortcode attributes are available:
      </p>
      <ul>
        <li>amount</li>
        <li>total</li>
        <li>timein</li>
        <li>timeout</li>
      </ul>
    </div>
  <?php
  }

  public function balances_page()
  {
  ?>
    <div class="wrap">
      <h1>Lightning Paywall Settings</h1>
    </div>
    <form method="post" action="options.php">
      <?php
      settings_fields('lnp');
      do_settings_sections('lnp');
      submit_button();
      ?>
    </form>
<?php
  }
  public function field_lnd_address()
  {
    $help = 'e.g. https://127.0.0.1:8080 - or <a href="#" id="load_from_lndconnect">click here to load details from a lndconnect</a>';
    printf(
      '<input type="text" name="lnp[lnd_address]" id="lnp_lnd_address" value="%s" autocomplete="off" /><br>%s',
      esc_attr($this->options['lnd_address']),
      $help
    );
  }
  public function field_lnd_macaroon()
  {
    printf(
      '<input type="text" name="lnp[lnd_macaroon]" id="lnp_lnd_macaroon" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['lnd_macaroon']),
      'Invoices macaroon in HEX format'
    );
  }
  public function field_lnd_cert()
  {
    printf(
      '<input type="text" name="lnp[lnd_cert]" value="%s" id="lnp_lnd_cert" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['lnd_cert']),
      'TLS Certificate'
    );
  }
  public function field_lnbits_apikey()
  {
    printf(
      '<input type="text" name="lnp[lnbits_apikey]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['lnbits_apikey']),
      'LNbits Invoice/read key'
    );
  }
  public function field_lnaddress_address()
  {
    printf(
      '<input type="text" name="lnp[lnaddress_address]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['lnaddress_address']),
      'Lightning Address (e.g. you@payaddress.co) - only works if the vistor supports WebLN!'
    );
  }
  public function field_lndhub_url()
  {
    printf(
      '<input type="text" name="lnp[lndhub_url]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['lndhub_url']),
      'Lndhub Url'
    );
  }
  public function field_lndhub_login()
  {
    printf(
      '<input type="text" name="lnp[lndhub_login]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['lndhub_login']),
      'Lndhub Login'
    );
  }
  public function field_lndhub_password()
  {
    printf(
      '<input type="password" name="lnp[lndhub_password]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['lndhub_password']),
      'Lndhub Password'
    );
  }
  public function field_paywall_text()
  {
    printf(
      '<input type="text" name="lnp[paywall_text]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['paywall_text']),
      'Paywall text (use %s for the amount)'
    );
  }
  public function field_paywall_button_text()
  {
    printf(
      '<input type="text" name="lnp[button_text]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['button_text']),
      'Button text'
    );
  }
  public function field_paywall_amount()
  {
    printf(
      '<input type="number" name="lnp[amount]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['amount']),
      'Amount in sats per article'
    );
  }
  public function field_paywall_total()
  {
    printf(
      '<input type="number" name="lnp[total]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['total']),
      'Total amount to collect. After that amount the article will be free'
    );
  }
  public function field_paywall_timeout()
  {
    printf(
      '<input type="number" name="lnp[timeout]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['timeout']),
      'Make the article free X days after it is published'
    );
  }
  public function field_paywall_timein()
  {
    printf(
      '<input type="number" name="lnp[timein]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['timein']),
      'Enable the paywall x days after the article is published'
    );
  }
  public function field_paywall_all_amount()
  {
    printf(
      '<input type="number" name="lnp[all_amount]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['all_amount']),
      'Amount for all articles'
    );
  }
  public function field_paywall_all_days()
  {
    printf(
      '<input type="number" name="lnp[all_days]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['all_days']),
      'How many days should all articles be available'
    );
  }
  public function field_paywall_all_confirmation()
  {
    printf(
      '<input type="text" name="lnp[all_confirmation]" value="%s" autocomplete="off" /><br><label>%s</label>',
      esc_attr($this->options['all_confirmation']),
      'Confirmation text for all article payments'
    );
  }
  public function field_paywall_lnurl_rss()
  {
    printf(
      '<input type="checkbox" name="lnp[lnurl_rss]" value="1" %s/><br><label>%s</label>',
      empty($this->options['lnurl_rss']) ? '' : 'checked',
      'Add lightning payment details to RSS items'
    );
  }
  public function field_paywall_disable_in_rss()
  {
    printf(
      '<input type="checkbox" name="lnp[disable_paywall_in_rss]" value="1" %s/><br><label>%s</label>',
      empty($this->options['disable_paywall_in_rss']) ? '' : 'checked',
      'Disable paywall in RSS items / show full content in RSS.'
    );
  }
}

new WP_LN_Paywall();
