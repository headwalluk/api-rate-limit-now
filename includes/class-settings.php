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
	}

	/**
	 * Sanitize the seconds between calls value.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Raw input value.
	 *
	 * @return int Sanitized positive integer.
	 */
	public function sanitize_seconds_between_calls( $value ): int {
		$sanitized = absint( $value );

		if ( 0 === $sanitized ) {
			$sanitized = DEF_SECONDS_BETWEEN_CALLS;
		}

		return $sanitized;
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
	public function sanitize_boolean( $value ): bool {
		return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Sanitize the log retention days value.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Raw input value.
	 *
	 * @return int Sanitized positive integer.
	 */
	public function sanitize_log_retention( $value ): int {
		$sanitized = absint( $value );

		if ( 0 === $sanitized ) {
			$sanitized = DEF_LOG_RETENTION;
		}

		return $sanitized;
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
	public function sanitize_ip_list( $value ): string {
		$raw_ips       = sanitize_textarea_field( (string) $value );
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
