# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.1.0] - 2026-09-17

### Added

- **A `Retry-After` header on every 429**, giving the whole seconds until the client may make its next request. Clients that honour it back off exactly as long as needed; the status code and JSON body are unchanged. The rate-limit transient now stores when the client's window ends, rather than `1`.
- **Rate-limited user agents setting.** Requests whose `User-Agent` contains any listed string (one per line, case-insensitive) are always rate-limited, including logged-in users and integrations authenticating with WooCommerce API keys or application passwords, which were previously always exempt. Never rate-limited IPs stay exempt.
- **Automatic updates from GitHub releases.** New releases appear on the Plugins and Updates screens like any other plugin update. Lookups are cached for 12 hours, a failed lookup backs off for an hour, and failures are always written to the PHP error log. Sites on 2.0.0 have no updater, so need to install this release manually once.
- The `wptarl_updater_enabled` filter, to turn the updater off on staging sites or pin a site to its current version.
- `Requires at least` and `Requires PHP` plugin headers, so WordPress refuses to activate the plugin on an unsupported host.

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
