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
