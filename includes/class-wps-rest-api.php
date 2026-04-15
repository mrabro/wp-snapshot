<?php
/**
 * REST API
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rest_Api
 *
 * Registers and handles all WP Snapshot REST API endpoints.
 */
class Rest_Api {

	/**
	 * Register all REST routes.
	 */
	public function register_routes(): void {
		$namespace = 'site-audit-snapshot/v1';

		// POST /wp-json/site-audit-snapshot/v1/generate — generate a new snapshot.
		register_rest_route(
			$namespace,
			'/generate',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate_snapshot' ],
				'permission_callback' => [ $this, 'admin_permission' ],
			]
		);

		// GET /wp-json/site-audit-snapshot/v1/snapshot — retrieve last saved snapshot.
		register_rest_route(
			$namespace,
			'/snapshot',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_snapshot' ],
				'permission_callback' => [ $this, 'admin_permission' ],
			]
		);

		// POST /wp-json/site-audit-snapshot/v1/share — create a shareable link.
		register_rest_route(
			$namespace,
			'/share',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_share_link' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'expires_hours' => [
						'type'              => 'integer',
						'default'           => 72,
						'minimum'           => 1,
						'maximum'           => 720,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// GET /wp-json/site-audit-snapshot/v1/pdf — download printable HTML report.
		register_rest_route(
			$namespace,
			'/pdf',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'download_pdf' ],
				'permission_callback' => [ $this, 'admin_permission' ],
			]
		);

		// DELETE /wp-json/site-audit-snapshot/v1/share/{token} — revoke a share link.
		register_rest_route(
			$namespace,
			'/share/(?P<token>[a-f0-9]{64})',
			[
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'revoke_share_link' ],
				'permission_callback' => [ $this, 'admin_permission' ],
				'args'                => [
					'token' => [
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => fn( $param ) => (bool) preg_match( '/^[a-f0-9]{64}$/', $param ),
					],
				],
			]
		);
	}

	/**
	 * Permission callback: require manage_options capability.
	 *
	 * @return bool|\WP_Error
	 */
	public function admin_permission(): bool|\WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to perform this action.', 'site-audit-snapshot' ),
				[ 'status' => 403 ]
			);
		}
		return true;
	}

	/**
	 * POST /generate — run all collectors and save snapshot to disk.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function generate_snapshot( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$engine   = new Snapshot_Engine();
		$snapshot = $engine->generate();

		$filepath = Snapshot_Engine::save( $snapshot );
		if ( false === $filepath ) {
			return new \WP_Error(
				'wps_save_failed',
				__( 'Snapshot was generated but could not be saved to disk.', 'site-audit-snapshot' ),
				[ 'status' => 500 ]
			);
		}

		return rest_ensure_response(
			[
				'success'      => true,
				'generated_at' => $snapshot['generated_at'],
				'snapshot'     => $snapshot,
			]
		);
	}

	/**
	 * GET /snapshot — return the last saved snapshot.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_snapshot( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$snapshot = Snapshot_Engine::load_last();

		if ( null === $snapshot ) {
			return new \WP_Error(
				'wps_no_snapshot',
				__( 'No snapshot has been generated yet. Click "Generate Snapshot" to create one.', 'site-audit-snapshot' ),
				[ 'status' => 404 ]
			);
		}

		return rest_ensure_response( $snapshot );
	}

	/**
	 * POST /share — create a temporary public share link.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_share_link( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$snapshot = Snapshot_Engine::load_last();

		if ( null === $snapshot ) {
			return new \WP_Error(
				'wps_no_snapshot',
				__( 'Generate a snapshot first before creating a share link.', 'site-audit-snapshot' ),
				[ 'status' => 400 ]
			);
		}

		$expires_hours = absint( $request->get_param( 'expires_hours' ) ) ?: 72;
		$expires_hours = min( $expires_hours, 720 ); // Cap at 30 days.

		// Sanitize snapshot before storing for public sharing.
		$sanitized = Snapshot_Sanitizer::sanitize_for_sharing( $snapshot );

		$share_manager = new Share_Manager();
		$result        = $share_manager->create_token( $sanitized, $expires_hours );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Action: wps_share_created
		 *
		 * @param string $token        The share token.
		 * @param int    $expires      Unix timestamp of expiry.
		 * @param array  $snapshot     The sanitized snapshot.
		 */
		do_action( 'wps_share_created', $result['token'], $result['expires'], $sanitized );

		return rest_ensure_response(
			[
				'success'    => true,
				'share_url'  => $result['url'],
				'token'      => $result['token'],
				'expires_at' => wp_date( 'Y-m-d H:i:s', $result['expires'] ),
				'expires_in' => human_time_diff( time(), $result['expires'] ),
			]
		);
	}

	/**
	 * GET /pdf — serve a printable HTML report for browser print-to-PDF.
	 *
	 * Exits directly with HTML output (not a JSON response).
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return void|\WP_Error
	 */
	public function download_pdf( \WP_REST_Request $request ) {
		$snapshot = Snapshot_Engine::load_last();

		if ( null === $snapshot ) {
			return new \WP_Error(
				'wps_no_snapshot',
				__( 'No snapshot found. Generate one first.', 'site-audit-snapshot' ),
				[ 'status' => 404 ]
			);
		}

		$generator = new Pdf_Generator();
		$html      = $generator->generate_html( $snapshot );

		// Send as a standalone HTML page. Browser handles print-to-PDF.
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Content-Disposition: inline; filename="wp-snapshot-' . sanitize_file_name( $snapshot['site_name'] ) . '.html"' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		// Disable caching.
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is assembled server-side with escaping in the generator.
		exit;
	}

	/**
	 * DELETE /share/{token} — revoke a share link.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function revoke_share_link( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$token         = sanitize_text_field( $request->get_param( 'token' ) );
		$share_manager = new Share_Manager();
		$revoked       = $share_manager->revoke_token( $token );

		if ( ! $revoked ) {
			return new \WP_Error(
				'wps_token_not_found',
				__( 'Token not found or already revoked.', 'site-audit-snapshot' ),
				[ 'status' => 404 ]
			);
		}

		return rest_ensure_response(
			[
				'success' => true,
				'message' => __( 'Share link has been revoked.', 'site-audit-snapshot' ),
			]
		);
	}
}
