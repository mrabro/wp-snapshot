<?php
/**
 * Themes Collector
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Collector_Themes
 *
 * Collects information about installed themes.
 */
class Collector_Themes implements Collector_Interface {

	public function get_key(): string {
		return 'themes';
	}

	public function get_title(): string {
		return __( 'Themes', 'site-audit-snapshot' );
	}

	public function collect(): array {
		$active_theme  = wp_get_theme();
		$all_themes    = wp_get_themes();
		$theme_updates = get_site_transient( 'update_themes' );

		$active_info = [
			'name'           => $active_theme->get( 'Name' ),
			'version'        => $active_theme->get( 'Version' ),
			'author'         => wp_strip_all_tags( $active_theme->get( 'Author' ) ),
			'uri'            => $active_theme->get( 'ThemeURI' ),
			'template'       => $active_theme->get_template(),
			'stylesheet'     => $active_theme->get_stylesheet(),
			'is_child_theme' => (bool) $active_theme->parent(),
			'parent_theme'   => $active_theme->parent() ? $active_theme->parent()->get( 'Name' ) : null,
			'is_block_theme' => $active_theme->is_block_theme(),
			'has_update'     => isset( $theme_updates->response[ $active_theme->get_stylesheet() ] ),
			'text_domain'    => $active_theme->get( 'TextDomain' ),
			'requires_wp'    => $active_theme->get( 'RequiresWP' ),
			'requires_php'   => $active_theme->get( 'RequiresPHP' ),
			'description'    => wp_strip_all_tags( $active_theme->get( 'Description' ) ),
			'tags'           => $active_theme->get( 'Tags' ),
		];

		// Summary of all installed themes.
		$installed = [];
		foreach ( $all_themes as $slug => $theme ) {
			$installed[] = [
				'slug'       => $slug,
				'name'       => $theme->get( 'Name' ),
				'version'    => $theme->get( 'Version' ),
				'author'     => wp_strip_all_tags( $theme->get( 'Author' ) ),
				'is_active'  => ( $slug === $active_theme->get_stylesheet() ),
				'has_update' => isset( $theme_updates->response[ $slug ] ),
			];
		}

		$updates_available = is_object( $theme_updates ) ? count( $theme_updates->response ?? [] ) : 0;

		return [
			'active_theme'     => $active_info,
			'total_themes'     => count( $all_themes ),
			'update_available' => $updates_available,
			'installed'        => $installed,
		];
	}
}
