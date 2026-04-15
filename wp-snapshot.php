<?php
/**
 * Plugin Name:       WP Snapshot
 * Plugin URI:        https://github.com/mrabro/wp-snapshot
 * Description:       Generate a complete site audit report — plugins, themes, server info, database, cron, security, and more. Export as PDF or share via temporary link.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            M Rafi Abro
 * Author URI:        https://github.com/mrabro
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-snapshot
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'WPS_VERSION', '1.0.0' );
define( 'WPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Explicit class-to-file map.
 * Avoids fragile string-manipulation autoloading.
 *
 * Format: 'WPSnapshot\Class_Name' => 'path/relative/to/WPS_PLUGIN_DIR'
 */
$wps_class_map = [
	// Core classes
	'WPSnapshot\Bootstrap'            => 'includes/class-wps-bootstrap.php',
	'WPSnapshot\Activator'            => 'includes/class-wps-activator.php',
	'WPSnapshot\Deactivator'          => 'includes/class-wps-deactivator.php',
	'WPSnapshot\Admin_Page'           => 'includes/class-wps-admin-page.php',
	'WPSnapshot\Snapshot_Engine'      => 'includes/class-wps-snapshot-engine.php',
	'WPSnapshot\Snapshot_Sanitizer'   => 'includes/class-wps-snapshot-sanitizer.php',
	'WPSnapshot\Rest_Api'             => 'includes/class-wps-rest-api.php',
	'WPSnapshot\Share_Manager'        => 'includes/class-wps-share-manager.php',
	'WPSnapshot\Pdf_Generator'        => 'includes/class-wps-pdf-generator.php',
	// Interface
	'WPSnapshot\Collector_Interface'  => 'includes/interface-wps-collector.php',
	// Collectors
	'WPSnapshot\Collector_Environment' => 'includes/collectors/class-wps-collector-environment.php',
	'WPSnapshot\Collector_Plugins'     => 'includes/collectors/class-wps-collector-plugins.php',
	'WPSnapshot\Collector_Themes'      => 'includes/collectors/class-wps-collector-themes.php',
	'WPSnapshot\Collector_Database'    => 'includes/collectors/class-wps-collector-database.php',
	'WPSnapshot\Collector_Cron'        => 'includes/collectors/class-wps-collector-cron.php',
	'WPSnapshot\Collector_Post_Types'  => 'includes/collectors/class-wps-collector-post-types.php',
	'WPSnapshot\Collector_Taxonomies'  => 'includes/collectors/class-wps-collector-taxonomies.php',
	'WPSnapshot\Collector_Users'       => 'includes/collectors/class-wps-collector-users.php',
	'WPSnapshot\Collector_Rest_Api'    => 'includes/collectors/class-wps-collector-rest-api.php',
	'WPSnapshot\Collector_Security'    => 'includes/collectors/class-wps-collector-security.php',
	'WPSnapshot\Collector_Media'       => 'includes/collectors/class-wps-collector-media.php',
	'WPSnapshot\Collector_Performance' => 'includes/collectors/class-wps-collector-performance.php',
];

spl_autoload_register( function ( string $class_name ) use ( $wps_class_map ): void {
	if ( isset( $wps_class_map[ $class_name ] ) ) {
		$file = WPS_PLUGIN_DIR . $wps_class_map[ $class_name ];
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
} );

// Activation & deactivation hooks.
register_activation_hook( __FILE__, [ 'WPSnapshot\\Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'WPSnapshot\\Deactivator', 'deactivate' ] );

// Boot the plugin.
add_action( 'plugins_loaded', function (): void {
	load_plugin_textdomain( 'wp-snapshot', false, dirname( WPS_PLUGIN_BASENAME ) . '/languages' );
	$bootstrap = new WPSnapshot\Bootstrap();
	$bootstrap->init();
} );
