<?php
/**
 * PDF Generator
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Pdf_Generator
 *
 * Generates a print-optimised HTML page from a snapshot.
 * The browser handles the actual PDF conversion via Ctrl+P / Print to PDF.
 */
class Pdf_Generator {

	/**
	 * Build the full HTML document for a snapshot.
	 *
	 * @param array $snapshot The snapshot data.
	 * @return string Complete HTML string.
	 */
	public function generate_html( array $snapshot ): string {
		ob_start();
		include WPS_PLUGIN_DIR . 'templates/pdf-template.php';
		return (string) ob_get_clean();
	}
}
