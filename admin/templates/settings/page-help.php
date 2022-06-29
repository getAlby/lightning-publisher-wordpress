<?php

// If this file is called directly, abort.
defined('WPINC') || die; ?>

<div class="wrap lnp">
    <h1><?php echo $this->get_page_title(); ?></h1>

    <h3>Shortcodes</h3>
    <p>
        To configure each article the following shortcode attributes are available:
    </p>
    <blockquote>
        <ul>
            <li>amount</li>
            <li>total</li>
            <li>timein</li>
            <li>timeout</li>
        </ul>
    </blockquote>

    <h3>Usage</h3>
    <blockquote>
        <p>[lnpaywall] eg: [lnpaywall amount="100"]</p>
    </blockquote>

</div>
