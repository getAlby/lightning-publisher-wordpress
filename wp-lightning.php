<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 * 
 * @since             1.0.0
 * @package           WP_Lightning
 *
 * @wordpress-plugin
 * Plugin Name:       WP Lightning Paywall
 * Description:       Wordpress content paywall using the lightning network. Directly connected to an LND node
 * Version:           1.0.0
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lnp-alby
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'WP_LIGHTNING_VERSION', '1.0.0' );
define('WP_LN_PAYWALL_JWT_KEY', hash_hmac('sha256', 'lnp-alby', AUTH_KEY));
define('WP_LN_PAYWALL_JWT_ALGORITHM', 'HS256');
define('WP_LN_ROOT_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('WP_LN_ROOT_URI', untrailingslashit(plugin_dir_url(__FILE__)));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-lightning-activator.php
 */
function activate_wp_lightning() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-lightning-activator.php';
	WP_lightning_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-lightning-deactivator.php
 */
function deactivate_wp_lightning() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-lightning-deactivator.php';
	WP_lightning_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_lightning' );
register_deactivation_hook( __FILE__, 'deactivate_wp_lightning' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-lightning.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_lightning() {

	$plugin = new WP_Lightning();
	$plugin->run();

}
run_wp_lightning();