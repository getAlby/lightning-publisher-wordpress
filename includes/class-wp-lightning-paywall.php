<?php

use \Firebase\JWT;

/**
 * Lightning Paywall related functionalities.
 *
 * @since      1.0.0
 * @package    WP_Lightning
 * @subpackage WP_Lightning/includes
 */
class WP_Lightning_Paywall
{

    /**
     * Full content that the Paywall is blocking.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $content    Full content that the Paywall is blocking.
     */
    protected $content;

    /**
     * Teaser of the content.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $teaser    Teaser of the full content blocked by the Paywall.
     */
    protected $teaser;

    /**
     * Status of the Paywall.
     *
     * @since    1.0.0
     * @access   protected
     * @var      boolean    $status    On/Off status of the Paywall.
     */
    protected $status = 1;

    /**
     * Paywall Options.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $options    Paywall options for displaying the shortcode.
     */
    protected $options = [
        'paywall_text' => null,
        'button_text'  => null,
        'amount'       => null,
        'total'        => null,
        'timeout'      => null,
        'timein'       => null,
        'disable_in_rss' => null,
    ];

    /**
     * Setup the paywall.
     *
     * Set the paywall options and locate markers in the content.
     *
     * @since    1.0.0
     */
    public function __construct($content)
    {
        $this->content = $content;

        $shortcode_options = $this->extract_shortcode();
        $paywall_page = new LNP_PaywallPage($this, 'lnp_settings');
        $database_options = $paywall_page->options;
        $this->options = array_merge($this->options, $database_options, $shortcode_options);

        $this->setTeaser();
    }

    /**
     * Locate the shortcode/marker from the content for the paywall.
     *
     * @since     1.0.0
     * @return array Array of shortcode properties of the paywall
     */
    protected function extract_shortcode()
    {
        if (preg_match('/\[ln(.+)\]/i', $this->content, $m)) {
            return shortcode_parse_atts($m[1]);
        }
        return [];
    }

    /**
     * Set the teaser of the content
     */
    protected function setTeaser()
    {
        $this->teaser = preg_split('/(<p>)?\[ln.+\](<\/p>)?/', $this->content, 2)[0];
    }

    /**
     * Check if paid for all
     */
    protected static function has_paid_for_all()
    {
        $wplnp = null;
        if (isset($_COOKIE['wplnp'])) {
            $wplnp = $_COOKIE['wplnp'];
        } elseif (isset($_GET['wplnp'])) {
            $wplnp = $_GET['wplnp'];
        }
        if (empty($wplnp)) return false;
        try {
            $jwt = JWT\JWT::decode($wplnp, new JWT\Key(WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM));
            return $jwt->{'all_until'} > time();
        } catch (Exception $e) {
            //setcookie("wplnp", "", time() - 3600, '/'); // delete invalid JWT cookie
            return false;
        }
    }

    protected static function get_paid_post_ids()
    {
        $wplnp = null;
        if (isset($_COOKIE['wplnp'])) {
            $wplnp = $_COOKIE['wplnp'];
        } elseif (isset($_GET['wplnp'])) {
            $wplnp = $_GET['wplnp'];
        }
        if (empty($wplnp)) return [];
        try {
            $jwt = JWT\JWT::decode($wplnp, new JWT\Key(WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM));
            $paid_post_ids = $jwt->{'post_ids'};
            if (!is_array($paid_post_ids)) return [];

            return $paid_post_ids;
        } catch (Exception $e) {
            //setcookie("wplnp", "", time() - 3600, '/'); // delete invalid JWT cookie
            return [];
        }
    }

    protected static function has_paid_for_post($post_id)
    {
        $paid_post_ids = self::get_paid_post_ids();
        return in_array($post_id, $paid_post_ids);
    }

    /**
     * Store the post_id in an cookie to remember the payment
     * and increment the paid amount on the post
     * must only be called once (can be exploited currently)
     */
    public static function save_as_paid($post_id, $amount_paid = 0)
    {
        $paid_post_ids = self::get_paid_post_ids();
        if (!in_array($post_id, $paid_post_ids)) {
            $amount_received = get_post_meta($post_id, '_lnp_amount_received', true);
            if (is_numeric($amount_received)) {
                $amount = $amount_received + $amount_paid;
            } else {
                $amount = $amount_paid;
            }
            update_post_meta($post_id, '_lnp_amount_received', $amount);

            array_push($paid_post_ids, $post_id);
        }
        $jwt = JWT\JWT::encode(array('post_ids' => $paid_post_ids), WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM);
        setcookie('wplnp', $jwt, time() + time() + 60 * 60 * 24 * 180, '/');
    }

    public static function save_paid_all($days)
    {
        $paid_post_ids = self::get_paid_post_ids();
        $jwt = JWT\JWT::encode(array('all_until' => time() + $days * 24 * 60 * 60, 'post_ids' => $paid_post_ids), WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM);
        setcookie('wplnp', $jwt, time() + time() + 60 * 60 * 24 * 180, '/');
    }

    /**
     * Get the content protected by Paywall
     *
     * @since     1.0.0
     * @return string Returns the entire if Paywall is Off, only the teaser if Paywall is On
     */
    public function getContent()
    {
        if ($this->options['disable_in_rss'] && is_feed()) {
            return $this->content;
        }

        if (!empty($this->options['timeout']) && time() > get_post_time('U') + $this->options['timeout'] * 24 * 60 * 60) {
            return $this->content;
        }

        if (!empty($this->options['timein']) && time() < get_post_time('U') + $this->options['timein'] * 24 * 60 * 60) {
            return $this->content;
        }

        $amount_received = get_post_meta(get_the_ID(), '_lnp_amount_received', true);
        if (!empty($this->options['total']) && $amount_received >= $this->options['total']) {
            return $this->content;
        }

        if (self::has_paid_for_all()) {
            return $this->content;
        }

        if (self::has_paid_for_post(get_the_ID())) {
            return $this->content;
        }

        return $this->teaser;
    }

    /**
     * Get the options of the Paywall
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get the protected content
     */
    public function getProtectedContent() {
        return trim($this->content, $this->teaser);
    }
}
