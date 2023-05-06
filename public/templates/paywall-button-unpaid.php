<?php

// If this file is called directly, abort.
defined('WPINC') || die; 

$button = sprintf(
    '<button class="wp-lnp-btn">%s</button>',
    $plugin->format_label($plugin->options['button_text'])
);


$description = '';

if (!empty($plugin->options['description']))
{
    $description = sprintf(
        '<p class="wp-lnp-description">%s</p>',
        $plugin->format_label($plugin->options['description'])
    );
}

printf(
    '%s<div id="wp-lnp-wrapper" class="wp-lnp-wrapper">%s%s</div>',
    $plugin->teaser,
    $description,
    $button
);