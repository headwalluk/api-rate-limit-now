<?php
/**
 * Uninstall handler.
 *
 * Runs when the plugin is deleted from the Plugins screen, not on deactivation.
 * Deletes nothing unless the "Delete all plugin data when uninstalled" setting is on.
 *
 * @package Api_Rate_Limiter
 */

namespace Api_Rate_Limiter;

defined( 'WP_UNINSTALL_PLUGIN' ) || die();

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/includes/class-github-updater.php';

wp_clear_scheduled_hook( CRON_PRUNE_LOG );

$wptarl_delete_data = (bool) filter_var(
	get_option( OPT_DELETE_DATA_ON_UNINSTALL, DEF_DELETE_DATA_ON_UNINSTALL ),
	FILTER_VALIDATE_BOOLEAN
);

if ( $wptarl_delete_data ) {
	global $wpdb;

	// Options and the per-IP rate-limit transients are found by prefix; each is then
	// deleted through the WordPress API so object caches are cleared with it.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off prefix lookup at uninstall; there is no API for it.
	$wptarl_option_names = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( OPTION_PREFIX ) . '%'
		)
	);

	// Under a persistent object cache, transients never reach the options table; they then
	// expire on their own within the rate-limit interval.
	$wptarl_transient_names = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_' . TRANSIENT_PREFIX ) . '%'
		)
	);

	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . LOG_TABLE ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Table name built from constants.
	// phpcs:enable

	foreach ( $wptarl_option_names as $wptarl_option_name ) {
		delete_option( $wptarl_option_name );
	}

	foreach ( $wptarl_transient_names as $wptarl_transient_name ) {
		delete_transient( substr( $wptarl_transient_name, strlen( '_transient_' ) ) );
	}

	Github_Updater::delete_cache( dirname( plugin_basename( __FILE__ ) ) );
}
