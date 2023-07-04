<?php

// If this file is called directly, abort.
defined('WPINC') || die;

/**
 * Lightning Paywall related functionalities.
 *
 * Lightning Paywall Class to manage the paywall initialization on
 * a given content.
 *
 * When applied to a content, it searches for the [lnpaywall ] shortcode,
 * parses the available options provided in the shortcode,
 * merges the provided options with the database options
 * and separates the protected and public part of the content.
 *
 * `the_content` filter is attached to the get_content function.
 * The get_content function hides the protected content by default
 * and shows the payment buttons.
 *
 * Depending on the post payment details, the get_content shows
 * the full content or the teaser.
 *
 * @since      1.0.0
 * @package    BLN_Publisher
 * @subpackage BLN_Publisher/includes
 */
class BLN_Publisher_Paywall
{
    /**
     * Main Plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    BLN_Publisher    $plugin    The main plugin object.
     */
    private $plugin;

    /**
     * ID of the post to which the paywall is applied
     *
     * @since  1.0.0
     * @access protected
     * @var    number    $post_id    Post ID
     */
    protected $post_id;

    /**
     * Length of the content.
     *
     * @since  1.0.0
     * @access protected
     * @var    int    $content_length    Length of the content.
     */
    protected $content_length;
    /**
     * Full content that the Paywall is blocking.
     *
     * @since  1.0.0
     * @access protected
     * @var    string    $content    Full content that the Paywall is blocking.
     */
    protected $content;

    /**
     * Teaser of the content.
     *
     * @since  1.0.0
     * @access protected
     * @var    string    $teaser    Teaser of the full content blocked by the Paywall.
     */
    protected $teaser;

    /**
     * Protected content of the content.
     *
     * @since  1.0.0
     * @access protected
     * @var    string    $protected_content    Protected content of the full content blocked by the Paywall.
     */
    protected $protected_content;

    /**
     * Status of the Paywall.
     *
     * On - 1 (Hide the protected content)
     * Off - 0 (Show the protected content)
     *
     * @since  1.0.0
     * @access protected
     * @var    boolean    $status    On/Off status of the Paywall.
     */
    protected $status = 1;

    /**
     * Default Paywall Options.
     *
     * @since  1.0.0
     * @access protected
     * @var    array    $options    Paywall options for displaying the shortcode.
     */
    protected $options = [
        'description' => null,
        'button_text'  => 'Pay now',
        'amount'       => null,
        'currency'     => 'btc',
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
     * @since 1.0.0
     */
    public function __construct($plugin, $args)
    {
        $this->plugin = $plugin;
        $this->content = $args['content'];
        $this->post_id = $args['post_id'];
        $this->content_length = strlen(wp_strip_all_tags($this->content));

        if ($this->content_has_shortcode()) {
            // Filter empty shortcode options as they should default to the global settings
            $shortcode_options = array_filter($this->extract_options_from_shortcode());
            $options_from_database = array_filter($this->plugin->getPaywallOptions());

            $this->options = array_merge($this->options, $options_from_database, $shortcode_options);
        } else {
            // If no shortcode found, do not enable the paywall
            $this->status = 0;
        }

        $this->split_public_protected();
    }


    /**
     * Get the content protected by Paywall
     *
     * @since  1.0.0
     * @return string Returns the entire if Paywall is Off, only the teaser if Paywall is On
     */
    public function get_content()
    {
        if ($this->status === 1) {
            $show_paid = false;
            if ($this->options['disable_in_rss'] && is_feed()) {
                $show_paid = true;
            }

            if (!empty($this->options['timeout']) && time() > get_post_time('U') + $this->options['timeout'] * 60 * 60) {
                $show_paid = true;
            }

            if (!empty($this->options['timein']) && time() < get_post_time('U') + $this->options['timein'] * 60 * 60) {
                $show_paid = true;
            }

            if (!empty($this->options['total'])) {
                $amount_received = get_post_meta($this->post_id, '_bln_amount_received', true);
                if ($amount_received >= $this->options['total']) {
                    $show_paid = true;
                }
            }

            if ($this->plugin->has_paid_for_post($this->post_id)) {
                $show_paid = true;
            }
            if (function_exists('wp_bln_has_paid_for_post')) {
                $show_paid = wp_bln_has_paid_for_post($show_paid, $this->post_id);
            }
            $show_paid = apply_filters('wp_bln_has_paid_for_post', $show_paid, $this->post_id);

            if ($show_paid) {
                return $this->format_paid();
            } else {
                return $this->format_unpaid();
            }
        }
        // if disabled
        return $this->format_paid();
    }

    /**
     * Get the options of the Paywall
     */
    public function get_options()
    {
        return $this->options;
    }

    /**
     * Get the formatted amount
     */
    public function get_formatted_amount()
    {
        if ($this->options['currency'] != 'btc') {
            // NumberFormatter relies on some i18n package that not all hosters have available
            // $locale = get_locale();
            // $currency_formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            // $currency_formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $this->options['currency']);
            // return $currency_formatter->format(intval($this->options['amount']) / 100.0); // we have cents, but the formatter assumes "dollars" - we only support usd,eur,gbp (all have 2 digits for cents)

            // amount is in cents - we want to show USD/EUR/GBP values
            return number_format_i18n(intval($this->options['amount']) / 100.0, 2) . " " . strtoupper($this->options['currency']);
        } else {
            return number_format_i18n(floatval($this->options['amount']), 0) . " sats";
        }
    }

    /**
     * Get the protected content
     */
    public function get_protected_content()
    {
        return $this->protected_content;
    }

    /**
     * Get the public content
     */
    public function get_public_content()
    {
        return $this->$teaser;
    }

    /**
     * Returns true if the content contains the lnpaywall shortcode
     *
     * @since  1.0.0
     * @return boolean true if the content contains the shortcode
     */
    protected function content_has_shortcode()
    {
        if (preg_match('/\[lnpaywall(.*)\]/i', $this->content, $m)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Locate the shortcode/marker from the content for the paywall.
     *
     * @since  1.0.0
     * @return array Array of shortcode properties of the paywall
     */
    protected function extract_options_from_shortcode()
    {
        if (preg_match('/\[lnpaywall(.*)\]/i', $this->content, $m)) {
            $atts_string = $m[1];
            // wptexturize might replace the quotes in the shortcode we try to make this undone
            // maybe related: https://github.com/WordPress/gutenberg/issues/37754 + https://github.com/elementor/elementor/issues/9340
            // replacing potential quotes that I found with normal quotes (`"`) that can be parsed by `shortcode_parse_atts`
            // NOTE: this means none of those character can be used in the paywall attributes (button text, description, etc.)

            $atts_string = html_entity_decode($atts_string); // some filter might turn the shortcode into html entities
            $invalid_quotes = array("＂", "ˮ", "ʺ", "“", "”", "˝", "‟", "″", "‘", "’", "`", "´");
            foreach($invalid_quotes as $invalid_char) {
                // trying to match only the relevant quotes (not the ones that might be used in the text
                $atts_string = str_replace("=". $invalid_char, '="', $atts_string); // replace the quotes at the beginning (after an = sign
                $atts_string = preg_replace('/' . $invalid_char . '(\s|\z)/', '" ', $atts_string); // replace the quotes at the end of an attribute (either followed by a whitespace or the end of the string)
            }

            $atts = shortcode_parse_atts($atts_string);
            if ($atts == "") {
                return [];
            } else {
                return $atts;
            }
        }
        return [];
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
        $template_default  = BLN_PUBLISHER_ROOT_PATH . '/public/templates/paywall-button-unpaid.php';
        $template_override = get_stylesheet_directory() . '/lightning-publisher-wordpress/paywall-button-unpaid.php';
        
        // Use template from theme folder
        if ( file_exists($template_override) )
        {
            $template = $template_override;
        }
        // Use default template from plugin folder
        else
        {
            $template = $template_default;
        }

        

        /**
         * Allow template override with the hook
         *
         * @param $template string  Absolute path to template file
         * @param $this     object  Plugin instance
         */
        $template = apply_filters( 'bln_paywall_unpaid_button_template', $template );

        ob_start();
            
            // Class instance with all plugin data
            $plugin = $this;

            // Load template file
            include_once $template;
        
        $return = ob_get_clean();
        
        return $return;
    }

    /**
     * Replaces text place holders (e.g. %{amount} with the actual value)
     */
    protected function format_label($text)
    {
        return strtr($text, [
            '%{amount}' => $this->options['amount'],
            '%{currency}' => $this->options['currency'],
            '%{formatted_amount}' => $this->get_formatted_amount(),
            '%{length}' => $this->content_length
        ]);

    }

    /**
     * Split the teaser and the protected content
     */
    protected function split_public_protected()
    {
        list($this->teaser, $this->protected_content) = array_pad(preg_split('/(<p>)?\[lnpaywall.*\](<\/p>)?/', $this->content, 2), 2, null);
    }
}
