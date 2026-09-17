<?php
/**
 * Private/internal helper functions.
 *
 * @package Api_Rate_Limiter
 */

namespace Api_Rate_Limiter;

defined( 'ABSPATH' ) || die();

/**
 * Check multiple $_SERVER elements to find the remote client's IP address.
 *
 * Results are cached in a global variable so the lookup only happens once
 * per request.
 *
 * @since 2.0.0
 *
 * @return string|null Remote client IP address, or null if not determined.
 */
function wptarl_client_ip(): ?string {
	global $wptarl_client_ip;

	if ( is_null( $wptarl_client_ip ) ) {
		$server_vars = [ 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ];

		foreach ( $server_vars as $server_var ) {
			if ( array_key_exists( $server_var, $_SERVER ) ) {
				$candidate = filter_var(
					wp_unslash( $_SERVER[ $server_var ] ),
					FILTER_VALIDATE_IP
				);

				if ( ! empty( $candidate ) ) {
					$wptarl_client_ip = $candidate;
					break;
				}
			}
		}

		// Don't leave an empty string or false in the global.
		if ( empty( $wptarl_client_ip ) ) {
			$wptarl_client_ip = null;
		}
	}

	return $wptarl_client_ip;
}

/**
 * Parse a string of IP addresses (comma or newline separated) into an array.
 *
 * @since 2.0.0
 *
 * @param string $raw_ips Raw IP string from settings.
 *
 * @return array<string> Array of trimmed, non-empty IP strings.
 */
function wptarl_parse_ip_list( string $raw_ips ): array {
	$ips = preg_split( '/[\s,]+/', $raw_ips, -1, PREG_SPLIT_NO_EMPTY );

	$result = [];

	foreach ( $ips as $ip ) {
		$ip = trim( $ip );
		if ( '' !== $ip ) {
			$result[] = $ip;
		}
	}

	return $result;
}

/**
 * Get the client's User-Agent header, or an empty string when none was sent.
 *
 * @since 2.1.0
 *
 * @return string Sanitized User-Agent.
 */
function wptarl_client_user_agent(): string {
	$user_agent = '';

	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
		$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
	}

	return $user_agent;
}

/**
 * Parse a newline-separated string into an array of trimmed, non-empty, unique lines.
 *
 * @since 2.1.0
 *
 * @param string $raw_lines Raw text, one entry per line.
 *
 * @return array<string> Lines in their original order.
 */
function wptarl_parse_line_list( string $raw_lines ): array {
	$lines = preg_split( '/\R/', $raw_lines );

	if ( ! is_array( $lines ) ) {
		$lines = array();
	}

	return array_values( array_unique( array_filter( array_map( 'trim', $lines ), 'strlen' ) ) );
}
