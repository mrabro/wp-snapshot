<?php
/**
 * Uninstall Site Audit Snapshot
 *
 * Runs when the plugin is deleted from wp-admin.
 * Removes all plugin data from the database and filesystem.
 *
 * @package WPSnapshot
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Remove plugin options.
delete_option( 'wps_settings' );
delete_option( 'wps_share_tokens' );
delete_option( 'wps_last_snapshot' );

// Remove transients.
delete_transient( 'wps_snapshot_cache' );
delete_transient( 'wps_wp_org_reachable' );

// Remove snapshot JSON files from uploads directory.
$upload_dir   = wp_get_upload_dir();
$snapshot_dir = $upload_dir['basedir'] . '/site-audit-snapshot';

if ( is_dir( $snapshot_dir ) ) {
	$files = glob( $snapshot_dir . '/*.json' );
	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}
	$index = $snapshot_dir . '/index.php';
	if ( is_file( $index ) ) {
		wp_delete_file( $index );
	}
	// Remove the directory only if it is now empty.
	@rmdir( $snapshot_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
}
