<?php
/**
 * Plugin Bootstrap
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Bootstrap
 *
 * Registers all WordPress hooks. Intentionally lean — only wires things up.
 */
class Bootstrap {

	/**
	 * Initialize all hooks.
	 */
	public function init(): void {
		// Admin page.
		$admin_page = new Admin_Page();
		add_action( 'admin_menu', [ $admin_page, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $admin_page, 'enqueue_assets' ] );

		// REST API.
		$rest_api = new Rest_Api();
		add_action( 'rest_api_init', [ $rest_api, 'register_routes' ] );

		// Share link handler (frontend, accessible by non-logged-in users).
		$share_manager = new Share_Manager();
		add_action( 'init', [ $share_manager, 'register_rewrite_rules' ] );
		add_filter( 'query_vars', [ $share_manager, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $share_manager, 'handle_share_request' ] );

		// Add settings link on plugins page.
		add_filter(
			'plugin_action_links_' . WPS_PLUGIN_BASENAME,
			[ $this, 'add_action_links' ]
		);

		// Cron: cleanup expired share tokens daily.
		add_action( 'wps_cleanup_expired_tokens', [ $share_manager, 'cleanup_expired' ] );
	}

	/**
	 * Add "Generate Snapshot" link on the Plugins list page.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_action_links( array $links ): array {
		$custom_links = [
			'<a href="' . esc_url( admin_url( 'tools.php?page=wp-snapshot' ) ) . '">'
			. esc_html__( 'Generate Snapshot', 'wp-snapshot' ) . '</a>',
		];
		return array_merge( $custom_links, $links );
	}
}
