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
		<?php esc_html_e( 'WP Snapshot', 'wp-snapshot' ); ?>
	</h1>

	<?php if ( $has_snapshot ) : ?>
		<p class="wps-dashboard__meta">
			<?php
			printf(
				/* translators: 1: site name, 2: date/time */
				esc_html__( 'Last snapshot for %1$s — generated %2$s', 'wp-snapshot' ),
				'<strong>' . esc_html( $snapshot['site_name'] ) . '</strong>',
				'<strong>' . esc_html( $snapshot['generated_at'] ) . '</strong>'
			);
			?>
		</p>
	<?php endif; ?>

	<!-- Action bar -->
	<div class="wps-dashboard__actions">
		<button id="wps-generate-btn" class="wps-dashboard__btn wps-dashboard__btn--primary">
			<?php esc_html_e( 'Generate Snapshot', 'wp-snapshot' ); ?>
		</button>

		<?php if ( $has_snapshot ) : ?>
			<a id="wps-pdf-btn" href="<?php echo esc_url( rest_url( 'wp-snapshot/v1/pdf' ) . '?_wpnonce=' . wp_create_nonce( 'wp_rest' ) ); ?>" target="_blank" class="wps-dashboard__btn wps-dashboard__btn--secondary">
				<?php esc_html_e( 'Print / Export PDF', 'wp-snapshot' ); ?>
			</a>
			<button id="wps-json-btn" class="wps-dashboard__btn wps-dashboard__btn--secondary">
				<?php esc_html_e( 'Export JSON', 'wp-snapshot' ); ?>
			</button>
			<button id="wps-share-btn" class="wps-dashboard__btn wps-dashboard__btn--secondary">
				<?php esc_html_e( 'Create Share Link', 'wp-snapshot' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<!-- Share links panel -->
	<?php if ( $has_snapshot ) : ?>
	<div id="wps-share-panel" class="wps-dashboard__share-panel" style="display:none;">
		<h3><?php esc_html_e( 'Share Links', 'wp-snapshot' ); ?></h3>
		<div id="wps-new-link-area"></div>
		<?php if ( ! empty( $active_shares ) ) : ?>
			<table class="wps-dashboard__table" style="margin-top:12px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Share URL', 'wp-snapshot' ); ?></th>
						<th><?php esc_html_e( 'Created', 'wp-snapshot' ); ?></th>
						<th><?php esc_html_e( 'Expires', 'wp-snapshot' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'wp-snapshot' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $active_shares as $share ) : ?>
					<tr data-token="<?php echo esc_attr( $share['token'] ); ?>">
						<td><a href="<?php echo esc_url( $share['url'] ); ?>" target="_blank"><?php echo esc_html( $share['url'] ); ?></a></td>
						<td><?php echo esc_html( $share['created'] ); ?></td>
						<td><?php echo esc_html( $share['expires_at'] ); ?> (<?php echo esc_html( $share['expires_in'] ); ?>)</td>
						<td>
							<button class="wps-revoke-btn button-link-delete" data-token="<?php echo esc_attr( $share['token'] ); ?>"><?php esc_html_e( 'Revoke', 'wp-snapshot' ); ?></button>
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
		<?php esc_html_e( 'Generating snapshot, please wait…', 'wp-snapshot' ); ?>
	</div>
	<div id="wps-error" class="notice notice-error" style="display:none;"><p></p></div>

	<?php if ( ! $has_snapshot ) : ?>
		<div class="wps-dashboard__empty">
			<p><?php esc_html_e( 'No snapshot generated yet. Click "Generate Snapshot" to collect site information.', 'wp-snapshot' ); ?></p>
		</div>
	<?php else : ?>

	<!-- Tab Navigation -->
	<nav class="wps-dashboard__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Snapshot Sections', 'wp-snapshot' ); ?>">
		<?php
		$tabs = [
			'overview'   => __( 'Overview', 'wp-snapshot' ),
			'plugins'    => __( 'Plugins', 'wp-snapshot' ),
			'themes'     => __( 'Themes', 'wp-snapshot' ),
			'database'   => __( 'Database', 'wp-snapshot' ),
			'cron'       => __( 'Cron', 'wp-snapshot' ),
			'post_types' => __( 'Post Types', 'wp-snapshot' ),
			'users'      => __( 'Users', 'wp-snapshot' ),
			'rest_api'   => __( 'REST API', 'wp-snapshot' ),
			'security'   => __( 'Security', 'wp-snapshot' ),
			'media'      => __( 'Media', 'wp-snapshot' ),
			'performance' => __( 'Performance', 'wp-snapshot' ),
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
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'WordPress', 'wp-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value"><?php echo esc_html( $env['wp_version'] ?? '–' ); ?></div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'PHP', 'wp-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value"><?php echo esc_html( $env['php_version'] ?? '–' ); ?></div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'Active Plugins', 'wp-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value"><?php echo esc_html( $plug['active_count'] ?? '–' ); ?></div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'Plugin Updates', 'wp-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value<?php echo ! empty( $plug['update_available'] ) ? ' wps-dashboard__card-value--warning' : ''; ?>">
					<?php echo esc_html( $plug['update_available'] ?? '0' ); ?>
				</div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'DB Size', 'wp-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value"><?php echo esc_html( $sec( 'database' )['total_db_size_human'] ?? '–' ); ?></div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'Active Theme', 'wp-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value"><?php echo esc_html( $sec( 'themes' )['active_theme']['name'] ?? '–' ); ?></div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'HTTPS', 'wp-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value">
					<?php
					$is_https = ! empty( $env['is_https'] );
					echo $is_https
						? '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Yes', 'wp-snapshot' ) . '</span>'
						: '<span class="wps-dashboard__badge wps-dashboard__badge--critical">' . esc_html__( 'No', 'wp-snapshot' ) . '</span>';
					?>
				</div>
			</div>
			<div class="wps-dashboard__card">
				<div class="wps-dashboard__card-label"><?php esc_html_e( 'Security Issues', 'wp-snapshot' ); ?></div>
				<div class="wps-dashboard__card-value">
					<?php
					$critical = (int) ( $sec_data['critical_count'] ?? 0 );
					$warning  = (int) ( $sec_data['warning_count'] ?? 0 );
					if ( $critical > 0 ) {
						echo '<span class="wps-dashboard__badge wps-dashboard__badge--critical">' . esc_html( $critical ) . ' ' . esc_html__( 'critical', 'wp-snapshot' ) . '</span> ';
					}
					if ( $warning > 0 ) {
						echo '<span class="wps-dashboard__badge wps-dashboard__badge--warning">' . esc_html( $warning ) . ' ' . esc_html__( 'warning', 'wp-snapshot' ) . '</span>';
					}
					if ( $critical === 0 && $warning === 0 ) {
						echo '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'All good', 'wp-snapshot' ) . '</span>';
					}
					?>
				</div>
			</div>
		</div>

		<!-- Environment quick-view -->
		<h3><?php esc_html_e( 'Environment Details', 'wp-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<tbody>
				<?php
				$env_rows = [
					__( 'Site URL', 'wp-snapshot' )            => $env['site_url'] ?? '',
					__( 'Home URL', 'wp-snapshot' )            => $env['home_url'] ?? '',
					__( 'Timezone', 'wp-snapshot' )            => $env['wp_timezone'] ?? '',
					__( 'Locale', 'wp-snapshot' )              => $env['wp_locale'] ?? '',
					__( 'DB Type', 'wp-snapshot' )             => ( $env['db_type'] ?? '' ) . ' ' . ( $env['db_version'] ?? '' ),
					__( 'Server', 'wp-snapshot' )              => $env['server_software'] ?? '',
					__( 'OS', 'wp-snapshot' )                  => $env['server_os'] ?? '',
					__( 'PHP Memory Limit', 'wp-snapshot' )    => $env['php_memory_limit'] ?? '',
					__( 'WP Memory Limit', 'wp-snapshot' )     => $env['wp_memory_limit'] ?? '',
					__( 'Max Upload Size', 'wp-snapshot' )     => $env['php_max_upload'] ?? '',
					__( 'Max Execution Time', 'wp-snapshot' )  => ( $env['php_max_execution'] ?? '' ) . 's',
					__( 'Multisite', 'wp-snapshot' )           => ! empty( $env['is_multisite'] ) ? __( 'Yes', 'wp-snapshot' ) : __( 'No', 'wp-snapshot' ),
					__( 'DISALLOW_FILE_EDIT', 'wp-snapshot' )  => ! empty( $env['disallow_file_edit'] ) ? __( 'Yes', 'wp-snapshot' ) : __( 'No', 'wp-snapshot' ),
					__( 'WP_DEBUG', 'wp-snapshot' )            => ! empty( $env['wp_debug'] ) ? __( 'Enabled', 'wp-snapshot' ) : __( 'Disabled', 'wp-snapshot' ),
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
			<h3><?php esc_html_e( 'PHP Extensions', 'wp-snapshot' ); ?></h3>
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
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $plug['total_plugins'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Active', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $plug['active_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Inactive', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $plug['inactive_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Updates Available', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ! empty( $plug['update_available'] ) ? ' wps-dashboard__card-value--warning' : ''; ?>"><?php echo esc_html( $plug['update_available'] ?? 0 ); ?></div></div>
		</div>

		<?php if ( ! empty( $plug['plugins'] ) ) : ?>
		<table class="wps-dashboard__table wps-dashboard__table--sortable">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Plugin', 'wp-snapshot' ); ?></th>
					<th><?php esc_html_e( 'Version', 'wp-snapshot' ); ?></th>
					<th><?php esc_html_e( 'Author', 'wp-snapshot' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wp-snapshot' ); ?></th>
					<th><?php esc_html_e( 'Update', 'wp-snapshot' ); ?></th>
					<th><?php esc_html_e( 'Auto-update', 'wp-snapshot' ); ?></th>
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
							<span class="wps-dashboard__badge wps-dashboard__badge--good"><?php esc_html_e( 'Active', 'wp-snapshot' ); ?></span>
						<?php else : ?>
							<span class="wps-dashboard__badge wps-dashboard__badge--info"><?php esc_html_e( 'Inactive', 'wp-snapshot' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $plugin['has_update'] ) : ?>
							<span class="wps-dashboard__badge wps-dashboard__badge--warning">
								<?php
								printf(
									/* translators: %s: new version number */
									esc_html__( 'v%s available', 'wp-snapshot' ),
									esc_html( $plugin['update_version'] )
								);
								?>
							</span>
						<?php else : ?>
							<span class="wps-dashboard__badge wps-dashboard__badge--good"><?php esc_html_e( 'Up to date', 'wp-snapshot' ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo $plugin['auto_update'] ? esc_html__( 'On', 'wp-snapshot' ) : esc_html__( 'Off', 'wp-snapshot' ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<?php if ( ! empty( $plug['mu_plugins'] ) ) : ?>
		<h3><?php esc_html_e( 'Must-Use Plugins', 'wp-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'File', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Name', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Version', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Author', 'wp-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $plug['mu_plugins'] as $mu ) : ?>
					<tr><td><?php echo esc_html( $mu['file'] ); ?></td><td><?php echo esc_html( $mu['name'] ); ?></td><td><?php echo esc_html( $mu['version'] ); ?></td><td><?php echo esc_html( $mu['author'] ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<?php if ( ! empty( $plug['dropins'] ) ) : ?>
		<h3><?php esc_html_e( 'Drop-ins', 'wp-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'File', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Name', 'wp-snapshot' ); ?></th></tr></thead>
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
			<h3><?php esc_html_e( 'Active Theme', 'wp-snapshot' ); ?></h3>
			<table class="wps-dashboard__table">
				<tbody>
					<tr><th><?php esc_html_e( 'Name', 'wp-snapshot' ); ?></th><td><strong><?php echo esc_html( $active['name'] ?? '' ); ?></strong><?php echo ! empty( $active['has_update'] ) ? ' <span class="wps-dashboard__badge wps-dashboard__badge--warning">' . esc_html__( 'Update available', 'wp-snapshot' ) . '</span>' : ''; ?></td></tr>
					<tr><th><?php esc_html_e( 'Version', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $active['version'] ?? '' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Author', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $active['author'] ?? '' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Block Theme', 'wp-snapshot' ); ?></th><td><?php echo ! empty( $active['is_block_theme'] ) ? esc_html__( 'Yes (FSE)', 'wp-snapshot' ) : esc_html__( 'No (classic)', 'wp-snapshot' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Child Theme', 'wp-snapshot' ); ?></th><td><?php echo ! empty( $active['is_child_theme'] ) ? esc_html( $active['parent_theme'] ?? '' ) . ' (' . esc_html__( 'parent', 'wp-snapshot' ) . ')' : esc_html__( 'No', 'wp-snapshot' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Requires WP', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $active['requires_wp'] ?? '–' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Requires PHP', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $active['requires_php'] ?? '–' ); ?></td></tr>
				</tbody>
			</table>
		</div>
		<?php endif; ?>

		<h3><?php printf( esc_html__( 'All Installed Themes (%d)', 'wp-snapshot' ), (int) ( $th['total_themes'] ?? 0 ) ); ?></h3>
		<?php if ( ! empty( $th['installed'] ) ) : ?>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Theme', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Version', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Author', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Status', 'wp-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $th['installed'] as $t ) : ?>
					<tr>
						<td><?php echo esc_html( $t['name'] ); ?> <small style="opacity:.6;"><?php echo esc_html( $t['slug'] ); ?></small></td>
						<td><?php echo esc_html( $t['version'] ); ?></td>
						<td><?php echo esc_html( $t['author'] ); ?></td>
						<td>
							<?php echo $t['is_active'] ? '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Active', 'wp-snapshot' ) . '</span>' : ''; ?>
							<?php echo $t['has_update'] ? '<span class="wps-dashboard__badge wps-dashboard__badge--warning">' . esc_html__( 'Update', 'wp-snapshot' ) . '</span>' : ''; ?>
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
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'DB Size', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $db['total_db_size_human'] ?? '–' ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Tables', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $db['total_tables'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Autoloaded Data', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ( $db['autoload_size'] ?? 0 ) > 1048576 ? ' wps-dashboard__card-value--warning' : ''; ?>"><?php echo esc_html( $db['autoload_size_human'] ?? '–' ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Revisions', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $db['revisions_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Trashed Posts', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $db['trashed_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Orphaned Postmeta', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ( $db['orphaned_postmeta'] ?? 0 ) > 0 ? ' wps-dashboard__card-value--warning' : ''; ?>"><?php echo esc_html( $db['orphaned_postmeta'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Transients', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $db['transients_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Autoloaded Options', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $db['autoloaded_options'] ?? 0 ); ?> / <?php echo esc_html( $db['total_options'] ?? 0 ); ?></div></div>
		</div>

		<h3><?php esc_html_e( 'Tables', 'wp-snapshot' ); ?></h3>
		<?php if ( ! empty( $db['tables'] ) ) : ?>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Table', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Engine', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Rows', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Size', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Collation', 'wp-snapshot' ); ?></th></tr></thead>
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
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total Events', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $cr['total_events'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Overdue', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ( $cr['overdue_count'] ?? 0 ) > 0 ? ' wps-dashboard__card-value--warning' : ''; ?>"><?php echo esc_html( $cr['overdue_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'WP-Cron Disabled', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo ! empty( $cr['wp_cron_disabled'] ) ? esc_html__( 'Yes', 'wp-snapshot' ) : esc_html__( 'No', 'wp-snapshot' ); ?></div></div>
		</div>
		<?php if ( ! empty( $cr['events'] ) ) : ?>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Hook', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Next Run', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Schedule', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Status', 'wp-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $cr['events'] as $event ) : ?>
					<tr>
						<td><code><?php echo esc_html( $event['hook'] ); ?></code></td>
						<td><?php echo esc_html( $event['next_run_human'] ); ?><br><small><?php echo esc_html( $event['next_run_diff'] ); ?></small></td>
						<td><?php echo esc_html( $event['schedule_label'] ); ?></td>
						<td><?php echo $event['overdue'] ? '<span class="wps-dashboard__badge wps-dashboard__badge--warning">' . esc_html__( 'Overdue', 'wp-snapshot' ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Scheduled', 'wp-snapshot' ) . '</span>'; ?></td>
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
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total Post Types', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $pt['total_post_types'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Custom', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $pt['custom_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total Taxonomies', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $tax['total_taxonomies'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Custom Taxonomies', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $tax['custom_count'] ?? 0 ); ?></div></div>
		</div>

		<?php if ( ! empty( $pt['post_types'] ) ) : ?>
		<h3><?php esc_html_e( 'Post Types', 'wp-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Slug', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Label', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Type', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Public', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'REST', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Published', 'wp-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $pt['post_types'] as $type ) : ?>
					<tr>
						<td><code><?php echo esc_html( $type['slug'] ); ?></code></td>
						<td><?php echo esc_html( $type['label'] ); ?></td>
						<td><?php echo $type['is_builtin'] ? '<span class="wps-dashboard__badge wps-dashboard__badge--info">' . esc_html__( 'Built-in', 'wp-snapshot' ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Custom', 'wp-snapshot' ) . '</span>'; ?></td>
						<td><?php echo $type['is_public'] ? esc_html__( 'Yes', 'wp-snapshot' ) : esc_html__( 'No', 'wp-snapshot' ); ?></td>
						<td><?php echo $type['show_in_rest'] ? esc_html__( 'Yes', 'wp-snapshot' ) : esc_html__( 'No', 'wp-snapshot' ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $type['published'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<?php if ( ! empty( $tax['taxonomies'] ) ) : ?>
		<h3><?php esc_html_e( 'Taxonomies', 'wp-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Slug', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Label', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Type', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Hierarchical', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'REST', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Terms', 'wp-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $tax['taxonomies'] as $t ) : ?>
					<tr>
						<td><code><?php echo esc_html( $t['slug'] ); ?></code></td>
						<td><?php echo esc_html( $t['label'] ); ?></td>
						<td><?php echo $t['is_builtin'] ? '<span class="wps-dashboard__badge wps-dashboard__badge--info">' . esc_html__( 'Built-in', 'wp-snapshot' ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Custom', 'wp-snapshot' ) . '</span>'; ?></td>
						<td><?php echo $t['hierarchical'] ? esc_html__( 'Yes', 'wp-snapshot' ) : esc_html__( 'No', 'wp-snapshot' ); ?></td>
						<td><?php echo $t['show_in_rest'] ? esc_html__( 'Yes', 'wp-snapshot' ) : esc_html__( 'No', 'wp-snapshot' ); ?></td>
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
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total Users', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $usr['total_users'] ?? 0 ); ?></div></div>
		</div>
		<?php if ( ! empty( $usr['roles'] ) ) : ?>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Role', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Slug', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Users', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Capabilities', 'wp-snapshot' ); ?></th></tr></thead>
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
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total Routes', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $rest['total_routes'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Namespaces', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( count( $rest['namespaces'] ?? [] ) ); ?></div></div>
		</div>

		<?php if ( ! empty( $rest['by_namespace'] ) ) : ?>
		<h3><?php esc_html_e( 'Routes by Namespace', 'wp-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Namespace', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Routes', 'wp-snapshot' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $rest['by_namespace'] as $ns => $count ) : ?>
					<tr><td><code><?php echo esc_html( $ns ); ?></code></td><td><?php echo esc_html( $count ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<?php if ( ! empty( $rest['endpoints'] ) ) : ?>
		<h3><?php esc_html_e( 'All Endpoints', 'wp-snapshot' ); ?></h3>
		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Route', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Methods', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Namespace', 'wp-snapshot' ); ?></th></tr></thead>
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
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Critical', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ( $sec_d['critical_count'] ?? 0 ) > 0 ? ' wps-dashboard__card-value--critical' : ''; ?>"><?php echo esc_html( $sec_d['critical_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Warnings', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ( $sec_d['warning_count'] ?? 0 ) > 0 ? ' wps-dashboard__card-value--warning' : ''; ?>"><?php echo esc_html( $sec_d['warning_count'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Good', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value<?php echo ( $sec_d['good_count'] ?? 0 ) > 0 ? ' wps-dashboard__card-value--good' : ''; ?>"><?php echo esc_html( $sec_d['good_count'] ?? 0 ); ?></div></div>
		</div>

		<table class="wps-dashboard__table">
			<thead><tr><th><?php esc_html_e( 'Check', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Status', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Note', 'wp-snapshot' ); ?></th></tr></thead>
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
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Total Attachments', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( number_format_i18n( $med['total_attachments'] ?? 0 ) ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Uploads Directory', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $med['upload_dir_size_human'] ?? '–' ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Images', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $med['mime_summary']['images'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Videos', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $med['mime_summary']['videos'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Audio', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $med['mime_summary']['audio'] ?? 0 ); ?></div></div>
			<div class="wps-dashboard__card"><div class="wps-dashboard__card-label"><?php esc_html_e( 'Documents', 'wp-snapshot' ); ?></div><div class="wps-dashboard__card-value"><?php echo esc_html( $med['mime_summary']['documents'] ?? 0 ); ?></div></div>
		</div>
		<p><strong><?php esc_html_e( 'Upload URL:', 'wp-snapshot' ); ?></strong> <a href="<?php echo esc_url( $med['upload_url'] ?? '#' ); ?>" target="_blank"><?php echo esc_html( $med['upload_url'] ?? '–' ); ?></a></p>
	</div>

	<!-- ======================================================= -->
	<!-- PERFORMANCE PANEL -->
	<!-- ======================================================= -->
	<div id="wps-panel-performance" class="wps-dashboard__panel" role="tabpanel">
		<?php $perf = $sec( 'performance' ); ?>
		<table class="wps-dashboard__table">
			<tbody>
				<tr><th><?php esc_html_e( 'Object Cache', 'wp-snapshot' ); ?></th><td><?php echo ! empty( $perf['object_cache_active'] ) ? '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html( $perf['object_cache_type'] ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--info">' . esc_html__( 'None', 'wp-snapshot' ) . '</span>'; ?></td></tr>
				<tr><th><?php esc_html_e( 'Page Cache', 'wp-snapshot' ); ?></th><td><?php echo ! empty( $perf['page_cache_likely'] ) ? '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Likely active', 'wp-snapshot' ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--info">' . esc_html__( 'Not detected', 'wp-snapshot' ) . '</span>'; ?></td></tr>
				<tr><th><?php esc_html_e( 'OPcache', 'wp-snapshot' ); ?></th><td><?php echo ! empty( $perf['opcache_enabled'] ) ? '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Enabled', 'wp-snapshot' ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--info">' . esc_html__( 'Disabled', 'wp-snapshot' ) . '</span>'; ?></td></tr>
				<tr><th><?php esc_html_e( 'Image Editor', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $perf['image_editor'] ?? '–' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Permalink Structure', 'wp-snapshot' ); ?></th><td><code><?php echo esc_html( $perf['permalink_structure'] ?? '–' ); ?></code></td></tr>
				<tr><th><?php esc_html_e( 'Max Upload Size', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $perf['max_upload_human'] ?? '–' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'WordPress.org Reachable', 'wp-snapshot' ); ?></th><td><?php echo ! empty( $perf['wp_org_reachable'] ) ? '<span class="wps-dashboard__badge wps-dashboard__badge--good">' . esc_html__( 'Yes', 'wp-snapshot' ) . '</span>' : '<span class="wps-dashboard__badge wps-dashboard__badge--warning">' . esc_html__( 'No', 'wp-snapshot' ) . '</span>'; ?></td></tr>
			</tbody>
		</table>
	</div>

	<?php endif; // end $has_snapshot ?>
</div><!-- .wps-dashboard -->
