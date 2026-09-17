# Troubleshooting

## Legitimate requests are being blocked

1. Open **Settings → API Rate Limiter → Log** and find the blocked requests. The request URI shows which feature was calling the API.
2. Decide how to let it through:
   - The requests come from a fixed address (a headless front end, an integration partner, a monitoring service): add that IP to **Never rate-limited IPs**.
   - Real visitors trigger them (a block-based WooCommerce checkout, a search-as-you-type widget): lower **Seconds between API calls**. See [choosing an interval](configuration.md#choosing-an-interval).
   - The caller is recognisable some other way, such as an API key header: a developer can exempt it with the `wptarl_is_client_rate_limited` filter. See [hooks and filters](developers/hooks-and-filters.md).

Logged-in users are not limited while **Rate-limited IPs** is empty, unless their browser's user agent matches **Rate-limited user agents**. If editors see 429 errors in the block editor, check both lists.

## Everyone appears to have the same IP address

If the Log tab shows one address for every blocked request, often your load balancer's or CDN's, the plugin isn't seeing your visitors' real addresses. All visitors then share a single allowance, and one request blocks everyone else for the interval.

The plugin reads `HTTP_CLIENT_IP`, then `HTTP_X_FORWARDED_FOR`, then `REMOTE_ADDR`, and uses the first one that holds a **single** valid IP address. A proxy that sends a list of addresses in `X-Forwarded-For` (`203.0.113.7, 10.0.0.2`) isn't read, and the plugin falls back to `REMOTE_ADDR`, which is the proxy.

**Fix:** configure the proxy or web server to hand WordPress the visitor's address in `REMOTE_ADDR`. Examples are nginx's `real_ip_module`, Apache's `mod_remoteip`, or Cloudflare's guidance on restoring visitor IPs.

The IP shown at the top of the settings page is the address the plugin sees for you. Compare it with your real public address to check.

## Nothing is being blocked

- **Check who is limited.** By default only visitors who aren't logged in are limited. Test from a private browsing window, not your logged-in admin session. Integrations using WooCommerce API keys or application passwords also count as logged in; list their user agent under **Rate-limited user agents** to limit them.
- **Check the never-limited list.** Requests from `127.0.0.1` and `::1` are never limited, which includes testing with `curl` on the server itself.
- **Make two requests inside the interval.** The first request is always allowed; only a second one within the interval is blocked:

  ```bash
  curl -s -o /dev/null -w "%{http_code}\n" https://example.com/wp-json/
  curl -s -o /dev/null -w "%{http_code}\n" https://example.com/wp-json/
  ```

  The second call should print `429`.
- **Check page caching.** A cache in front of WordPress (a CDN, Varnish, a caching plugin) that serves `/wp-json/` responses returns them without running WordPress, so the plugin never sees those requests.

## The Log tab is empty

- **Enable logging** must be on. Only blocked requests are logged, never allowed ones.
- Entries older than the **Log retention** period are deleted daily.
- The log table is created the first time the plugin loads. If the database user can't create tables, nothing can be logged; check the PHP error log for database errors.

## Updates aren't appearing

1. Check the plugin version under **Plugins**. Versions up to and including 2.0.0 have no updater: install a newer release manually once, and later updates arrive automatically.
2. Check the PHP error log for lines starting `Api_Rate_Limiter Github_Updater [error]:`. Failed lookups are always logged there, whether or not `WP_DEBUG` is on, with the reason — the server can't reach `api.github.com`, GitHub returned an error or rate-limited the request, or a release has no plugin zip.
3. A lookup is cached for 12 hours, and a failed one for an hour. To check again now, delete the cache, then go to **Dashboard → Updates** and click **Check again**:

   ```bash
   wp transient delete wptarl_github_release
   wp transient delete wptarl_github_failed
   ```

   The updater runs only in the admin area and during cron, so `wp plugin list` and `wp plugin update` don't see GitHub releases.
4. Check that no code on the site disables updates with the `wptarl_updater_enabled` filter.

## The plugin was deactivated after an update

The first release after 2.0.0 renamed the plugin's main file from `api-rate-limiter-now.php` to `api-rate-limit-now.php`. WordPress tracks active plugins by file name, so it deactivates the plugin during that update. Reactivate it under **Plugins**; settings and the log are unaffected.
