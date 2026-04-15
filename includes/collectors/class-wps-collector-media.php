<?php
/**
 * Media Library Collector
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Collector_Media
 *
 * Collects information about the WordPress media library.
 */
class Collector_Media implements Collector_Interface {

	public function get_key(): string {
		return 'media';
	}

	public function get_title(): string {
		return __( 'Media Library', 'site-audit-snapshot' );
	}

	public function collect(): array {
		$counts      = wp_count_posts( 'attachment' );
		$total_count = (int) ( $counts->inherit ?? 0 )
			+ (int) ( $counts->private ?? 0 )
			+ (int) ( $counts->publish ?? 0 );

		// Breakdown by MIME type.
		$mime_counts = (array) wp_count_attachments();

		// Categorise MIME groups for summary.
		$mime_summary = [
			'images'    => 0,
			'videos'    => 0,
			'audio'     => 0,
			'documents' => 0,
			'other'     => 0,
		];
		foreach ( $mime_counts as $mime => $count ) {
			$count = (int) $count;
			if ( str_starts_with( $mime, 'image/' ) ) {
				$mime_summary['images'] += $count;
			} elseif ( str_starts_with( $mime, 'video/' ) ) {
				$mime_summary['videos'] += $count;
			} elseif ( str_starts_with( $mime, 'audio/' ) ) {
				$mime_summary['audio'] += $count;
			} elseif ( in_array( $mime, [ 'application/pdf', 'application/msword',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'application/vnd.ms-excel', 'application/vnd.ms-powerpoint',
				'text/plain', 'text/csv' ], true ) ) {
				$mime_summary['documents'] += $count;
			} else {
				$mime_summary['other'] += $count;
			}
		}

		$upload_dir  = wp_get_upload_dir();
		$upload_path = $upload_dir['basedir'];
		$upload_size = 0;

		// recurse_dirsize() is available since WP 5.6 and is the official helper.
		if ( function_exists( 'recurse_dirsize' ) ) {
			$upload_size = (int) recurse_dirsize( $upload_path );
		}

		return [
			'total_attachments'     => $total_count,
			'mime_breakdown'        => $mime_counts,
			'mime_summary'          => $mime_summary,
			'upload_path'           => $upload_path,
			'upload_url'            => $upload_dir['baseurl'],
			'upload_dir_size'       => $upload_size,
			'upload_dir_size_human' => $upload_size > 0
				? size_format( $upload_size )
				: __( 'Unable to calculate', 'site-audit-snapshot' ),
		];
	}
}
