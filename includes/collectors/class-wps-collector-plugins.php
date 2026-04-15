<?php
/**
 * Plugins Collector
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Collector_Plugins
 *
 * Collects information about installed plugins.
 */
class Collector_Plugins implements Collector_Interface {

	public function get_key(): string {
		return 'plugins';
	}

	public function get_title(): string {
		return __( 'Plugins', 'wp-snapshot' );
	}

	public function collect(): array {
		// get_plugins() lives in admin includes — load it if not already available.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// is_plugin_active_for_network() also requires this include.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins    = get_plugins();
		$active_plugins = get_option( 'active_plugins', [] );
		$plugin_updates = get_site_transient( 'update_plugins' );
		$auto_updates   = (array) get_site_option( 'auto_update_plugins', [] );
		$mu_plugins     = get_mu_plugins();
		$dropins        = get_dropins();

		$plugins = [];

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$has_update  = isset( $plugin_updates->response[ $plugin_file ] );
			$update_ver  = $has_update ? $plugin_updates->response[ $plugin_file ]->new_version : null;
			$is_active   = in_array( $plugin_file, $active_plugins, true );
			$is_auto     = in_array( $plugin_file, $auto_updates, true );

			$plugins[] = [
				'file'           => $plugin_file,
				'name'           => $plugin_data['Name'],
				'version'        => $plugin_data['Version'],
				'author'         => wp_strip_all_tags( $plugin_data['Author'] ),
				'uri'            => $plugin_data['PluginURI'],
				'description'    => wp_strip_all_tags( $plugin_data['Description'] ),
				'is_active'      => $is_active,
				'has_update'     => $has_update,
				'update_version' => $update_ver,
				'auto_update'    => $is_auto,
				'requires_wp'    => $plugin_data['RequiresWP'] ?? null,
				'requires_php'   => $plugin_data['RequiresPHP'] ?? null,
				'text_domain'    => $plugin_data['TextDomain'],
				'network_active' => is_multisite() ? is_plugin_active_for_network( $plugin_file ) : false,
			];
		}

		// Must-use plugins.
		$mu_list = [];
		foreach ( $mu_plugins as $mu_file => $mu_data ) {
			$mu_list[] = [
				'file'    => $mu_file,
				'name'    => $mu_data['Name'],
				'version' => $mu_data['Version'],
				'author'  => wp_strip_all_tags( $mu_data['Author'] ),
			];
		}

		// Drop-in plugins.
		$dropin_list = [];
		foreach ( $dropins as $dropin_file => $dropin_data ) {
			$dropin_list[] = [
				'file' => $dropin_file,
				'name' => $dropin_data['Name'],
			];
		}

		$updates_available = is_object( $plugin_updates ) ? count( $plugin_updates->response ?? [] ) : 0;

		return [
			'total_plugins'    => count( $all_plugins ),
			'active_count'     => count( $active_plugins ),
			'inactive_count'   => count( $all_plugins ) - count( $active_plugins ),
			'update_available' => $updates_available,
			'mu_plugins'       => $mu_list,
			'dropins'          => $dropin_list,
			'plugins'          => $plugins,
		];
	}
}
