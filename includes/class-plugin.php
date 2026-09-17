<?php
/**
 * Main Plugin class.
 *
 * Registers hooks and handles rate-limiting logic.
 *
 * @package Api_Rate_Limiter
 */

namespace Api_Rate_Limiter;

defined( 'ABSPATH' ) || die();

/**
 * Plugin class.
 *
 * @since 2.0.0
 */
class Plugin {

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Admin hooks instance.
	 *
	 * @var Admin_Hooks|null
	 */
	private ?Admin_Hooks $admin_hooks = null;

	/**
	 * Run the plugin by registering all hooks.
	 *
	 * @since 2.0.0
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 */
	public function run( string $plugin_file ): void {
		add_action( 'rest_api_init', array( $this, 'handle_rate_limiting' ), 10, 1 );

		$this->settings = new Settings();
		add_action( 'admin_init', array( $this->settings, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_clear_log' ) );

		Log::maybe_create_table();

		add_action( 'wptarl_prune_log', array( $this, 'prune_log' ) );
		if ( ! wp_next_scheduled( 'wptarl_prune_log' ) ) {
			wp_schedule_event( time(), 'daily', 'wptarl_prune_log' );
		}

		if ( is_admin() ) {
			$this->admin_hooks = new Admin_Hooks( $plugin_file );
			add_action( 'admin_menu', array( $this->admin_hooks, 'add_menu_items' ) );
			add_action( 'admin_enqueue_scripts', array( $this->admin_hooks, 'enqueue_assets' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( $plugin_file ), array( $this->admin_hooks, 'add_settings_link' ) );
		}
	}

	/**
	 * Handle the "Clear Log" form submission.
	 *
	 * @since 2.0.0
	 */
	public function handle_clear_log(): void {
		if ( ! isset( $_POST['wptarl_clear_log'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'wptarl_clear_log', 'wptarl_clear_log_nonce' );

		Log::clear();

		wp_safe_redirect( admin_url( 'options-general.php?page=' . ADMIN_MENU_SLUG . '#log' ) );
		exit;
	}

	/**
	 * Get the Settings instance.
	 *
	 * @since 2.0.0
	 *
	 * @return Settings The settings instance.
	 */
	public function get_settings(): Settings {
		return $this->settings;
	}

	/**
	 * Prune old log entries. Called via daily cron.
	 *
	 * @since 2.0.0
	 */
	public function prune_log(): void {
		Log::prune();
	}

	/**
	 * When the WordPress REST API is initialised, check whether the request
	 * should be blocked or allowed to proceed.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Server $wp_rest_server The REST server instance (unused).
	 */
	public function handle_rate_limiting( \WP_REST_Server $wp_rest_server ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$client_ip              = wptarl_client_ip();
		$is_client_rate_limited = false;
		$transient_key          = null;

		if ( ! empty( $client_ip ) ) {
			$is_client_rate_limited = $this->is_rate_limited( $client_ip );
			$transient_key          = TRANSIENT_PREFIX . $client_ip;
		}

		$is_client_rate_limited = (bool) apply_filters( 'wptarl_is_client_rate_limited', $is_client_rate_limited );

		if ( $is_client_rate_limited && ! empty( $transient_key ) ) {
			$this->enforce_rate_limit( $transient_key, $client_ip );
		}
	}

	/**
	 * Determine whether a client IP should be rate-limited.
	 *
	 * @since 2.0.0
	 *
	 * @param string $client_ip The client's IP address.
	 *
	 * @return bool Whether the client is rate-limited.
	 */
	private function is_rate_limited( string $client_ip ): bool {
		$is_limited = false;

		$never_limited_ips = wptarl_parse_ip_list( (string) get_option( OPT_NEVER_RATE_LIMITED_IPS, DEF_NEVER_RATE_LIMITED_IPS ) );

		if ( in_array( $client_ip, $never_limited_ips, true ) ) {
			// Never rate-limit these IPs.
			$is_limited = false;
		} else {
			$rate_limited_ips = apply_filters( 'wptarl_rate_limited_ips', wptarl_parse_ip_list( (string) get_option( OPT_RATE_LIMITED_IPS, DEF_RATE_LIMITED_IPS ) ) );

			if ( ! empty( $rate_limited_ips ) ) {
				$is_limited = in_array( $client_ip, $rate_limited_ips, true );
			} else {
				$rate_limit_all_guests = (bool) filter_var( get_option( OPT_RATE_LIMIT_ALL_GUESTS, DEF_RATE_LIMIT_ALL_GUESTS ), FILTER_VALIDATE_BOOLEAN );

				if ( $rate_limit_all_guests ) {
					$is_limited = ! is_user_logged_in();
				}
			}
		}

		return $is_limited;
	}

	/**
	 * Check the transient and either allow the request or send a 429 response.
	 *
	 * @since 2.0.0
	 *
	 * @param string $transient_key The transient key for this client.
	 * @param string $client_ip     The client's IP address.
	 */
	private function enforce_rate_limit( string $transient_key, string $client_ip ): void {
		if ( empty( get_transient( $transient_key ) ) ) {
			// No recent request from this IP - allow it and set the transient.
			$seconds_between_calls = absint( apply_filters( 'wptarl_seconds_between_api_calls', get_option( OPT_SECONDS_BETWEEN_CALLS, DEF_SECONDS_BETWEEN_CALLS ), $client_ip ) );

			if ( $seconds_between_calls > 0 ) {
				set_transient( $transient_key, '1', $seconds_between_calls );
			}
		} else {
			// Recent request exists - block with 429.
			Log::record_block( $client_ip );

			$response = array(
				'code'    => 'rate_limited',
				'message' => __( 'Too many requests. Please slow down.', 'api-rate-limit-now' ),
			);

			wp_send_json( $response, 429 );
		}
	}
}
