<?php
/**
 * Snapshot Engine
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Snapshot_Engine
 *
 * Runs all collectors and assembles the full snapshot.
 */
class Snapshot_Engine {

	/**
	 * Registered collector instances.
	 *
	 * @var Collector_Interface[]
	 */
	private array $collectors = [];

	/**
	 * Constructor — register all built-in collectors.
	 */
	public function __construct() {
		$this->collectors = [
			new Collector_Environment(),
			new Collector_Plugins(),
			new Collector_Themes(),
			new Collector_Database(),
			new Collector_Cron(),
			new Collector_Post_Types(),
			new Collector_Taxonomies(),
			new Collector_Users(),
			new Collector_Rest_Api(),
			new Collector_Security(),
			new Collector_Media(),
			new Collector_Performance(),
		];

		/**
		 * Filter: wps_collectors
		 *
		 * Allows third-party plugins to add, remove, or reorder collectors.
		 *
		 * @param Collector_Interface[] $collectors
		 */
		$this->collectors = apply_filters( 'wps_collectors', $this->collectors );
	}

	/**
	 * Generate a full snapshot.
	 *
	 * Fires wps_before_generate before collecting and wps_after_generate after.
	 *
	 * @return array{
	 *     generated_at: string,
	 *     generated_at_gmt: string,
	 *     generator_version: string,
	 *     site_url: string,
	 *     site_name: string,
	 *     sections: array<string, array{title: string, data: array}>
	 * }
	 */
	public function generate(): array {
		/**
		 * Action: wps_before_generate
		 *
		 * Fires immediately before snapshot generation begins.
		 */
		do_action( 'wps_before_generate' );

		$snapshot = [
			'generated_at'     => wp_date( 'Y-m-d H:i:s' ),
			'generated_at_gmt' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'generated_by'     => wp_get_current_user()->user_login,
			'generator_version' => WPS_VERSION,
			'site_url'         => get_site_url(),
			'site_name'        => get_bloginfo( 'name' ),
			'sections'         => [],
		];

		foreach ( $this->collectors as $collector ) {
			if ( ! $collector instanceof Collector_Interface ) {
				continue;
			}

			$key   = $collector->get_key();
			$title = $collector->get_title();

			try {
				$data = $collector->collect();
			} catch ( \Throwable $e ) {
				$data = [
					'error' => sprintf(
						/* translators: %s: error message */
						__( 'Collection failed: %s', 'site-audit-snapshot' ),
						$e->getMessage()
					),
				];
			}

			$snapshot['sections'][ $key ] = [
				'title' => $title,
				'data'  => $data,
			];
		}

		/**
		 * Filter: wps_snapshot_data
		 *
		 * Modify the complete snapshot data before it is stored or displayed.
		 *
		 * @param array $snapshot The full snapshot array.
		 */
		$snapshot = apply_filters( 'wps_snapshot_data', $snapshot );

		/**
		 * Action: wps_after_generate
		 *
		 * Fires after snapshot generation is complete.
		 *
		 * @param array $snapshot The complete snapshot data.
		 */
		do_action( 'wps_after_generate', $snapshot );

		return $snapshot;
	}

	/**
	 * Save a snapshot to a JSON file and store the metadata in wp_options.
	 *
	 * @param array $snapshot The snapshot data to save.
	 * @return string|false The absolute path to the saved file, or false on failure.
	 */
	public static function save( array $snapshot ): string|false {
		$upload_dir   = wp_get_upload_dir();
		$snapshot_dir = $upload_dir['basedir'] . '/site-audit-snapshot';

		// Ensure directory exists (created on activation, but defensive check).
		if ( ! is_dir( $snapshot_dir ) ) {
			wp_mkdir_p( $snapshot_dir );
		}

		$filename = 'snapshot-' . gmdate( 'YmdHis' ) . '.json';
		$filepath = $snapshot_dir . '/' . $filename;

		$json = wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$written = file_put_contents( $filepath, $json );
		if ( false === $written ) {
			return false;
		}

		// Store lightweight metadata so we can retrieve the latest snapshot quickly.
		update_option(
			'wps_last_snapshot',
			[
				'file'         => $filepath,
				'filename'     => $filename,
				'generated_at' => $snapshot['generated_at'],
				'site_url'     => $snapshot['site_url'],
				'size'         => $written,
			],
			false // Do not autoload.
		);

		return $filepath;
	}

	/**
	 * Load the last saved snapshot from disk.
	 *
	 * @return array|null Decoded snapshot array, or null if no snapshot exists.
	 */
	public static function load_last(): ?array {
		$meta = get_option( 'wps_last_snapshot' );
		if ( empty( $meta['file'] ) || ! file_exists( $meta['file'] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$json = file_get_contents( $meta['file'] );
		if ( false === $json ) {
			return null;
		}

		$data = json_decode( $json, true );
		return is_array( $data ) ? $data : null;
	}
}
