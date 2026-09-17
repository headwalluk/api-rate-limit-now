# Examples

Complete settings for common situations. Each one starts from the defaults and lists only what changes. Settings are under **Settings → API Rate Limiter**. The same values can be set with WP-CLI; see [hooks and filters](developers/hooks-and-filters.md#what-about-constants-options-and-functions).

## A WooCommerce store, out of the box

Nothing to change. The defaults:

- limit every guest REST request to one per 10 seconds
- exempt shoppers' Store API (`wc/store`) and PayPal button (`wc-ppcp`) requests
- leave logged-in users, including integrations using WooCommerce API keys, unlimited

| Request | Result |
|---------|--------|
| A bot requesting `/wp-json/wp/v2/users` twice in a second | 200, then 429 |
| A shopper's block checkout calling `/wp-json/wc/store/v1/cart` five times in a second | 200 every time |
| A PayPal button calling `/?wc-ajax=wc_ppcp_frontend_request&path=/wc-ppcp/v1/cart/` | 200 every time |
| An inventory integration using API keys, paging `/wp-json/wc/v3/products` | 200 every time |

Turn on WooCommerce's **Rate limit checkout** feature (WooCommerce → Settings → Advanced → Features) to protect the exempt checkout route from card testing.

## Limit an integration that uses API keys

An inventory system and a mailing service both connect with WooCommerce API keys, so they count as logged in and are never limited. They send these user agents:

```
ExampleInventorySync/4.2 (+https://inventory.example)
ExampleMailer/1.0
```

**Rate-limited user agents:**

```
ExampleInventorySync
ExampleMailer/1.
```

`ExampleMailer/1.` includes the dot so that a future `ExampleMailer/10` isn't matched by accident.

**Seconds between API calls:** `5`, or whatever the integrations can live with.

| Request | Result |
|---------|--------|
| `ExampleInventorySync/4.2` → `/wp-json/wc/v3/products?page=1` | 200 |
| same client, `page=2`, one second later | 429, `Retry-After: 5` |
| same client, `page=2`, after waiting 5 seconds | 200 |
| `ExampleMailer/1.0` → `/wp-json/wc/v3/orders` | 200, then limited like the above |
| A browser user, logged in, using the block editor | Not limited |

A well-behaved integration reads `Retry-After` and pages at the pace you set. One that retries straight away shows up on the **Log** tab as a run of refusals seconds apart; ask its developer to honour `Retry-After`.

Both integrations running on **one server** share one IP address and so one window. If they need to run at once, give them a longer interval or ask for separate source addresses.

## Limit only chosen integrations

You don't want to limit visitors at all: only a couple of integrations that call the API far more often than they need to.

**Rate-limit all guests:** off

**Rate-limited user agents:**

```
ExampleInventorySync
ExampleMailer/1.
```

**Seconds between API calls:** `2`, short enough for the integrations' normal paging, long enough to stop bursts.

With *Rate-limited IPs* empty and guest limiting off, only requests whose `User-Agent` matches a line are ever limited. Every visitor, shopper, bot and other integration passes unchecked, and *Never rate-limited routes* makes no difference, because nothing else is limited.

| Request | Result |
|---------|--------|
| A guest requesting `/wp-json/wp/v2/users` twice in a second | 200, 200 |
| `ExampleMailer/1.0` fetching customers, then products, in the same second | 200, then 429 with `Retry-After: 3` |
| `ExampleMailer/1.0` retrying after the 3 seconds it was given | 200 |
| `ExampleInventorySync/4.2` polling orders every 10 minutes | 200 every time |

The **Log** tab then only shows the listed integrations, which makes it easy to see whether they honour `Retry-After`.

## Exempt a plugin whose front-end requests are refused

Customers report that a product configurator stops responding. The **Log** tab shows refusals for:

```
/wp-json/example-configurator/v2/price?options=12,40
/wp-json/example-configurator/v2/price?options=12,41
```

Paste one of those lines into **Never rate-limited routes**. It's saved as:

```
example-configurator/v2/price
```

That exempts the price route only, and any routes below it. To exempt everything the plugin provides, shorten it to its namespace:

```
example-configurator
```

Keep the existing `wc/store` and `wc-ppcp` lines — the box replaces the defaults, it doesn't add to them.

| Listed | `/wp-json/example-configurator/v2/price` | `/wp-json/example-configurator/v2/save` | `/wp-json/example-configurator-pro/v1/price` |
|--------|------|------|------|
| `example-configurator/v2/price` | Exempt | Limited | Limited |
| `example-configurator` | Exempt | Exempt | Limited — `example-configurator-pro` is a different segment |

## Protect the Store API with this plugin too

Your store has no block-based cart or checkout, and the Store API is being used to guess coupon codes.

**Never rate-limited routes:**

```
wc-ppcp
```

(`wc/store` removed.)

Now every guest request to `/wp-json/wc/store/…` is limited to one per interval. If you later add block-based cart or checkout pages, put `wc/store` back, or shoppers will be refused mid-purchase.

## A headless front end

A separate front-end server at `198.51.100.20` renders pages by calling the REST API many times a second, for every visitor.

**Never rate-limited IPs:**

```
127.0.0.1, ::1, 198.51.100.20
```

The front-end server is never limited. Everyone else calling the API directly still is. Don't shorten the interval for everyone to make room for one server.

## Limit only specific addresses

Rather than limiting all guests, you only want to slow down two addresses that hammer the API.

**Rate-limited IPs:**

```
203.0.113.50
203.0.113.51
```

While this list has entries, *Rate-limit all guests* is ignored. Only these two addresses are limited, logged in or not. Every other guest is unlimited. Empty the list to go back to limiting all guests.

## Check it's working

From a machine that isn't on *Never rate-limited IPs*, and not logged in:

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://example.com/wp-json/
curl -s -D - -o /dev/null https://example.com/wp-json/ | grep -iE "^HTTP|^retry-after"
```

The first prints `200`. The second shows `429` and a `Retry-After` header. A route on *Never rate-limited routes*, such as `/wp-json/wc/store/v1/cart`, returns its normal response however quickly you repeat it.
