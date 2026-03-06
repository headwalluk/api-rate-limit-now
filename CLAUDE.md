# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

API Rate Limiter is a WordPress plugin that rate-limits REST API calls by client IP address using WordPress transients. It hooks into `rest_api_init` and returns HTTP 429 when a rate-limited client makes requests too frequently.

**Tutorial origin:** https://wp-tutorials.tech/optimise-wordpress/rate-limit-wordpress-api-calls/

**Text Domain:** `api-rate-limiter-now`
**Function Prefix:** `wptarl_`
**Namespace:** `Api_Rate_Limiter`
**Option Prefix:** `wptarl_`
**Short Prefix:** `wptarl`

---

## Architecture

Currently a single-file plugin (`api-rate-limiter.php`). Being refactored to a multi-file structure:

```
api-rate-limit-now/
    api-rate-limiter-now.php     # Main plugin file (bootstrap)
    constants.php               # Constants, option keys, defaults
    functions-private.php       # Private/internal helper functions
    phpcs.xml                   # Code standards configuration
    includes/
        class-plugin.php        # Main Plugin class, hook registration
        class-settings.php      # Settings API registration & sanitization
        class-admin-hooks.php   # Admin menu, asset enqueueing
    admin-templates/            # Admin page templates (code-first, no inline HTML)
    assets/
        admin/                  # Admin CSS/JS
    dev-notes/                  # Development documentation
    languages/                  # Translation files
```

### Core Components

- **IP detection** (`wptarl_client_ip`): Checks `HTTP_CLIENT_IP`, `HTTP_X_FORWARDED_FOR`, `REMOTE_ADDR` in order, caches result in a global variable.
- **Rate limiting** (`wptarl_rest_api_init`): Uses WordPress transients (keyed by `wptarl_{ip}`) as short-lived flags. If the transient exists, the request is blocked with 429; otherwise a new transient is created with a TTL.
- **Configuration**: All settings stored in `wp_options`, managed via an admin settings page. No backward compatibility with old PHP constants - this is a clean rewrite. Defaults defined in `constants.php`.
- **Extensibility filters**: `wptarl_rate_limited_ips`, `wptarl_is_client_rate_limited`, `wptarl_seconds_between_api_calls`.

---

## Coding Standards

All code must follow the standards defined in `.github/copilot-instructions.md` and `dev-notes/`. Key rules:

### PHP

- **WordPress Coding Standards** enforced via phpcs/phpcbf
- **PHP 8.0+** features: type hints, union types, nullable types
- **NO `declare(strict_types=1);`** - breaks WordPress/WooCommerce interop
- **Tabs for indentation**, Yoda conditions
- **Namespaces** for classes (`Api_Rate_Limiter`), no prefix needed inside namespaces
- **`snake_case`** function names, prefixed with `wptarl_` for global functions
- **Single-Entry Single-Exit (SESE)**: functions should have one return statement at the end
- **No magic values**: all strings/numbers defined as constants in `constants.php`
  - `DEF_` prefix for defaults, `OPT_` prefix for wp_options keys
- **Boolean options**: use `filter_var()` with `FILTER_VALIDATE_BOOLEAN`
- **Date/time**: store as human-readable `Y-m-d H:i:s T`, not Unix timestamps

### Security

- Sanitize all input (`sanitize_text_field`, `absint`, etc.)
- Escape all output (`esc_html`, `esc_attr`, `esc_url`)
- Verify nonces on all form submissions
- Check capabilities (`current_user_can()`)
- Prepare database queries with `$wpdb->prepare()`

### Templates & HTML

- **No inline HTML** in functions or templates - use `printf()` / `echo` (code-first)
- **No inline JavaScript** - all JS in separate files loaded via `wp_enqueue_script()`
- **Button elements** must include the `button` CSS class

### JavaScript

- Class-based selectors (not IDs, except unique admin elements)
- Modern vanilla JS (no jQuery dependency unless necessary)
- Container-scoped initialization

### Git Workflow

- Run `phpcs` before every commit, auto-fix with `phpcbf`
- Commit message format: `type: brief description` (`feat:`, `fix:`, `chore:`, `refactor:`, `docs:`, `style:`, `test:`)
- Commit related changes together, never commit with phpcs violations

---

## Protected Directories

### `pwpl/` (if present)

DO NOT ALTER any files within `pwpl/`. Contains Power Plugins licence management. Treat as a sealed third-party dependency.

### `dev-notes/`

Development documentation. Not checked by phpcs. Do not delete.

---

## Key Behaviors

- If the rate-limited IPs list is empty (default), all non-logged-in users are rate-limited.
- Localhost IPs (`127.0.0.1`, `::1`) are never rate-limited.
- The plugin has no build step and no dependencies beyond WordPress core.

---

## WordPress Environment

This plugin lives inside a WordPress installation at `/var/www/devx.headwall.tech/web/`.

---

## Reference Documentation

- `.github/copilot-instructions.md` - Full coding standards (portable across projects)
- `dev-notes/patterns/` - Implementation patterns (settings API, admin tabs, caching, templates, JS)
- `dev-notes/workflows/` - Git commit workflow, PHPCS setup
- `dev-notes/00-project-tracker.md` - Development plan and milestone tracking
