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
			PLUGIN_NAME,
			PLUGIN_NAME,
			ADMIN_CAPABILITY,
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
		if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
			return;
		}

		include PLUGIN_DIR . '/admin-templates/settings-page.php';
	}

	/**
	 * Add a "Settings" link to the plugin's row on the Plugins page.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $links Existing plugin action links.
	 *
	 * @return mixed The links with Settings first, or the value unchanged when it isn't an array.
	 */
	public function add_settings_link( mixed $links ): mixed {
		if ( is_array( $links ) ) {
			$settings_link = sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'options-general.php?page=' . ADMIN_MENU_SLUG ) ),
				esc_html__( 'Settings', 'api-rate-limit-now' )
			);

			array_unshift( $links, $settings_link );
		}

		return $links;
	}

	/**
	 * Enqueue admin styles on the plugin settings page only.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_assets( mixed $hook_suffix ): void {
		if ( 'settings_page_' . ADMIN_MENU_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			ADMIN_ASSET_HANDLE,
			plugins_url( 'assets/admin/admin.css', $this->plugin_file ),
			array(),
			WPTARL_VERSION
		);

		wp_enqueue_script(
			ADMIN_ASSET_HANDLE,
			plugins_url( 'assets/admin/admin.js', $this->plugin_file ),
			array(),
			WPTARL_VERSION,
			true
		);

		wp_localize_script(
			ADMIN_ASSET_HANDLE,
			'wptarlAdmin',
			array(
				'copiedText' => _x( 'copied!', 'confirmation after copying the IP address to the clipboard', 'api-rate-limit-now' ),
			)
		);
	}
}
