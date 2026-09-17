<?php
/**
 * Admin Hooks class.
 *
 * Handles admin menu registration, settings page rendering, and asset
 * enqueueing.
 *
 * @package Api_Rate_Limiter
 */

namespace Api_Rate_Limiter;

defined( 'ABSPATH' ) || die();

/**
 * Admin_Hooks class.
 *
 * @since 2.0.0
 */
class Admin_Hooks {

	/**
	 * The main plugin file path, used for plugin_basename().
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 */
	public function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	/**
	 * Register admin menu item under Settings.
	 *
	 * @since 2.0.0
	 */
	public function add_menu_items(): void {
		add_options_page(
			__( 'API Rate Limiter', 'api-rate-limit-now' ),
			__( 'API Rate Limiter', 'api-rate-limit-now' ),
			'manage_options',
			ADMIN_MENU_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @since 2.0.0
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include PLUGIN_DIR . '/admin-templates/settings-page.php';
	}

	/**
	 * Add a "Settings" link to the plugin's row on the Plugins page.
	 *
	 * @since 2.0.0
	 *
	 * @param array $links Existing plugin action links.
	 *
	 * @return array Modified plugin action links.
	 */
	public function add_settings_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . ADMIN_MENU_SLUG ) ),
			esc_html__( 'Settings', 'api-rate-limit-now' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Enqueue admin styles on the plugin settings page only.
	 *
	 * @since 2.0.0
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . ADMIN_MENU_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'wptarl-admin',
			plugins_url( 'assets/admin/admin.css', $this->plugin_file ),
			array(),
			'2.0.0'
		);

		wp_enqueue_script(
			'wptarl-admin',
			plugins_url( 'assets/admin/admin.js', $this->plugin_file ),
			array(),
			'2.0.0',
			true
		);

		wp_localize_script(
			'wptarl-admin',
			'wptarlAdmin',
			array(
				'copiedText' => __( 'copied!', 'api-rate-limit-now' ),
			)
		);
	}

	/**
	 * Get the plugin file path.
	 *
	 * @since 2.0.0
	 *
	 * @return string The main plugin file path.
	 */
	public function get_plugin_file(): string {
		return $this->plugin_file;
	}
}
