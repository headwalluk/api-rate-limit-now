# How it works

API Rate Limiter allows each rate-limited client one REST API request per interval. A second request inside the interval is refused with HTTP 429 (Too Many Requests) and told how long to wait.

## Which requests are checked

Only **real REST API requests** are checked: requests to `/wp-json/…`, or to `?rest_route=…` on sites without pretty permalinks. That includes plugin routes such as WooCommerce's REST API (`wc/v3`) and Store API (`wc/store`).

These are never checked:

- Normal page loads, the admin area, `admin-ajax.php`, WP-CLI and cron
- REST requests that WordPress, a theme or a plugin makes internally while building a page (`rest_do_request()`). They run inside the visitor's page load, not as a separate API call

Some plugins serve REST routes through a different URL. A payment plugin that answers `/?wc-ajax=…&path=/…` by handing the request on to the REST API is making a real REST request, and it's checked like one. Its route is the `path` value.

## The request flow

1. **Find the client's IP address.** The plugin reads `HTTP_CLIENT_IP`, then `HTTP_X_FORWARDED_FOR`, then `REMOTE_ADDR`, and uses the first that holds a single valid IP address. If none does, the request is let through.
2. **Decide whether this client is rate-limited**, from the settings. See [Who is rate-limited](#who-is-rate-limited).
3. **Let code change the decision.** The `wptarl_is_client_rate_limited` filter has the final say. See [hooks and filters](developers/hooks-and-filters.md).
4. **Check the client's window.** Each rate-limited IP address has a short-lived WordPress transient.
   - No transient: the request proceeds, and a transient is created that lasts for the interval.
   - A transient exists: the request is refused with a 429 and, if logging is on, recorded in the log.

A refused request doesn't restart the window, so the interval runs from the last request that was **allowed**. A client that waits for the `Retry-After` time on its 429 is allowed on its next request.

The window belongs to the IP address, not to a user or user agent. Two clients behind the same address, such as an office network or two integrations on one server, share one window.

## Who is rate-limited

The settings are applied in this order, and the first that decides wins:

| Step | Setting | Outcome |
|------|---------|---------|
| 1 | **Never rate-limited IPs** contains the client's IP | Not limited |
| 2 | **Never rate-limited routes** contains the request's route, or a route above it | Not limited |
| 3 | **Rate-limited user agents** has a string found in the request's `User-Agent` | Limited, even if logged in |
| 4 | **Rate-limited IPs** has any entries | Limited only if the client's IP is on the list, even if logged in |
| 5 | **Rate-limit all guests** is on (the default) | Limited only if the client isn't logged in |
| 6 | None of the above | Not limited |

Some consequences of that order:

- An exempt IP or route beats a listed user agent. A listed integration calling a never rate-limited route isn't limited.
- Once **Rate-limited IPs** has an entry, *Rate-limit all guests* is ignored, so guests not on the list are no longer limited.
- With the defaults (steps 2 and 5), every guest REST request is limited except those to the WooCommerce Store API and PayPal button routes.

### API clients count as logged in

"Logged in" means WordPress has identified a user for the request, which covers more than a browser session. Requests authenticated with WooCommerce REST API keys or WordPress application passwords count as logged in, so under the default settings **integrations using API keys are never rate-limited**.

To limit an integration that authenticates, add a string from its `User-Agent` to **Rate-limited user agents**. See [examples](examples.md#limit-an-integration-that-uses-api-keys).

## What a blocked client sees

An HTTP 429 status, a `Retry-After` header giving the whole seconds to wait before the next request is allowed, and a JSON body:

```http
HTTP/1.1 429 Too Many Requests
Retry-After: 8
Content-Type: application/json; charset=UTF-8

{"code":"rate_limited","message":"Too many requests. Please slow down."}
```

`Retry-After` counts down as the window runs out. A well-built client reads it and waits; one that retries straight away is refused again each time.

## Logging

When logging is enabled, each refused request is written to a database table (`{prefix}wptarl_log`) with the client IP, the time and the request URI. Allowed requests are never logged, so normal traffic adds no database writes.

Credentials in the request URI are never stored. Any query parameter whose name contains `key`, `secret`, `token`, `password`, `passwd`, `nonce`, `signature`, `oauth`, `session` or `credential` (ignoring case) has its value replaced with `REDACTED`, keeping the parameter's name:

```
/wp-json/wc/v3/products?consumer_key=ck_…&consumer_secret=cs_…&page=2      ← request
/wp-json/wc/v3/products?consumer_key=REDACTED&consumer_secret=REDACTED&page=2  ← logged
```

Parameters with other names are logged as sent, and the stored URI is cut to 255 characters. This protects this plugin's log only: your web server's access log still records full URLs. Integrations that send credentials in the URL should use an `Authorization` header instead.

The **Log** tab on the settings page shows the 100 most recent entries. Times are in the site's timezone. A daily scheduled task deletes entries older than the retention period, and trims the table to its newest 5,000 rows.

## Performance

For a rate-limited client, an allowed request costs one transient read and one write. A request that's exempt, or from a client that isn't limited, costs no transient work at all. Settings are ordinary WordPress options, loaded with the rest of the site's options. With a persistent object cache (Redis, Memcached), transients never touch the database.

## Updates

The plugin updates itself from its [GitHub releases](https://github.com/headwalluk/api-rate-limit-now/releases). When WordPress checks for plugin updates, the plugin asks GitHub for the latest release, and a newer version appears on the Plugins and Updates screens like any other plugin update. It's installed the same way, manually or through WordPress's automatic updates if you've enabled them for this plugin.

The GitHub response is cached for 12 hours. If GitHub can't be reached, the plugin waits an hour before trying again. Checks run only in the admin area and during WordPress cron.

## Deactivating and deleting

- **Deactivating** stops the daily log clean-up task. Settings and the log are kept.
- **Deleting** the plugin from the Plugins screen removes every setting and cached update lookup, and drops the log table. Any rate-limit transients still in place expire on their own within the interval.
