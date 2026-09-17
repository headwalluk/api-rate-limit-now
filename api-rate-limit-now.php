<?php
/**
 * Plugin Name:  API Rate Limiter
 * Plugin URI:   https://wp-tutorials.tech/optimise-wordpress/rate-limit-wordpress-api-calls/
 * Description:  Rate-limit REST API calls by client IP address.
 * Version:      2.0.0
 * Author:       Paul Faulkner
 * Author URI:   https://wp-tutorials.tech/
 * License:      GPLv2 or later
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  api-rate-limit-now
 *
 * @package Api_Rate_Limiter
 */

defined( 'ABSPATH' ) || die();

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/functions-private.php';
require_once __DIR__ . '/includes/class-plugin.php';
require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-admin-hooks.php';
require_once __DIR__ . '/includes/class-log.php';

/**
 * Initialise the plugin.
 *
 * @since 2.0.0
 */
function wptarl_init(): void {
	global $wptarl_plugin;

	$wptarl_plugin = new Api_Rate_Limiter\Plugin();
	$wptarl_plugin->run( __FILE__ );
}
wptarl_init();

/**
 * Clear the scheduled cron event on plugin deactivation.
 *
 * @since 2.0.0
 */
function wptarl_deactivate(): void {
	wp_clear_scheduled_hook( 'wptarl_prune_log' );
}
register_deactivation_hook( __FILE__, 'wptarl_deactivate' );
