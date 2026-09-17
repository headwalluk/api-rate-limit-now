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

/**
 * Get the REST route being served, lower-cased and without surrounding slashes, or '' outside a REST request.
 *
 * @since 2.1.0
 *
 * @return string Route such as 'wc/store/v1/cart'.
 */
function wptarl_current_rest_route(): string {
	$route = '';

	if ( isset( $GLOBALS['wp'] ) && $GLOBALS['wp'] instanceof \WP && isset( $GLOBALS['wp']->query_vars['rest_route'] ) && is_string( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
		$route = strtolower( trim( $GLOBALS['wp']->query_vars['rest_route'], '/' ) );
	}

	return $route;
}

/**
 * Normalise a route prefix for matching: lower-cased, without host, REST URL prefix, query string or surrounding slashes.
 *
 * @since 2.1.0
 *
 * @param string $route_prefix Route prefix as entered, e.g. '/wc/store/', '/wp-json/wc/store' or a full URL.
 *
 * @return string Normalised prefix, e.g. 'wc/store'.
 */
function wptarl_normalise_route_prefix( string $route_prefix ): string {
	$route_path = trim( $route_prefix );

	$query_string = (string) wp_parse_url( $route_path, PHP_URL_QUERY );
	$query_args   = array();
	wp_parse_str( $query_string, $query_args );

	if ( isset( $query_args['rest_route'] ) && is_string( $query_args['rest_route'] ) ) {
		// A pasted "/?rest_route=/wc/store" URL names its route in the query string.
		$route_path = $query_args['rest_route'];
	} elseif ( isset( $query_args['wc-ajax'], $query_args['path'] ) && is_string( $query_args['path'] ) ) {
		// Plugins proxying REST through "/?wc-ajax=…&path=/route" name the route in path.
		$route_path = $query_args['path'];
	} elseif ( str_contains( $route_path, '://' ) ) {
		$route_path = (string) wp_parse_url( $route_path, PHP_URL_PATH );
	} else {
		// Already a path or bare route.
	}

	$route_path      = strtolower( trim( (string) preg_replace( '/[?#].*$/s', '', $route_path ), " \t/" ) );
	$rest_url_prefix = strtolower( trim( rest_get_url_prefix(), '/' ) );

	if ( '' !== $rest_url_prefix && ( $route_path === $rest_url_prefix || str_starts_with( $route_path, $rest_url_prefix . '/' ) ) ) {
		$route_path = trim( substr( $route_path, strlen( $rest_url_prefix ) ), '/' );
	}

	return $route_path;
}

/**
 * Replace the values of credential-like query parameters in a request URI, leaving names and other parameters intact.
 *
 * @since 2.1.1
 *
 * @param string $request_uri Raw request URI, e.g. '/wp-json/wc/v3/orders?consumer_key=ck_1&page=2'.
 *
 * @return string URI with sensitive values replaced, e.g. '/wp-json/wc/v3/orders?consumer_key=REDACTED&page=2'.
 */
function wptarl_redact_request_uri( string $request_uri ): string {
	$redacted_uri   = $request_uri;
	$query_position = strpos( $request_uri, '?' );

	if ( false !== $query_position ) {
		/**
		 * Filter the name fragments that mark a query parameter as sensitive in the log.
		 *
		 * A parameter is redacted when its URL-decoded, lower-cased name contains any fragment.
		 *
		 * @since 2.1.1
		 *
		 * @param array<string> $name_fragments Lower-case fragments. Default REDACTED_QUERY_PARAM_FRAGMENTS.
		 */
		$name_fragments = apply_filters( 'wptarl_redacted_query_params', REDACTED_QUERY_PARAM_FRAGMENTS );

		if ( ! is_array( $name_fragments ) ) {
			$name_fragments = REDACTED_QUERY_PARAM_FRAGMENTS;
		}

		$query_pairs = explode( '&', substr( $request_uri, $query_position + 1 ) );

		foreach ( $query_pairs as $pair_index => $query_pair ) {
			$equals_position = strpos( $query_pair, '=' );

			if ( false === $equals_position ) {
				// A bare name carries no value to redact.
				continue;
			}

			$param_name = strtolower( rawurldecode( substr( $query_pair, 0, $equals_position ) ) );

			foreach ( $name_fragments as $name_fragment ) {
				if ( is_string( $name_fragment ) && '' !== $name_fragment && str_contains( $param_name, strtolower( $name_fragment ) ) ) {
					$query_pairs[ $pair_index ] = substr( $query_pair, 0, $equals_position + 1 ) . REDACTED_QUERY_VALUE;
					break;
				}
			}
		}

		$redacted_uri = substr( $request_uri, 0, $query_position + 1 ) . implode( '&', $query_pairs );
	}

	return $redacted_uri;
}
