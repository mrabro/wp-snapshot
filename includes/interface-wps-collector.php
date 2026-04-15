<?php
/**
 * Collector Interface
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Interface Collector_Interface
 *
 * All data collectors must implement this interface.
 */
interface Collector_Interface {

	/**
	 * Returns a unique snake_case key for this collector section.
	 *
	 * @return string
	 */
	public function get_key(): string;

	/**
	 * Returns the human-readable section title.
	 *
	 * @return string
	 */
	public function get_title(): string;

	/**
	 * Collects and returns the data array.
	 *
	 * @return array<string, mixed>
	 */
	public function collect(): array;
}
