<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @since   1.0.0
 * @package BLN_Publisher
 *
 * @wordpress-plugin
 * Plugin Name:       Bitcoin Lightning Publisher
 * Description:       Bitcoin Lightning Publisher is a Paywall and Donation plugin for WordPress to accept instant Bitcoin Lightning payments and donations directly to your favorit wallet.
 * Version:           1.4.1
 * License:           GPL-3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       lnp-alby
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC') ) {
    die;
}

define('BLN_PUBLISHER_VERSION', '1.4.1');
define('BLN_PUBLISHER_PAYWALL_JWT_KEY', hash_hmac('sha256', 'lnp-alby', AUTH_KEY));
define('BLN_PUBLISHER_PAYWALL_JWT_ALGORITHM', 'HS256');
define('BLN_PUBLISHER_ROOT_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('BLN_PUBLISHER_ROOT_URI', untrailingslashit(plugin_dir_url(__FILE__)));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-bln-publisher-activator.php
 */
function activate_bln_publisher()
{
    include_once plugin_dir_path(__FILE__) . 'includes/class-bln-publisher-activator.php';
    BLN_Publisher_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-bln-publisher-deactivator.php
 */
function deactivate_bln_publisher()
{
    include_once plugin_dir_path(__FILE__) . 'includes/class-bln-publisher-deactivator.php';
    BLN_Publisher_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_bln_publisher');
register_deactivation_hook(__FILE__, 'deactivate_bln_publisher');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-bln-publisher.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function run_bln_publisher()
{

    $plugin = new BLN_Publisher();
    $plugin->run();

}
run_bln_publisher();
