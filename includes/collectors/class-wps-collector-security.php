<?php
/**
 * Security Collector
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Collector_Security
 *
 * Collects security posture indicators for the site.
 */
class Collector_Security implements Collector_Interface {

	public function get_key(): string {
		return 'security';
	}

	public function get_title(): string {
		return __( 'Security', 'wp-snapshot' );
	}

	public function collect(): array {
		global $wpdb;

		$checks = [];

		// 1. WP_DEBUG
		$debug_on = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$checks['wp_debug'] = [
			'label'  => __( 'WP_DEBUG', 'wp-snapshot' ),
			'value'  => $debug_on,
			'status' => $debug_on ? 'warning' : 'good',
			'note'   => $debug_on
				? __( 'Debug mode is enabled — disable on production.', 'wp-snapshot' )
				: __( 'Disabled.', 'wp-snapshot' ),
		];

		// 2. WP_DEBUG_DISPLAY
		$debug_display = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY;
		$checks['wp_debug_display'] = [
			'label'  => __( 'WP_DEBUG_DISPLAY', 'wp-snapshot' ),
			'value'  => $debug_display,
			'status' => $debug_display ? 'critical' : 'good',
			'note'   => $debug_display
				? __( 'Errors displayed to visitors — serious security risk.', 'wp-snapshot' )
				: __( 'Disabled.', 'wp-snapshot' ),
		];

		// 3. WP_DEBUG_LOG
		$debug_log = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;
		$checks['wp_debug_log'] = [
			'label'  => __( 'WP_DEBUG_LOG', 'wp-snapshot' ),
			'value'  => $debug_log,
			'status' => $debug_log ? 'info' : 'good',
			'note'   => $debug_log
				? __( 'Errors are logged to a file.', 'wp-snapshot' )
				: __( 'Disabled.', 'wp-snapshot' ),
		];

		// 4. HTTPS
		$is_ssl = is_ssl();
		$checks['ssl'] = [
			'label'  => __( 'HTTPS', 'wp-snapshot' ),
			'value'  => $is_ssl,
			'status' => $is_ssl ? 'good' : 'critical',
			'note'   => ! $is_ssl ? __( 'Site is not using HTTPS.', 'wp-snapshot' ) : __( 'Active.', 'wp-snapshot' ),
		];

		// 5. DISALLOW_FILE_EDIT
		$file_edit_disabled = defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT;
		$checks['file_editing'] = [
			'label'  => __( 'File editing disabled (DISALLOW_FILE_EDIT)', 'wp-snapshot' ),
			'value'  => $file_edit_disabled,
			'status' => $file_edit_disabled ? 'good' : 'warning',
			'note'   => ! $file_edit_disabled
				? __( 'Theme/plugin editor is accessible in wp-admin.', 'wp-snapshot' )
				: __( 'Editor disabled.', 'wp-snapshot' ),
		];

		// 6. DISALLOW_FILE_MODS
		$file_mods_disabled = defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS;
		$checks['file_mods'] = [
			'label'  => __( 'File modifications disabled (DISALLOW_FILE_MODS)', 'wp-snapshot' ),
			'value'  => $file_mods_disabled,
			'status' => $file_mods_disabled ? 'good' : 'info',
			'note'   => $file_mods_disabled
				? __( 'Plugin/theme installs and updates are blocked.', 'wp-snapshot' )
				: __( 'Not set.', 'wp-snapshot' ),
		];

		// 7. Custom database table prefix
		$has_custom_prefix = $wpdb->prefix !== 'wp_';
		$checks['db_prefix'] = [
			'label'  => __( 'Custom database prefix', 'wp-snapshot' ),
			'value'  => $has_custom_prefix,
			'status' => $has_custom_prefix ? 'good' : 'info',
			'note'   => $has_custom_prefix
				? sprintf( __( 'Prefix is: %s', 'wp-snapshot' ), esc_html( $wpdb->prefix ) )
				: __( 'Using default wp_ prefix.', 'wp-snapshot' ),
		];

		// 8. Core auto-updates
		$auto_updates_core = wp_is_auto_update_enabled_for_type( 'core' );
		$checks['auto_updates_core'] = [
			'label'  => __( 'Core auto-updates', 'wp-snapshot' ),
			'value'  => $auto_updates_core,
			'status' => $auto_updates_core ? 'good' : 'info',
			'note'   => $auto_updates_core
				? __( 'WordPress will update automatically.', 'wp-snapshot' )
				: __( 'Disabled — manual updates required.', 'wp-snapshot' ),
		];

		// 9. Application passwords
		$app_passwords = wp_is_application_passwords_available();
		$checks['app_passwords'] = [
			'label'  => __( 'Application passwords', 'wp-snapshot' ),
			'value'  => $app_passwords,
			'status' => 'info',
			'note'   => $app_passwords
				? __( 'Enabled (REST API authentication).', 'wp-snapshot' )
				: __( 'Disabled.', 'wp-snapshot' ),
		];

		// 10. wp-config.php writable
		$wp_config_path = ABSPATH . 'wp-config.php';
		if ( ! file_exists( $wp_config_path ) ) {
			$wp_config_path = dirname( ABSPATH ) . '/wp-config.php';
		}
		$config_writable = file_exists( $wp_config_path ) && wp_is_writable( $wp_config_path );
		$checks['wp_config_writable'] = [
			'label'  => __( 'wp-config.php writable', 'wp-snapshot' ),
			'value'  => $config_writable,
			'status' => $config_writable ? 'warning' : 'good',
			'note'   => $config_writable
				? __( 'wp-config.php is web-writable — restrict permissions to 440 or 400.', 'wp-snapshot' )
				: __( 'Read-only.', 'wp-snapshot' ),
		];

		// 11. xmlrpc.php enabled
		$xmlrpc_enabled = apply_filters( 'xmlrpc_enabled', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		$checks['xmlrpc'] = [
			'label'  => __( 'XML-RPC', 'wp-snapshot' ),
			'value'  => $xmlrpc_enabled,
			'status' => $xmlrpc_enabled ? 'info' : 'good',
			'note'   => $xmlrpc_enabled
				? __( 'XML-RPC is enabled.', 'wp-snapshot' )
				: __( 'Disabled.', 'wp-snapshot' ),
		];

		// Summarize by status.
		$critical_count = count( array_filter( $checks, fn( $c ) => $c['status'] === 'critical' ) );
		$warning_count  = count( array_filter( $checks, fn( $c ) => $c['status'] === 'warning' ) );
		$good_count     = count( array_filter( $checks, fn( $c ) => $c['status'] === 'good' ) );

		return [
			'critical_count' => $critical_count,
			'warning_count'  => $warning_count,
			'good_count'     => $good_count,
			'checks'         => $checks,
		];
	}
}
