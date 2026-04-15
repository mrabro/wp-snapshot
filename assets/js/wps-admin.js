/**
 * WP Snapshot — Admin Dashboard JavaScript
 * Vanilla JS only. No jQuery dependency.
 */
/* global wpsData */
( function () {
	'use strict';

	const data = window.wpsData || {};
	const i18n = data.i18n || {};

	// =========================================================
	// Tab switching
	// =========================================================
	function initTabs() {
		const tabs   = document.querySelectorAll( '.wps-dashboard__tab' );
		const panels = document.querySelectorAll( '.wps-dashboard__panel' );

		if ( ! tabs.length ) {
			return;
		}

		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				const target = tab.dataset.tab;

				tabs.forEach( function ( t ) {
					t.classList.remove( 'wps-dashboard__tab--active' );
					t.setAttribute( 'aria-selected', 'false' );
				} );
				panels.forEach( function ( p ) {
					p.classList.remove( 'wps-dashboard__panel--active' );
				} );

				tab.classList.add( 'wps-dashboard__tab--active' );
				tab.setAttribute( 'aria-selected', 'true' );

				const panel = document.getElementById( 'wps-panel-' + target );
				if ( panel ) {
					panel.classList.add( 'wps-dashboard__panel--active' );
				}
			} );
		} );
	}

	// =========================================================
	// Generate snapshot
	// =========================================================
	function initGenerateBtn() {
		const btn     = document.getElementById( 'wps-generate-btn' );
		const spinner = document.getElementById( 'wps-spinner' );
		const errorEl = document.getElementById( 'wps-error' );

		if ( ! btn ) {
			return;
		}

		btn.addEventListener( 'click', async function () {
			btn.disabled    = true;
			btn.textContent = i18n.generating || 'Generating\u2026';

			if ( spinner ) {
				spinner.style.display = 'flex';
			}
			if ( errorEl ) {
				errorEl.style.display = 'none';
			}

			try {
				const response = await fetch( data.restUrl + 'generate', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce':   data.nonce,
					},
				} );

				if ( ! response.ok ) {
					const body = await response.json().catch( () => null );
					throw new Error( ( body && body.message ) || response.statusText );
				}

				// Reload so the PHP template re-renders with the new snapshot.
				window.location.reload();
			} catch ( err ) {
				console.error( 'WP Snapshot generate error:', err );

				if ( errorEl ) {
					const p     = errorEl.querySelector( 'p' );
					const msg   = ( i18n.error || 'An error occurred.' ) + ' ' + err.message;
					p.textContent = msg;
					errorEl.style.display = 'block';
				}

				btn.disabled    = false;
				btn.textContent = i18n.generate || 'Generate Snapshot';

				if ( spinner ) {
					spinner.style.display = 'none';
				}
			}
		} );
	}

	// =========================================================
	// JSON export
	// =========================================================
	function initJsonExport() {
		const btn = document.getElementById( 'wps-json-btn' );
		if ( ! btn ) {
			return;
		}

		btn.addEventListener( 'click', async function () {
			btn.disabled = true;

			try {
				const response = await fetch( data.restUrl + 'snapshot', {
					headers: { 'X-WP-Nonce': data.nonce },
				} );

				if ( ! response.ok ) {
					throw new Error( response.statusText );
				}

				const snapshot = await response.json();
				const blob     = new Blob(
					[ JSON.stringify( snapshot, null, 2 ) ],
					{ type: 'application/json' }
				);

				const url  = URL.createObjectURL( blob );
				const link = document.createElement( 'a' );
				link.href     = url;
				link.download = 'wp-snapshot-' + new Date().toISOString().slice( 0, 10 ) + '.json';
				document.body.appendChild( link );
				link.click();
				document.body.removeChild( link );
				URL.revokeObjectURL( url );
			} catch ( err ) {
				console.error( 'WP Snapshot JSON export error:', err );
				// eslint-disable-next-line no-alert
				window.alert( ( i18n.error || 'An error occurred.' ) + ' ' + err.message );
			} finally {
				btn.disabled = false;
			}
		} );
	}

	// =========================================================
	// Share link panel
	// =========================================================
	function initShareBtn() {
		const btn         = document.getElementById( 'wps-share-btn' );
		const panel       = document.getElementById( 'wps-share-panel' );
		const newLinkArea = document.getElementById( 'wps-new-link-area' );

		if ( ! btn || ! panel ) {
			return;
		}

		// Toggle panel visibility.
		btn.addEventListener( 'click', function () {
			const isHidden = panel.style.display === 'none' || panel.style.display === '';
			panel.style.display = isHidden ? 'block' : 'none';
		} );

		// Inject a "Create New Link" button into the panel.
		if ( newLinkArea ) {
			const createBtn       = document.createElement( 'button' );
			createBtn.id          = 'wps-create-share-btn';
			createBtn.className   = 'wps-dashboard__btn wps-dashboard__btn--primary';
			createBtn.style.marginBottom = '12px';
			createBtn.textContent = 'Create New Link (72 h)';
			panel.insertBefore( createBtn, newLinkArea );

			createBtn.addEventListener( 'click', async function () {
				createBtn.disabled    = true;
				createBtn.textContent = i18n.creatingLink || 'Creating link\u2026';

				try {
					const response = await fetch( data.shareUrl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce':   data.nonce,
						},
						body: JSON.stringify( { expires_hours: 72 } ),
					} );

					if ( ! response.ok ) {
						const body = await response.json().catch( () => null );
						throw new Error( ( body && body.message ) || response.statusText );
					}

					const result = await response.json();
					renderShareLink( result, newLinkArea );
				} catch ( err ) {
					console.error( 'WP Snapshot share error:', err );
					// eslint-disable-next-line no-alert
					window.alert( ( i18n.error || 'An error occurred.' ) + ' ' + err.message );
				} finally {
					createBtn.disabled    = false;
					createBtn.textContent = 'Create New Link (72 h)';
				}
			} );
		}
	}

	/**
	 * Build and prepend a newly created share link row using safe DOM methods.
	 * No innerHTML — all values assigned via textContent or setAttribute.
	 *
	 * @param {Object}  result    REST API response from POST /share.
	 * @param {Element} container Element to prepend the row into.
	 */
	function renderShareLink( result, container ) {
		if ( ! container ) {
			return;
		}

		// Outer row.
		const row       = document.createElement( 'div' );
		row.className   = 'wps-share-url-row';

		// URL display.
		const code      = document.createElement( 'code' );
		code.textContent = result.share_url;

		// Copy button.
		const copyBtn       = document.createElement( 'button' );
		copyBtn.className   = 'wps-copy-btn wps-dashboard__btn wps-dashboard__btn--secondary';
		copyBtn.setAttribute( 'data-url', result.share_url );
		copyBtn.textContent = i18n.copy || 'Copy Link';

		// Expiry label.
		const expiry       = document.createElement( 'small' );
		expiry.style.color = '#646970';
		expiry.textContent = ( i18n.shareExpiry || 'Expires' ) + ': ' + result.expires_at + ' (' + result.expires_in + ')';

		row.appendChild( code );
		row.appendChild( copyBtn );
		row.appendChild( expiry );

		container.prepend( row );
	}

	// =========================================================
	// Copy link — delegated handler on document
	// =========================================================
	function initCopyButtons() {
		document.addEventListener( 'click', function ( e ) {
			if ( ! e.target.matches( '.wps-copy-btn' ) ) {
				return;
			}

			const btn = e.target;
			const url = btn.getAttribute( 'data-url' );

			if ( ! url ) {
				return;
			}

			const original = btn.textContent;

			navigator.clipboard.writeText( url ).then( function () {
				btn.textContent = i18n.copying || 'Copied!';
				btn.disabled    = true;
				setTimeout( function () {
					btn.textContent = original;
					btn.disabled    = false;
				}, 2000 );
			} ).catch( function () {
				// Fallback for environments without clipboard API.
				try {
					const el          = document.createElement( 'textarea' );
					el.value          = url;
					el.style.position = 'fixed';
					el.style.opacity  = '0';
					el.style.top      = '-9999px';
					document.body.appendChild( el );
					el.select();
					document.execCommand( 'copy' ); // eslint-disable-line no-undef
					document.body.removeChild( el );
					btn.textContent = i18n.copying || 'Copied!';
					setTimeout( function () {
						btn.textContent = original;
					}, 2000 );
				} catch ( fallbackErr ) {
					console.error( 'Copy fallback failed:', fallbackErr );
				}
			} );
		} );
	}

	// =========================================================
	// Revoke share link — delegated handler on document
	// =========================================================
	function initRevokeButtons() {
		document.addEventListener( 'click', async function ( e ) {
			if ( ! e.target.matches( '.wps-revoke-btn' ) ) {
				return;
			}

			const btn   = e.target;
			const token = btn.getAttribute( 'data-token' );

			// eslint-disable-next-line no-alert
			if ( ! window.confirm( i18n.revokeConfirm || 'Revoke this share link?' ) ) {
				return;
			}

			btn.disabled = true;

			try {
				const response = await fetch( data.restUrl + 'share/' + encodeURIComponent( token ), {
					method: 'DELETE',
					headers: { 'X-WP-Nonce': data.nonce },
				} );

				if ( ! response.ok ) {
					const body = await response.json().catch( () => null );
					throw new Error( ( body && body.message ) || response.statusText );
				}

				const row = btn.closest( 'tr' );
				if ( row ) {
					row.remove();
				}
			} catch ( err ) {
				console.error( 'WP Snapshot revoke error:', err );
				// eslint-disable-next-line no-alert
				window.alert( ( i18n.error || 'An error occurred.' ) + ' ' + err.message );
				btn.disabled = false;
			}
		} );
	}

	// =========================================================
	// Bootstrap — wait for DOM ready
	// =========================================================
	document.addEventListener( 'DOMContentLoaded', function () {
		initTabs();
		initGenerateBtn();
		initJsonExport();
		initShareBtn();
		initCopyButtons();
		initRevokeButtons();
	} );
}() );
