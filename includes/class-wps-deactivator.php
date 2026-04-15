<?php
/**
 * Plugin Deactivator
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Deactivator
 *
 * Handles plugin deactivation tasks.
 */
class Deactivator {

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		// Unschedule the daily cleanup cron event.
		$timestamp = wp_next_scheduled( 'wps_cleanup_expired_tokens' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'wps_cleanup_expired_tokens' );
		}

		// Flush rewrite rules to remove our custom share-link rules.
		flush_rewrite_rules();
	}
}
