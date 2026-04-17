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
	// Markdown export
	// =========================================================

	/**
	 * Convert a snapshot object into a Markdown string.
	 * All sections are handled gracefully — missing data renders as '–'.
	 *
	 * @param  {Object} s  Full snapshot from GET /snapshot.
	 * @return {string}    Formatted Markdown document.
	 */
	function snapshotToMarkdown( s ) {
		const lines = [];

		/** Helper: safely get a section's data object. */
		function sec( key ) {
			return ( s.sections && s.sections[ key ] && s.sections[ key ].data )
				? s.sections[ key ].data
				: {};
		}

		/** Helper: format a status string into an emoji prefix. */
		function statusIcon( status ) {
			if ( status === 'good' )     { return '✅'; }
			if ( status === 'warning' )  { return '⚠️'; }
			if ( status === 'critical' ) { return '❌'; }
			return 'ℹ️';
		}

		// ── Header ───────────────────────────────────────────
		lines.push( '# Site Audit Snapshot — ' + ( s.site_name || '' ) );
		lines.push( '' );
		lines.push( '| | |' );
		lines.push( '|---|---|' );
		lines.push( '| **Site URL** | ' + ( s.site_url || '–' ) + ' |' );
		lines.push( '| **Generated** | ' + ( s.generated_at || '–' ) + ' |' );
		lines.push( '| **Generated by** | ' + ( s.generated_by || '–' ) + ' |' );
		lines.push( '| **Plugin version** | ' + ( s.generator_version || '–' ) + ' |' );
		lines.push( '' );
		lines.push( '---' );
		lines.push( '' );

		// ── Environment ──────────────────────────────────────
		const env = sec( 'environment' );
		lines.push( '## Environment' );
		lines.push( '' );
		lines.push( '| Key | Value |' );
		lines.push( '|---|---|' );
		lines.push( '| WordPress | ' + ( env.wp_version || '–' ) + ' |' );
		lines.push( '| PHP | ' + ( env.php_version || '–' ) + ' |' );
		lines.push( '| Database | ' + ( ( env.db_type || '' ) + ' ' + ( env.db_version || '' ) ).trim() + ' |' );
		lines.push( '| Server | ' + ( env.server_software || '–' ) + ' |' );
		lines.push( '| OS | ' + ( env.server_os || '–' ) + ' |' );
		lines.push( '| Site URL | ' + ( env.site_url || '–' ) + ' |' );
		lines.push( '| Home URL | ' + ( env.home_url || '–' ) + ' |' );
		lines.push( '| Timezone | ' + ( env.wp_timezone || '–' ) + ' |' );
		lines.push( '| Locale | ' + ( env.wp_locale || '–' ) + ' |' );
		lines.push( '| PHP Memory Limit | ' + ( env.php_memory_limit || '–' ) + ' |' );
		lines.push( '| Max Upload | ' + ( env.php_max_upload || '–' ) + ' |' );
		lines.push( '| HTTPS | ' + ( env.is_https ? 'Yes' : 'No' ) + ' |' );
		lines.push( '| Multisite | ' + ( env.is_multisite ? 'Yes' : 'No' ) + ' |' );
		lines.push( '| WP_DEBUG | ' + ( env.wp_debug ? 'On' : 'Off' ) + ' |' );
		lines.push( '' );

		// ── Plugins ──────────────────────────────────────────
		const plug = sec( 'plugins' );
		lines.push( '## Plugins' );
		lines.push( '' );
		lines.push(
			'**Active:** ' + ( plug.active_count || 0 ) +
			' / **Total:** ' + ( plug.total_plugins || 0 ) +
			' / **Updates available:** ' + ( plug.update_available || 0 )
		);
		lines.push( '' );
		if ( plug.plugins && plug.plugins.length ) {
			lines.push( '| Plugin | Version | Status | Update |' );
			lines.push( '|---|---|---|---|' );
			plug.plugins.forEach( function ( p ) {
				lines.push(
					'| ' + ( p.name || p.file || '–' ) +
					' | ' + ( p.version || '–' ) +
					' | ' + ( p.is_active ? 'Active' : 'Inactive' ) +
					' | ' + ( p.has_update ? '⚠️ ' + ( p.update_version || 'Update available' ) : '✅ Up to date' ) +
					' |'
				);
			} );
			lines.push( '' );
		}

		// ── Themes ───────────────────────────────────────────
		const themes = sec( 'themes' );
		const at     = themes.active_theme || {};
		lines.push( '## Active Theme' );
		lines.push( '' );
		lines.push( '| Key | Value |' );
		lines.push( '|---|---|' );
		lines.push( '| Name | ' + ( at.name || '–' ) + ' |' );
		lines.push( '| Version | ' + ( at.version || '–' ) + ' |' );
		lines.push( '| Author | ' + ( at.author || '–' ) + ' |' );
		lines.push( '| Type | ' + ( at.is_block_theme ? 'Block Theme (FSE)' : 'Classic Theme' ) + ' |' );
		lines.push( '| Child Theme | ' + ( at.is_child_theme ? ( at.parent_theme || 'Yes' ) : 'No' ) + ' |' );
		lines.push( '' );

		// ── Database ─────────────────────────────────────────
		const db = sec( 'database' );
		lines.push( '## Database' );
		lines.push( '' );
		lines.push( '| Key | Value |' );
		lines.push( '|---|---|' );
		lines.push( '| Total Size | ' + ( db.total_db_size_human || '–' ) + ' |' );
		lines.push( '| Tables | ' + ( db.total_tables || 0 ) + ' |' );
		lines.push( '| Autoloaded Data | ' + ( db.autoload_size_human || '–' ) + ' |' );
		lines.push( '| Revisions | ' + ( db.revisions_count || 0 ) + ' |' );
		lines.push( '| Orphaned Postmeta | ' + ( db.orphaned_postmeta || 0 ) + ' |' );
		lines.push( '| Trashed Posts | ' + ( db.trashed_count || 0 ) + ' |' );
		lines.push( '| Table Prefix | `' + ( db.db_prefix || '–' ) + '` |' );
		lines.push( '| Charset | ' + ( db.db_charset || '–' ) + ' |' );
		lines.push( '' );

		// ── Security ─────────────────────────────────────────
		const secu = sec( 'security' );
		lines.push( '## Security' );
		lines.push( '' );
		if ( secu.checks && secu.checks.length ) {
			lines.push( '| Check | Status | Note |' );
			lines.push( '|---|---|---|' );
			secu.checks.forEach( function ( c ) {
				lines.push(
					'| ' + ( c.label || '' ) +
					' | ' + statusIcon( c.status ) + ' ' + ( c.status || '' ) +
					' | ' + ( c.note || '' ) +
					' |'
				);
			} );
			lines.push( '' );
		}

		// ── Performance ──────────────────────────────────────
		const perf = sec( 'performance' );
		lines.push( '## Performance' );
		lines.push( '' );
		lines.push( '| Key | Value |' );
		lines.push( '|---|---|' );
		lines.push( '| Object Cache | ' + ( perf.object_cache_active ? ( perf.object_cache_type || 'Active' ) : 'None' ) + ' |' );
		lines.push( '| OPcache | ' + ( perf.opcache_enabled ? 'Enabled' : 'Disabled' ) + ' |' );
		lines.push( '| Page Cache | ' + ( perf.page_cache_likely ? 'Likely active' : 'Not detected' ) + ' |' );
		lines.push( '| Image Editor | ' + ( perf.image_editor || '–' ) + ' |' );
		lines.push( '| Permalink Structure | `' + ( perf.permalink_structure || '–' ) + '` |' );
		lines.push( '' );

		// ── Cron Jobs ────────────────────────────────────────
		const cron = sec( 'cron' );
		lines.push( '## Cron Jobs' );
		lines.push( '' );
		lines.push(
			'**Total events:** ' + ( cron.total_events || 0 ) +
			' / **Overdue:** ' + ( cron.overdue_count || 0 )
		);
		lines.push( '' );
		if ( cron.events && cron.events.length ) {
			lines.push( '| Hook | Next Run | Schedule | Overdue |' );
			lines.push( '|---|---|---|---|' );
			cron.events.forEach( function ( e ) {
				lines.push(
					'| `' + ( e.hook || '' ) + '`' +
					' | ' + ( e.next_run || '–' ) +
					' | ' + ( e.schedule || 'One-time' ) +
					' | ' + ( e.is_overdue ? '⚠️ Yes' : 'No' ) +
					' |'
				);
			} );
			lines.push( '' );
		}

		// ── Users & Roles ────────────────────────────────────
		const users = sec( 'users' );
		lines.push( '## Users & Roles' );
		lines.push( '' );
		lines.push( '**Total users:** ' + ( users.total_users || 0 ) );
		lines.push( '' );
		if ( users.roles && users.roles.length ) {
			lines.push( '| Role | Slug | Users |' );
			lines.push( '|---|---|---|' );
			users.roles.forEach( function ( r ) {
				lines.push(
					'| ' + ( r.name || '' ) +
					' | `' + ( r.slug || '' ) + '`' +
					' | ' + ( r.user_count || 0 ) +
					' |'
				);
			} );
			lines.push( '' );
		}

		// ── Media Library ────────────────────────────────────
		const media = sec( 'media' );
		lines.push( '## Media Library' );
		lines.push( '' );
		lines.push( '| Key | Value |' );
		lines.push( '|---|---|' );
		lines.push( '| Total Attachments | ' + ( media.total_attachments || 0 ) + ' |' );
		lines.push( '| Upload Directory Size | ' + ( media.upload_dir_size_human || '–' ) + ' |' );
		if ( media.mime_summary ) {
			lines.push( '| Images | ' + ( media.mime_summary.images || 0 ) + ' |' );
			lines.push( '| Videos | ' + ( media.mime_summary.videos || 0 ) + ' |' );
			lines.push( '| Documents | ' + ( media.mime_summary.documents || 0 ) + ' |' );
			lines.push( '| Audio | ' + ( media.mime_summary.audio || 0 ) + ' |' );
			lines.push( '| Other | ' + ( media.mime_summary.other || 0 ) + ' |' );
		}
		lines.push( '' );

		// ── Post Types ───────────────────────────────────────
		const pts = sec( 'post_types' );
		lines.push( '## Post Types' );
		lines.push( '' );
		if ( pts.post_types && pts.post_types.length ) {
			lines.push( '| Slug | Label | Published |' );
			lines.push( '|---|---|---|' );
			pts.post_types.forEach( function ( pt ) {
				lines.push( '| `' + ( pt.slug || '' ) + '` | ' + ( pt.label || '' ) + ' | ' + ( pt.count || 0 ) + ' |' );
			} );
			lines.push( '' );
		}

		// ── Taxonomies ───────────────────────────────────────
		const taxs = sec( 'taxonomies' );
		lines.push( '## Taxonomies' );
		lines.push( '' );
		if ( taxs.taxonomies && taxs.taxonomies.length ) {
			lines.push( '| Slug | Label | Terms |' );
			lines.push( '|---|---|---|' );
			taxs.taxonomies.forEach( function ( t ) {
				lines.push( '| `' + ( t.slug || '' ) + '` | ' + ( t.label || '' ) + ' | ' + ( t.term_count || 0 ) + ' |' );
			} );
			lines.push( '' );
		}

		// ── REST API ─────────────────────────────────────────
		const restApi = sec( 'rest_api' );
		lines.push( '## REST API Namespaces' );
		lines.push( '' );
		if ( restApi.namespaces && restApi.namespaces.length ) {
			restApi.namespaces.forEach( function ( ns ) {
				lines.push( '- `' + ns + '`' );
			} );
			lines.push( '' );
		}

		// ── Footer ───────────────────────────────────────────
		lines.push( '---' );
		lines.push( '' );
		lines.push(
			'*Generated by [Site Audit Snapshot](https://wordpress.org/plugins/site-audit-snapshot/) v' +
			( s.generator_version || '' ) + '*'
		);

		return lines.join( '\n' );
	}

	function initMdExport() {
		const btn = document.getElementById( 'wps-md-btn' );
		if ( ! btn ) {
			return;
		}

		btn.addEventListener( 'click', async function () {
			btn.disabled    = true;
			btn.textContent = i18n.exportMd ? i18n.exportMd + '\u2026' : 'Exporting\u2026';

			try {
				const response = await fetch( data.restUrl + 'snapshot', {
					headers: { 'X-WP-Nonce': data.nonce },
				} );

				if ( ! response.ok ) {
					throw new Error( response.statusText );
				}

				const snapshot = await response.json();
				const md       = snapshotToMarkdown( snapshot );
				const blob     = new Blob( [ md ], { type: 'text/markdown' } );

				const url  = URL.createObjectURL( blob );
				const link = document.createElement( 'a' );
				link.href     = url;
				link.download = 'site-audit-snapshot-' + new Date().toISOString().slice( 0, 10 ) + '.md';
				document.body.appendChild( link );
				link.click();
				document.body.removeChild( link );
				URL.revokeObjectURL( url );
			} catch ( err ) {
				console.error( 'Site Audit Snapshot MD export error:', err );
				// eslint-disable-next-line no-alert
				window.alert( ( i18n.error || 'An error occurred.' ) + ' ' + err.message );
			} finally {
				btn.disabled    = false;
				btn.textContent = i18n.exportMd || 'Export Markdown';
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
		initMdExport();
		initShareBtn();
		initCopyButtons();
		initRevokeButtons();
	} );
}() );
