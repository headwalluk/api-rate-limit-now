<?php
/**
 * Log class.
 *
 * Handles the rate-limit log table: creation, inserts, queries, and pruning.
 *
 * @package Api_Rate_Limiter
 */

namespace Api_Rate_Limiter;

defined( 'ABSPATH' ) || die();

/**
 * Log class.
 *
 * @since 2.0.0
 */
class Log {

	/**
	 * Get the full table name including the WordPress prefix.
	 *
	 * @since 2.0.0
	 *
	 * @return string The prefixed table name.
	 */
	public static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . LOG_TABLE;
	}

	/**
	 * Create or update the log table. Safe to call multiple times (uses dbDelta).
	 *
	 * @since 2.0.0
	 */
	public static function create_table(): void {
		global $wpdb;

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			client_ip varchar(45) NOT NULL,
			blocked_at datetime NOT NULL,
			request_uri varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY client_ip (client_ip),
			KEY blocked_at (blocked_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( OPT_DB_VERSION, DB_VERSION );
	}

	/**
	 * Check whether the log table needs creating or updating.
	 *
	 * @since 2.0.0
	 */
	public static function maybe_create_table(): void {
		$current_version = absint( get_option( OPT_DB_VERSION, 0 ) );

		if ( $current_version < DB_VERSION ) {
			self::create_table();
		}
	}

	/**
	 * Record a blocked request in the log.
	 *
	 * @since 2.0.0
	 *
	 * @param string $client_ip The blocked client's IP address.
	 */
	public static function record_block( string $client_ip ): void {
		$logging_enabled = (bool) filter_var(
			get_option( OPT_LOGGING_ENABLED, DEF_LOGGING_ENABLED ),
			FILTER_VALIDATE_BOOLEAN
		);

		if ( ! $logging_enabled ) {
			return;
		}

		global $wpdb;

		// Redact before sanitizing and truncating: sanitize_text_field() strips %XX octets from names, and
		// truncating first could cut a parameter name short and let its value through.
		$request_uri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wptarl_redact_request_uri( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by sanitize_text_field() after redaction.
			: '';

		if ( mb_strlen( $request_uri ) > LOG_REQUEST_URI_MAX_LENGTH ) {
			$request_uri = mb_substr( $request_uri, 0, LOG_REQUEST_URI_MAX_LENGTH );
		}

		$now = new \DateTime( 'now', wp_timezone() );

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			self::table_name(),
			array(
				'client_ip'   => $client_ip,
				'blocked_at'  => $now->format( 'Y-m-d H:i:s' ),
				'request_uri' => $request_uri,
			),
			array( '%s', '%s', '%s' )
		);
	}

	/**
	 * Get recent log entries.
	 *
	 * @since 2.0.0
	 *
	 * @param int $limit Maximum number of rows to return.
	 *
	 * @return array<object> Array of log row objects.
	 */
	public static function get_recent( int $limit = 50 ): array {
		global $wpdb;

		$table_name = self::table_name();

		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT client_ip, blocked_at, request_uri FROM {$table_name} ORDER BY blocked_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe
				$limit
			)
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Delete log entries older than the retention period.
	 *
	 * @since 2.0.0
	 */
	public static function prune(): void {
		global $wpdb;

		$retention_days = absint( get_option( OPT_LOG_RETENTION, DEF_LOG_RETENTION ) );

		if ( 0 === $retention_days ) {
			$retention_days = DEF_LOG_RETENTION;
		}

		$table_name = self::table_name();
		$cutoff     = new \DateTime( "-{$retention_days} days", wp_timezone() );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE blocked_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe
				$cutoff->format( 'Y-m-d H:i:s' )
			)
		);

		// Also cap total rows as a safety net.
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $count > LOG_MAX_ROWS ) {
			$excess = $count - LOG_MAX_ROWS;
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"DELETE FROM {$table_name} ORDER BY blocked_at ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe
					$excess
				)
			);
		}
	}

	/**
	 * Clear all log entries.
	 *
	 * @since 2.0.0
	 */
	public static function clear(): void {
		global $wpdb;

		$table_name = self::table_name();

		$wpdb->query( "TRUNCATE TABLE {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe
	}
}
