# Configuration

All settings live under **Settings → API Rate Limiter** in the WordPress admin, on the **Settings** tab. The top of the page shows your own IP address as the plugin sees it, with a link to copy it — useful for adding yourself to an IP list.

For complete setups, see [examples](examples.md).

## Settings

| Setting | Default | What it does |
|---------|---------|--------------|
| Seconds between API calls | `10` | The interval. A rate-limited client may make one REST API request in this many seconds. Must be at least 1; saving `0` or an empty value restores the default |
| Rate-limit all guests | On | Limit every client that isn't logged in. Ignored while *Rate-limited IPs* has entries |
| Rate-limited IPs | *(empty)* | When this list has entries, **only** these IPs are limited, logged in or not |
| Never rate-limited IPs | `127.0.0.1, ::1` | IPs that are never limited. Checked before everything else |
| Never rate-limited routes | `wc/store`<br>`wc-ppcp` | REST routes that are never limited, together with every route below them. One per line |
| Rate-limited user agents | *(empty)* | Requests whose `User-Agent` contains one of these strings are limited, logged in or not. One per line |
| Enable logging | On | Record refused requests on the **Log** tab |
| Log retention (days) | `7` | Log entries older than this are deleted by a daily task. Saving `0` or an empty value restores the default |

The order these are applied in is set out in [how it works](how-it-works.md#who-is-rate-limited).

## IP lists

*Rate-limited IPs* and *Never rate-limited IPs* accept addresses separated by commas, spaces or new lines. Invalid addresses are dropped when you save.

Only exact, single IPv4 or IPv6 addresses match. Ranges, CIDR blocks (`203.0.113.0/24`) and wildcards (`203.0.113.*`) aren't supported, and are dropped when you save.

## Never rate-limited routes

Each line names a REST route. A request is exempt when its route **is** that route, or sits **below** it.

### How matching works

Matching compares whole path segments — the parts between slashes — starting from the beginning of the route, and ignores case. A listed route never matches part of a segment:

| Listed | Request route | Exempt? | Why |
|--------|---------------|---------|-----|
| `wc/store` | `wc/store` | ✅ Yes | Same route |
| `wc/store` | `wc/store/v1/cart` | ✅ Yes | Below `wc/store` |
| `wc/store` | `WC/Store/v1/cart` | ✅ Yes | Case is ignored |
| `wc/store` | `wc/storefront/v1/items` | ❌ No | `storefront` is a different segment from `store` |
| `wc/store` | `wc/v3/products` | ❌ No | Different second segment |
| `wc` | `wc/v3/products` | ✅ Yes | Below `wc` — this exempts **all** of WooCommerce's REST API, including API-key integrations |
| `wc/store/v1/cart` | `wc/store/v1/cart/add-item` | ✅ Yes | Below the listed route |
| `wc/store/v1/cart` | `wc/store/v1/checkout` | ❌ No | A sibling, not below |
| `store` | `wc/store/v1/cart` | ❌ No | Matching starts at the first segment |
| `wc/store/*` | `wc/store/v1/cart` | ❌ No | Wildcards aren't supported. List `wc/store` |

To exempt two routes that start the same way, such as `wc/store` and `wc/storefront`, list both.

### What you can paste

When you save, each line is reduced to its route, so you can paste a request as the **Log** tab or your browser shows it:

| You enter | Saved as |
|-----------|----------|
| `wc/store` | `wc/store` |
| `/WC/Store/` | `wc/store` |
| `/wp-json/wc/store/v1/cart` | `wc/store/v1/cart` |
| `https://example.com/wp-json/wc/store/v1/cart?per_page=10` | `wc/store/v1/cart` |
| `/?rest_route=/wc/store/v1/cart` | `wc/store/v1/cart` |
| `/?wc-ajax=wc_ppcp_frontend_request&path=/wc-ppcp/v1/cart/` | `wc-ppcp/v1/cart` |

Blank lines and duplicates are removed. Check what was saved: a pasted request gives the **full** route, which is often narrower than you want. `wc-ppcp/v1/cart` exempts only the cart requests; shorten it to `wc-ppcp` to cover the plugin's other routes. Going too far the other way is the riskier mistake: `wc` exempts far more than `wc/store`.

### The defaults

The defaults cover requests that a shopper's own browser makes, often several a second, while they use the cart and checkout:

| Route | Used by |
|-------|---------|
| `wc/store` | WooCommerce's Store API: block-based cart and checkout, the mini-cart, and many themes and plugins |
| `wc-ppcp` | Payment Plugins for PayPal WooCommerce: PayPal button requests, sent through `/?wc-ajax=wc_ppcp_frontend_request` |

Rate-limiting these to one request per interval refuses real customers mid-checkout, so payment buttons fail and orders are lost.

The defaults apply until the settings are first saved. After that, whatever the box holds is used, and an empty box exempts nothing. When you add a route, keep the default lines unless you mean to remove them.

**Trade-off:** exempted routes lose this plugin's protection. The Store API can be abused to guess coupon codes through the cart, or to test stolen cards through checkout. WooCommerce has protection designed for that traffic, which allows a shopper's bursts:

- **Rate limit checkout** under WooCommerce → Settings → Advanced → Features, which limits orders placed through the Store API checkout
- The Store API rate limiter, turned on with the `woocommerce_store_api_rate_limit_options` filter

To protect the Store API with this plugin instead, remove `wc/store` from the list, and choose an interval a real checkout can live with.

## Rate-limited user agents

Each line is looked for **anywhere** in the request's `User-Agent` header, ignoring case. Blank lines and duplicates are removed when you save.

| Listed | Request `User-Agent` | Limited? | Why |
|--------|----------------------|----------|-----|
| `ExampleInventorySync` | `ExampleInventorySync` | ✅ Yes | Exact |
| `ExampleInventorySync` | `exampleinventorysync-agent/2.0` | ✅ Yes | Found inside, case ignored |
| `ExampleMailer/1` | `ExampleMailer/1.0` | ✅ Yes | Found inside |
| `ExampleMailer/1` | `ExampleMailer/10.2` | ✅ Yes | `ExampleMailer/1` is found inside `ExampleMailer/10.2` |
| `ExampleMailer/1.` | `ExampleMailer/10.2` | ❌ No | Add the dot to match version 1 only |
| `ExampleMailer/1` | `ExampleMailer/2.0` | ❌ No | Not found |
| `Mozilla` | almost every browser | ✅ Yes | Far too broad — never list this |

Unlike routes, this is a plain substring match: there are no segments, and a short string matches more than you might expect. Use the most specific string the client sends. The **Log** tab doesn't record user agents, but your web server's access log does, usually as the last quoted field on each line.

This setting is how to limit an integration that authenticates with API keys, which otherwise counts as logged in and is exempt. See [API clients count as logged in](how-it-works.md#api-clients-count-as-logged-in). IPs on *Never rate-limited IPs*, and routes on *Never rate-limited routes*, stay exempt whatever the user agent.

A `User-Agent` is chosen by the client, so this identifies well-behaved integrations. It doesn't stop a client that is trying to avoid the limit.

Before listing an integration, check how fast it legitimately calls the API. One that pages through products or orders faster than the interval has every request after the first refused until the window ends. Every 429 carries a `Retry-After` header saying how long to wait, so a well-built client slows down and completes its sync. A client that ignores it and retries at once is refused each time, which shows up as bursts of refused requests on the **Log** tab. Raise that with the integration's developer.

## Choosing an interval

The default of 10 seconds suits most sites. The right value depends on how your site's legitimate clients use the REST API:

- **Standard WordPress sites** — 10–30 seconds is usually fine. Most visitors don't make repeated REST API calls.
- **WooCommerce stores** — 5–10 seconds. Shoppers' cart and checkout requests are exempt by default through *Never rate-limited routes*; if you remove that exemption, a long interval can interrupt a real purchase.
- **Headless or decoupled front ends** — add the front end's server IPs to *Never rate-limited IPs* rather than shortening the interval for everyone.
- **Different limits per client** — use the `wptarl_seconds_between_api_calls` filter; see [hooks and filters](developers/hooks-and-filters.md).

If you're unsure, start with the default and watch the **Log** tab for requests you recognise as legitimate.

## The Log tab

The **Log** tab lists the 100 most recent refused requests: IP address, time and request URI. **Clear Log** deletes every entry. Entries are also deleted automatically after the retention period.

Credential-like query values, such as `consumer_secret` or `oauth_signature`, appear as `REDACTED`. See [logging](how-it-works.md#logging). Entries logged by version 2.1.0 or earlier weren't redacted, so clear the log after updating if an integration sends credentials in its URLs.

A request URI can be pasted straight into *Never rate-limited routes* if the request turns out to be legitimate. See [what you can paste](#what-you-can-paste).

## WooCommerce

WooCommerce's REST API (`/wp-json/wc/v3/…`) is an ordinary WordPress REST route, so guest requests to it are limited with no extra setup. That covers bots probing product, order and customer endpoints.

The Store API (`/wp-json/wc/store/…`), which shoppers' browsers call during cart and checkout, is exempt by default. See [the defaults](#the-defaults) for why, and for WooCommerce's own protection of those routes.

Integrations that connect with WooCommerce API keys (inventory, email marketing, marketplace sync) count as logged in and aren't limited by default. Use [rate-limited user agents](#rate-limited-user-agents) to limit a specific one.
