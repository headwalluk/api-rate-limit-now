<?php
/**
 * Admin settings page template.
 *
 * @package Api_Rate_Limiter
 */

namespace Api_Rate_Limiter;

defined( 'ABSPATH' ) || die();

$seconds_between_calls  = absint( get_option( OPT_SECONDS_BETWEEN_CALLS, DEF_SECONDS_BETWEEN_CALLS ) );
$rate_limit_all_guests  = (bool) filter_var( get_option( OPT_RATE_LIMIT_ALL_GUESTS, DEF_RATE_LIMIT_ALL_GUESTS ), FILTER_VALIDATE_BOOLEAN );
$rate_limited_ips       = (string) get_option( OPT_RATE_LIMITED_IPS, DEF_RATE_LIMITED_IPS );
$never_rate_limited_ips = (string) get_option( OPT_NEVER_RATE_LIMITED_IPS, DEF_NEVER_RATE_LIMITED_IPS );
$rate_limited_agents    = (string) get_option( OPT_RATE_LIMITED_USER_AGENTS, DEF_RATE_LIMITED_USER_AGENTS );
$never_limited_routes   = (string) get_option( OPT_NEVER_RATE_LIMITED_ROUTES, DEF_NEVER_RATE_LIMITED_ROUTES );
$logging_enabled        = (bool) filter_var( get_option( OPT_LOGGING_ENABLED, DEF_LOGGING_ENABLED ), FILTER_VALIDATE_BOOLEAN );
$log_retention          = absint( get_option( OPT_LOG_RETENTION, DEF_LOG_RETENTION ) );
$delete_data            = (bool) filter_var( get_option( OPT_DELETE_DATA_ON_UNINSTALL, DEF_DELETE_DATA_ON_UNINSTALL ), FILTER_VALIDATE_BOOLEAN );
$client_ip              = wptarl_client_ip();

printf(
	'<div class="wrap"><h1>%s</h1>',
	esc_html( get_admin_page_title() )
);

if ( ! empty( $client_ip ) ) {
	printf(
		'<p class="description">%s <code>%s</code> <a href="#" class="wptarl-copy-ip" data-ip="%s">%s</a></p>',
		esc_html__( 'Your current IP address:', 'api-rate-limit-now' ),
		esc_html( $client_ip ),
		esc_attr( $client_ip ),
		esc_html_x( 'click to copy', 'link: copy the IP address to the clipboard', 'api-rate-limit-now' )
	);
}

// Tab navigation.
printf(
	'<nav class="nav-tab-wrapper wp-clearfix"><a href="#settings" class="nav-tab nav-tab-active" data-tab="settings">%s</a><a href="#log" class="nav-tab" data-tab="log">%s</a></nav>',
	esc_html__( 'Settings', 'api-rate-limit-now' ),
	esc_html_x( 'Log', 'noun; settings page tab listing blocked requests', 'api-rate-limit-now' )
);

// --- Settings tab panel ---

echo '<div id="settings-panel" class="wptarl-tab-panel">';

echo '<form method="post" action="options.php">';

settings_fields( SETTINGS_GROUP );

echo '<table class="form-table" role="presentation"><tbody>';

// Seconds between API calls.
printf(
	'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input type="number" id="%1$s" name="%1$s" value="%3$s" min="%5$s" max="%6$s" class="small-text" /><p class="description">%4$s</p></td></tr>',
	esc_attr( OPT_SECONDS_BETWEEN_CALLS ),
	esc_html__( 'Seconds between API calls', 'api-rate-limit-now' ),
	esc_attr( $seconds_between_calls ),
	esc_html(
		sprintf(
			/* translators: 1: lowest accepted number of seconds, 2: highest accepted number of seconds */
			__( 'Minimum number of seconds between REST API requests from the same IP address, from %1$d to %2$d.', 'api-rate-limit-now' ),
			SECONDS_BETWEEN_CALLS_MIN,
			SECONDS_BETWEEN_CALLS_MAX
		)
	),
	esc_attr( (string) SECONDS_BETWEEN_CALLS_MIN ),
	esc_attr( (string) SECONDS_BETWEEN_CALLS_MAX )
);

// Rate-limit all guests.
printf(
	'<tr><th scope="row">%1$s</th><td><label for="%2$s"><input type="checkbox" id="%2$s" name="%2$s" value="1" %3$s /> %4$s</label><p class="description">%5$s</p></td></tr>',
	esc_html__( 'Rate-limit all guests', 'api-rate-limit-now' ),
	esc_attr( OPT_RATE_LIMIT_ALL_GUESTS ),
	checked( $rate_limit_all_guests, true, false ),
	esc_html__( 'Apply rate limiting to all non-logged-in users', 'api-rate-limit-now' ),
	esc_html__( 'When enabled, all API requests from visitors who are not logged in are rate-limited. Ignored while the Rate-limited IPs list below has any addresses in it.', 'api-rate-limit-now' )
);

// Rate-limited IPs.
printf(
	'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><textarea id="%1$s" name="%1$s" rows="4" cols="40" class="large-text code">%3$s</textarea><p class="description">%4$s</p></td></tr>',
	esc_attr( OPT_RATE_LIMITED_IPS ),
	esc_html__( 'Rate-limited IPs', 'api-rate-limit-now' ),
	esc_textarea( $rate_limited_ips ),
	esc_html__( 'IP addresses to rate-limit, separated by commas or newlines. While this list has any addresses in it, only these addresses are rate-limited, whether or not they are logged in, and "Rate-limit all guests" is ignored.', 'api-rate-limit-now' )
);

// Never rate-limited IPs.
printf(
	'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><textarea id="%1$s" name="%1$s" rows="4" cols="40" class="large-text code">%3$s</textarea><p class="description">%4$s</p></td></tr>',
	esc_attr( OPT_NEVER_RATE_LIMITED_IPS ),
	esc_html__( 'Never rate-limited IPs', 'api-rate-limit-now' ),
	esc_textarea( $never_rate_limited_ips ),
	esc_html__( 'IP addresses that are always exempt from rate limiting, separated by commas or newlines.', 'api-rate-limit-now' )
);

// Never rate-limited routes.
printf(
	'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><textarea id="%1$s" name="%1$s" rows="4" cols="40" class="large-text code">%3$s</textarea><p class="description">%4$s</p></td></tr>',
	esc_attr( OPT_NEVER_RATE_LIMITED_ROUTES ),
	esc_html__( 'Never rate-limited routes', 'api-rate-limit-now' ),
	esc_textarea( $never_limited_routes ),
	esc_html__( 'REST API routes that are never rate-limited, one per line. Each covers the routes below it, matching whole path segments: wc/store covers wc/store/v1/cart, but not wc/storefront. Wildcards are not supported. You can paste a request URI from the Log tab. The defaults exempt the WooCommerce Store API and PayPal button requests that shoppers make during checkout.', 'api-rate-limit-now' )
);

// Rate-limited user agents.
printf(
	'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><textarea id="%1$s" name="%1$s" rows="4" cols="40" class="large-text code">%3$s</textarea><p class="description">%4$s</p></td></tr>',
	esc_attr( OPT_RATE_LIMITED_USER_AGENTS ),
	esc_html__( 'Rate-limited user agents', 'api-rate-limit-now' ),
	esc_textarea( $rate_limited_agents ),
	esc_html__( 'Always rate-limit requests whose User-Agent contains one of these strings, one per line. Matching ignores case. Applies to logged-in users and authenticated API clients too, but never to the IPs listed above as never rate-limited.', 'api-rate-limit-now' )
);

// Enable logging.
printf(
	'<tr><th scope="row">%1$s</th><td><label for="%2$s"><input type="checkbox" id="%2$s" name="%2$s" value="1" %3$s /> %4$s</label><p class="description">%5$s</p></td></tr>',
	esc_html_x( 'Enable logging', 'settings row heading', 'api-rate-limit-now' ),
	esc_attr( OPT_LOGGING_ENABLED ),
	checked( $logging_enabled, true, false ),
	esc_html__( 'Log blocked API requests', 'api-rate-limit-now' ),
	esc_html__( 'When enabled, blocked requests are recorded in the Log tab. Logging only occurs when a request is blocked, so there is no impact on normal traffic.', 'api-rate-limit-now' )
);

// Log retention.
printf(
	'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input type="number" id="%1$s" name="%1$s" value="%3$s" min="%5$s" max="%6$s" class="small-text" /><p class="description">%4$s</p></td></tr>',
	esc_attr( OPT_LOG_RETENTION ),
	esc_html__( 'Log retention (days)', 'api-rate-limit-now' ),
	esc_attr( $log_retention ),
	esc_html(
		sprintf(
			/* translators: 1: lowest accepted number of days, 2: highest accepted number of days */
			__( 'Number of days to keep log entries before they are automatically pruned, from %1$d to %2$d.', 'api-rate-limit-now' ),
			LOG_RETENTION_MIN,
			LOG_RETENTION_MAX
		)
	),
	esc_attr( (string) LOG_RETENTION_MIN ),
	esc_attr( (string) LOG_RETENTION_MAX )
);

// Delete data on uninstall.
printf(
	'<tr><th scope="row">%1$s</th><td><label for="%2$s"><input type="checkbox" id="%2$s" name="%2$s" value="1" %3$s /> %4$s</label><p class="description">%5$s</p></td></tr>',
	esc_html_x( 'Uninstall', 'settings row heading: what happens when the plugin is deleted', 'api-rate-limit-now' ),
	esc_attr( OPT_DELETE_DATA_ON_UNINSTALL ),
	checked( $delete_data, true, false ),
	esc_html__( 'Delete all plugin data when uninstalled', 'api-rate-limit-now' ),
	esc_html__( 'When enabled, deleting the plugin from the Plugins screen also removes its settings and the log. Deactivating never deletes anything.', 'api-rate-limit-now' )
);

echo '</tbody></table>';

submit_button();

echo '</form></div>';

// --- Log tab panel ---

echo '<div id="log-panel" class="wptarl-tab-panel" hidden>';

$log_entries = Log::get_recent( Log::get_view_row_limit() );

if ( empty( $log_entries ) ) {
	printf(
		'<p>%s</p>',
		esc_html__( 'No blocked requests have been logged yet.', 'api-rate-limit-now' )
	);
} else {
	printf(
		'<p class="description">%s</p>',
		esc_html(
			sprintf(
				/* translators: %d: number of log entries shown */
				_n( 'Showing the %d most recent blocked request.', 'Showing the %d most recent blocked requests.', count( $log_entries ), 'api-rate-limit-now' ),
				count( $log_entries )
			)
		)
	);

	echo '<table class="widefat fixed striped"><thead><tr>';
	printf( '<th>%s</th>', esc_html__( 'IP Address', 'api-rate-limit-now' ) );
	printf( '<th>%s</th>', esc_html_x( 'Blocked At', 'log table column: date and time the request was refused', 'api-rate-limit-now' ) );
	printf( '<th>%s</th>', esc_html__( 'Request URI', 'api-rate-limit-now' ) );
	echo '</tr></thead><tbody>';

	foreach ( $log_entries as $entry ) {
		printf(
			'<tr><td><code>%s</code></td><td>%s</td><td><code>%s</code></td></tr>',
			esc_html( $entry->client_ip ),
			esc_html( $entry->blocked_at ),
			esc_html( $entry->request_uri )
		);
	}

	echo '</tbody></table>';
}

// Clear log form.
echo '<form method="post" class="wptarl-clear-log-form">';
printf( '<input type="hidden" name="%s" value="1" />', esc_attr( CLEAR_LOG_FIELD ) );
wp_nonce_field( NONCE_CLEAR_LOG_ACTION, NONCE_CLEAR_LOG_FIELD );
submit_button( _x( 'Clear Log', 'verb; button that deletes every log entry', 'api-rate-limit-now' ), 'secondary' );
echo '</form>';

echo '</div>';

echo '</div>'; // .wrap
