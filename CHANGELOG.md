# Changelog

All notable changes to this project will be documented in this file.

Operator and developer documentation lives in [`docs/`](docs/README.md), including [worked examples](docs/examples.md).

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2026-11-12

**Updating from 2.0.0:** 2.1.0 and 2.1.1 below were never published as releases, so this is the first release since 2.0.0 and includes both. Read all three entries. In particular:

- **The plugin is deactivated by this update** (the main file was renamed in 2.1.0). Reactivate it under Plugins; settings and the log are kept
- **Requires PHP 8.2 and WordPress 6.2** (from 8.0 and 6.0)
- **Behind a proxy or CDN, check the client IP** (see Security below). The settings page shows the address the plugin sees for you
- 2.0.0 has no updater, so install this release manually once. Later releases arrive through the normal Updates screen

### Security

- **Any client could escape the rate limit, or claim to be exempt, with a forged header.** The client IP was read from the `Client-IP` and `X-Forwarded-For` request headers before `REMOTE_ADDR`, and a client can set those headers to anything. Sending a different `X-Forwarded-For` with each request gave a new allowance every time, and sending `Client-IP: 127.0.0.1` matched the default *Never rate-limited IPs* list. The plugin now uses only `REMOTE_ADDR`. Sites behind a proxy or CDN should have the web server restore the visitor's address into `REMOTE_ADDR` (nginx `real_ip_module`, Apache `mod_remoteip`); without that, every visitor shares the proxy's address. Introduced in 2.0.0.

### Added

- The `wptarl_client_ip` filter, to supply the client IP where the web server can't be configured. Return `null` to leave a request unlimited.
- **Delete all plugin data when uninstalled** setting, off by default.
- Translations: the plugin loads its text domain, and ships German, Greek, British English, Spanish, French, Italian, Dutch and Polish.
- A privacy section in `docs/how-it-works.md`.

### Changed

- **Deleting the plugin keeps its settings and log** unless *Delete all plugin data when uninstalled* is on. Before, deleting always removed everything. Deactivating still never deletes anything.
- **Seconds between API calls** accepts 1 to 3,600 and **Log retention** 1 to 90 days, checked when saving. A value outside the range restores the default; before, any positive number was saved.
- `wptarl_is_client_rate_limited` is read as a boolean the way WordPress options are, so `'no'`, `'off'` and `'false'` now count as false. Before, any non-empty string counted as true.
- `wptarl_seconds_between_api_calls` receives the setting as an integer, and a non-numeric result is ignored.
- The log table and the daily prune event are checked on activation and in the admin area, no longer on every request.
- The GitHub updater is the shared Headwall updater. Its cache is now `api_rate_limit_now_github_release` (was `wptarl_github_release`), and it also runs under WP-CLI, so `wp plugin update` sees new releases.
- The plugin and author links point at headwall-hosting.com.
- The plugin name stays as written in every language, in the menu, on the settings page and on the Plugins screen. Only the description is translated.

### Fixed

- A `wptarl_rate_limited_ips` filter returning anything other than an array made every REST request fail with a fatal error. It's now ignored, and the saved list is used.
- The *Rate-limit all guests* and *Rate-limited IPs* help text said the list was only used with guest limiting off. While the list has addresses in it, it's used whatever that setting says, and applies to logged-in users too; the text now says so. Behaviour is unchanged.
- Database failures were silent. A failed log insert, prune or clear is now written to the PHP error log, and **Clear Log** falls back to `DELETE` on hosts that don't allow `TRUNCATE`. A log table that couldn't be created is retried, instead of being recorded as created.
- An unknown tab in the settings page URL showed an empty page; it now shows the Settings tab.

### Removed

- `readme.txt`. The README, `CHANGELOG.md` and `docs/` cover everything it did, and the release workflow no longer checks its stable tag.

## [2.1.1] - 2026-09-17

### Security

- **Credentials in request URLs were stored in the log.** Blocked requests were logged with their full query string, so an integration authenticating with query-string credentials (WooCommerce's legacy `consumer_key` / `consumer_secret` parameters, or OAuth 1.0a `oauth_*` parameters) had its API secret written to the log table and shown on the **Log** tab. Values of parameters whose names contain `key`, `secret`, `token`, `password`, `passwd`, `nonce`, `signature`, `oauth`, `session` or `credential` are now replaced with `REDACTED` before the entry is stored. Existing log entries are not changed: **clear the log** (Settings → API Rate Limiter → Log → Clear Log) after updating, and rotate any API key that appears in it. Introduced in 2.0.0.

### Added

- The `wptarl_redacted_query_params` filter, to add name fragments to redact.
- The `wptarl_log_view_rows` filter, to change how many of the most recent entries the Log tab shows (default 100, at most 1,000).

### Fixed

- The Log tab's "Showing the N most recent blocked requests" line now uses the correct singular and plural forms in every language.
- Long request URIs were truncated by byte count, which could split a multi-byte character and make the log insert fail. They are now truncated by character.

## [2.1.0] - 2026-09-17

### Added

- **Never rate-limited routes setting**, defaulting to `wc/store` (WooCommerce Store API) and `wc-ppcp` (Payment Plugins for PayPal WooCommerce). Routes match on whole path segments (`wc/store` covers `wc/store/v1/cart`, not `wc/storefront`), and a request URI pasted from the Log tab, including `/wp-json/…`, `?rest_route=…` and `wc-ajax` forms, is saved as its route. Shoppers' browsers call these several times a second during cart and checkout; limiting them refused real customers and broke PayPal buttons. **Sites updating from 2.0.0 stop rate-limiting the Store API.** Remove `wc/store` from the list to restore that, and see the configuration docs for WooCommerce's own checkout rate limiting.

- **A `Retry-After` header on every 429**, giving the whole seconds until the client may make its next request. A client that waits exactly that long is allowed. Clients that honour it back off exactly as long as needed; the status code and JSON body are unchanged. The rate-limit transient now stores when the client's window ends, rather than `1`.
- **Rate-limited user agents setting.** Requests whose `User-Agent` contains any listed string (one per line, case-insensitive) are always rate-limited, including logged-in users and integrations authenticating with WooCommerce API keys or application passwords, which were previously always exempt. Never rate-limited IPs stay exempt.
- **Automatic updates from GitHub releases.** New releases appear on the Plugins and Updates screens like any other plugin update. Lookups are cached for 12 hours, a failed lookup backs off for an hour, and failures are always written to the PHP error log. Sites on 2.0.0 have no updater, so need to install this release manually once.
- The `wptarl_updater_enabled` filter, to turn the updater off on staging sites or pin a site to its current version.
- `Requires at least` and `Requires PHP` plugin headers, so WordPress refuses to activate the plugin on an unsupported host.

### Fixed

- **Internal REST requests made while building a page were rate-limited.** `rest_api_init` also fires when WordPress, a theme or a plugin calls `rest_do_request()` during a page load. For a guest whose window was open, the page stopped part-way through and a 429 JSON body was sent instead of the rest of the page. Only real REST requests are now checked. Introduced in 2.0.0.

### Changed

- **The main plugin file is now `api-rate-limit-now.php`**, matching the plugin's directory and repository name. WordPress records an active plugin by its file path, so **the plugin is deactivated by this update and must be reactivated** under Plugins. Settings and the log are kept.
- The text domain is now `api-rate-limit-now`, matching the plugin slug.
- Admin CSS and JavaScript are versioned from the plugin version, not a fixed string, so browsers fetch fresh copies after an update.
- The release workflow refuses to build when the plugin header, `WPTARL_VERSION` and the `readme.txt` stable tag don't all match the git tag, and publishes byte-identical versioned and generic zips.
- Deleting the plugin also removes its cached GitHub release lookups.

## [2.0.0] - 2026-03-06

### Added

- Tabbed admin settings page under Settings > API Rate Limiter (Settings + Log tabs).
- Configurable rate limit interval from the dashboard.
- "Rate-limit all guests" toggle.
- Rate-limited IPs setting (specific IPs to throttle).
- Never rate-limited IPs setting (IP allowlist).
- Request logging with configurable retention (blocked requests only, zero performance impact).
- Daily cron for automatic log pruning with a hard cap at 5,000 rows.
- "Clear Log" button in the Log tab.
- Current client IP display with click-to-copy on the settings page.
- Settings sanitisation and validation.
- Extensibility filters: `wptarl_rate_limited_ips`, `wptarl_is_client_rate_limited`, `wptarl_seconds_between_api_calls`.
- Settings link on the Plugins page.
- Deactivation hook to clear scheduled cron.
- `uninstall.php` to clean up all options and drop the log table on deletion.
- `.distignore` for release workflow.
- GitHub Actions release workflow.

### Changed

- Complete rewrite with multi-file architecture.
- Configuration moved from PHP constants to `wp_options` via the settings page.
- Improved IP detection with `wp_unslash()` and proper validation.
- Plugin file renamed to `api-rate-limiter-now.php`.
- Text domain changed to `api-rate-limiter-now`.

### Removed

- PHP constant-based configuration (`WPTARL_SECONDS_BETWEEN_GUEST_API_CALLS`, `WPTARL_RATE_LIMITED_IPS`, `WPTARL_NEVER_RATE_LIMITED_IPS`).

## [1.0.1] - 2024-01-01

### Added

- Initial public release.
- Rate-limiting via WordPress transients.
- IP detection from `HTTP_CLIENT_IP`, `HTTP_X_FORWARDED_FOR`, `REMOTE_ADDR`.
- Localhost exclusion from rate limiting.
