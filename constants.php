<?php
/**
 * Plugin constants, option keys, and default values.
 *
 * @package Api_Rate_Limiter
 */

namespace Api_Rate_Limiter;

defined( 'ABSPATH' ) || die();

// Plugin name, shown as written in every language.
const PLUGIN_NAME = 'API Rate Limiter';

// Every option name starts with this, so uninstall.php can find them by prefix.
const OPTION_PREFIX = 'wptarl_';

// Option keys (wp_options) - prefix with OPT_.
const OPT_SECONDS_BETWEEN_CALLS     = 'wptarl_seconds_between_calls';
const OPT_RATE_LIMIT_ALL_GUESTS     = 'wptarl_rate_limit_all_guests';
const OPT_RATE_LIMITED_IPS          = 'wptarl_rate_limited_ips';
const OPT_NEVER_RATE_LIMITED_IPS    = 'wptarl_never_rate_limited_ips';
const OPT_RATE_LIMITED_USER_AGENTS  = 'wptarl_rate_limited_user_agents';
const OPT_NEVER_RATE_LIMITED_ROUTES = 'wptarl_never_rate_limited_routes';
const OPT_DELETE_DATA_ON_UNINSTALL  = 'wptarl_delete_data_on_uninstall';

// Default values - prefix with DEF_.
const DEF_SECONDS_BETWEEN_CALLS    = 10;
const DEF_RATE_LIMIT_ALL_GUESTS    = true;
const DEF_RATE_LIMITED_IPS         = '';
const DEF_NEVER_RATE_LIMITED_IPS   = '127.0.0.1, ::1';
const DEF_RATE_LIMITED_USER_AGENTS = '';
// WooCommerce Store API (shopper cart/checkout) and Payment Plugins for PayPal button requests.
const DEF_NEVER_RATE_LIMITED_ROUTES = "wc/store\nwc-ppcp";
const DEF_DELETE_DATA_ON_UNINSTALL  = false;

// Bounds for numeric settings. A saved value outside them falls back to the default.
const SECONDS_BETWEEN_CALLS_MIN = 1;
const SECONDS_BETWEEN_CALLS_MAX = 3600;
const LOG_RETENTION_MIN         = 1;
const LOG_RETENTION_MAX         = 90;

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

// Capability needed to change settings and clear the log.
const ADMIN_CAPABILITY = 'manage_options';

// Admin asset handle, shared by the stylesheet and the script.
const ADMIN_ASSET_HANDLE = 'wptarl-admin';

// Clear Log form: the field that marks the submission, and its nonce.
const CLEAR_LOG_FIELD        = 'wptarl_clear_log';
const NONCE_CLEAR_LOG_ACTION = 'wptarl_clear_log';
const NONCE_CLEAR_LOG_FIELD  = 'wptarl_clear_log_nonce';

// Daily cron event that prunes the log.
const CRON_PRUNE_LOG = 'wptarl_prune_log';

// Filter that can switch the GitHub updater off.
const FILTER_UPDATER_ENABLED = 'wptarl_updater_enabled';

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

// Rows shown on the Log tab, and the most a wptarl_log_view_rows filter may ask for.
const LOG_VIEW_ROWS     = 100;
const LOG_VIEW_ROWS_MAX = 1000;

// Longest request URI stored in the log, matching the request_uri column.
const LOG_REQUEST_URI_MAX_LENGTH = 255;

// Query parameters whose (decoded, lower-cased) name contains any of these have their value redacted in the log.
const REDACTED_QUERY_PARAM_FRAGMENTS = array( 'key', 'secret', 'token', 'password', 'passwd', 'nonce', 'signature', 'oauth', 'session', 'credential' );
const REDACTED_QUERY_VALUE           = 'REDACTED';

// GitHub repository the updater checks for releases.
const UPDATER_GITHUB_REPO = 'headwalluk/api-rate-limit-now';
