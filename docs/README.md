# API Rate Limiter — Documentation

Rate-limit WordPress REST API calls by client IP address.

## For site administrators

- [How it works](how-it-works.md) — Which requests are checked, who is rate-limited, what a blocked client sees
- [Configuration](configuration.md) — Every setting explained, how route and user-agent matching work, and choosing an interval
- [Examples](examples.md) — Complete setups: a WooCommerce store, limiting API-key integrations, exempting a plugin's routes, headless front ends
- [Troubleshooting](troubleshooting.md) — Legitimate requests being refused, a listed route still refused, proxies and CDNs, an empty log

## For developers

- [Hooks and filters](developers/hooks-and-filters.md) — The public extension surface
