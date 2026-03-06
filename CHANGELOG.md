# Changelog

All notable changes to this project will be documented in this file.

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
