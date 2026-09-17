<?php
/**
 * Plugin constants, option keys, and default values.
 *
 * @package Api_Rate_Limiter
 */

namespace Api_Rate_Limiter;

defined( 'ABSPATH' ) || die();

// Option keys (wp_options) - prefix with OPT_.
const OPT_SECONDS_BETWEEN_CALLS     = 'wptarl_seconds_between_calls';
const OPT_RATE_LIMIT_ALL_GUESTS     = 'wptarl_rate_limit_all_guests';
const OPT_RATE_LIMITED_IPS          = 'wptarl_rate_limited_ips';
const OPT_NEVER_RATE_LIMITED_IPS    = 'wptarl_never_rate_limited_ips';
const OPT_RATE_LIMITED_USER_AGENTS  = 'wptarl_rate_limited_user_agents';
const OPT_NEVER_RATE_LIMITED_ROUTES = 'wptarl_never_rate_limited_routes';

// Default values - prefix with DEF_.
const DEF_SECONDS_BETWEEN_CALLS    = 10;
const DEF_RATE_LIMIT_ALL_GUESTS    = true;
const DEF_RATE_LIMITED_IPS         = '';
const DEF_NEVER_RATE_LIMITED_IPS   = '127.0.0.1, ::1';
const DEF_RATE_LIMITED_USER_AGENTS = '';
// WooCommerce Store API (shopper cart/checkout) and Payment Plugins for PayPal button requests.
const DEF_NEVER_RATE_LIMITED_ROUTES = "wc/store\nwc-ppcp";

// Transient key prefix. The value is the Unix time the client's window ends.
const TRANSIENT_PREFIX = 'wptarl_';

// Shortest Retry-After sent with a 429, in seconds.
const RETRY_AFTER_MIN = 1;

// Settings group and page slug.
const SETTINGS_GROUP           = 'wptarl_settings';
const SETTINGS_PAGE            = 'wptarl-settings';
const SETTINGS_SECTION_GENERAL = 'wptarl_general_section';

// Menu slug for the admin page.
const ADMIN_MENU_SLUG = 'wptarl-settings';

// Plugin basename (set during init, used for settings link).
const PLUGIN_DIR = __DIR__;

// Database.
const DB_VERSION     = 1;
const OPT_DB_VERSION = 'wptarl_db_version';
const LOG_TABLE      = 'wptarl_log';

// Logging defaults.
const OPT_LOGGING_ENABLED = 'wptarl_logging_enabled';
const DEF_LOGGING_ENABLED = true;
const OPT_LOG_RETENTION   = 'wptarl_log_retention';
const DEF_LOG_RETENTION   = 7;
const LOG_MAX_ROWS        = 5000;

// Longest request URI stored in the log, matching the request_uri column.
const LOG_REQUEST_URI_MAX_LENGTH = 255;

// Query parameters whose (decoded, lower-cased) name contains any of these have their value redacted in the log.
const REDACTED_QUERY_PARAM_FRAGMENTS = array( 'key', 'secret', 'token', 'password', 'passwd', 'nonce', 'signature', 'oauth', 'session', 'credential' );
const REDACTED_QUERY_VALUE           = 'REDACTED';

// GitHub updater. A failed lookup is cached for the shorter failure TTL.
const UPDATER_GITHUB_REPO       = 'headwalluk/api-rate-limit-now';
const UPDATER_CACHE_KEY         = 'wptarl_github_release';
const UPDATER_CACHE_TTL         = 12 * HOUR_IN_SECONDS;
const UPDATER_FAILURE_CACHE_KEY = 'wptarl_github_failed';
const UPDATER_FAILURE_CACHE_TTL = HOUR_IN_SECONDS;
const UPDATER_REQUEST_TIMEOUT   = 10;
