<?php
/**
 * Snapshot Sanitizer
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Snapshot_Sanitizer
 *
 * Strips sensitive data from snapshots before sharing publicly.
 *
 * Redaction policy (agreed with project owner):
 * - DB credentials (host, name) → "[redacted]"
 * - Server filesystem path (ABSPATH) → "[redacted]"
 * - wp-content directory path → "[redacted]"
 * - All other data (plugins, PHP info, server software, etc.) is kept
 *   since share links are intended for trusted clients and developers.
 */
class Snapshot_Sanitizer {

	/**
	 * Sanitize a snapshot for sharing or exporting via a share link.
	 *
	 * @param array $snapshot Full snapshot array.
	 * @return array Sanitized snapshot array.
	 */
	public static function sanitize_for_sharing( array $snapshot ): array {
		// --- Environment section ---
		if ( isset( $snapshot['sections']['environment']['data'] ) ) {
			$env = &$snapshot['sections']['environment']['data'];
			// Redact local filesystem paths.
			if ( isset( $env['abspath'] ) ) {
				$env['abspath'] = '[redacted]';
			}
			if ( isset( $env['wp_content_dir'] ) ) {
				$env['wp_content_dir'] = '[redacted]';
			}
		}

		// --- Database section ---
		if ( isset( $snapshot['sections']['database']['data'] ) ) {
			$db = &$snapshot['sections']['database']['data'];
			if ( isset( $db['db_host'] ) ) {
				$db['db_host'] = '[redacted]';
			}
			if ( isset( $db['db_name'] ) ) {
				$db['db_name'] = '[redacted]';
			}
		}

		// --- Media section ---
		// Remove the local upload path; keep the public URL.
		if ( isset( $snapshot['sections']['media']['data']['upload_path'] ) ) {
			$snapshot['sections']['media']['data']['upload_path'] = '[redacted]';
		}

		return $snapshot;
	}
}
