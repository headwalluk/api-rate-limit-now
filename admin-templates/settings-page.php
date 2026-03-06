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
$logging_enabled        = (bool) filter_var( get_option( OPT_LOGGING_ENABLED, DEF_LOGGING_ENABLED ), FILTER_VALIDATE_BOOLEAN );
$log_retention          = absint( get_option( OPT_LOG_RETENTION, DEF_LOG_RETENTION ) );
$client_ip              = wptarl_client_ip();

printf(
	'<div class="wrap"><h1>%s</h1>',
	esc_html( get_admin_page_title() )
);

if ( ! empty( $client_ip ) ) {
	printf(
		'<p class="description">%s <code>%s</code> <a href="#" class="wptarl-copy-ip" data-ip="%s">%s</a></p>',
		esc_html__( 'Your current IP address:', 'api-rate-limiter-now' ),
		esc_html( $client_ip ),
		esc_attr( $client_ip ),
		esc_html__( 'click to copy', 'api-rate-limiter-now' )
	);
}

// Tab navigation.
printf(
	'<nav class="nav-tab-wrapper wp-clearfix"><a href="#settings" class="nav-tab nav-tab-active" data-tab="settings">%s</a><a href="#log" class="nav-tab" data-tab="log">%s</a></nav>',
	esc_html__( 'Settings', 'api-rate-limiter-now' ),
	esc_html__( 'Log', 'api-rate-limiter-now' )
);

// --- Settings tab panel ---

echo '<div id="settings-panel" class="wptarl-tab-panel">';

echo '<form method="post" action="options.php">';

settings_fields( SETTINGS_GROUP );

echo '<table class="form-table" role="presentation"><tbody>';

// Seconds between API calls.
printf(
	'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input type="number" id="%1$s" name="%1$s" value="%3$s" min="1" max="3600" class="small-text" /><p class="description">%4$s</p></td></tr>',
	esc_attr( OPT_SECONDS_BETWEEN_CALLS ),
	esc_html__( 'Seconds between API calls', 'api-rate-limiter-now' ),
	esc_attr( $seconds_between_calls ),
	esc_html__( 'Minimum number of seconds between REST API requests from the same IP address.', 'api-rate-limiter-now' )
);

// Rate-limit all guests.
printf(
	'<tr><th scope="row">%1$s</th><td><label for="%2$s"><input type="checkbox" id="%2$s" name="%2$s" value="1" %3$s /> %4$s</label><p class="description">%5$s</p></td></tr>',
	esc_html__( 'Rate-limit all guests', 'api-rate-limiter-now' ),
	esc_attr( OPT_RATE_LIMIT_ALL_GUESTS ),
	checked( $rate_limit_all_guests, true, false ),
	esc_html__( 'Apply rate limiting to all non-logged-in users', 'api-rate-limiter-now' ),
	esc_html__( 'When enabled, all guest (non-logged-in) API requests are rate-limited. When disabled, only the specific IPs listed below are rate-limited.', 'api-rate-limiter-now' )
);

// Rate-limited IPs.
printf(
	'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><textarea id="%1$s" name="%1$s" rows="4" cols="40" class="large-text code">%3$s</textarea><p class="description">%4$s</p></td></tr>',
	esc_attr( OPT_RATE_LIMITED_IPS ),
	esc_html__( 'Rate-limited IPs', 'api-rate-limiter-now' ),
	esc_textarea( $rate_limited_ips ),
	esc_html__( 'IP addresses to rate-limit, separated by commas or newlines. Only used when "Rate-limit all guests" is disabled.', 'api-rate-limiter-now' )
);

// Never rate-limited IPs.
printf(
	'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><textarea id="%1$s" name="%1$s" rows="4" cols="40" class="large-text code">%3$s</textarea><p class="description">%4$s</p></td></tr>',
	esc_attr( OPT_NEVER_RATE_LIMITED_IPS ),
	esc_html__( 'Never rate-limited IPs', 'api-rate-limiter-now' ),
	esc_textarea( $never_rate_limited_ips ),
	esc_html__( 'IP addresses that are always exempt from rate limiting, separated by commas or newlines.', 'api-rate-limiter-now' )
);

// Enable logging.
printf(
	'<tr><th scope="row">%1$s</th><td><label for="%2$s"><input type="checkbox" id="%2$s" name="%2$s" value="1" %3$s /> %4$s</label><p class="description">%5$s</p></td></tr>',
	esc_html__( 'Enable logging', 'api-rate-limiter-now' ),
	esc_attr( OPT_LOGGING_ENABLED ),
	checked( $logging_enabled, true, false ),
	esc_html__( 'Log blocked API requests', 'api-rate-limiter-now' ),
	esc_html__( 'When enabled, blocked requests are recorded in the Log tab. Logging only occurs when a request is blocked, so there is no impact on normal traffic.', 'api-rate-limiter-now' )
);

// Log retention.
printf(
	'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input type="number" id="%1$s" name="%1$s" value="%3$s" min="1" max="90" class="small-text" /><p class="description">%4$s</p></td></tr>',
	esc_attr( OPT_LOG_RETENTION ),
	esc_html__( 'Log retention (days)', 'api-rate-limiter-now' ),
	esc_attr( $log_retention ),
	esc_html__( 'Number of days to keep log entries before they are automatically pruned.', 'api-rate-limiter-now' )
);

echo '</tbody></table>';

submit_button();

echo '</form></div>';

// --- Log tab panel ---

echo '<div id="log-panel" class="wptarl-tab-panel" style="display:none;">';

$log_entries = Log::get_recent( 100 );

if ( empty( $log_entries ) ) {
	printf(
		'<p>%s</p>',
		esc_html__( 'No blocked requests have been logged yet.', 'api-rate-limiter-now' )
	);
} else {
	printf(
		'<p class="description">%s</p>',
		esc_html(
			sprintf(
				/* translators: %d: number of log entries */
				__( 'Showing the %d most recent blocked requests.', 'api-rate-limiter-now' ),
				count( $log_entries )
			)
		)
	);

	echo '<table class="widefat fixed striped"><thead><tr>';
	printf( '<th>%s</th>', esc_html__( 'IP Address', 'api-rate-limiter-now' ) );
	printf( '<th>%s</th>', esc_html__( 'Blocked At', 'api-rate-limiter-now' ) );
	printf( '<th>%s</th>', esc_html__( 'Request URI', 'api-rate-limiter-now' ) );
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
echo '<form method="post" style="margin-top: 12px;">';
echo '<input type="hidden" name="wptarl_clear_log" value="1" />';
wp_nonce_field( 'wptarl_clear_log', 'wptarl_clear_log_nonce' );
submit_button( __( 'Clear Log', 'api-rate-limiter-now' ), 'secondary' );
echo '</form>';

echo '</div>';

echo '</div>'; // .wrap
