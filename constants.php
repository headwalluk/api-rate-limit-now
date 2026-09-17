<?php
/**
 * Plugin constants, option keys, and default values.
 *
 * @package Api_Rate_Limiter
 */

namespace Api_Rate_Limiter;

defined( 'ABSPATH' ) || die();

// Option keys (wp_options) - prefix with OPT_.
const OPT_SECONDS_BETWEEN_CALLS  = 'wptarl_seconds_between_calls';
const OPT_RATE_LIMIT_ALL_GUESTS  = 'wptarl_rate_limit_all_guests';
const OPT_RATE_LIMITED_IPS       = 'wptarl_rate_limited_ips';
const OPT_NEVER_RATE_LIMITED_IPS = 'wptarl_never_rate_limited_ips';

// Default values - prefix with DEF_.
const DEF_SECONDS_BETWEEN_CALLS  = 10;
const DEF_RATE_LIMIT_ALL_GUESTS  = true;
const DEF_RATE_LIMITED_IPS       = '';
const DEF_NEVER_RATE_LIMITED_IPS = '127.0.0.1, ::1';

// Transient key prefix.
const TRANSIENT_PREFIX = 'wptarl_';

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

// GitHub updater. A failed lookup is cached for the shorter failure TTL.
const UPDATER_GITHUB_REPO       = 'headwalluk/api-rate-limit-now';
const UPDATER_CACHE_KEY         = 'wptarl_github_release';
const UPDATER_CACHE_TTL         = 12 * HOUR_IN_SECONDS;
const UPDATER_FAILURE_CACHE_KEY = 'wptarl_github_failed';
const UPDATER_FAILURE_CACHE_TTL = HOUR_IN_SECONDS;
const UPDATER_REQUEST_TIMEOUT   = 10;
