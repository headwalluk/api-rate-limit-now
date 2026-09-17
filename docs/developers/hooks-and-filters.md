# Hooks and filters

This is the **public extension surface** of API Rate Limiter. Anything not listed here is internal and may change without notice between releases. Functions, classes and constants in the `Api_Rate_Limiter` namespace are private; the filters below are the supported integration points.

## Compatibility

- A filter's name and arguments don't change within a major version. New arguments are only ever added at the end.
- A renamed filter keeps working under its old name until at least the next major version, and any change that could break an integration is listed in `CHANGELOG.md`.

## Filters

The first three filters run during the rate-limit check on `rest_api_init`, for real REST requests only (`REST_REQUEST` is true), in this order: `wptarl_rate_limited_ips` → `wptarl_is_client_rate_limited` → `wptarl_seconds_between_api_calls`. Add them from a plugin or mu-plugin, or from your theme's `functions.php`, so they're registered before the REST API starts.

---

### `wptarl_rate_limited_ips`

Filter the list of IP addresses to rate-limit. Runs only when the client's IP isn't on the *Never rate-limited IPs* list, the route isn't on *Never rate-limited routes*, and the user agent isn't on *Rate-limited user agents*.

**Parameters:**
- `array $ips` — IPs from the *Rate-limited IPs* setting, as an array of strings. Empty by default

**Returns:** `array` — IP address strings. If the array has any entries, only those IPs are limited and the *Rate-limit all guests* setting is ignored. Return an empty array to fall back to that setting.

```php
add_filter( 'wptarl_rate_limited_ips', function ( $ips ) {
    $ips[] = '203.0.113.50';
    return $ips;
} );
```

---

### `wptarl_is_client_rate_limited`

Override whether the current client is rate-limited, after the plugin has made its own decision from the settings, including *Rate-limited user agents*. For a fixed list of user agents, use that setting instead of this filter.

**Parameters:**
- `bool $is_limited` — The plugin's decision

**Returns:** `bool` — `true` to rate-limit this request, `false` to let it through unchecked.

Returning `true` has no effect when the plugin couldn't determine the client's IP address.

```php
// Never rate-limit requests carrying our integration's API key.
add_filter( 'wptarl_is_client_rate_limited', function ( $is_limited ) {
    $api_key = isset( $_SERVER['HTTP_X_API_KEY'] ) ? wp_unslash( $_SERVER['HTTP_X_API_KEY'] ) : '';

    if ( defined( 'MY_API_KEY' ) && hash_equals( MY_API_KEY, $api_key ) ) {
        $is_limited = false;
    }

    return $is_limited;
} );
```

---

### `wptarl_seconds_between_api_calls`

Filter the interval for a client. Runs only for a rate-limited client whose previous request is outside the interval, just before its new transient is set.

**Parameters:**
- `mixed $seconds` — The *Seconds between API calls* setting. Read from the database, so usually a numeric string rather than an `int`
- `string $client_ip` — The client's IP address

**Returns:** `int` — Seconds until this client may make another request. The value is passed through `absint()`; `0` sets no transient, so the client's next request is allowed too.

```php
// Allow a known partner to make faster requests.
add_filter( 'wptarl_seconds_between_api_calls', function ( $seconds, $client_ip ) {
    if ( '198.51.100.10' === $client_ip ) {
        $seconds = 2;
    }

    return $seconds;
}, 10, 2 );
```

---

### `wptarl_updater_enabled`

Disable the in-plugin GitHub updater. Useful for staging environments, local development, or pinning a site to its current version.

**Parameters:**
- `bool $enabled` — `true` by default

**Returns:** `bool` — Return `false` to stop update checks. Read as a boolean the way WordPress options are, so `'no'`, `'off'` and `'0'` also count as false.

```php
// Disable updates on staging.
add_filter( 'wptarl_updater_enabled', function ( $enabled ) {
    if ( 'staging' === wp_get_environment_type() ) {
        $enabled = false;
    }

    return $enabled;
} );

// Pin a production site to its current version.
add_filter( 'wptarl_updater_enabled', '__return_false' );
```

The updater runs only in the admin area and during cron, so register this filter from a plugin or mu-plugin.

---

## Actions

API Rate Limiter doesn't define any action hooks.

## What about constants, options and functions?

Constants, functions and classes in `Api_Rate_Limiter\*` are **private**. Don't reference them from your own code. If you find yourself wanting to call into the plugin, open an issue on GitHub describing the use case so a public hook can be considered.

To configure the plugin from code or WP-CLI, write the options WordPress stores for the settings page. These option names are stable:

| Option | Setting |
|--------|---------|
| `wptarl_seconds_between_calls` | Seconds between API calls |
| `wptarl_rate_limit_all_guests` | Rate-limit all guests |
| `wptarl_rate_limited_ips` | Rate-limited IPs (comma-separated) |
| `wptarl_never_rate_limited_ips` | Never rate-limited IPs (comma-separated) |
| `wptarl_never_rate_limited_routes` | Never rate-limited routes (newline-separated). When the option doesn't exist, the defaults `wc/store` and `wc-ppcp` apply; an empty string exempts nothing |
| `wptarl_rate_limited_user_agents` | Rate-limited user agents (newline-separated) |
| `wptarl_logging_enabled` | Enable logging |
| `wptarl_log_retention` | Log retention (days) |

```bash
wp option update wptarl_seconds_between_calls 5
wp option update wptarl_rate_limited_user_agents "$(printf 'ExampleInventorySync\nExampleMailer/1')"
```

Values written this way skip the settings page's validation, so write valid IP addresses and whole numbers greater than zero.
