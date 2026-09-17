<?php
/**
 * Uninstall handler.
 *
 * Cleans up all plugin data when the plugin is deleted via the WordPress admin.
 *
 * @package Api_Rate_Limiter
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || die();

// Remove options.
delete_option( 'wptarl_seconds_between_calls' );
delete_option( 'wptarl_rate_limit_all_guests' );
delete_option( 'wptarl_rate_limited_ips' );
delete_option( 'wptarl_never_rate_limited_ips' );
delete_option( 'wptarl_rate_limited_user_agents' );
delete_option( 'wptarl_logging_enabled' );
delete_option( 'wptarl_log_retention' );
delete_option( 'wptarl_db_version' );

// Remove cached GitHub release lookups.
delete_transient( 'wptarl_github_release' );
delete_transient( 'wptarl_github_failed' );

// Drop the log table.
global $wpdb;
$wptarl_table_name = $wpdb->prefix . 'wptarl_log';
$wpdb->query( "DROP TABLE IF EXISTS {$wptarl_table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// Clear scheduled cron.
wp_clear_scheduled_hook( 'wptarl_prune_log' );
