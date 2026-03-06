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
