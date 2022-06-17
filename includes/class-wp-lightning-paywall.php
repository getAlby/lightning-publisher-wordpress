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
     * Main Plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      WP_Lightning    $plugin    The main plugin object.
     */
    private $plugin;

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
     * Protected content of the content.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $protected_content    Protected content of the full content blocked by the Paywall.
     */
    protected $protected_content;

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
    public function __construct($plugin, $content)
    {
        $this->plugin = $plugin;
        $this->content = $content;

        $shortcode_options = $this->extract_shortcode();
        if (!empty($shortcode_options)) {
            $database_options = $this->plugin->getPaywallOptions();
            $this->options = array_merge($this->options, $database_options, $shortcode_options);
        } else {
            // If no shortcode found, do not enable the paywall
            $this->status = 0;
        }
        $this->splitPublicProtected();
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
     * Split the teaser and the protected content
     */
    public function splitPublicProtected()
    {
        list($this->teaser, $this->protected_content) = array_pad(preg_split('/(<p>)?\[ln.+\](<\/p>)?/', $this->content, 2),2,null);
    }

    /**
     * Format display for paid post
     */
    protected function format_paid()
    {
        return sprintf('%s%s', $this->teaser, $this->protected_content);
    }

    /**
     * Format display for unpaid post. Injects the payment request HTML
     */
    protected function format_unpaid()
    {
        $text   = '<p>' . sprintf(empty($this->options['paywall_text']) ? 'To continue reading the rest of this post, please pay <em>%s Sats</em>.' : $this->options['paywall_text'], $this->options['amount']) . '</p>';
        $button = sprintf('<button class="wp-lnp-btn">%s</button>', empty($this->options['button_text']) ? 'Pay now' : $this->options['button_text']);
        // $autopay = '<p><label><input type="checkbox" value="1" class="wp-lnp-autopay" />Enable autopay<label</p>';
        return sprintf('%s<div id="wp-lnp-wrapper" class="wp-lnp-wrapper" data-lnp-postid="%d">%s%s</div>', $this->teaser, get_the_ID(), $text, $button);
    }

    /**
     * Get the content protected by Paywall
     *
     * @since     1.0.0
     * @return string Returns the entire if Paywall is Off, only the teaser if Paywall is On
     */
    public function getContent()
    {
        if ($this->status === 1) {
            if ($this->options['disable_in_rss'] && is_feed()) {
                return $this->format_paid();
            }
    
            if (!empty($this->options['timeout']) && time() > get_post_time('U') + $this->options['timeout'] * 24 * 60 * 60) {
                return $this->format_paid();
            }
    
            if (!empty($this->options['timein']) && time() < get_post_time('U') + $this->options['timein'] * 24 * 60 * 60) {
                return $this->format_paid();
            }
    
            $amount_received = get_post_meta(get_the_ID(), '_lnp_amount_received', true);
            if (!empty($this->options['total']) && $amount_received >= $this->options['total']) {
                return $this->format_paid();
            }
    
            if (WP_Lightning::has_paid_for_all()) {
                return $this->format_paid();
            }
    
            if (WP_Lightning::has_paid_for_post(get_the_ID())) {
                return $this->format_paid();
            }
    
            return $this->format_unpaid();
        }
        return $this->format_paid();
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
    public function getProtectedContent()
    {
        return $this->protected_content;
    }
}
