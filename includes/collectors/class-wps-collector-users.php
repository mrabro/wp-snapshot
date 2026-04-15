<?php
/**
 * Users & Roles Collector
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Collector_Users
 *
 * Collects information about users and roles.
 */
class Collector_Users implements Collector_Interface {

	public function get_key(): string {
		return 'users';
	}

	public function get_title(): string {
		return __( 'Users & Roles', 'site-audit-snapshot' );
	}

	public function collect(): array {
		$user_count_data = count_users(); // ['total_users' => int, 'avail_roles' => [...]]
		$wp_roles        = wp_roles();

		$roles = [];
		foreach ( $wp_roles->roles as $role_slug => $role_info ) {
			$roles[] = [
				'slug'       => $role_slug,
				'name'       => translate_user_role( $role_info['name'] ),
				'user_count' => $user_count_data['avail_roles'][ $role_slug ] ?? 0,
				'cap_count'  => count( array_filter( $role_info['capabilities'] ) ),
			];
		}

		// Sort roles by user count descending.
		usort( $roles, fn( $a, $b ) => $b['user_count'] <=> $a['user_count'] );

		return [
			'total_users' => $user_count_data['total_users'],
			'roles'       => $roles,
		];
	}
}
