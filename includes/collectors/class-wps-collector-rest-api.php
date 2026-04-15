<?php
/**
 * REST API Collector
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Collector_Rest_Api
 *
 * Collects information about registered REST API endpoints.
 *
 * Note: This collector is called from within a REST API request (POST /generate),
 * which means rest_api_init has already fired and all routes are registered.
 */
class Collector_Rest_Api implements Collector_Interface {

	public function get_key(): string {
		return 'rest_api';
	}

	public function get_title(): string {
		return __( 'REST API', 'site-audit-snapshot' );
	}

	public function collect(): array {
		$server     = rest_get_server();
		$routes     = $server->get_routes();
		$namespaces = $server->get_namespaces();

		$endpoints   = [];
		$by_namespace = [];

		foreach ( $routes as $route => $handlers ) {
			$methods = [];
			foreach ( $handlers as $handler ) {
				if ( isset( $handler['methods'] ) && is_array( $handler['methods'] ) ) {
					$methods = array_merge( $methods, array_keys( $handler['methods'] ) );
				}
			}
			$methods = array_unique( $methods );

			// Determine which namespace this route belongs to.
			$namespace = '';
			$route_trimmed = ltrim( $route, '/' );
			foreach ( $namespaces as $ns ) {
				if ( str_starts_with( $route_trimmed, $ns ) ) {
					$namespace = $ns;
					break;
				}
			}

			$endpoints[] = [
				'route'     => $route,
				'methods'   => $methods,
				'namespace' => $namespace,
			];

			$ns_key = $namespace ?: 'root';
			$by_namespace[ $ns_key ] = ( $by_namespace[ $ns_key ] ?? 0 ) + 1;
		}

		// Sort endpoints alphabetically by route.
		usort( $endpoints, fn( $a, $b ) => strcmp( $a['route'], $b['route'] ) );

		// Sort namespace summary by count descending.
		arsort( $by_namespace );

		return [
			'total_routes' => count( $endpoints ),
			'namespaces'   => array_values( $namespaces ),
			'by_namespace' => $by_namespace,
			'endpoints'    => $endpoints,
		];
	}
}
