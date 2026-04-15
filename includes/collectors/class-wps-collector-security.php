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
		return __( 'Security', 'site-audit-snapshot' );
	}

	public function collect(): array {
		global $wpdb;

		$checks = [];

		// 1. WP_DEBUG
		$debug_on = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$checks['wp_debug'] = [
			'label'  => __( 'WP_DEBUG', 'site-audit-snapshot' ),
			'value'  => $debug_on,
			'status' => $debug_on ? 'warning' : 'good',
			'note'   => $debug_on
				? __( 'Debug mode is enabled — disable on production.', 'site-audit-snapshot' )
				: __( 'Disabled.', 'site-audit-snapshot' ),
		];

		// 2. WP_DEBUG_DISPLAY
		$debug_display = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY;
		$checks['wp_debug_display'] = [
			'label'  => __( 'WP_DEBUG_DISPLAY', 'site-audit-snapshot' ),
			'value'  => $debug_display,
			'status' => $debug_display ? 'critical' : 'good',
			'note'   => $debug_display
				? __( 'Errors displayed to visitors — serious security risk.', 'site-audit-snapshot' )
				: __( 'Disabled.', 'site-audit-snapshot' ),
		];

		// 3. WP_DEBUG_LOG
		$debug_log = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;
		$checks['wp_debug_log'] = [
			'label'  => __( 'WP_DEBUG_LOG', 'site-audit-snapshot' ),
			'value'  => $debug_log,
			'status' => $debug_log ? 'info' : 'good',
			'note'   => $debug_log
				? __( 'Errors are logged to a file.', 'site-audit-snapshot' )
				: __( 'Disabled.', 'site-audit-snapshot' ),
		];

		// 4. HTTPS
		$is_ssl = is_ssl();
		$checks['ssl'] = [
			'label'  => __( 'HTTPS', 'site-audit-snapshot' ),
			'value'  => $is_ssl,
			'status' => $is_ssl ? 'good' : 'critical',
			'note'   => ! $is_ssl ? __( 'Site is not using HTTPS.', 'site-audit-snapshot' ) : __( 'Active.', 'site-audit-snapshot' ),
		];

		// 5. DISALLOW_FILE_EDIT
		$file_edit_disabled = defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT;
		$checks['file_editing'] = [
			'label'  => __( 'File editing disabled (DISALLOW_FILE_EDIT)', 'site-audit-snapshot' ),
			'value'  => $file_edit_disabled,
			'status' => $file_edit_disabled ? 'good' : 'warning',
			'note'   => ! $file_edit_disabled
				? __( 'Theme/plugin editor is accessible in wp-admin.', 'site-audit-snapshot' )
				: __( 'Editor disabled.', 'site-audit-snapshot' ),
		];

		// 6. DISALLOW_FILE_MODS
		$file_mods_disabled = defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS;
		$checks['file_mods'] = [
			'label'  => __( 'File modifications disabled (DISALLOW_FILE_MODS)', 'site-audit-snapshot' ),
			'value'  => $file_mods_disabled,
			'status' => $file_mods_disabled ? 'good' : 'info',
			'note'   => $file_mods_disabled
				? __( 'Plugin/theme installs and updates are blocked.', 'site-audit-snapshot' )
				: __( 'Not set.', 'site-audit-snapshot' ),
		];

		// 7. Custom database table prefix
		$has_custom_prefix = $wpdb->prefix !== 'wp_';
		$checks['db_prefix'] = [
			'label'  => __( 'Custom database prefix', 'site-audit-snapshot' ),
			'value'  => $has_custom_prefix,
			'status' => $has_custom_prefix ? 'good' : 'info',
			'note'   => $has_custom_prefix
				? sprintf( __( 'Prefix is: %s', 'site-audit-snapshot' ), esc_html( $wpdb->prefix ) )
				: __( 'Using default wp_ prefix.', 'site-audit-snapshot' ),
		];

		// 8. Core auto-updates
		$auto_updates_core = wp_is_auto_update_enabled_for_type( 'core' );
		$checks['auto_updates_core'] = [
			'label'  => __( 'Core auto-updates', 'site-audit-snapshot' ),
			'value'  => $auto_updates_core,
			'status' => $auto_updates_core ? 'good' : 'info',
			'note'   => $auto_updates_core
				? __( 'WordPress will update automatically.', 'site-audit-snapshot' )
				: __( 'Disabled — manual updates required.', 'site-audit-snapshot' ),
		];

		// 9. Application passwords
		$app_passwords = wp_is_application_passwords_available();
		$checks['app_passwords'] = [
			'label'  => __( 'Application passwords', 'site-audit-snapshot' ),
			'value'  => $app_passwords,
			'status' => 'info',
			'note'   => $app_passwords
				? __( 'Enabled (REST API authentication).', 'site-audit-snapshot' )
				: __( 'Disabled.', 'site-audit-snapshot' ),
		];

		// 10. wp-config.php writable
		$wp_config_path = ABSPATH . 'wp-config.php';
		if ( ! file_exists( $wp_config_path ) ) {
			$wp_config_path = dirname( ABSPATH ) . '/wp-config.php';
		}
		$config_writable = file_exists( $wp_config_path ) && wp_is_writable( $wp_config_path );
		$checks['wp_config_writable'] = [
			'label'  => __( 'wp-config.php writable', 'site-audit-snapshot' ),
			'value'  => $config_writable,
			'status' => $config_writable ? 'warning' : 'good',
			'note'   => $config_writable
				? __( 'wp-config.php is web-writable — restrict permissions to 440 or 400.', 'site-audit-snapshot' )
				: __( 'Read-only.', 'site-audit-snapshot' ),
		];

		// 11. xmlrpc.php enabled
		$xmlrpc_enabled = apply_filters( 'xmlrpc_enabled', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		$checks['xmlrpc'] = [
			'label'  => __( 'XML-RPC', 'site-audit-snapshot' ),
			'value'  => $xmlrpc_enabled,
			'status' => $xmlrpc_enabled ? 'info' : 'good',
			'note'   => $xmlrpc_enabled
				? __( 'XML-RPC is enabled.', 'site-audit-snapshot' )
				: __( 'Disabled.', 'site-audit-snapshot' ),
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
