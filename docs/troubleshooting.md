# Troubleshooting

## Legitimate requests are being refused

1. Open **Settings → API Rate Limiter → Log** and find the refused requests. The request URI shows which route was called.
2. Decide how to let them through:
   - **Visitors' browsers make them** (payment buttons, a cart widget, a product configurator, search-as-you-type): add the route to **Never rate-limited routes**. You can paste the request URI straight from the log. See [exempt a plugin whose front-end requests are refused](examples.md#exempt-a-plugin-whose-front-end-requests-are-refused).
   - **They come from a fixed address** (a headless front end, a monitoring service): add that IP to **Never rate-limited IPs**.
   - **The caller is recognisable some other way**, such as a header carrying an API key: a developer can exempt it with the `wptarl_is_client_rate_limited` filter. See [hooks and filters](developers/hooks-and-filters.md).
   - **The same client appears several times within a second or two:** it's retrying straight away instead of waiting for the `Retry-After` time sent with each 429. That's a fault in the client, not the site. Ask its developer to honour `Retry-After`.

Logged-in users aren't limited unless their user agent matches **Rate-limited user agents**, or their IP is on **Rate-limited IPs**. If editors see 429 errors in the block editor, check both.

## A route I listed is still being refused

Compare the refused request's URI on the **Log** tab with the saved list. Matching follows [whole path segments](configuration.md#how-matching-works), so the common causes are:

- **A different segment that starts the same way.** `wc/store` doesn't cover `wc/storefront/…`, and `my-plugin` doesn't cover `my-plugin-pro/…`. List each one.
- **A sibling route.** `my-plugin/v1/cart` doesn't cover `my-plugin/v1/checkout`. List their shared parent, `my-plugin/v1`, or both routes. Pasting a request from the log saves its full route, so this is easy to hit.
- **A wildcard.** `my-plugin/*` is saved as written and matches nothing. List `my-plugin`.
- **Not starting from the first segment.** `store/v1/cart` doesn't cover `wc/store/v1/cart`. Routes are matched from the start.
- **The request isn't to that route.** A plugin using `/?wc-ajax=…&path=/…` is calling the route in `path`. A plugin calling `admin-ajax.php` isn't using the REST API at all, so this plugin never refuses it; look for another cause.
- **The defaults were replaced.** The defaults stop applying once the settings have been saved, even if the box was emptied. Make sure `wc/store` and `wc-ppcp` are still listed if you need them.
- **A filter overrides the settings.** Code using the `wptarl_is_client_rate_limited` filter can force a request to be limited, exempt route or not. Search the active theme and plugins for `wptarl_`.

## Everyone appears to have the same IP address

If the Log tab shows one address for every refused request, often your load balancer's or CDN's, the plugin isn't seeing your visitors' real addresses. All visitors then share a single window, and one request blocks everyone else for the interval.

The plugin reads `HTTP_CLIENT_IP`, then `HTTP_X_FORWARDED_FOR`, then `REMOTE_ADDR`, and uses the first that holds a **single** valid IP address. A proxy that sends a list of addresses in `X-Forwarded-For` (`203.0.113.7, 10.0.0.2`) isn't read, and the plugin falls back to `REMOTE_ADDR`, which is the proxy.

**Fix:** configure the proxy or web server to hand WordPress the visitor's address in `REMOTE_ADDR`. Examples are nginx's `real_ip_module`, Apache's `mod_remoteip`, or Cloudflare's guidance on restoring visitor IPs.

The IP shown at the top of the settings page is the address the plugin sees for you. Compare it with your real public address to check.

## Nothing is being refused

- **Check who is limited.** By default only visitors who aren't logged in are limited. Test from a private browsing window or `curl`, not your logged-in admin session. Integrations using WooCommerce API keys or application passwords also count as logged in; list their user agent under **Rate-limited user agents** to limit them.
- **Check the route isn't exempt.** Requests to routes on **Never rate-limited routes**, including the Store API (`wc/store`) by default, are never limited. Test with `/wp-json/`, which isn't exempt.
- **Check your address isn't exempt.** Requests from `127.0.0.1` and `::1` are never limited. Running `curl` on the server itself may arrive from one of those, or from the server's public address, depending on how the site's hostname resolves. The IP on the settings page shows which address the plugin sees for your browser.
- **Make two requests inside the interval.** The first is always allowed; only a second one within the interval is refused:

  ```bash
  curl -s -o /dev/null -w "%{http_code}\n" https://example.com/wp-json/
  curl -s -D - -o /dev/null https://example.com/wp-json/ | grep -iE "^HTTP|^retry-after"
  ```

  The second shows `429` and a `Retry-After` header.
- **Check page caching.** A cache in front of WordPress (a CDN, Varnish, a caching plugin) that serves `/wp-json/` responses answers without running WordPress, so the plugin never sees those requests.
- **Check for filters.** Code on the site may override the decision with the `wptarl_is_client_rate_limited` filter. Search the active theme and plugins for `wptarl_`.

## The Log tab is empty

- **Enable logging** must be on. Only refused requests are logged, never allowed ones.
- Entries older than the **Log retention** period are deleted daily.
- **The log table may not exist.** It's created the first time the plugin loads. If the database user couldn't create tables at that moment, the plugin doesn't report it and doesn't try again, so nothing is ever logged. Check with WP-CLI:

  ```bash
  wp db query "SHOW TABLES LIKE '$(wp db prefix)wptarl_log'"
  ```

  If nothing is listed, fix the database permissions, then make the plugin try again on the next page load:

  ```bash
  wp option delete wptarl_db_version
  ```

## An API secret appears in the log

Versions before 2.1.1 logged request URIs in full, including credentials some integrations send in the query string (`consumer_secret=cs_…`). To clean up:

1. Update to 2.1.1 or later, which redacts them.
2. **Clear Log** under **Settings → API Rate Limiter → Log**.
3. Treat the secret as exposed: in WooCommerce → Settings → Advanced → REST API, revoke the key and issue the integration a new one.
4. Ask the integration's developer to authenticate with an `Authorization` header instead of URL parameters. Your web server's access log records every URL, and no plugin can redact that.

To redact another parameter name, see the `wptarl_redacted_query_params` filter in [hooks and filters](developers/hooks-and-filters.md).

## Updates aren't appearing

1. Check the plugin version under **Plugins**. Versions up to and including 2.0.0 have no updater: install a newer release manually once, and later updates arrive automatically.
2. Check the PHP error log for lines starting `Api_Rate_Limiter Github_Updater [error]:`. Failed lookups are always logged there, whether or not `WP_DEBUG` is on, with the reason: the server can't reach `api.github.com`, GitHub returned an error or rate-limited the request, or a release has no plugin zip.
3. A lookup is cached for 12 hours, and a failed one for an hour. To check again now, delete the cache, then go to **Dashboard → Updates** and click **Check again**:

   ```bash
   wp transient delete wptarl_github_release
   wp transient delete wptarl_github_failed
   ```

   The updater runs only in the admin area and during cron, so `wp plugin list` and `wp plugin update` don't see GitHub releases.
4. Check that no code on the site disables updates with the `wptarl_updater_enabled` filter.

## The plugin was deactivated after an update

Version 2.1.0 renamed the plugin's main file from `api-rate-limiter-now.php` to `api-rate-limit-now.php`. WordPress tracks active plugins by file name, so it deactivates the plugin during that update. Reactivate it under **Plugins**; settings and the log are unaffected.
