# API Rate Limiter

Rate-limit WordPress REST API calls by client IP address.

Lightweight plugin that uses WordPress transients to throttle REST API requests. When a rate-limited client makes requests too frequently, the plugin returns an HTTP 429 (Too Many Requests) response.

## Features

- Rate-limit REST API calls by client IP address
- Configurable rate limit interval (seconds between allowed calls)
- Option to rate-limit all non-logged-in users or specific IPs only
- IP allowlist to exempt specific addresses from rate limiting
- Tabbed admin settings page for easy configuration (no code editing required)
- Request logging with configurable retention (blocked requests only, zero impact on normal traffic)
- Extensibility via WordPress filters
- Clean uninstall (removes all options and data)

## Requirements

- WordPress 6.0 or later
- PHP 8.0 or later

## Installation

1. Upload the `api-rate-limit-now` directory to `/wp-content/plugins/`
2. Activate the plugin through the WordPress Plugins screen
3. Configure settings under **Settings > API Rate Limiter**

## Configuration

All settings are managed from the WordPress admin dashboard under **Settings > API Rate Limiter**:

- **Seconds between API calls** - Minimum interval between requests from the same IP (default: 10)
- **Rate-limit all guests** - When enabled, all non-logged-in users are rate-limited (default: enabled)
- **Rate-limited IPs** - Specific IP addresses to rate-limit (used when "Rate-limit all guests" is disabled)
- **Never rate-limited IPs** - IP addresses exempt from rate limiting (default: 127.0.0.1, ::1)
- **Enable logging** - Log blocked API requests to the Log tab (default: enabled)
- **Log retention** - Number of days to keep log entries (default: 7)

## Filters

The plugin provides filters for developers to customise behaviour from a theme or plugin.

### `wptarl_rate_limited_ips`

Modify the array of IP addresses that should be rate-limited. Only used when "Rate-limit all guests" is disabled.

```php
add_filter( 'wptarl_rate_limited_ips', function ( array $ips ): array {
    // Add an IP to the rate-limited list.
    $ips[] = '203.0.113.50';
    return $ips;
} );
```

### `wptarl_is_client_rate_limited`

Override whether the current client is rate-limited. Receives a boolean after the plugin has made its own determination, so you can apply custom logic.

```php
add_filter( 'wptarl_is_client_rate_limited', function ( bool $is_limited ): bool {
    // Never rate-limit requests that include a valid API key.
    if ( ! empty( $_SERVER['HTTP_X_API_KEY'] ) && $_SERVER['HTTP_X_API_KEY'] === MY_API_KEY ) {
        $is_limited = false;
    }
    return $is_limited;
} );
```

### `wptarl_seconds_between_api_calls`

Adjust the rate limit interval. Receives the configured seconds value and the client IP, so you can set different limits per IP.

```php
add_filter( 'wptarl_seconds_between_api_calls', function ( int $seconds, string $client_ip ): int {
    // Allow a known partner IP to make faster requests.
    if ( '198.51.100.10' === $client_ip ) {
        $seconds = 2;
    }
    return $seconds;
}, 10, 2 );
```

## Based On

This plugin is based on the tutorial [Rate-Limit WordPress API Calls](https://wp-tutorials.tech/optimise-wordpress/rate-limit-wordpress-api-calls/) by Paul Faulkner.

## License

GPLv2 or later. See [LICENSE](http://www.gnu.org/licenses/gpl-2.0.html).
