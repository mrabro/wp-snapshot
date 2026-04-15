<?php
/**
 * Plugin Activator
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Activator
 *
 * Handles plugin activation tasks.
 */
class Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		// Enforce minimum PHP version.
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			deactivate_plugins( WPS_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'Site Audit Snapshot requires PHP 8.1 or higher.', 'site-audit-snapshot' ),
				'Plugin Activation Error',
				[ 'back_link' => true ]
			);
		}

		// Enforce minimum WordPress version.
		global $wp_version;
		if ( version_compare( $wp_version, '6.5', '<' ) ) {
			deactivate_plugins( WPS_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'Site Audit Snapshot requires WordPress 6.5 or higher.', 'site-audit-snapshot' ),
				'Plugin Activation Error',
				[ 'back_link' => true ]
			);
		}

		// Create the uploads directory for storing snapshot JSON files.
		$upload_dir = wp_get_upload_dir();
		$snapshot_dir = $upload_dir['basedir'] . '/site-audit-snapshot';
		if ( ! file_exists( $snapshot_dir ) ) {
			wp_mkdir_p( $snapshot_dir );
		}

		// Drop an index.php to prevent directory listing.
		$index_file = $snapshot_dir . '/index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, '<?php // Silence is golden.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		// Register rewrite rules and flush so share links work immediately.
		$share_manager = new Share_Manager();
		$share_manager->register_rewrite_rules();
		flush_rewrite_rules();

		// Schedule daily cleanup of expired share tokens.
		if ( ! wp_next_scheduled( 'wps_cleanup_expired_tokens' ) ) {
			wp_schedule_event( time(), 'daily', 'wps_cleanup_expired_tokens' );
		}

		// Set default plugin settings (autoload = false to keep options table lean).
		add_option(
			'wps_settings',
			[
				'share_expiry_hours' => 72,
				'pdf_include_rest'   => false,
				'pdf_include_cron'   => true,
			],
			false
		);
	}
}
