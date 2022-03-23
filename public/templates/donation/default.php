<?php

// If this file is called directly, abort.
defined('WPINC') || die;

/**
 * Hook for programatically adding something before widget
 */
do_action( 'before_lnp_alby_donation_widget' );

/**
 * 
 * @var $wplnp array of user options from 'Donations' settings page
 *
 * Check what's inside by using:
 *
 * printf('<pre>%s</pre>', print_r($wplnp, true) );
 * 
 */

// ?printf('<pre>%s</pre>', print_r($wplnp, true) );
?>
<div class="wp-lnp-donations-wrapper">
    <div class="wpl-donation-box">
        <div class="span-qr"></div>
        <div class="error"></div>
        <div class="span-address">
            <?php

            // Title
            if ( ! empty($wplnp['widget_title']) )
            {
                printf(
                    '<h2>%s</h2>',
                    sanitize_text_field($wplnp['widget_title'])
                );
            }

            // Description
            if ( ! empty($wplnp['widget_description']) )
            {
                printf(
                    '<p>%s</p>',
                    sanitize_text_field($wplnp['widget_description'])
                );
            }

            /**
             * Note:
             * If you manually modify template, class .wpl-donate-amount is required
             * Or Js will not work
             */
            ?>
            <p class="wpl-donate-amount-wrap">
                <label><?php _e('Donation Amount', 'lnp-alby') ?></label>
                <input
                    type="number"
                    value="<?php echo intval($wplnp['widget_amount']); ?>"
                    class="wpl-donate-amount regular-text">
                <span class="wplnp-donate-currency">SATS</span>
            </p>

            <?php /* p class="wpl-donate-address-wrap">
                <input
                    type="text"
                    value="<?php echo sanitize_text_field($wplnp['lnd_address']); ?>"
                    class="wpl-donate-address regular-text"
                    onclick="this.select()"
                    readonly>
            </p */ 


            // Button
            printf(
                '<button class="wpl-donate-button wp-lnp-btn" data-post-id="%d">%s</button>',
                get_the_ID(),
                $wplnp['widget_button_label']
            ); ?>
        </div>
    </div>
</div>
