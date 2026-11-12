<?php
/**
 * Settings class.
 *
 * Registers plugin settings with the WordPress Settings API.
 *
 * @package Api_Rate_Limiter
 */

namespace Api_Rate_Limiter;

defined( 'ABSPATH' ) || die();

/**
 * Settings class.
 *
 * @since 2.0.0
 */
class Settings {

	/**
	 * Register all settings, sections, and fields.
	 *
	 * @since 2.0.0
	 */
	public function register_settings(): void {
		register_setting(
			SETTINGS_GROUP,
			OPT_SECONDS_BETWEEN_CALLS,
			[
				'type'              => 'integer',
				'sanitize_callback' => [ $this, 'sanitize_seconds_between_calls' ],
				'default'           => DEF_SECONDS_BETWEEN_CALLS,
			]
		);

		register_setting(
			SETTINGS_GROUP,
			OPT_RATE_LIMIT_ALL_GUESTS,
			[
				'type'              => 'boolean',
				'sanitize_callback' => [ $this, 'sanitize_boolean' ],
				'default'           => DEF_RATE_LIMIT_ALL_GUESTS,
			]
		);

		register_setting(
			SETTINGS_GROUP,
			OPT_RATE_LIMITED_IPS,
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_ip_list' ],
				'default'           => DEF_RATE_LIMITED_IPS,
			]
		);

		register_setting(
			SETTINGS_GROUP,
			OPT_NEVER_RATE_LIMITED_IPS,
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_ip_list' ],
				'default'           => DEF_NEVER_RATE_LIMITED_IPS,
			]
		);

		register_setting(
			SETTINGS_GROUP,
			OPT_NEVER_RATE_LIMITED_ROUTES,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_route_list' ),
				'default'           => DEF_NEVER_RATE_LIMITED_ROUTES,
			)
		);

		register_setting(
			SETTINGS_GROUP,
			OPT_RATE_LIMITED_USER_AGENTS,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_user_agent_list' ),
				'default'           => DEF_RATE_LIMITED_USER_AGENTS,
			)
		);

		register_setting(
			SETTINGS_GROUP,
			OPT_LOGGING_ENABLED,
			[
				'type'              => 'boolean',
				'sanitize_callback' => [ $this, 'sanitize_boolean' ],
				'default'           => DEF_LOGGING_ENABLED,
			]
		);

		register_setting(
			SETTINGS_GROUP,
			OPT_LOG_RETENTION,
			[
				'type'              => 'integer',
				'sanitize_callback' => [ $this, 'sanitize_log_retention' ],
				'default'           => DEF_LOG_RETENTION,
			]
		);

		register_setting(
			SETTINGS_GROUP,
			OPT_DELETE_DATA_ON_UNINSTALL,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_boolean' ),
				'default'           => DEF_DELETE_DATA_ON_UNINSTALL,
			)
		);
	}

	/**
	 * Sanitize the seconds between calls value.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Raw input value.
	 *
	 * @return int Seconds between SECONDS_BETWEEN_CALLS_MIN and SECONDS_BETWEEN_CALLS_MAX.
	 */
	public function sanitize_seconds_between_calls( mixed $value ): int {
		return $this->sanitize_range( $value, SECONDS_BETWEEN_CALLS_MIN, SECONDS_BETWEEN_CALLS_MAX, DEF_SECONDS_BETWEEN_CALLS );
	}

	/**
	 * Sanitize a boolean option value.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Raw input value.
	 *
	 * @return bool Sanitized boolean.
	 */
	public function sanitize_boolean( mixed $value ): bool {
		return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Sanitize the log retention days value.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Raw input value.
	 *
	 * @return int Days between LOG_RETENTION_MIN and LOG_RETENTION_MAX.
	 */
	public function sanitize_log_retention( mixed $value ): int {
		return $this->sanitize_range( $value, LOG_RETENTION_MIN, LOG_RETENTION_MAX, DEF_LOG_RETENTION );
	}

	/**
	 * Return a whole number within the bounds, or the fallback when the value is outside them or not numeric.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $value    Raw input value.
	 * @param int   $minimum  Lowest accepted value.
	 * @param int   $maximum  Highest accepted value.
	 * @param int   $fallback Value used when the input is out of range.
	 *
	 * @return int The accepted value or the fallback.
	 */
	private function sanitize_range( mixed $value, int $minimum, int $maximum, int $fallback ): int {
		$number = is_numeric( $value ) ? (int) $value : $fallback;

		return ( $number >= $minimum && $number <= $maximum ) ? $number : $fallback;
	}

	/**
	 * Sanitize an IP address list.
	 *
	 * Accepts comma or newline separated IPs. Validates each one and discards
	 * invalid entries.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Raw input value.
	 *
	 * @return string Sanitized comma-separated IP list.
	 */
	public function sanitize_ip_list( mixed $value ): string {
		$raw_ips       = sanitize_textarea_field( is_string( $value ) ? $value : '' );
		$parsed_ips    = wptarl_parse_ip_list( $raw_ips );
		$validated_ips = [];

		foreach ( $parsed_ips as $ip ) {
			if ( false !== filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				$validated_ips[] = $ip;
			}
		}

		return implode( ', ', $validated_ips );
	}

	/**
	 * Sanitize the route list to one normalised, unique route prefix per line.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed $value Raw input value.
	 *
	 * @return string Newline-separated route prefixes, e.g. "wc/store\nwc-ppcp".
	 */
	public function sanitize_route_list( mixed $value ): string {
		$raw_lines   = is_string( $value ) ? $value : '';
		$route_lines = array_map( __NAMESPACE__ . '\wptarl_normalise_route_prefix', wptarl_parse_line_list( sanitize_textarea_field( $raw_lines ) ) );

		return implode( "\n", array_values( array_unique( array_filter( $route_lines, 'strlen' ) ) ) );
	}

	/**
	 * Sanitize the User-Agent list to one trimmed, unique string per line.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed $value Raw input value.
	 *
	 * @return string Newline-separated User-Agent strings.
	 */
	public function sanitize_user_agent_list( mixed $value ): string {
		$raw_lines = is_string( $value ) ? $value : '';

		return implode( "\n", wptarl_parse_line_list( sanitize_textarea_field( $raw_lines ) ) );
	}
}
