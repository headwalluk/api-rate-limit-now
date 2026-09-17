# API Rate Limiter

![Version](https://img.shields.io/github/v/tag/headwalluk/api-rate-limit-now?label=version&sort=semver)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-7A86B8)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759B)
![License](https://img.shields.io/github/license/headwalluk/api-rate-limit-now)
![Build](https://img.shields.io/github/actions/workflow/status/headwalluk/api-rate-limit-now/release.yml?label=release)

Rate-limit WordPress REST API calls by client IP address.

A lightweight plugin that uses WordPress transients to throttle REST API requests. When a rate-limited client makes requests too frequently, it gets an HTTP 429 (Too Many Requests) response.

## What it does

- Rate-limits REST API calls by client IP address, including WooCommerce's REST and Store APIs
- Limits every visitor who isn't logged in, or only a list of specific IPs
- Exempts trusted IPs, with localhost exempt by default
- Exempts chosen REST routes, with shoppers' WooCommerce Store API and PayPal button requests exempt by default
- Always limits chosen integrations by user agent, including ones that authenticate with API keys
- Configured from a settings page, with no code editing
- Logs blocked requests, with configurable retention. Allowed requests are never logged, so normal traffic is unaffected
- Filters for developers to customise who is limited and how often
- Removes all its settings and data when deleted

## Install

1. Download `api-rate-limit-now.zip` from the [latest release](https://github.com/headwalluk/api-rate-limit-now/releases/latest)
2. WordPress admin → Plugins → Add New → Upload Plugin → choose the zip → Install Now → Activate
3. Settings → API Rate Limiter to configure

Once installed, the plugin receives future updates through its bundled GitHub updater, with no extra configuration.

### Requirements

- WordPress 6.0 or later
- PHP 8.0 or later

## Documentation

Full user and developer documentation lives in [`docs/`](docs/):

- [How it works](docs/how-it-works.md)
- [Configuration](docs/configuration.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Hooks and filters](docs/developers/hooks-and-filters.md) *(for developers)*

## Based on

This plugin is based on the tutorial [Rate-Limit WordPress API Calls](https://wp-tutorials.tech/optimise-wordpress/rate-limit-wordpress-api-calls/) by Paul Faulkner.

## License

GPLv2 or later. See [LICENSE](http://www.gnu.org/licenses/gpl-2.0.html).
