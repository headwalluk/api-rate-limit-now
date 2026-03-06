# API Rate Limiter

![Version](https://img.shields.io/github/v/tag/headwalluk/api-rate-limit-now?label=version&sort=semver)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-7A86B8)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759B)
![License](https://img.shields.io/github/license/headwalluk/api-rate-limit-now)
![Build](https://img.shields.io/github/actions/workflow/status/headwalluk/api-rate-limit-now/release.yml?label=release)

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

## WooCommerce

This plugin works with WooCommerce out of the box. WooCommerce's REST API (`/wp-json/wc/`) and Store API (`/wp-json/wc/store/`) are standard WordPress REST API routes, so they are automatically protected by this plugin.

This is particularly useful for WooCommerce sites that are targeted by bots scraping product data, brute-forcing coupon codes, or spamming checkout endpoints.

**Headless storefronts:** If you are running a headless WooCommerce setup where your frontend makes rapid legitimate API calls, you may want to lower the rate limit interval (e.g. 2-3 seconds) or use the `wptarl_is_client_rate_limited` filter to exempt authenticated API consumers.

## Choosing the Right Interval

The default interval of 10 seconds works well for most sites, but the right value depends on your use case:

- **Standard WordPress sites** - 10-30 seconds is usually fine. Most legitimate visitors don't make rapid API calls.
- **WooCommerce stores** - 5-10 seconds balances protection against bots with a smooth shopping experience.
- **Headless / decoupled frontends** - 1-3 seconds, or consider exempting known frontend IPs via the "Never rate-limited IPs" setting.
- **High-traffic APIs** - Use the `wptarl_seconds_between_api_calls` filter to set different limits per endpoint or client.

If you're unsure, start with the default and check the **Log** tab to see if legitimate requests are being blocked.

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
