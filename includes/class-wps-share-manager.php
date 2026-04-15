<?php
/**
 * Share Manager
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Share_Manager
 *
 * Manages temporary, token-based public share links.
 *
 * Tokens are stored in wp_options under `wps_share_tokens` with autoload=false.
 * Each token stores the sanitized snapshot plus expiry metadata.
 * Tokens are 64-char hex strings generated from random_bytes(32).
 */
class Share_Manager {

	/** @var string Option key for all share tokens. */
	private const OPTION_KEY = 'wps_share_tokens';

	/**
	 * Register rewrite rules for clean share URLs.
	 */
	public function register_rewrite_rules(): void {
		add_rewrite_rule(
			'^site-audit-snapshot/share/([a-f0-9]{64})/?$',
			'index.php?wps_share_token=$matches[1]',
			'top'
		);
	}

	/**
	 * Add `wps_share_token` to recognised query vars.
	 *
	 * @param string[] $vars Existing query vars.
	 * @return string[]
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'wps_share_token';
		return $vars;
	}

	/**
	 * Intercept a share-link request and render the share page.
	 */
	public function handle_share_request(): void {
		$token = get_query_var( 'wps_share_token' );
		if ( empty( $token ) ) {
			return;
		}

		$tokens = $this->get_all_tokens();
		$matched_key = null;

		// Use hash_equals() for timing-safe comparison across all stored tokens.
		foreach ( array_keys( $tokens ) as $stored_token ) {
			if ( hash_equals( $stored_token, $token ) ) {
				$matched_key = $stored_token;
				break;
			}
		}

		if ( null === $matched_key ) {
			wp_die(
				esc_html__( 'This snapshot link is invalid or has expired.', 'site-audit-snapshot' ),
				esc_html__( 'Site Audit Snapshot — Not Found', 'site-audit-snapshot' ),
				[ 'response' => 404 ]
			);
		}

		$share = $tokens[ $matched_key ];

		// Check expiry.
		if ( time() > $share['expires'] ) {
			// Cleanup the expired token.
			unset( $tokens[ $matched_key ] );
			update_option( self::OPTION_KEY, $tokens, false );

			wp_die(
				esc_html__( 'This snapshot link has expired.', 'site-audit-snapshot' ),
				esc_html__( 'Site Audit Snapshot — Expired', 'site-audit-snapshot' ),
				[ 'response' => 410 ]
			);
		}

		/**
		 * Action: wps_share_accessed
		 *
		 * @param string $token    The accessed share token.
		 * @param array  $snapshot The snapshot data being viewed.
		 */
		do_action( 'wps_share_accessed', $matched_key, $share['snapshot'] );

		$snapshot = $share['snapshot'];
		include WPS_PLUGIN_DIR . 'templates/share-page.php';
		exit;
	}

	/**
	 * Create a new share token.
	 *
	 * @param array $snapshot      The sanitized snapshot to store.
	 * @param int   $expires_hours Hours until expiry.
	 * @return array{token: string, url: string, expires: int}|\WP_Error
	 */
	public function create_token( array $snapshot, int $expires_hours = 72 ): array|\WP_Error {
		try {
			$token = bin2hex( random_bytes( 32 ) ); // 64-char cryptographically secure hex token.
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'wps_token_generation_failed',
				__( 'Failed to generate a secure token.', 'site-audit-snapshot' ),
				[ 'status' => 500 ]
			);
		}

		$expires = time() + ( $expires_hours * HOUR_IN_SECONDS );
		$tokens  = $this->get_all_tokens();

		$tokens[ $token ] = [
			'snapshot' => $snapshot,
			'expires'  => $expires,
			'created'  => time(),
		];

		update_option( self::OPTION_KEY, $tokens, false );

		return [
			'token'   => $token,
			'url'     => home_url( 'site-audit-snapshot/share/' . $token ),
			'expires' => $expires,
		];
	}

	/**
	 * Revoke (delete) a specific share token.
	 *
	 * @param string $token The token to revoke.
	 * @return bool True if the token existed and was removed.
	 */
	public function revoke_token( string $token ): bool {
		$tokens = $this->get_all_tokens();

		// Find and remove using hash_equals for timing safety.
		foreach ( array_keys( $tokens ) as $stored_token ) {
			if ( hash_equals( $stored_token, $token ) ) {
				unset( $tokens[ $stored_token ] );
				update_option( self::OPTION_KEY, $tokens, false );
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove all expired tokens from storage.
	 * Called by the daily wps_cleanup_expired_tokens cron event.
	 */
	public function cleanup_expired(): void {
		$tokens  = $this->get_all_tokens();
		$now     = time();
		$changed = false;

		foreach ( $tokens as $token => $data ) {
			if ( $now > $data['expires'] ) {
				unset( $tokens[ $token ] );
				$changed = true;
			}
		}

		if ( $changed ) {
			update_option( self::OPTION_KEY, $tokens, false );
		}
	}

	/**
	 * Get all active share tokens with their metadata (without snapshot data).
	 * Used by the admin page to display current share links.
	 *
	 * @return array[]
	 */
	public function get_active_tokens_summary(): array {
		$tokens  = $this->get_all_tokens();
		$now     = time();
		$summary = [];

		foreach ( $tokens as $token => $data ) {
			if ( $now <= $data['expires'] ) {
				$summary[] = [
					'token'      => $token,
					'url'        => home_url( 'site-audit-snapshot/share/' . $token ),
					'created'    => wp_date( 'Y-m-d H:i:s', $data['created'] ),
					'expires_at' => wp_date( 'Y-m-d H:i:s', $data['expires'] ),
					'expires_in' => human_time_diff( $now, $data['expires'] ),
				];
			}
		}

		return $summary;
	}

	/**
	 * Retrieve all share tokens from the options table.
	 *
	 * @return array
	 */
	private function get_all_tokens(): array {
		$tokens = get_option( self::OPTION_KEY, [] );
		return is_array( $tokens ) ? $tokens : [];
	}
}
