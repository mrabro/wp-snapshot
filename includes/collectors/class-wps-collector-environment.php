<?php
/**
 * Environment Collector
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Collector_Environment
 *
 * Collects server and WordPress environment information.
 */
class Collector_Environment implements Collector_Interface {

	public function get_key(): string {
		return 'environment';
	}

	public function get_title(): string {
		return __( 'Environment', 'wp-snapshot' );
	}

	public function collect(): array {
		global $wpdb;

		// wp_get_wp_version() was introduced in WP 6.7; fall back for 6.5–6.6.
		$wp_version = function_exists( 'wp_get_wp_version' )
			? wp_get_wp_version()
			: get_bloginfo( 'version' );

		// Parse db_server_info to distinguish MySQL from MariaDB.
		$db_server_info = $wpdb->db_server_info();
		$db_type        = str_contains( strtolower( $db_server_info ), 'mariadb' )
			? 'MariaDB'
			: 'MySQL';

		// Filter PHP extensions to the set relevant for WordPress.
		$relevant_extensions = [
			'curl', 'gd', 'imagick', 'mbstring', 'openssl',
			'xml', 'zip', 'intl', 'sodium', 'exif', 'fileinfo',
		];
		$loaded = get_loaded_extensions();
		$extensions = [];
		foreach ( $relevant_extensions as $ext ) {
			$extensions[ $ext ] = in_array( $ext, $loaded, true );
		}

		return [
			'wp_version'           => $wp_version,
			'php_version'          => PHP_VERSION,
			'php_memory_limit'     => ini_get( 'memory_limit' ),
			'php_max_execution'    => ini_get( 'max_execution_time' ),
			'php_max_upload'       => size_format( wp_max_upload_size() ),
			'php_max_upload_bytes' => wp_max_upload_size(),
			'php_post_max_size'    => ini_get( 'post_max_size' ),
			'php_extensions'       => $extensions,
			'db_version'           => $wpdb->db_version(),
			'db_type'              => $db_type,
			'db_server_info'       => $db_server_info,
			'server_software'      => sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ),
			'server_os'            => php_uname( 's' ) . ' ' . php_uname( 'r' ),
			'wp_debug'             => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'wp_debug_display'     => defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY,
			'wp_debug_log'         => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
			'is_multisite'         => is_multisite(),
			'wp_locale'            => get_locale(),
			'wp_timezone'          => wp_timezone_string(),
			'site_url'             => get_site_url(),
			'home_url'             => get_home_url(),
			'wp_content_dir'       => WP_CONTENT_DIR,
			'is_https'             => is_ssl(),
			'wp_memory_limit'      => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : 'Not set',
			'wp_max_memory_limit'  => defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : 'Not set',
			'abspath'              => ABSPATH,
			'htaccess_writable'    => wp_is_writable( ABSPATH . '.htaccess' ),
			'wp_cron_disabled'     => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			'alternate_cron'       => defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON,
			'concatenate_scripts'  => defined( 'CONCATENATE_SCRIPTS' ) ? CONCATENATE_SCRIPTS : 'Not set',
			'disallow_file_edit'   => defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT,
			'disallow_file_mods'   => defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS,
		];
	}
}
