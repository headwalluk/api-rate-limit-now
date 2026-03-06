# Project Tracker

**Version:** 2.0.0
**Last Updated:** 06 March 2026
**Current Phase:** Release
**Overall Progress:** 100%

---

## Overview

Evolve API Rate Limiter from a single-file tutorial plugin into a proper WordPress plugin with an admin settings page. Site owners should be able to configure rate limiting from the WordPress dashboard without editing source code.

---

## Active TODO Items

None. Ready to ship v2.0.0.

---

## Milestones

### Milestone 1: Project Setup & Refactor (Foundation) - COMPLETE

Restructure the single-file plugin into a multi-file architecture following the coding standards in `.github/copilot-instructions.md`.

- [x] Create `phpcs.xml` with `wptarl` / `Api_Rate_Limiter` prefixes
- [x] Create `constants.php` with option keys (`OPT_` prefix) and defaults (`DEF_` prefix)
- [x] Create `includes/class-plugin.php` - main Plugin class, hook registration
- [x] Create `includes/class-settings.php` - Settings API registration & sanitization
- [x] Move rate-limiting logic into the Plugin class
- [x] Move IP detection into `functions-private.php`
- [x] Update main plugin file as a slim bootstrap (`api-rate-limiter-now.php`)
- [x] Re-implement extensibility filters
- [x] Run phpcs/phpcbf and fix all violations

### Milestone 2: Admin Settings Page - COMPLETE

- [x] Create `includes/class-admin-hooks.php` - admin menu, asset enqueueing
- [x] Register settings page under Settings menu (`Settings > API Rate Limiter`)
- [x] Create admin template for the settings page (code-first, no inline HTML)
- [x] All settings fields with sanitization callbacks
- [x] Settings link on the Plugins page row
- [x] Conditional admin asset loading (only on plugin settings page)

### Milestone 3: Admin UI Polish - COMPLETE

- [x] Tabbed interface (Settings + Log tabs) with hash-based navigation
- [x] Display current client IP on settings page (with click-to-copy)
- [x] Inline help text / descriptions for each setting
- [x] Admin CSS and JS (conditionally loaded)

### Milestone 4: Logging - COMPLETE

- [x] Custom database table (`wp_wptarl_log`) with dbDelta and version tracking
- [x] Log blocked requests (IP, timestamp, request URI) on the 429 path only
- [x] Enable/disable logging setting
- [x] Configurable log retention (days)
- [x] Daily cron for automatic pruning + hard cap at 5,000 rows
- [x] Log tab with recent blocked requests table
- [x] Clear Log button with nonce verification

### Milestone 5: Ship-Ready Polish - COMPLETE

- [x] `.distignore` for release workflow
- [x] Deactivation hook to clear cron schedule
- [x] `uninstall.php` to clean up options and drop log table
- [x] GitHub Actions release workflow (`release.yml`)
- [x] `README.md`, `readme.txt`, `CHANGELOG.md` fully up to date
- [x] All files passing phpcs with zero errors and warnings

---

## Completed Items

- [x] Project restructured from single-file to multi-file architecture
- [x] All configuration moved from PHP constants to `wp_options`
- [x] Tabbed admin settings page with all fields and sanitisation
- [x] Request logging with automatic pruning
- [x] Settings link on Plugins page
- [x] Current IP display with click-to-copy
- [x] Inline help descriptions on all settings fields
- [x] Admin CSS and JS (conditionally loaded)
- [x] Deactivation and uninstall cleanup
- [x] `README.md`, `readme.txt`, `CHANGELOG.md` created and updated
- [x] GitHub Actions release workflow configured
- [x] `.distignore` created
- [x] All files passing phpcs with zero errors and warnings

---

## Technical Debt

None currently. All legacy issues from v1.x were resolved during the rewrite.

---

## Future Considerations

- Per-route rate limiting (different limits for different API endpoints)
- Custom 429 response message (configurable from settings)
- Export/import settings
- Multisite support

---

## Notes for Development

- No backward compatibility with the old PHP constants. This is a clean rewrite.
- Settings are stored as individual `wp_options` entries (not a serialised array).
- Defaults are defined in `constants.php` using `DEF_` prefixed constants.
- The plugin has no build step. No npm, no Composer runtime dependencies.
- Main plugin file is `api-rate-limiter-now.php` (no namespace on this file).
- Text domain is `api-rate-limiter-now`.
- Logging only writes to DB on the 429 path, so zero impact on normal traffic.
