<?php
/**
 * Database Collector
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Collector_Database
 *
 * Collects information about the WordPress database.
 */
class Collector_Database implements Collector_Interface {

	public function get_key(): string {
		return 'database';
	}

	public function get_title(): string {
		return __( 'Database', 'site-audit-snapshot' );
	}

	public function collect(): array {
		global $wpdb;

		// Table sizes and row counts — no user input, no prepare() needed.
		$tables     = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			'SHOW TABLE STATUS FROM `' . DB_NAME . '`',
			ARRAY_A
		);

		$table_data = [];
		$total_size = 0;
		$total_rows = 0;

		if ( is_array( $tables ) ) {
			foreach ( $tables as $table ) {
				$size        = (int) $table['Data_length'] + (int) $table['Index_length'];
				$total_size += $size;
				$total_rows += (int) $table['Rows'];

				// Include only tables that belong to this WordPress installation.
				if ( str_starts_with( $table['Name'], $wpdb->prefix ) ) {
					$table_data[] = [
						'name'       => $table['Name'],
						'engine'     => $table['Engine'],
						'rows'       => (int) $table['Rows'],
						'data_size'  => (int) $table['Data_length'],
						'index_size' => (int) $table['Index_length'],
						'total_size' => $size,
						'collation'  => $table['Collation'],
						'auto_incr'  => $table['Auto_increment'],
					];
				}
			}
		}

		// Sort tables by total size descending.
		usort( $table_data, fn( $a, $b ) => $b['total_size'] <=> $a['total_size'] );

		// Autoloaded options size.
		$autoload_size = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes'"
		);

		$total_options = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COUNT(*) FROM {$wpdb->options}"
		);

		$autoloaded_options = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE autoload = 'yes'"
		);

		$transients_count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_%'"
		);

		// Post counts by type and status.
		$post_counts_raw = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT post_type, post_status, COUNT(*) as count
			 FROM {$wpdb->posts}
			 GROUP BY post_type, post_status
			 ORDER BY post_type, count DESC",
			ARRAY_A
		);

		$post_counts = [];
		if ( is_array( $post_counts_raw ) ) {
			foreach ( $post_counts_raw as $row ) {
				$post_counts[ $row['post_type'] ][ $row['post_status'] ] = (int) $row['count'];
			}
		}

		$revisions_count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'"
		);

		$trashed_count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'"
		);

		$orphaned_meta = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COUNT(*) FROM {$wpdb->postmeta}
			 WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})"
		);

		return [
			'db_name'             => DB_NAME,
			'db_host'             => DB_HOST,
			'db_prefix'           => $wpdb->prefix,
			'db_charset'          => defined( 'DB_CHARSET' ) ? DB_CHARSET : 'Unknown',
			'db_collate'          => defined( 'DB_COLLATE' ) ? DB_COLLATE : 'Unknown',
			'total_tables'        => count( $table_data ),
			'total_db_size'       => $total_size,
			'total_db_size_human' => size_format( $total_size ),
			'total_rows'          => $total_rows,
			'autoload_size'       => $autoload_size,
			'autoload_size_human' => size_format( $autoload_size ),
			'total_options'       => $total_options,
			'autoloaded_options'  => $autoloaded_options,
			'transients_count'    => $transients_count,
			'revisions_count'     => $revisions_count,
			'trashed_count'       => $trashed_count,
			'orphaned_postmeta'   => $orphaned_meta,
			'post_counts'         => $post_counts,
			'tables'              => $table_data,
		];
	}
}
