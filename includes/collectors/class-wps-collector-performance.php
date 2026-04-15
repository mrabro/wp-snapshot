<?php
/**
 * Performance Collector
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Collector_Performance
 *
 * Collects performance-related indicators.
 */
class Collector_Performance implements Collector_Interface {

	public function get_key(): string {
		return 'performance';
	}

	public function get_title(): string {
		return __( 'Performance', 'site-audit-snapshot' );
	}

	public function collect(): array {
		// Object cache type detection.
		$object_cache_type = __( 'None (default transient/DB cache)', 'site-audit-snapshot' );
		if ( wp_using_ext_object_cache() ) {
			if ( defined( 'WP_REDIS_VERSION' ) ) {
				$object_cache_type = 'Redis (v' . WP_REDIS_VERSION . ')';
			} elseif ( defined( 'MEMCACHE_VERS' ) || class_exists( 'Memcache' ) ) {
				$object_cache_type = 'Memcache';
			} elseif ( class_exists( 'Memcached' ) ) {
				$object_cache_type = 'Memcached';
			} else {
				$object_cache_type = __( 'External (type unknown)', 'site-audit-snapshot' );
			}
		}

		// Page cache detection via advanced-cache.php drop-in.
		$page_cache_active = file_exists( WP_CONTENT_DIR . '/advanced-cache.php' );

		// OPcache status.
		$opcache_enabled = false;
		if ( function_exists( 'opcache_get_status' ) ) {
			$opcache_status  = opcache_get_status( false );
			$opcache_enabled = ! empty( $opcache_status['opcache_enabled'] );
		}

		// Image editor — check class availability instead of private _wp_image_editor_choose().
		$image_editor = 'Unknown';
		if ( class_exists( 'Imagick' ) ) {
			$image_editor = 'Imagick';
		} elseif ( function_exists( 'gd_info' ) ) {
			$image_editor = 'GD';
		}

		// Permalink structure.
		$permalink_structure = get_option( 'permalink_structure', '' );

		// WordPress.org reachability — cache for 24 hours to avoid blocking on every snapshot.
		$wp_org_reachable = get_transient( 'wps_wp_org_reachable' );
		if ( false === $wp_org_reachable ) {
			$api_response = wp_remote_get(
				'https://api.wordpress.org/',
				[
					'timeout'  => 5,
					'blocking' => true,
					'sslverify' => true,
				]
			);
			$wp_org_reachable = ( ! is_wp_error( $api_response )
				&& wp_remote_retrieve_response_code( $api_response ) === 200 )
				? 'yes' : 'no';

			// Cache for 24 hours.
			set_transient( 'wps_wp_org_reachable', $wp_org_reachable, DAY_IN_SECONDS );
		}

		// Active object cache drop-in file.
		$object_cache_dropin = file_exists( WP_CONTENT_DIR . '/object-cache.php' );

		return [
			'object_cache_active'  => wp_using_ext_object_cache(),
			'object_cache_type'    => $object_cache_type,
			'object_cache_dropin'  => $object_cache_dropin,
			'page_cache_likely'    => $page_cache_active,
			'opcache_enabled'      => $opcache_enabled,
			'image_editor'         => $image_editor,
			'permalink_structure'  => $permalink_structure ?: __( 'Plain (no pretty permalinks)', 'site-audit-snapshot' ),
			'max_upload_size'      => wp_max_upload_size(),
			'max_upload_human'     => size_format( wp_max_upload_size() ),
			'wp_org_reachable'     => $wp_org_reachable === 'yes',
		];
	}
}
