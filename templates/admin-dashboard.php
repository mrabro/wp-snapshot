<?php
/**
 * Admin Dashboard Template
 *
 * Variables available from Admin_Page::render_page():
 * @var array|null  $snapshot      Last generated snapshot, or null.
 * @var array|false $last_meta     wp_options metadata for last snapshot.
 * @var array[]     $active_shares Active share link summaries.
 *
 * @package WPSnapshot
 */

defined( 'ABSPATH' ) || exit;

$has_snapshot = ! empty( $snapshot );
$sections     = $has_snapshot ? ( $snapshot['sections'] ?? [] ) : [];

// Helper: safely get nested section data.
$sec = fn( string $key ): array => $sections[ $key ]['data'] ?? [];
?>
<div class="wrap wps-dashboard">

	<h1 class="wps-dashboard__title">
		<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M9 9h6M9 12h6M9 15h4"/></svg>
		<?php esc_html_e( 'Site Audit Snapshot', 'site-audit-snapshot' ); ?>
	</h1>

	<?php if ( $has_snapshot ) : ?>
		<p class="wps-dashboard__meta">
			<?php
			printf(
				/* translators: 1: site name, 2: date/time */
				esc_html__( 'Last snapshot for %1$s — generated %2$s', 'site-audit-snapshot' ),
				'<strong>' . esc_html( $snapshot['site_name'] ) . '</strong>',
				'<strong>' . esc_html( $snapshot['generated_at'] ) . '</strong>'
			);
			?>
		</p>
	<?php endif; ?>

	<!-- Action bar -->
	<div class="wps-dashboard__actions">
		<button id="wps-generate-btn" class="wps-dashboard__btn wps-dashboard__btn--primary">
			<?php esc_html_e( 'Generate Snapshot', 'site-audit-snapshot' ); ?>
		</button>

		<?php if ( $has_snapshot ) : ?>
			<a id="wps-pdf-btn" href="<?php echo esc_url( rest_url( 'site-audit-snapshot/v1/pdf' ) . '?_wpnonce=' . wp_create_nonce( 'wp_rest' ) ); ?>" target="_blank" class="wps-dashboard__btn wps-dashboard__btn--secondary">
				<?php esc_html_e( 'Print / Export PDF', 'site-audit-snapshot' ); ?>
			</a>
			<button id="wps-json-btn" class="wps-dashboard__btn wps-dashboard__btn--secondary">
				<?php esc_html_e( 'Export JSON', 'site-audit-snapshot' ); ?>
			</button>
			<button id="wps-md-btn" class="wps-dashboard__btn wps-dashboard__btn--secondary">
				<?php esc_html_e( 'Export Markdown', 'site-audit-snapshot' ); ?>
			</button>
			<button id="wps-share-btn" class="wps-dashboard__btn wps-dashboard__btn--secondary">
				<?php esc_html_e( 'Create Share Link', 'site-audit-snapshot' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<!-- Share links panel -->
	<?php if ( $has_snapshot ) : ?>
	<div id="wps-share-panel" class="wps-dashboard__share-panel" style="display:none;">
		<h3><?php esc_html_e( 'Share Links', 'site-audit-snapshot' ); ?></h3>
		<div id="wps-new-link-area"></div>
		<?php if ( ! empty( $active_shares ) ) : ?>
			<table class="wps-dashboard__table" style="margin-top:12px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Share URL', 'site-audit-snapshot' ); ?></th>
						<th><?php esc_html_e( 'Created', 'site-audit-snapshot' ); ?></th>
						<th><?php esc_html_e( 'Expires', 'site-audit-snapshot' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'site-audit-snapshot' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $active_shares as $share ) : ?>
					<tr data-token="<?php echo esc_attr( $share['token'] ); ?>">
						<td><a href="<?php echo esc_url( $share['url'] ); ?>" target="_blank"><?php echo esc_html( $share['url'] ); ?></a></td>
						<td><?php echo esc_html( $share['created'] ); ?></td>
						<td><?php echo esc_html( $share['expires_at'] ); ?> (<?php echo esc_html( $share['expires_in'] ); ?>)</td>
						<td>
							<button class="wps-revoke-btn button-link-delete" data-token="<?php echo esc_attr( $share['token'] ); ?>"><?php esc_html_e( 'Revoke', 'site-audit-snapshot' ); ?></button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<div id="wps-spinner" class="wps-dashboard__spinner" style="display:none;">
		<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>
		<?php esc_html_e( 'Generating snapshot, please wait…', 'site-audit-snapshot' ); ?>
	</div>
	<div id="wps-error" class="notice notice-error" style="display:none;"><p></p></div>

	<?php if ( ! $has_snapshot ) : ?>
		<div class="wps-dashboard__empty">
			<p><?php esc_html_e( 'No snapshot generated yet. Click "Generate Snapshot" to collect site information.', 'site-audit-snapshot' ); ?></p>
		</div>
	<?php else : ?>

	<!-- Tab Navigation -->
	<nav class="wps-dashboard__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Snapshot Sections', 'site-audit-snapshot' ); ?>">
		<?php
		$tabs = [
			'overview'   => __( 'Overview', 'site-audit-snapshot' ),
			'plugins'    => __( 'Plugins', 'site-audit-snapshot' ),
			'themes'     => __( 'Themes', 'site-audit-snapshot' ),
			'database'   => __( 'Database', 'site-audit-snapshot' ),
			'cron'       => __( 'Cron', 'site-audit-snapshot' ),
			'post_types' => __( 'Post Types', 'site-audit-snapshot' ),
			'users'      => __( 'Users', 'site-audit-snapshot' ),
			'rest_api'   => __( 'REST API', 'site-audit-snapshot' ),
			'security'   => __( 'Security', 'site-audit-snapshot' ),
			'media'      => __( 'Media', 'site-audit-snapshot' ),
			'performance' => __( 'Performance', 'site-audit-snapshot' ),
		];
		foreach ( $tabs as $tab_id => $tab_label ) :
			$is_active = $tab_id === 'overview';
			?>
			<button
				class="wps-dashboard__tab<?php echo $is_active ? ' wps-dashboard__tab--active' : ''; ?>"
				role="tab"
				aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
				aria-controls="wps-panel-<?php echo esc_attr( $tab_id ); ?>"
				data-tab="<?php echo esc_attr( $tab_id ); ?>"
			><?php echo esc_html( $tab_label ); ?></button>
		<?php endforeach; ?>
	</nav>

	<!-- ======================================================= -->
	<!-- OVERVIEW PANEL -->
	<!-- ======================================================= -->
	<div id="wps-panel-overview" class="wps-dashboard__panel wps-dashboard__panel--active" role="tabpanel">
		<?php
		$env  = $sec( 'environment' );
		$plug = $sec( 'plugins' );
		$sec_data = $sec( 'security' );
		?>
		<div class="wps-dashboard__cards">
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'WordPress', 'site-audit-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value"><?php echo esc_html( $env['wp_version'] ?? '–' ); ?></div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'PHP', 'site-audit-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value"><?php echo esc_html( $env['php_version'] ?? '–' ); ?></div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'Active Plugins', 'site-audit-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value"><?php echo esc_html( $plug['active_count'] ?? '–' ); ?></div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'Plugin Updates', 'site-audit-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value<?php echo ! empty( $plug['update_available'] ) ? ' wps-dashboard__card-value--warning' : ''; ?>">
					<?php echo esc_html( $plug['update_available'] ?? '0' ); ?>
				</div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'DB Size', 'site-audit-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value"><?php echo esc_html( $sec( 'database' )['total_db_size_human'] ?? '–' ); ?></div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'Active Theme', 'site-audit-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value"><?php echo esc_html( $sec( 'themes' )['active_theme']['name'] ?? '–' ); ?></div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'HTTPS', 'site-audit-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value">
					<?php
					$is_https = ! empty( $env['is_https'] );
					echo $is_https
						? '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Yes', 'site-audit-snapshot' ) . '</span>'
						: '<span class="wps-dashboard__badge wps-dashboard__badge--critical">' . esc_html__( 'No', 'site-audit-snapshot' ) . '</span>';
					?>
				</div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'Security Issues', 'site-audit-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value">
					<?php
					$critical = (int) ( $sec_data['critical_count'] ?? 0 );
					$warning  = (int) ( $sec_data['warning_count'] ?? 0 );
					if ( $critical > 0 ) {
						echo '<span class="wps-dashboard__badge wps-dashboard__badge--critical">' . esc_html( $critical ) . ' ' . esc_html__( 'critical', 'site-audit-snapshot' ) . '</span> ';
					}
					if ( $warning > 0 ) {
						echo '<span class="wps-dashboard__badge wps-dashboard__badge--warning">' . esc_html( $warning ) . ' ' . esc_html__( 'warning', 'site-audit-snapshot' ) . '</span>';
					}
					if ( $critical === 0 && $warning === 0 ) {
						echo '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'All good', 'site-audit-snapshot' ) . '</span>';
					}
					?>
				</div>
			</div>
		</div>

		<!-- Environment quick-view -->
		<h3><?php esc_html_e( 'Environment Details', 'site-audit-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<tbody>
				<?php
				$env_rows = [
					__( 'Site URL', 'site-audit-snapshot' )            => $env['site_url'] ?? '',
					__( 'Home URL', 'site-audit-snapshot' )            => $env['home_url'] ?? '',
					__( 'Timezone', 'site-audit-snapshot' )            => $env['wp_timezone'] ?? '',
					__( 'Locale', 'site-audit-snapshot' )              => $env['wp_locale'] ?? '',
					__( 'DB Type', 'site-audit-snapshot' )             => ( $env['db_type'] ?? '' ) . ' ' . ( $env['db_version'] ?? '' ),
					__( 'Server', 'site-audit-snapshot' )              => $env['server_software'] ?? '',
					__( 'OS', 'site-audit-snapshot' )                  => $env['server_os'] ?? '',
					__( 'PHP Memory Limit', 'site-audit-snapshot' )    => $env['php_memory_limit'] ?? '',
					__( 'WP Memory Limit', 'site-audit-snapshot' )     => $env['wp_memory_limit'] ?? '',
					__( 'Max Upload Size', 'site-audit-snapshot' )     => $env['php_max_upload'] ?? '',
					__( 'Max Execution Time', 'site-audit-snapshot' )  => ( $env['php_max_execution'] ?? '' ) . 's',
					__( 'Multisite', 'site-audit-snapshot' )           => ! empty( $env['is_multisite'] ) ? __( 'Yes', 'site-audit-snapshot' ) : __( 'No', 'site-audit-snapshot' ),
					__( 'DISALLOW_FILE_EDIT', 'site-audit-snapshot' )  => ! empty( $env['disallow_file_edit'] ) ? __( 'Yes', 'site-audit-snapshot' ) : __( 'No', 'site-audit-snapshot' ),
					__( 'WP_DEBUG', 'site-audit-snapshot' )            => ! empty( $env['wp_debug'] ) ? __( 'Enabled', 'site-audit-snapshot' ) : __( 'Disabled', 'site-audit-snapshot' ),
				];
				foreach ( $env_rows as $label => $value ) :
					?>
					<tr>
						<th><?php echo esc_html( $label ); ?></th>
						<td><?php echo esc_html( $value ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( ! empty( $env['php_extensions'] ) ) : ?>
			<h3><?php esc_html_e( 'PHP Extensions', 'site-audit-snapshot' ); ?></h3>
			<div class="wps-dashboard__extension-grid">
				<?php foreach ( $env['php_extensions'] as $ext => $loaded ) : ?>
					<span class="wps-dashboard__badge wps-dashboard__badge--<?php echo $loaded ? 'good' : 'info'; ?>">
						<?php echo esc_html( $ext ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- ======================================================= -->
	<!-- PLUGINS PANEL -->
	<!-- ======================================================= -->
	<div id="wps-panel-plugins" class="wps-dashboard__panel" role="tabpanel">
		<?php $plug = $sec( 'plugins' ); ?>
		<div class="wps-dashboard__cards">
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $plug['total_plugins'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Active', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $plug['active_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Inactive', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $plug['inactive_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Updates Available', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ! empty( $plug['update_available'] ) ? ' wps-dashboard__card-value--warning' : ''; ?>"><?php echo esc_html( $plug['update_available'] ?? 0 ); ?></div></div>
		</div>

		<?php if ( ! empty( $plug['plugins'] ) ) : ?>
		<table class="wps-dashboard__table wps-dashboard__table--sortable">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Plugin', 'site-audit-snapshot' ); ?></th>
					<th><?php esc_html_e( 'Version', 'site-audit-snapshot' ); ?></th>
					<th><?php esc_html_e( 'Author', 'site-audit-snapshot' ); ?></th>
					<th><?php esc_html_e( 'Status', 'site-audit-snapshot' ); ?></th>
					<th><?php esc_html_e( 'Update', 'site-audit-snapshot' ); ?></th>
					<th><?php esc_html_e( 'Auto-update', 'site-audit-snapshot' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $plug['plugins'] as $plugin ) : ?>
				<tr>
					<td>
						<strong><?php echo esc_html( $plugin['name'] ); ?></strong>
						<?php if ( $plugin['uri'] ) : ?><br><a href="<?php echo esc_url( $plugin['uri'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $plugin['file'] ); ?></a><?php endif; ?>
					</td>
					<td><?php echo esc_html( $plugin['version'] ); ?></td>
					<td><?php echo esc_html( $plugin['author'] ); ?></td>
					<td>
						<?php if ( $plugin['is_active'] ) : ?>
							<span class="wps-dashboard__badge wps-dashboard__badge--good"><?php esc_html_e( 'Active', 'site-audit-snapshot' ); ?></span>
						<?php else : ?>
							<span class="wps-dashboard__badge wps-dashboard__badge--info"><?php esc_html_e( 'Inactive', 'site-audit-snapshot' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $plugin['has_update'] ) : ?>
							<span class="wps-dashboard__badge wps-dashboard__badge--warning">
								<?php
								printf(
									/* translators: %s: new version number */
									esc_html__( 'v%s available', 'site-audit-snapshot' ),
									esc_html( $plugin['update_version'] )
								);
								?>
							</span>
						<?php else : ?>
							<span class="wps-dashboard__badge wps-dashboard__badge--good"><?php esc_html_e( 'Up to date', 'site-audit-snapshot' ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo $plugin['auto_update'] ? esc_html__( 'On', 'site-audit-snapshot' ) : esc_html__( 'Off', 'site-audit-snapshot' ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<?php if ( ! empty( $plug['mu_plugins'] ) ) : ?>
		<h3><?php esc_html_e( 'Must-Use Plugins', 'site-audit-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'File', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Name', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Version', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Author', 'site-audit-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $plug['mu_plugins'] as $mu ) : ?>
					<tr><td><?php echo esc_html( $mu['file'] ); ?></td><td><?php echo esc_html( $mu['name'] ); ?></td><td><?php echo esc_html( $mu['version'] ); ?></td><td><?php echo esc_html( $mu['author'] ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<?php if ( ! empty( $plug['dropins'] ) ) : ?>
		<h3><?php esc_html_e( 'Drop-ins', 'site-audit-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'File', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Name', 'site-audit-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $plug['dropins'] as $di ) : ?>
					<tr><td><?php echo esc_html( $di['file'] ); ?></td><td><?php echo esc_html( $di['name'] ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

	<!-- ======================================================= -->
	<!-- THEMES PANEL -->
	<!-- ======================================================= -->
	<div id="wps-panel-themes" class="wps-dashboard__panel" role="tabpanel">
		<?php $th = $sec( 'themes' ); $active = $th['active_theme'] ?? []; ?>
		<?php if ( ! empty( $active ) ) : ?>
		<div class="wps-dashboard__card wps-dashboard__card--wide">
			<h3><?php esc_html_e( 'Active Theme', 'site-audit-snapshot' ); ?></h3>
			<table class="wps-dashboard__table">
				<tbody>
					<tr><th><?php esc_html_e( 'Name', 'site-audit-snapshot' ); ?></th><td><strong><?php echo esc_html( $active['name'] ?? '' ); ?></strong><?php echo ! empty( $active['has_update'] ) ? ' <span class="wps-dashboard__badge wps-dashboard__badge--warning">' . esc_html__( 'Update available', 'site-audit-snapshot' ) . '</span>' : ''; ?></td></tr>
					<tr><th><?php esc_html_e( 'Version', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $active['version'] ?? '' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Author', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $active['author'] ?? '' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Block Theme', 'site-audit-snapshot' ); ?></th><td><?php echo ! empty( $active['is_block_theme'] ) ? esc_html__( 'Yes (FSE)', 'site-audit-snapshot' ) : esc_html__( 'No (classic)', 'site-audit-snapshot' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Child Theme', 'site-audit-snapshot' ); ?></th><td><?php echo ! empty( $active['is_child_theme'] ) ? esc_html( $active['parent_theme'] ?? '' ) . ' (' . esc_html__( 'parent', 'site-audit-snapshot' ) . ')' : esc_html__( 'No', 'site-audit-snapshot' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Requires WP', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $active['requires_wp'] ?? '–' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Requires PHP', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $active['requires_php'] ?? '–' ); ?></td></tr>
				</tbody>
			</table>
		</div>
		<?php endif; ?>

		<h3><?php printf( esc_html__( 'All Installed Themes (%d)', 'site-audit-snapshot' ), (int) ( $th['total_themes'] ?? 0 ) ); ?></h3>
		<?php if ( ! empty( $th['installed'] ) ) : ?>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Theme', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Version', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Author', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Status', 'site-audit-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $th['installed'] as $t ) : ?>
					<tr>
						<td><?php echo esc_html( $t['name'] ); ?> <small style="opacity:.6;"><?php echo esc_html( $t['slug'] ); ?></small></td>
						<td><?php echo esc_html( $t['version'] ); ?></td>
						<td><?php echo esc_html( $t['author'] ); ?></td>
						<td>
							<?php echo $t['is_active'] ? '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Active', 'site-audit-snapshot' ) . '</span>' : ''; ?>
							<?php echo $t['has_update'] ? '<span class="wps-dashboard__badge wps-dashboard__badge--warning">' . esc_html__( 'Update', 'site-audit-snapshot' ) . '</span>' : ''; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

	<!-- ======================================================= -->
	<!-- DATABASE PANEL -->
	<!-- ======================================================= -->
	<div id="wps-panel-database" class="wps-dashboard__panel" role="tabpanel">
		<?php $db = $sec( 'database' ); ?>
		<div class="wps-dashboard__cards">
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'DB Size', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $db['total_db_size_human'] ?? '–' ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Tables', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $db['total_tables'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Autoloaded Data', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ( $db['autoload_size'] ?? 0 ) > 1048576 ? ' wps-dashboard__card-value--warning' : ''; ?>"><?php echo esc_html( $db['autoload_size_human'] ?? '–' ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Revisions', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $db['revisions_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Trashed Posts', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $db['trashed_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Orphaned Postmeta', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ( $db['orphaned_postmeta'] ?? 0 ) > 0 ? ' wps-dashboard__card-value--warning' : ''; ?>"><?php echo esc_html( $db['orphaned_postmeta'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Transients', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $db['transients_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Autoloaded Options', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $db['autoloaded_options'] ?? 0 ); ?> / <?php echo esc_html( $db['total_options'] ?? 0 ); ?></div></div>
		</div>

		<h3><?php esc_html_e( 'Tables', 'site-audit-snapshot' ); ?></h3>
		<?php if ( ! empty( $db['tables'] ) ) : ?>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Table', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Engine', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Rows', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Size', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Collation', 'site-audit-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $db['tables'] as $tbl ) : ?>
					<tr>
						<td><?php echo esc_html( $tbl['name'] ); ?></td>
						<td><?php echo esc_html( $tbl['engine'] ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $tbl['rows'] ) ); ?></td>
						<td><?php echo esc_html( size_format( $tbl['total_size'] ) ); ?></td>
						<td><?php echo esc_html( $tbl['collation'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

	<!-- ======================================================= -->
	<!-- CRON PANEL -->
	<!-- ======================================================= -->
	<div id="wps-panel-cron" class="wps-dashboard__panel" role="tabpanel">
		<?php $cr = $sec( 'cron' ); ?>
		<div class="wps-dashboard__cards">
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total Events', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $cr['total_events'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Overdue', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ( $cr['overdue_count'] ?? 0 ) > 0 ? ' wps-dashboard__card-value--warning' : ''; ?>"><?php echo esc_html( $cr['overdue_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'WP-Cron Disabled', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo ! empty( $cr['wp_cron_disabled'] ) ? esc_html__( 'Yes', 'site-audit-snapshot' ) : esc_html__( 'No', 'site-audit-snapshot' ); ?></div></div>
		</div>
		<?php if ( ! empty( $cr['events'] ) ) : ?>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Hook', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Next Run', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Schedule', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Status', 'site-audit-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $cr['events'] as $event ) : ?>
					<tr>
						<td><code><?php echo esc_html( $event['hook'] ); ?></code></td>
						<td><?php echo esc_html( $event['next_run_human'] ); ?><br><small><?php echo esc_html( $event['next_run_diff'] ); ?></small></td>
						<td><?php echo esc_html( $event['schedule_label'] ); ?></td>
						<td><?php echo $event['overdue'] ? '<span class="wps-dashboard__badge wps-dashboard__badge--warning">' . esc_html__( 'Overdue', 'site-audit-snapshot' ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Scheduled', 'site-audit-snapshot' ) . '</span>'; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

	<!-- ======================================================= -->
	<!-- POST TYPES PANEL -->
	<!-- ======================================================= -->
	<div id="wps-panel-post_types" class="wps-dashboard__panel" role="tabpanel">
		<?php
		$pt  = $sec( 'post_types' );
		$tax = $sec( 'taxonomies' );
		?>
		<div class="wps-dashboard__cards">
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total Post Types', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $pt['total_post_types'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Custom', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $pt['custom_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total Taxonomies', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $tax['total_taxonomies'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Custom Taxonomies', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $tax['custom_count'] ?? 0 ); ?></div></div>
		</div>

		<?php if ( ! empty( $pt['post_types'] ) ) : ?>
		<h3><?php esc_html_e( 'Post Types', 'site-audit-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Slug', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Label', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Type', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Public', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'REST', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Published', 'site-audit-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $pt['post_types'] as $type ) : ?>
					<tr>
						<td><code><?php echo esc_html( $type['slug'] ); ?></code></td>
						<td><?php echo esc_html( $type['label'] ); ?></td>
						<td><?php echo $type['is_builtin'] ? '<span class="wps-dashboard__badge wps-dashboard__badge--info">' . esc_html__( 'Built-in', 'site-audit-snapshot' ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Custom', 'site-audit-snapshot' ) . '</span>'; ?></td>
						<td><?php echo $type['is_public'] ? esc_html__( 'Yes', 'site-audit-snapshot' ) : esc_html__( 'No', 'site-audit-snapshot' ); ?></td>
						<td><?php echo $type['show_in_rest'] ? esc_html__( 'Yes', 'site-audit-snapshot' ) : esc_html__( 'No', 'site-audit-snapshot' ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $type['published'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<?php if ( ! empty( $tax['taxonomies'] ) ) : ?>
		<h3><?php esc_html_e( 'Taxonomies', 'site-audit-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Slug', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Label', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Type', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Hierarchical', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'REST', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Terms', 'site-audit-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $tax['taxonomies'] as $t ) : ?>
					<tr>
						<td><code><?php echo esc_html( $t['slug'] ); ?></code></td>
						<td><?php echo esc_html( $t['label'] ); ?></td>
						<td><?php echo $t['is_builtin'] ? '<span class="wps-dashboard__badge wps-dashboard__badge--info">' . esc_html__( 'Built-in', 'site-audit-snapshot' ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Custom', 'site-audit-snapshot' ) . '</span>'; ?></td>
						<td><?php echo $t['hierarchical'] ? esc_html__( 'Yes', 'site-audit-snapshot' ) : esc_html__( 'No', 'site-audit-snapshot' ); ?></td>
						<td><?php echo $t['show_in_rest'] ? esc_html__( 'Yes', 'site-audit-snapshot' ) : esc_html__( 'No', 'site-audit-snapshot' ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $t['term_count'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

	<!-- ======================================================= -->
	<!-- USERS PANEL -->
	<!-- ======================================================= -->
	<div id="wps-panel-users" class="wps-dashboard__panel" role="tabpanel">
		<?php $usr = $sec( 'users' ); ?>
		<div class="wps-dashboard__cards">
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total Users', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $usr['total_users'] ?? 0 ); ?></div></div>
		</div>
		<?php if ( ! empty( $usr['roles'] ) ) : ?>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Role', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Slug', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Users', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Capabilities', 'site-audit-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $usr['roles'] as $role ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $role['name'] ); ?></strong></td>
						<td><code><?php echo esc_html( $role['slug'] ); ?></code></td>
						<td><?php echo esc_html( $role['user_count'] ); ?></td>
						<td><?php echo esc_html( $role['cap_count'] ); ?> caps</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

	<!-- ======================================================= -->
	<!-- REST API PANEL -->
	<!-- ======================================================= -->
	<div id="wps-panel-rest_api" class="wps-dashboard__panel" role="tabpanel">
		<?php $rest = $sec( 'rest_api' ); ?>
		<div class="wps-dashboard__cards">
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total Routes', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $rest['total_routes'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Namespaces', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( count( $rest['namespaces'] ?? [] ) ); ?></div></div>
		</div>

		<?php if ( ! empty( $rest['by_namespace'] ) ) : ?>
		<h3><?php esc_html_e( 'Routes by Namespace', 'site-audit-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Namespace', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Routes', 'site-audit-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $rest['by_namespace'] as $ns => $count ) : ?>
					<tr><td><code><?php echo esc_html( $ns ); ?></code></td><td><?php echo esc_html( $count ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<?php if ( ! empty( $rest['endpoints'] ) ) : ?>
		<h3><?php esc_html_e( 'All Endpoints', 'site-audit-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Route', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Methods', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Namespace', 'site-audit-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $rest['endpoints'] as $ep ) : ?>
					<tr>
						<td><code><?php echo esc_html( $ep['route'] ); ?></code></td>
						<td><?php echo esc_html( implode( ', ', $ep['methods'] ) ); ?></td>
						<td><?php echo esc_html( $ep['namespace'] ?: '–' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

	<!-- ======================================================= -->
	<!-- SECURITY PANEL -->
	<!-- ======================================================= -->
	<div id="wps-panel-security" class="wps-dashboard__panel" role="tabpanel">
		<?php $sec_d = $sec( 'security' ); ?>
		<div class="wps-dashboard__cards">
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Critical', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ( $sec_d['critical_count'] ?? 0 ) > 0 ? ' wps-dashboard__card-value--critical' : ''; ?>"><?php echo esc_html( $sec_d['critical_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Warnings', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ( $sec_d['warning_count'] ?? 0 ) > 0 ? ' wps-dashboard__card-value--warning' : ''; ?>"><?php echo esc_html( $sec_d['warning_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Good', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ( $sec_d['good_count'] ?? 0 ) > 0 ? ' wps-dashboard__card-value--good' : ''; ?>"><?php echo esc_html( $sec_d['good_count'] ?? 0 ); ?></div></div>
		</div>

		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Check', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Status', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Note', 'site-audit-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $sec_d['checks'] ?? [] as $check ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $check['label'] ); ?></strong></td>
						<td><span class="wps-dashboard__badge wps-dashboard__badge--<?php echo esc_attr( $check['status'] ); ?>"><?php echo esc_html( ucfirst( $check['status'] ) ); ?></span></td>
						<td><?php echo esc_html( $check['note'] ?? '' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<!-- ======================================================= -->
	<!-- MEDIA PANEL -->
	<!-- ======================================================= -->
	<div id="wps-panel-media" class="wps-dashboard__panel" role="tabpanel">
		<?php $med = $sec( 'media' ); ?>
		<div class="wps-dashboard__cards">
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total Attachments', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( number_format_i18n( $med['total_attachments'] ?? 0 ) ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Uploads Directory', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $med['upload_dir_size_human'] ?? '–' ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Images', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $med['mime_summary']['images'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Videos', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $med['mime_summary']['videos'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Audio', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $med['mime_summary']['audio'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Documents', 'site-audit-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $med['mime_summary']['documents'] ?? 0 ); ?></div></div>
		</div>
		<p><strong><?php esc_html_e( 'Upload URL:', 'site-audit-snapshot' ); ?></strong> <a href="<?php echo esc_url( $med['upload_url'] ?? '#' ); ?>" target="_blank"><?php echo esc_html( $med['upload_url'] ?? '–' ); ?></a></p>
	</div>

	<!-- ======================================================= -->
	<!-- PERFORMANCE PANEL -->
	<!-- ======================================================= -->
	<div id="wps-panel-performance" class="wps-dashboard__panel" role="tabpanel">
		<?php $perf = $sec( 'performance' ); ?>
		<table class="wps-dashboard__table">
			<tbody>
				<tr><th><?php esc_html_e( 'Object Cache', 'site-audit-snapshot' ); ?></th><td><?php echo ! empty( $perf['object_cache_active'] ) ? '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html( $perf['object_cache_type'] ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--info">' . esc_html__( 'None', 'site-audit-snapshot' ) . '</span>'; ?></td></tr>
				<tr><th><?php esc_html_e( 'Page Cache', 'site-audit-snapshot' ); ?></th><td><?php echo ! empty( $perf['page_cache_likely'] ) ? '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Likely active', 'site-audit-snapshot' ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--info">' . esc_html__( 'Not detected', 'site-audit-snapshot' ) . '</span>'; ?></td></tr>
				<tr><th><?php esc_html_e( 'OPcache', 'site-audit-snapshot' ); ?></th><td><?php echo ! empty( $perf['opcache_enabled'] ) ? '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Enabled', 'site-audit-snapshot' ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--info">' . esc_html__( 'Disabled', 'site-audit-snapshot' ) . '</span>'; ?></td></tr>
				<tr><th><?php esc_html_e( 'Image Editor', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $perf['image_editor'] ?? '–' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Permalink Structure', 'site-audit-snapshot' ); ?></th><td><code><?php echo esc_html( $perf['permalink_structure'] ?? '–' ); ?></code></td></tr>
				<tr><th><?php esc_html_e( 'Max Upload Size', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $perf['max_upload_human'] ?? '–' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'WordPress.org Reachable', 'site-audit-snapshot' ); ?></th><td><?php echo ! empty( $perf['wp_org_reachable'] ) ? '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Yes', 'site-audit-snapshot' ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--warning">' . esc_html__( 'No', 'site-audit-snapshot' ) . '</span>'; ?></td></tr>
			</tbody>
		</table>
	</div>

	<?php endif; // end $has_snapshot ?>
</div><!-- .wps-dashboard -->
