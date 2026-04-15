<?php
/**
 * Taxonomies Collector
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Collector_Taxonomies
 *
 * Collects information about registered taxonomies.
 */
class Collector_Taxonomies implements Collector_Interface {

	/**
	 * WordPress built-in taxonomies.
	 *
	 * @var string[]
	 */
	private array $builtin = [
		'category', 'post_tag', 'nav_menu', 'link_category',
		'post_format', 'wp_theme', 'wp_template_part_area',
		'wp_pattern_category',
	];

	public function get_key(): string {
		return 'taxonomies';
	}

	public function get_title(): string {
		return __( 'Taxonomies', 'wp-snapshot' );
	}

	public function collect(): array {
		$taxonomies = get_taxonomies( [], 'objects' );

		$tax_data = [];
		foreach ( $taxonomies as $slug => $tax ) {
			// wp_count_terms() returns int|string|WP_Error — handle all cases.
			$term_count_raw = wp_count_terms( [ 'taxonomy' => $slug, 'hide_empty' => false ] );
			if ( is_wp_error( $term_count_raw ) ) {
				$term_count = 0;
			} else {
				$term_count = (int) $term_count_raw;
			}

			$tax_data[] = [
				'slug'         => $slug,
				'label'        => $tax->label,
				'is_builtin'   => in_array( $slug, $this->builtin, true ),
				'is_public'    => $tax->public,
				'hierarchical' => $tax->hierarchical,
				'show_in_rest' => $tax->show_in_rest,
				'rest_base'    => $tax->rest_base ?: $slug,
				'object_type'  => $tax->object_type,
				'term_count'   => $term_count,
			];
		}

		$custom_taxonomies = array_filter( $tax_data, fn( $t ) => ! $t['is_builtin'] );

		return [
			'total_taxonomies' => count( $tax_data ),
			'custom_count'     => count( $custom_taxonomies ),
			'taxonomies'       => $tax_data,
		];
	}
}
