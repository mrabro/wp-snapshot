<?php
/**
 * Post Types Collector
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Collector_Post_Types
 *
 * Collects information about registered post types and taxonomies.
 */
class Collector_Post_Types implements Collector_Interface {

	/**
	 * WordPress built-in post types (not custom).
	 *
	 * @var string[]
	 */
	private array $builtin = [
		'post', 'page', 'attachment', 'revision', 'nav_menu_item',
		'custom_css', 'customize_changeset', 'oembed_cache',
		'user_request', 'wp_block', 'wp_template', 'wp_template_part',
		'wp_global_styles', 'wp_navigation', 'wp_font_family', 'wp_font_face',
	];

	public function get_key(): string {
		return 'post_types';
	}

	public function get_title(): string {
		return __( 'Post Types', 'site-audit-snapshot' );
	}

	public function collect(): array {
		$post_types = get_post_types( [], 'objects' );

		$data = [];
		foreach ( $post_types as $slug => $pt ) {
			$count     = wp_count_posts( $slug );
			$published = isset( $count->publish ) ? (int) $count->publish : 0;

			$data[] = [
				'slug'         => $slug,
				'label'        => $pt->label,
				'is_builtin'   => in_array( $slug, $this->builtin, true ),
				'is_public'    => $pt->public,
				'hierarchical' => $pt->hierarchical,
				'has_archive'  => (bool) $pt->has_archive,
				'show_in_rest' => $pt->show_in_rest,
				'rest_base'    => $pt->rest_base ?: $slug,
				'supports'     => array_keys( get_all_post_type_supports( $slug ) ),
				'published'    => $published,
				'menu_icon'    => $pt->menu_icon,
			];
		}

		$custom_types = array_filter( $data, fn( $pt ) => ! $pt['is_builtin'] );

		return [
			'total_post_types' => count( $data ),
			'custom_count'     => count( $custom_types ),
			'post_types'       => $data,
		];
	}
}
