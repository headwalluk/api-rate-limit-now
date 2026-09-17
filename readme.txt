=== API Rate Limiter ===
Contributors: headwalluk
Tags: api, rate-limit, rest-api, throttle, security
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 2.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Rate-limit WordPress REST API calls by client IP address.

== Description ==

API Rate Limiter is a lightweight plugin that uses WordPress transients to throttle REST API requests. When a rate-limited client makes requests too frequently, the plugin returns an HTTP 429 (Too Many Requests) response.

= Features =

* Rate-limit REST API calls by client IP address
* Configurable rate limit interval (seconds between allowed calls)
* Option to rate-limit all non-logged-in users or specific IPs only
* IP allowlist to exempt specific addresses from rate limiting
* Route allowlist, with shoppers' WooCommerce Store API and PayPal button requests exempt by default
* Always rate-limit chosen integrations by User-Agent, including ones that authenticate with API keys
* Tabbed admin settings page for easy configuration (no code editing required)
* Request logging with configurable retention (blocked requests only, zero impact on normal traffic)
* Extensibility via WordPress filters
* Clean uninstall (removes all options and data)

= Filters for Developers =

The plugin provides filters to customise behaviour:

* `wptarl_rate_limited_ips` - Modify the list of rate-limited IP addresses
* `wptarl_is_client_rate_limited` - Override whether the current client is rate-limited
* `wptarl_seconds_between_api_calls` - Adjust the rate limit interval per client IP
* `wptarl_redacted_query_params` - Add query parameter names to redact from the log
* `wptarl_log_view_rows` - Change how many recent entries the Log tab shows
* `wptarl_updater_enabled` - Turn off updates from GitHub

= Based On =

This plugin is based on the tutorial [Rate-Limit WordPress API Calls](https://wp-tutorials.tech/optimise-wordpress/rate-limit-wordpress-api-calls/) by Paul Faulkner.

== Installation ==

1. Upload the `api-rate-limit-now` directory to `/wp-content/plugins/`
2. Activate the plugin through the WordPress Plugins screen
3. Configure settings under **Settings > API Rate Limiter**

== Frequently Asked Questions ==

= How does it work? =

For each real REST API request, the plugin decides from its settings whether the client is rate-limited. A rate-limited client may make one request per interval, tracked with a WordPress transient per IP address. A request inside the interval gets an HTTP 429 response with a Retry-After header saying how many seconds to wait. Page loads, and REST requests made internally while building a page, are never checked.

= Which users are rate-limited? =

By default, every client that isn't logged in, except requests to the WooCommerce Store API and PayPal button routes, which shoppers' browsers make during checkout. Integrations using WooCommerce API keys or application passwords count as logged in, so they aren't limited unless you list their User-Agent under "Rate-limited user agents". You can also limit only specific IP addresses, turn off guest limiting to limit only the listed integrations, or use the `wptarl_is_client_rate_limited` filter.

= Does it work behind a reverse proxy or CDN? =

The plugin reads `HTTP_CLIENT_IP`, then `HTTP_X_FORWARDED_FOR`, then `REMOTE_ADDR`, and uses the first that holds a single valid IP address. A proxy that sends a list of addresses in `X-Forwarded-For` isn't read, so every visitor appears to come from the proxy. Configure your web server to pass the visitor's address in `REMOTE_ADDR` (nginx `real_ip_module`, Apache `mod_remoteip`, or your CDN's equivalent). The settings page shows the address the plugin sees for you.

= Will it slow down my site? =

No. The plugin uses WordPress transients, which are very fast (especially with an object cache). It only runs on REST API requests, not on regular page loads.

== Screenshots ==

1. Settings tab - configure rate limiting from the WordPress dashboard.
2. Log tab - view recently blocked API requests.

== Changelog ==

= 2.1.1 =
* Security: credential values in logged request URLs (consumer_secret, oauth_signature, tokens, nonces and similar) are now redacted before they are stored
* Added the wptarl_redacted_query_params filter
* Fixed long request URIs being cut mid-character in the log

= 2.1.0 =
* Added a "Never rate-limited routes" setting; by default shoppers' WooCommerce Store API and PayPal button requests are no longer rate-limited
* Fixed internal REST requests made during a page load being rate-limited, which could cut a page off with a 429 response
* Added a Retry-After header to every 429 response, giving the seconds until the client may try again
* Added a "Rate-limited user agents" setting to always rate-limit chosen integrations, including ones that authenticate with API keys
* Added automatic updates from GitHub releases, with a wptarl_updater_enabled filter to turn them off
* Changed the main plugin file to api-rate-limit-now.php and the text domain to api-rate-limit-now
* Added Requires at least and Requires PHP headers

= 2.0.0 =
* Complete rewrite with multi-file architecture
* Added tabbed admin settings page (Settings > API Rate Limiter)
* Configuration now managed via WordPress dashboard instead of PHP constants
* Added IP allowlist and blocklist settings
* Added "rate-limit all guests" toggle
* Added request logging with configurable retention
* Improved IP detection with proper sanitisation
* Added deactivation and uninstall cleanup

= 1.0.1 =
* Initial public release based on tutorial code

== Upgrade Notice ==

= 2.1.1 =
Security fix. Blocked requests carrying API credentials in their URL were logged with those credentials. After updating, clear the log under Settings > API Rate Limiter > Log, and rotate any API key that appeared in it.

= 2.1.0 =
The plugin's main file has been renamed, so WordPress deactivates the plugin during this update. Reactivate it under Plugins; settings and the log are kept. The WooCommerce Store API is no longer rate-limited by default; see "Never rate-limited routes". Future updates install automatically from GitHub.

= 2.0.0 =
Major update. Configuration has moved from PHP constants to the WordPress settings page. Previous constant-based configuration will no longer apply after updating.
