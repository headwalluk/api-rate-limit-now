# Configuration

All settings live under **Settings → API Rate Limiter** in the WordPress admin, on the **Settings** tab. The top of the page shows your own IP address, with a link to copy it — useful for adding yourself to an IP list.

## Settings

| Setting | Default | What it does |
|---------|---------|--------------|
| Seconds between API calls | `10` | The interval. A rate-limited client may make one REST API request in this many seconds. Must be at least 1; saving `0` or an empty value restores the default |
| Rate-limit all guests | On | Limit every client that isn't logged in. Only applies while *Rate-limited IPs* is empty |
| Rate-limited IPs | *(empty)* | When this list has entries, **only** these IPs are limited, logged in or not |
| Never rate-limited IPs | `127.0.0.1, ::1` | IPs that are never limited. Checked before everything else |
| Rate-limited user agents | *(empty)* | Requests whose `User-Agent` contains one of these strings are always limited, logged in or not. One per line |
| Enable logging | On | Record blocked requests on the **Log** tab |
| Log retention (days) | `7` | Log entries older than this are deleted by a daily task. Saving `0` or an empty value restores the default |

IP lists accept addresses separated by commas, spaces or new lines. Invalid addresses are dropped when you save. Only single IPv4 or IPv6 addresses are supported, not ranges or CIDR blocks.

See [how it works](how-it-works.md#who-is-rate-limited) for the order in which the IP lists and the guest setting are applied.

## Rate-limited user agents

Each line is matched as a case-insensitive substring of the request's `User-Agent` header. Blank lines and duplicates are removed when you save.

```
ExampleInventorySync
ExampleMailer/1
```

With that list, `ExampleInventorySync` matches `ExampleInventorySync` and `exampleinventorysync-agent/2.0`, and `ExampleMailer/1` matches `ExampleMailer/1.0` but not `ExampleMailer/2.0`. Choose strings specific enough not to match browsers: a line such as `Mozilla` would limit almost every visitor.

This is the way to limit an integration that authenticates with API keys, which otherwise counts as logged in and is exempt. See [API clients count as logged in](how-it-works.md#api-clients-count-as-logged-in). IPs on *Never rate-limited IPs* stay exempt whatever their user agent.

A `User-Agent` is chosen by the client, so this identifies well-behaved integrations. It doesn't stop a client that is trying to avoid the limit.

Before listing an integration, check how fast it legitimately calls the API. One that pages through products or orders faster than the interval has every request after the first refused until the interval passes. Every 429 carries a `Retry-After` header saying how long to wait, so a well-built client slows down and completes its sync. A client that ignores it and retries immediately is refused again each time, which shows up as bursts of blocked requests on the **Log** tab — raise that with the integration's developer.

## Choosing an interval

The default of 10 seconds suits most sites. The right value depends on how your site's legitimate visitors use the REST API:

- **Standard WordPress sites** — 10–30 seconds is usually fine. Most visitors don't make repeated REST API calls.
- **WooCommerce stores** — 5–10 seconds. The block-based cart and checkout call the Store API as shoppers interact with them, so a long interval can interrupt a real purchase.
- **Headless or decoupled front ends** — 1–3 seconds, or add the front end's server IPs to *Never rate-limited IPs*.
- **Different limits per client** — use the `wptarl_seconds_between_api_calls` filter; see [hooks and filters](developers/hooks-and-filters.md).

If you're unsure, start with the default and watch the **Log** tab for requests you recognise as legitimate.

## WooCommerce

WooCommerce's REST API (`/wp-json/wc/…`) and Store API (`/wp-json/wc/store/…`) are ordinary WordPress REST routes, so they're covered with no extra setup. This helps on stores targeted by bots scraping product data, guessing coupon codes or spamming checkout endpoints.

Integrations that connect with WooCommerce API keys (inventory, email marketing, marketplace sync) count as logged in and aren't limited by default. Use [rate-limited user agents](#rate-limited-user-agents) to limit a specific one.
