# How it works

API Rate Limiter allows each rate-limited client one REST API request per interval. A second request inside the interval is refused with HTTP 429 (Too Many Requests).

## Which requests are checked

The check runs on WordPress's `rest_api_init` action, which fires whenever WordPress starts its REST API server. That covers every request to `/wp-json/…` (or `?rest_route=…`), including plugin routes such as WooCommerce's REST and Store APIs.

Normal page loads, the admin area, AJAX (`admin-ajax.php`), WP-CLI and cron don't start the REST server and are never checked.

## The request flow

1. **Find the client's IP address.** The plugin reads `HTTP_CLIENT_IP`, then `HTTP_X_FORWARDED_FOR`, then `REMOTE_ADDR`, and uses the first that holds a single valid IP address. If none does, the request is let through.
2. **Decide whether this client is rate-limited.** See [Who is rate-limited](#who-is-rate-limited).
3. **Check the client's recent activity.** Each rate-limited IP gets a short-lived WordPress transient.
   - No transient: the request proceeds, and a transient is created that expires after the configured interval.
   - A transient exists: the request is blocked with a 429 response and, if logging is on, recorded in the log.

A blocked request doesn't create a new transient, so the interval is measured from the last request that was allowed, not the last one attempted. A client that waits for the `Retry-After` seconds on its 429 is always allowed on its next request.

## Who is rate-limited

The decision is made in this order:

1. **Never rate-limited IPs.** An IP on this list is never limited. By default the list holds `127.0.0.1` and `::1`, so the server's own requests are never blocked.
2. **Rate-limited user agents.** A request whose `User-Agent` header contains one of the listed strings is limited, whether or not the client is logged in.
3. **Rate-limited IPs.** If this list has any entries, only the IPs on it are limited, whether or not the client is logged in, and the *Rate-limit all guests* setting is not consulted.
4. **Rate-limit all guests.** If the rate-limited IPs list is empty and this setting is on (the default), every client that isn't logged in is limited.
5. Otherwise, the client isn't limited.

Developers can change the outcome with the filters in [hooks and filters](developers/hooks-and-filters.md).

### API clients count as logged in

"Logged in" means WordPress has identified a user for the request, which covers more than a browser session. Requests authenticated with WooCommerce REST API keys or WordPress application passwords count as logged in, so under the default settings **integrations using API keys are never rate-limited**.

To limit an integration that authenticates, add a string from its `User-Agent` to **Rate-limited user agents**. The access log shows each client's user agent, usually as the last quoted field on each line.

## What a blocked client sees

An HTTP 429 status, a `Retry-After` header giving the whole seconds until the client's window ends, and a JSON body:

```http
HTTP/1.1 429 Too Many Requests
Retry-After: 7
Content-Type: application/json; charset=UTF-8
```

```json
{
    "code": "rate_limited",
    "message": "Too many requests. Please slow down."
}
```

## Logging

When logging is enabled, each blocked request is written to a database table (`{prefix}wptarl_log`) with the client IP, the time and the request URI. Allowed requests are never logged, so normal traffic adds no database writes.

The **Log** tab on the settings page shows the 100 most recent entries, in the site's timezone. A daily scheduled task deletes entries older than the retention period, and trims the table to its newest 5,000 rows.

## Performance

For an allowed request, the cost is reading the plugin's options (which WordPress loads on every request anyway) and one transient read and write. With a persistent object cache (Redis, Memcached) transients never touch the database.

## Updates

The plugin updates itself from its [GitHub releases](https://github.com/headwalluk/api-rate-limit-now/releases). When WordPress checks for plugin updates, the plugin asks GitHub for the latest release, and a newer version appears on the Plugins and Updates screens like any other plugin update. It is installed the same way, manually or through WordPress's automatic updates if you've enabled them for this plugin.

The GitHub response is cached for 12 hours. If GitHub can't be reached, the plugin waits an hour before trying again. Checks run only in the admin area and during WordPress cron, never on front-end requests.

## Deactivating and deleting

- **Deactivating** stops the daily log clean-up task. Settings and the log are kept.
- **Deleting** the plugin from the Plugins screen removes every setting and drops the log table. Any rate-limit transients still in place expire on their own within the interval.
