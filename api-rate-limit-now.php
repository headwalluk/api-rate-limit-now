<?php
/**
 * Plugin Name:       API Rate Limiter
 * Plugin URI:        https://headwall-hosting.com/plugins/api-rate-limit-now/
 * Description:       Rate-limit REST API calls by client IP address.
 * Version:           3.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * Author:            Paul Faulkner
 * Author URI:        https://headwall-hosting.com/a-web-guys-blog/
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       api-rate-limit-now
 * Domain Path:       /languages
 *
 * @package Api_Rate_Limiter
 */

defined( 'ABSPATH' ) || die();

define( 'WPTARL_VERSION', '3.0.0' );
define( 'WPTARL_FILE', __FILE__ );
define( 'WPTARL_BASENAME', plugin_basename( __FILE__ ) );

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/functions-private.php';
require_once __DIR__ . '/includes/class-plugin.php';
require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-admin-hooks.php';
require_once __DIR__ . '/includes/class-log.php';

// GitHub updates: admin, cron and WP-CLI only.
if ( is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once __DIR__ . '/includes/class-github-updater.php';
	new Api_Rate_Limiter\Github_Updater( WPTARL_FILE, Api_Rate_Limiter\UPDATER_GITHUB_REPO, WPTARL_VERSION, Api_Rate_Limiter\FILTER_UPDATER_ENABLED );
}

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
 * Create the log table and schedule the prune event on plugin activation.
 *
 * @since 3.0.0
 */
function wptarl_activate(): void {
	Api_Rate_Limiter\Plugin::maintain();
}
register_activation_hook( __FILE__, 'wptarl_activate' );

/**
 * Clear the scheduled cron event on plugin deactivation.
 *
 * @since 2.0.0
 */
function wptarl_deactivate(): void {
	wp_clear_scheduled_hook( Api_Rate_Limiter\CRON_PRUNE_LOG );
}
register_deactivation_hook( __FILE__, 'wptarl_deactivate' );
