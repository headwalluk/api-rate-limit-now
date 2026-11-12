# CLAUDE.md

Repository: public

Follows the **headwall-wp-plugin** skill. Load it before working on this plugin; this file holds
only what is specific to API Rate Limiter.

## Project Overview

Rate-limits REST API calls by client IP address. On `rest_api_init` it decides whether the client
is rate-limited and, if so, allows one request per interval, tracked with a transient keyed by
IP. A request inside the interval gets HTTP 429 with `Retry-After`.

- **Slug / directory / repo:** `api-rate-limit-now` (`headwalluk/api-rate-limit-now`). Main file `api-rate-limit-now.php`
- **Namespace:** `Api_Rate_Limiter` · **Text domain:** `api-rate-limit-now` · **Prefixes:** `wptarl_` (options, filters, transients, log table, cron hook, asset handle), `WPTARL_` (global constants)
- **PHP / WordPress floor:** 8.2 / 6.2, the skill's defaults
- **No build system, no WP-CLI commands.** Plain CSS and JS in `assets/admin/`
- **Origin:** the tutorial at https://wp-tutorials.tech/optimise-wordpress/rate-limit-wordpress-api-calls/

## Request Flow

1. `rest_api_init` → `Plugin::handle_rate_limiting()`, which returns at once unless `REST_REQUEST` is true (`rest_api_init` also fires for internal `rest_do_request()` calls during page renders)
2. `wptarl_client_ip()`: `REMOTE_ADDR`, validated, then the `wptarl_client_ip` filter, cached in a global for the request. Forwarded headers are never read: a client sets them, and trusting them let anyone escape the limit or match `127.0.0.1` on the never-limited list (fixed in 3.0.0)
3. `Plugin::is_rate_limited()`: never-limited IPs → never-limited routes (segment match on `$wp->query_vars['rest_route']`) → rate-limited user agents → rate-limited IPs (`wptarl_rate_limited_ips`; a non-empty list replaces the next step) → *all guests* toggle. WooCommerce API-key and application-password requests are already authenticated at this point, so they count as logged in
4. `wptarl_is_client_rate_limited` filter has the final say
5. `Plugin::enforce_rate_limit()`: no transient → set one for the interval (`wptarl_seconds_between_api_calls`), holding the window's end time; transient present → `Log::record_block()`, a `Retry-After` header from `get_retry_after()`, and `wp_send_json( …, 429 )`, which exits

Admin and activation: `Plugin::maintain()` (activation hook and `admin_init`) creates the log table
when `DB_VERSION` has moved and schedules the daily `wptarl_prune_log` event. Nothing on the
front-end path checks the table or cron.

## Key Files

| File | Purpose |
|------|---------|
| `api-rate-limit-now.php` | Header, `WPTARL_*` constants, requires, updater, init, activation and deactivation hooks |
| `constants.php` | Option keys (`OPT_`) and defaults (`DEF_`, incl. the default route exemptions), bounds, handles, nonce names, DB version, log limits, redaction fragments, updater repository |
| `functions-private.php` | Client IP and User-Agent, IP and line list parsing, current REST route, route prefix normalisation (paste handling), request URI redaction, `log_error()` |
| `includes/class-plugin.php` | Hook registration, `maintain()`, `REST_REQUEST` guard, the rate-limit decision and enforcement, Clear Log handler, prune cron callback |
| `includes/class-settings.php` | Settings API registration and sanitisers (`sanitize_range()` for the numeric ones) |
| `includes/class-admin-hooks.php` | Settings menu, page render, Plugins-screen link, admin assets |
| `includes/class-log.php` | Log table: `dbDelta` creation with an existence check, redacted insert, recent query, prune, clear. Every failure goes to `log_error()` |
| `admin-templates/settings-page.php` | Settings and Log tabs |
| `includes/class-github-updater.php` | Copied from Quick 2FA; change only `namespace` and `@package` |
| `uninstall.php` | Opt-in: options and rate-limit transients by prefix, the log table, the updater cache. Always clears the cron event |

## Data Storage

- **Options:** one per setting (not a serialised array), all `wptarl_`, plus `wptarl_db_version`
- **Transients:** `wptarl_{ip}`, value = Unix time the window ends (`'1'` in 2.0.0, handled as a fallback), TTL = the interval. Updater: `api_rate_limit_now_github_release` / `_github_failed`
- **Custom table:** `{prefix}wptarl_log` (`id`, `client_ip`, `blocked_at` DATETIME in site time, `request_uri`). Pruned daily by retention days and capped at `LOG_MAX_ROWS`
- **Uninstall deletes nothing** unless `wptarl_delete_data_on_uninstall` is on. Multisite is untested: options, transients and the table are per site

## Public Contracts

The skill's `public-contracts.md` has the rules. This plugin's:

| Contract | Names |
|----------|-------|
| Filters | `wptarl_client_ip`, `wptarl_rate_limited_ips`, `wptarl_is_client_rate_limited`, `wptarl_seconds_between_api_calls`, `wptarl_redacted_query_params`, `wptarl_log_view_rows`, `wptarl_updater_enabled` |
| Option names | the `OPT_*` values. Documented as stable for WP-CLI configuration in `docs/developers/hooks-and-filters.md` |
| Stored formats | log table columns, `blocked_at` in site time, comma-separated IP lists, newline-separated user-agent and route lists |
| Default route exemptions | `DEF_NEVER_RATE_LIMITED_ROUTES` (`wc/store`, `wc-ppcp`). Removing one silently starts limiting shoppers' checkout requests on sites that never saved the setting |
| 429 response | `{ "code": "rate_limited", … }` and `Retry-After`. Clients and monitoring match on them |
| Main file path | `api-rate-limit-now/api-rate-limit-now.php`. Renamed once, in 2.1.0; renaming deactivates the plugin on update |
| Settings page | `options-general.php?page=wptarl-settings` |

Stored data has no migration path beyond `DB_VERSION` + `dbDelta`.

### High-Impact Code

These paths decide whether a site's REST API answers:

- `wptarl_client_ip()`: a wrong IP either lets everyone through or makes every visitor share one allowance. Any change must say how it behaves behind a reverse proxy
- `Plugin::handle_rate_limiting()` (incl. the `REST_REQUEST` guard), `is_rate_limited()` and `enforce_rate_limit()`
- The matchers: `Plugin::is_route_never_rate_limited()` with `wptarl_current_rest_route()` and `wptarl_normalise_route_prefix()`, and `Plugin::is_user_agent_rate_limited()`. A wrong match refuses shoppers mid-checkout or exempts an abusive client
- `Plugin::get_retry_after()`: under-reporting sends well-behaved clients straight back into a 429

The rate-limit check runs on every REST request, front end included: keep it to autoloaded
options and one transient, no uncached queries, no logging on the allowed path. Database writes
happen only when a request is blocked.

## Testing

Harnesses live in `dev-notes/testing/` (see its `README.md`). Run them all before a release and
after touching high-impact code, the matchers or the `docs/` matching tables. All except
`test-github-updater.php` run with the plugin inactive; that one needs it active.

From the WordPress root:

```bash
for HARNESS_FILE in wp-content/plugins/api-rate-limit-now/dev-notes/testing/test-*.php; do echo "== ${HARNESS_FILE}"; wp eval-file "${HARNESS_FILE}"; done
```

HTTP checks need the plugin active; deactivate it afterwards. `/wp-json/` twice inside the
interval should give 200 then 429. Check which address the requests arrive from first (the
newest `wptarl_log` row shows it): `curl` on the server itself can arrive as a loopback address,
which is never limited by default. Under an external object cache, `wp transient list` doesn't
show the rate-limit transients. Delete test rows from `wptarl_log` afterwards.

## Release

Follow the skill's `release.md` (public). The updater's repository is `UPDATER_GITHUB_REPO` in
`constants.php`. The default branch is `main`.

## Reference Files

`docs/` (tracked, public):

- `docs/how-it-works.md`, `docs/configuration.md`, `docs/examples.md`, `docs/troubleshooting.md`: site operators. `configuration.md` holds the matching tables for routes and user agents; any change to `wptarl_normalise_route_prefix()` or the matchers must update them and the examples, and `test-doc-tables.php` checks them
- `docs/developers/hooks-and-filters.md`: the public extension surface

`dev-notes/` (private, untracked):

- `dev-notes/00-project-tracker.md`: milestones, deferred features
- `dev-notes/01-prompts-and-code-housekeeping.md`: the housekeeping refactor, findings F1–F13 and the Stage 5 per-file plan. Check it before touching a PHP file
- `dev-notes/02-client-site-api-traffic.md`: real integration traffic on a client site. **Contains client names, IPs and log extracts**: never copy from it into a tracked file
- `dev-notes/testing/`: the harnesses

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
