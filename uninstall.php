<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @since 1.0.0
 *
 * @package BLN_Publisher
 */

// If uninstall not called from WordPress, then exit.
if (! defined('WP_UNINSTALL_PLUGIN') ) {
    exit;
}

// Drop a custom database table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}lightning_publisher_payments");

delete_option( 'lnp_connection' );
delete_option( 'lnp_general' );
delete_option( 'lnp_paywall' );
