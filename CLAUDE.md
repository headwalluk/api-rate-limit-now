# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

API Rate Limiter is a WordPress plugin that rate-limits REST API calls by client IP address. On `rest_api_init` it decides whether the client is rate-limited and, if so, allows one request per interval, tracked with a transient keyed by IP. A request inside the interval gets HTTP 429.

- **Slug / directory / repo:** `api-rate-limit-now` (`headwalluk/api-rate-limit-now` on GitHub)
- **Main file:** `api-rate-limit-now.php`
- **Namespace:** `Api_Rate_Limiter` for all classes and helpers
- **Text Domain:** `api-rate-limit-now`
- **Prefix:** `wptarl_` for options, filters, transients, the log table, cron hook and asset handles
- **PHP:** 8.0+ (do NOT use `declare(strict_types=1)` — breaks WordPress interop). `mixed`, union types and the nullsafe operator are fine; `true`/`false`/`null` standalone types and `readonly` properties are not (8.1/8.2)
- **WordPress:** 6.0+
- **No build system** — no npm, no Composer, no bundler. Assets are plain CSS/JS.
- **Origin:** the tutorial at https://wp-tutorials.tech/optimise-wordpress/rate-limit-wordpress-api-calls/

The maintainer's reference plugin for structure and conventions is `quick-2fa` (a sibling
directory). Where this file is silent, follow it.

This plugin is published publicly on GitHub. Tracked files must contain no client names,
client URLs or client data of any kind — in code, comments, docs, fixtures or commit messages.

`dev-notes/` is **private and untracked** (`.gitignore`), backed up with the dev site, and
blocked from the web by `dev-notes/.htaccess`. Never copy content from `dev-notes/` into a
tracked file without scrubbing it, and never reference a `dev-notes/` path from a file that
ships in the release zip.

## Commands

```bash
phpcs                              # Check WordPress Coding Standards (configured in phpcs.xml)
phpcbf                             # Auto-fix coding standards violations
phpcs includes/class-plugin.php    # Check a specific file
```

```bash
wp-translate . --check-instructions   # Is the block at the end of this file still current?
wp-translate . --sync-instructions    # Update it; review the diff afterwards
wp-translate . --dry-run              # Preview; no DeepL calls, no writes
```

Never hand-edit inside the `wp-translate:begin`/`end` markers — the block is hash-validated.

## Testing

There is no unit-test framework and none is wanted. Behaviour is exercised against the live
dev site (`https://devx.headwall.tech`, WordPress root `/var/www/devx.headwall.tech/web/`)
through WP-CLI and `curl`:

```bash
# Two requests inside the interval: the second should print 429
curl -s -o /dev/null -w "%{http_code}\n" https://devx.headwall.tech/wp-json/
curl -s -o /dev/null -w "%{http_code}\n" https://devx.headwall.tech/wp-json/

# Exercise the cron path and inspect the log table
wp cron event run wptarl_prune_log
wp db query "SELECT * FROM $(wp db prefix)wptarl_log ORDER BY id DESC LIMIT 5"
```

Reusable harnesses live in `dev-notes/testing/` (see its `README.md`); run them all after touching
the matchers, `docs/` matching tables, or `Plugin::enforce_rate_limit()`.

Two rules: reset the state you touch afterwards (`wp option delete`, `wp transient delete`),
and test the **defensive** path as well as the happy one — a filter returning the wrong type,
a missing `$_SERVER` key, a request with no determinable IP.

## Architecture

### Entry Point & Initialization

`api-rate-limit-now.php` defines `WPTARL_VERSION`, `WPTARL_FILE` and `WPTARL_BASENAME`,
requires `constants.php`, `functions-private.php` and each class (no autoloader), creates
`Github_Updater` in the admin area and cron only, then `wptarl_init()` creates
`Api_Rate_Limiter\Plugin` and calls `run( __FILE__ )`. `run()` registers every hook, creates the log table if `DB_VERSION` has
moved, and schedules the daily `wptarl_prune_log` event.

### Request Flow

1. `rest_api_init` → `Plugin::handle_rate_limiting()`, which returns at once unless `REST_REQUEST` is true (`rest_api_init` also fires for internal `rest_do_request()` calls during page renders)
2. `wptarl_client_ip()` reads `HTTP_CLIENT_IP`, `HTTP_X_FORWARDED_FOR`, `REMOTE_ADDR` in order; the first single valid IP wins, cached in a global for the request
3. `Plugin::is_rate_limited()`: never-limited IPs → never-limited routes (segment match on `$wp->query_vars['rest_route']`) → rate-limited user agents → rate-limited IPs (`wptarl_rate_limited_ips`) → *all guests* toggle. WooCommerce API-key and application-password requests are already authenticated at this point, so they count as logged in
4. `wptarl_is_client_rate_limited` filter has the final say
5. `Plugin::enforce_rate_limit()`: no transient → set one for `wptarl_seconds_between_api_calls` seconds, holding the window's end time; transient present → `Log::record_block()`, a `Retry-After` header from `get_retry_after()`, and `wp_send_json( …, 429 )`, which exits

### Key Files

| File | Purpose |
|------|---------|
| `api-rate-limit-now.php` | Plugin header, version/file/basename constants, class loading, init, deactivation hook |
| `constants.php` | Option keys (`OPT_`), defaults (`DEF_`), settings slugs, DB version, log limits |
| `functions-private.php` | Namespaced helpers: client IP detection, IP list parsing. Private to the plugin |
| `includes/class-plugin.php` | Hook registration, rate-limit decision and enforcement, Clear Log handler, prune cron callback |
| `includes/class-settings.php` | Settings API registration and sanitize callbacks |
| `includes/class-admin-hooks.php` | Settings menu, settings page render, Plugins-screen link, admin asset loading |
| `includes/class-log.php` | Log table: `dbDelta` creation, insert, recent query, prune, clear |
| `admin-templates/settings-page.php` | Settings and Log tabs (code-first template) |
| `assets/admin/admin.js`, `admin.css` | Tab switching, click-to-copy IP; loaded only on the settings page |
| `includes/class-github-updater.php` | In-plugin updater: checks GitHub Releases and feeds the WordPress update transient. Holds the `log()` / `log_error()` split described under **Logging** |
| `uninstall.php` | Deletes options and updater transients, drops the log table, clears cron |

### Data Storage

- **`wp_options`** — one option per setting (not a serialised array), plus `wptarl_db_version`
- **Transients** — `wptarl_{ip}`, value = Unix time the window ends (`'1'` in 2.0.0, handled as a fallback), TTL = the interval. Updater: `wptarl_github_release` (12 h) and `wptarl_github_failed` (1 h back-off)
- **Custom table** — `{prefix}wptarl_log` (`id`, `client_ip`, `blocked_at` DATETIME in site time, `request_uri`). Pruned daily by retention days and capped at `LOG_MAX_ROWS`

## Public Contracts

Code and sites outside the plugin depend on more than `docs/developers/hooks-and-filters.md`
lists. Treat everything in this table as a contract:

| Contract | Examples | Breaks when |
|----------|----------|-------------|
| Filters | `wptarl_rate_limited_ips`, `wptarl_is_client_rate_limited`, `wptarl_seconds_between_api_calls`, `wptarl_redacted_query_params`, `wptarl_log_view_rows`, `wptarl_updater_enabled` | renamed or removed, or an argument is removed or reordered |
| Option names | `wptarl_seconds_between_calls`, `wptarl_never_rate_limited_ips` | a constant's **value** changes. Documented as stable for WP-CLI configuration; saved settings under the old name are silently ignored |
| Stored formats | log table columns, `blocked_at` in site time, comma-separated IP lists, newline-separated user-agent and route lists |
| Default route exemptions | `DEF_NEVER_RATE_LIMITED_ROUTES` (`wc/store`, `wc-ppcp`) | a default is removed. Sites that never saved the setting silently start limiting shoppers' checkout requests | the format changes with no migration |
| 429 response | `{ "code": "rate_limited", … }`, `Retry-After` header | the status, `code` or header changes. Clients and monitoring match on them, and well-behaved clients schedule retries from `Retry-After` |
| Main file path | `api-rate-limit-now/api-rate-limit-now.php` | renamed. WordPress deactivates the plugin on update |

- **Add, don't change.** New filter arguments go at the end. New behaviour gets a new filter, not a new meaning for an existing one
- **Deprecate, don't rename.** Fire the old name through `apply_filters_deprecated()` and pass its result into the new filter. Remove the old name no earlier than the next major version
- **Stored data has no migration path** beyond `DB_VERSION` + `dbDelta`. Changing a stored key or value format is a breaking change: stop and ask

Before changing anything in the table: name the contract, assume there are consumers you can't
see, take the additive path if one exists, record it in `CHANGELOG.md`, and if you can't tell
whether anything depends on it, stop and ask.

### High-Impact Code

These paths decide whether a site's REST API answers:

- `wptarl_client_ip()` — a wrong IP either lets everyone through or makes every visitor share one allowance
- `Plugin::handle_rate_limiting()`, `is_rate_limited()` and `enforce_rate_limit()`

When a change touches one, say so, test it against the dev site with `curl` (limited and
exempt clients, logged in and out), and recommend the maintainer read that diff line by line
before tagging. A review by an AI agent, your own included, does not count as that read.

### Where the Plugin Runs

- **Every REST request, front end included.** `run()` and the rate-limit check run on public traffic. Keep that path cheap: autoloaded options and one transient, no uncached queries. Database writes happen only when a request is blocked
- **Request context.** Hook callbacks can run under WP-CLI, cron and REST, with no current user or admin screen. Check for what the code needs
- **Install layout.** Build URLs and paths with `admin_url()`, `plugins_url()` and `__DIR__`-based paths, never by joining strings onto the domain
- **Proxies and CDNs.** The client IP is only as good as what the web server hands PHP. Any change to IP detection must say how it behaves behind a reverse proxy
- **Multisite is untested.** Options, transients and the log table are per site

## Code Conventions

### PHP Style

- **Namespace:** `Api_Rate_Limiter` for all classes and helper functions. Global functions in the main file are prefixed `wptarl_`
- **Single-Entry Single-Exit (SESE):** functions generally have one `return` at the end. Top-of-function guard clauses (capability checks, disabled-feature short-circuits, missing input) are acceptable when they keep the rest of the function flat. Never `return` mid-function, inside a loop, or nested several `if` blocks deep
- **An `if` with one or more `elseif` branches ends in a plain `else`**, never an `elseif`. A branch that does nothing is still written out, with a short comment. A lone `if` needs no `else`. `phpcs.xml` excludes the `if`/`elseif`/`else` codes of `Generic.CodeAnalysis.EmptyStatement` so these comment-only branches pass; empty `catch`, loop and `switch` bodies are still errors
- **No assignment inside a condition** — assign on the line before
- **No unreachable `return`** after a call that always exits (`wp_send_json()`, `wp_safe_redirect()` + `exit`, `wp_die()`), and no bare `return;` as the last statement of a `void` function
- **Constants for all magic strings/numbers** in `constants.php`: option keys, hook names, nonce actions, capabilities, limits. A key constant's **value** is stored data and never changes; see **Public Contracts**
- **Type hints and return types** on all functions and class properties, within the PHP 8.0 floor. Only PHP 8.4+ is installed on the dev server, so nothing checks the floor mechanically: check new syntax against it by hand
- **Callbacks on hooks the plugin doesn't own take `mixed`.** Any earlier callback can hand over the wrong type, and a typed parameter turns that into a `TypeError` on a request the plugin doesn't control. Check each value before use, and pass a value a filter callback can't use through unchanged
- **Check what a filter returns.** Read a boolean result with `filter_var( …, FILTER_VALIDATE_BOOLEAN )`, and fall back to the default for a malformed array
- **Cast at the boundary.** `get_option()` returns strings (or `false`). Cast at the point of read: `absint( get_option( OPT_LOG_RETENTION, DEF_LOG_RETENTION ) )`
- **Don't duplicate derived values.** Where two places need the same computation (reading a boolean option, the settings page URL), extract a helper
- **Dates:** new stored dates are Unix timestamps (`time()`), formatted for display with `wp_date()`. The log table's `blocked_at` predates this rule and is a stored format; see **Public Contracts**

### Security

- Sanitize all input (`sanitize_text_field()`, `absint()`, `wp_unslash()` first)
- Escape all output at the point of output (`esc_html()`, `esc_attr()`, `esc_url()`, `esc_textarea()`)
- Verify a nonce and check `current_user_can()` on every state-changing request
- `$wpdb->prepare()` for every query with a variable; the table name is the one interpolation, from `Log::table_name()`
- **Never store or display credentials.** Anything request-derived that is persisted (today: the logged request URI) goes through `wptarl_redact_request_uri()` **before** sanitizing and truncating. Integrations still send secrets in query strings

### Template Pattern (Code-First)

Templates use `printf()`/`echo` exclusively — no inline HTML mixed with PHP snippets. This prevents whitespace bleeding into attributes and values.

```php
// Correct
printf(
	'<button class="button">%s</button>',
	esc_html__( 'Clear Log', 'api-rate-limit-now' )
);

// Wrong — no inline HTML
<button class="button"><?php esc_html_e( 'Clear Log', 'api-rate-limit-now' ); ?></button>
```

- No inline JavaScript and no inline `style` attributes — use `assets/admin/`
- Button elements carry the `button` CSS class

### CSS and JavaScript

- **Logical properties for anything with a left or right** (`margin-inline-start`, `text-align: start`), never `margin-left` or `text-align: left`. Top and bottom stay physical
- **Plain JavaScript**, no jQuery. Class-based selectors, initialised within a container

### Logging

Two methods, deliberately split — see `Github_Updater::log()` / `log_error()`. There is no
logging dependency; use `error_log()` with a `phpcs:ignore`.

- **Genuine failures** (a failed insert, a failed HTTP request, a malformed response) log **unconditionally**, so a sysadmin sees them without touching config
- **Routine flow tracing** logs only when `WP_DEBUG` is on

An error that only appears under `WP_DEBUG` is a silent failure in production. Never leave a
`catch` that records nothing. Never log on the allowed-request path: it runs on every REST call.

### Comments

- One-line docblock summary per function, saying what it does. `@param`, `@return` and `@since` lines don't count
- Inline comments only where the mechanism isn't obvious: a load-order trap, an API behaving unexpectedly, a guard whose absence would be silently wrong
- Don't restate what the names already say
- Reasoning and history go in `docs/`, not in comments; see **Reference Files**

### Boolean Options

Use `filter_var()` with `FILTER_VALIDATE_BOOLEAN` to handle all WordPress boolean formats (`'1'`, `'yes'`, `'on'`, `true`):

```php
$logging_enabled = (bool) filter_var( get_option( OPT_LOGGING_ENABLED, DEF_LOGGING_ENABLED ), FILTER_VALIDATE_BOOLEAN );
```

### Commit Messages

Format: `type: brief description` where type is one of: `feat:`, `fix:`, `chore:`, `refactor:`, `docs:`, `style:`, `test:`

### Pre-Commit Workflow

1. `phpcs` — check violations
2. `phpcbf` — auto-fix
3. `phpcs` — verify clean: no errors **and no warnings**
4. Stage and commit

Every `phpcs:ignore` and `phpcs:disable` names the exact sniff and ends with `-- reason`.

## Release Workflow

1. Update the version in `api-rate-limit-now.php` — **both** the `Version:` header and the `WPTARL_VERSION` constant
2. Update `CHANGELOG.md`: move the `[Unreleased]` entries under the new version
3. Update `readme.txt`: stable tag, changelog, and an upgrade notice for anything a site owner must act on
4. Run `phpcs` to verify compliance
5. If the release touches **High-Impact Code**, the maintainer reads that diff line by line
6. Tag `vX.Y.Z` and push; `.github/workflows/release.yml` builds the zip (excluding `.distignore` entries) and creates the GitHub Release

The version lives in **three** places that must agree with the git tag: the header `Version:`
field, `WPTARL_VERSION`, and the `readme.txt` stable tag. `release.yml` refuses to build on a
mismatch, and `Github_Updater` logs an error when the header and constant drift at runtime.

**The `Version` must always correspond to a real GitHub Release tag.** Setting it to a version
that doesn't exist on GitHub degrades the updater experience. New `@since` tags use the next
planned version; if the release number changes, update them.

## Reference Files

`docs/` is the maintained entry point and is tracked/public. **One audience per document** —
do not mix operator and developer material in the same file:

- `docs/how-it-works.md`, `docs/configuration.md`, `docs/examples.md`, `docs/troubleshooting.md` — site operators. `configuration.md` holds the matching tables for routes and user agents; any change to `wptarl_normalise_route_prefix()` or the matchers must update them and the examples
- `docs/developers/hooks-and-filters.md` — the public extension surface

A change to behaviour, a setting or a filter updates the matching `docs/` page in the same
commit. Rationale, evidence and history belong in `docs/`, not in code comments.

Supporting material (private, untracked):

- `dev-notes/00-project-tracker.md` — milestones, deferred features
- `dev-notes/01-prompts-and-code-housekeeping.md` — the current housekeeping refactor: decisions, findings, stage checklist. Check it before touching a PHP file
- `CHANGELOG.md` — per-version release notes (tracked)

<!-- wp-translate:begin v=1.2.0 hash=d8f2f50cf76cdc356af3e990068b37aa947f7012477f3639d41acbfa9db96c36 -->
## Translating this plugin (wp-translate conventions)

This plugin's `.po`/`.mo` files are generated from source by
[wp-translate](https://github.com/headwalluk/wp-translate-tool), which
machine-translates strings with DeepL. Machine translation is only as good as
the strings you give it — follow these conventions when adding or editing
user-facing text.

### 1. Disambiguate short or ambiguous strings with `_x()`

DeepL handles full sentences well but guesses badly on short, context-free
labels. Give it context with `_x()` (or `esc_html_x()`, `_ex()`):

```php
// Ambiguous out of context — DeepL may read "Sent" as "late", "Folder" as "leaflet"
__( 'Sent', 'api-rate-limit-now' );

// Disambiguated — the context is passed to the translator and to DeepL
_x( 'Sent', 'email delivery status', 'api-rate-limit-now' );
_x( 'Folder', 'IMAP mailbox', 'api-rate-limit-now' );
_x( 'Open', 'verb; button label', 'api-rate-limit-now' );
```

The context (2nd argument) is never shown to users. Use it whenever a string is a
single word, a short label, or has more than one plausible meaning.

### 2. Use placeholders, never concatenation

Build dynamic text with `printf`/`sprintf` so the whole sentence translates as a
unit, and add a `translators:` comment to explain each placeholder:

```php
/* translators: %s is the user's display name */
printf( esc_html__( 'Welcome back, %s', 'api-rate-limit-now' ), $name );
```

Never split a sentence across multiple translation calls — word order differs
between languages.

### 3. Use `_n()` for anything that can be counted

Never build a count-dependent sentence by hand, and never settle for a single
form that reads correctly only for one number. Languages differ in how many
plural forms they have — English and German have two, French treats 0 as
singular, Polish and Russian have three, Japanese has one, Arabic has six — and
`_n()` is the only way to express that.

```php
// Wrong — "1 reviews", and untranslatable into languages with other forms
printf( esc_html__( '%d reviews', 'api-rate-limit-now' ), $count );

// Right — wp-translate fills every form the target locale needs
printf(
    esc_html( _n( '%d review', '%d reviews', $count, 'api-rate-limit-now' ) ),
    $count
);
```

Keep the placeholder in **both** forms, even when the singular reads fine
without it (`'%d review'`, not `'One review'`) — some locales use the singular
slot for other numbers too.

For a short or ambiguous countable noun, use `_nx()` — the plural equivalent of
`_x()` — so the context reaches DeepL:

```php
// "Review" alone is ambiguous: critique? opinion? inspection?
_nx( '%d review', '%d reviews', $count, 'customer feedback on a company', 'api-rate-limit-now' );
```

**Locales needing more than two forms will have their extra slots left empty for
a human translator.** DeepL supplies a singular and a plural; nobody can invent
Polish's third form from those, and wp-translate deliberately leaves it blank
rather than filling it with a plausible guess. Expect to see empty
`msgstr[2]` entries in `pl_PL` — that is correct behaviour, not a failure.

### 4. Acronyms and technical tokens

wp-translate keeps common acronyms (`TLS`, `API`, `SMTP`, `URL`, `ID`, `UTC`, …)
verbatim automatically. If you introduce an unusual acronym or product name that
must not be translated, keep it as its own standalone string so it is recognised,
or ask the maintainer to add it to the tool's acronym list.

### 5. Don't translate dates — let WordPress localise them

Never add month or day-of-week names (full or abbreviated) as translatable
strings. DeepL frequently mistranslates short forms like `Mon`, `Tue`, `Jan`,
`Feb` even with context hints. WordPress already ships locale-aware names — use
`$wp_locale`:

```php
global $wp_locale;
$wp_locale->get_month( $month_number );        // "January" (1-based)
$wp_locale->get_month_abbrev( $month_name );   // "Jan"
$wp_locale->get_weekday( $weekday_number );     // "Monday" (0 = Sunday)
$wp_locale->get_weekday_abbrev( $weekday_name ); // "Mon"
```

For formatted dates, prefer `wp_date()` / `date_i18n()`, which localise month and
day names automatically.

### 6. English source dialect

Write source strings in standard English. wp-translate handles English targets
locally (no DeepL): `en`/`en_US` use the source as-is, and `en_GB`/`en_AU`/… get
American spellings converted to British automatically (`color` → `colour`).

### Running wp-translate

After changing strings, regenerate translations:

```bash
wp-translate /path/to/this-plugin              # auto-detect locales from languages/
wp-translate /path/to/this-plugin en_GB,fr_FR  # explicit locales
wp-translate /path/to/this-plugin --dry-run    # preview; no API calls, no writes
```

Requires WP-CLI (`wp`) and a DeepL API key at `~/.config/deepl.env`. The tool
regenerates the `.pot` from source, translates new/changed strings for each
locale, and compiles the `.mo` files.
<!-- wp-translate:end -->
