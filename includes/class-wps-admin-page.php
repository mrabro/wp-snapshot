<?php
/**
 * Admin Page
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin_Page
 *
 * Registers the Site Audit Snapshot admin menu page under Tools and enqueues assets.
 */
class Admin_Page {

	/**
	 * Register the admin page under Tools.
	 */
	public function register_menu(): void {
		add_management_page(
			__( 'Site Audit Snapshot', 'site-audit-snapshot' ),
			__( 'Site Audit Snapshot', 'site-audit-snapshot' ),
			'manage_options',
			'site-audit-snapshot',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Enqueue CSS and JS assets — only on our plugin page.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'tools_page_site-audit-snapshot' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wps-admin',
			WPS_PLUGIN_URL . 'assets/css/wps-admin.css',
			[],
			WPS_VERSION
		);

		wp_enqueue_script(
			'wps-admin',
			WPS_PLUGIN_URL . 'assets/js/wps-admin.js',
			[], // No jQuery dependency — vanilla JS only.
			WPS_VERSION,
			true // Load in footer.
		);

		// Expose data to JS.
		wp_localize_script(
			'wps-admin',
			'wpsData',
			[
				'restUrl'  => rest_url( 'site-audit-snapshot/v1/' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'pdfUrl'   => rest_url( 'site-audit-snapshot/v1/pdf' ),
				'shareUrl' => rest_url( 'site-audit-snapshot/v1/share' ),
				'i18n'     => [
					'generating'    => __( 'Generating…', 'site-audit-snapshot' ),
					'generate'      => __( 'Generate Snapshot', 'site-audit-snapshot' ),
					'copying'       => __( 'Copied!', 'site-audit-snapshot' ),
					'copy'          => __( 'Copy Link', 'site-audit-snapshot' ),
					'creatingLink'  => __( 'Creating link…', 'site-audit-snapshot' ),
					'shareExpiry'   => __( 'Share link expires in', 'site-audit-snapshot' ),
					'revokeConfirm' => __( 'Revoke this share link?', 'site-audit-snapshot' ),
					'error'         => __( 'An error occurred. Please try again.', 'site-audit-snapshot' ),
					'exportMd'      => __( 'Export Markdown', 'site-audit-snapshot' ),
				],
			]
		);
	}

	/**
	 * Render the admin page. Template handles all output.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'site-audit-snapshot' ) );
		}

		// Load existing snapshot if available.
		$snapshot      = Snapshot_Engine::load_last();
		$last_meta     = get_option( 'wps_last_snapshot' );
		$share_manager = new Share_Manager();
		$active_shares = $share_manager->get_active_tokens_summary();

		include WPS_PLUGIN_DIR . 'templates/admin-dashboard.php';
	}
}
