<?php
/**
 * PDF / Print Template
 *
 * Rendered by Pdf_Generator::generate_html().
 * Accessed via GET /wp-json/site-audit-snapshot/v1/pdf.
 * Designed for browser print-to-PDF (Ctrl+P).
 *
 * Variables available:
 * @var array $snapshot The full snapshot data.
 *
 * @package WPSnapshot
 */

defined( 'ABSPATH' ) || exit;

$sections = $snapshot['sections'] ?? [];
$sec      = fn( string $key ): array => $sections[ $key ]['data'] ?? [];
$env      = $sec( 'environment' );
$plug     = $sec( 'plugins' );
$themes   = $sec( 'themes' );
$db       = $sec( 'database' );
$security = $sec( 'security' );
$cron     = $sec( 'cron' );
$post_types = $sec( 'post_types' );
$taxes    = $sec( 'taxonomies' );
$users    = $sec( 'users' );
$media    = $sec( 'media' );
$perf     = $sec( 'performance' );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="robots" content="noindex, nofollow">
	<title><?php
		printf(
			/* translators: 1: site name */
			esc_html__( 'Site Audit Snapshot — %s', 'site-audit-snapshot' ),
			esc_html( $snapshot['site_name'] ?? '' )
		);
	?></title>
	<style>
		/* === Base === */
		*, *::before, *::after { box-sizing: border-box; }
		body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 12px; line-height: 1.5; color: #1e1e1e; background: #fff; margin: 0; padding: 0; }
		a { color: #2271b1; text-decoration: none; }

		/* === Print layout === */
		.wps-pdf { max-width: 900px; margin: 0 auto; padding: 24px 32px; }
		.wps-pdf__cover { page-break-after: always; display: flex; flex-direction: column; justify-content: center; min-height: 80vh; text-align: center; }
		.wps-pdf__cover-logo { font-size: 48px; margin-bottom: 16px; }
		.wps-pdf__cover h1 { font-size: 28px; margin: 0 0 8px; }
		.wps-pdf__cover .wps-pdf__cover-meta { color: #646970; font-size: 13px; }
		.wps-pdf__section { page-break-inside: avoid; margin-bottom: 28px; border: 1px solid #dcdcde; border-radius: 6px; overflow: hidden; }
		.wps-pdf__section-title { background: #1d2327; color: #fff; padding: 8px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin: 0; }
		.wps-pdf__section-body { padding: 14px; }
		.wps-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 12px; }
		.wps-card { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; padding: 8px; text-align: center; }
		.wps-card__label { font-size: 10px; color: #646970; text-transform: uppercase; margin-bottom: 2px; }
		.wps-card__value { font-size: 16px; font-weight: 700; }
		table { width: 100%; border-collapse: collapse; font-size: 11px; }
		th { background: #f6f7f7; text-align: left; padding: 5px 8px; border-bottom: 1px solid #dcdcde; white-space: nowrap; font-weight: 600; }
		td { padding: 5px 8px; border-bottom: 1px solid #f0f0f1; vertical-align: top; word-break: break-word; }
		tr:last-child td { border-bottom: none; }
		.badge { display: inline-block; padding: 1px 6px; border-radius: 2px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
		.badge--good { background: #d1fae5; color: #065f46; }
		.badge--warning { background: #fef3c7; color: #92400e; }
		.badge--critical { background: #fee2e2; color: #991b1b; }
		.badge--info { background: #e0f2fe; color: #075985; }
		code { background: #f0f0f1; padding: 0 4px; border-radius: 2px; font-size: 10px; }
		.wps-print-btn { position: fixed; top: 16px; right: 16px; background: #2271b1; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; z-index: 100; }

		/* === Print styles === */
		@media print {
			.wps-print-btn { display: none; }
			.wps-pdf { padding: 0; }
			.wps-pdf__section { page-break-inside: avoid; border: 1px solid #ccc; }
			.wps-pdf__cover { page-break-after: always; }
		}
		@page { margin: 16mm; }
	</style>
</head>
<body>
<button class="wps-print-btn" onclick="window.print()"><?php esc_html_e( 'Print / Save as PDF', 'site-audit-snapshot' ); ?></button>
<div class="wps-pdf">

	<!-- Cover Page -->
	<div class="wps-pdf__cover">
		<div class="wps-pdf__cover-logo">📋</div>
		<h1><?php printf( esc_html__( 'Site Audit Snapshot', 'site-audit-snapshot' ) ); ?></h1>
		<h2 style="color:#646970;font-weight:400;margin:0 0 12px;"><?php echo esc_html( $snapshot['site_name'] ?? '' ); ?></h2>
		<p class="wps-pdf__cover-meta">
			<?php echo esc_html( $snapshot['site_url'] ?? '' ); ?><br>
			<?php printf( esc_html__( 'Generated: %s', 'site-audit-snapshot' ), esc_html( $snapshot['generated_at'] ?? '' ) ); ?><br>
			<?php printf( esc_html__( 'Site Audit Snapshot v%s', 'site-audit-snapshot' ), esc_html( $snapshot['generator_version'] ?? '' ) ); ?>
		</p>
	</div>

	<!-- Environment -->
	<section class="wps-pdf__section">
		<h2 class="wps-pdf__section-title"><?php esc_html_e( 'Environment', 'site-audit-snapshot' ); ?></h2>
		<div class="wps-pdf__section-body">
			<div class="wps-cards">
				<div class="wps-card"><div class="wps-card__label">WordPress</div><div class="wps-card__value"><?php echo esc_html( $env['wp_version'] ?? '–' ); ?></div></div>
				<div class="wps-card"><div class="wps-card__label">PHP</div><div class="wps-card__value"><?php echo esc_html( $env['php_version'] ?? '–' ); ?></div></div>
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'Database', 'site-audit-snapshot' ); ?></div><div class="wps-card__value" style="font-size:12px;"><?php echo esc_html( ( $env['db_type'] ?? '' ) . ' ' . ( $env['db_version'] ?? '' ) ); ?></div></div>
				<div class="wps-card"><div class="wps-card__label">HTTPS</div><div class="wps-card__value"><?php echo ! empty( $env['is_https'] ) ? '<span class="badge badge--good">Yes</span>' : '<span class="badge badge--critical">No</span>'; ?></div></div>
			</div>
			<table>
				<tbody>
					<tr><th><?php esc_html_e( 'Site URL', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $env['site_url'] ?? '' ); ?></td><th><?php esc_html_e( 'Home URL', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $env['home_url'] ?? '' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Server', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $env['server_software'] ?? '' ); ?></td><th><?php esc_html_e( 'OS', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $env['server_os'] ?? '' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'PHP Memory', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $env['php_memory_limit'] ?? '' ); ?></td><th><?php esc_html_e( 'Max Upload', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $env['php_max_upload'] ?? '' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Timezone', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $env['wp_timezone'] ?? '' ); ?></td><th><?php esc_html_e( 'Locale', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $env['wp_locale'] ?? '' ); ?></td></tr>
					<tr><th>WP_DEBUG</th><td><?php echo ! empty( $env['wp_debug'] ) ? '<span class="badge badge--warning">On</span>' : 'Off'; ?></td><th><?php esc_html_e( 'Multisite', 'site-audit-snapshot' ); ?></th><td><?php echo ! empty( $env['is_multisite'] ) ? 'Yes' : 'No'; ?></td></tr>
				</tbody>
			</table>
		</div>
	</section>

	<!-- Plugins -->
	<section class="wps-pdf__section">
		<h2 class="wps-pdf__section-title"><?php printf( esc_html__( 'Plugins — %d Active / %d Total', 'site-audit-snapshot' ), (int) ( $plug['active_count'] ?? 0 ), (int) ( $plug['total_plugins'] ?? 0 ) ); ?></h2>
		<div class="wps-pdf__section-body">
			<?php if ( ! empty( $plug['plugins'] ) ) : ?>
			<table>
				<thead><tr><th><?php esc_html_e( 'Name', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Version', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Author', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Status', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Update', 'site-audit-snapshot' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $plug['plugins'] as $p ) : ?>
						<tr>
							<td><?php echo esc_html( $p['name'] ); ?></td>
							<td><?php echo esc_html( $p['version'] ); ?></td>
							<td><?php echo esc_html( $p['author'] ); ?></td>
							<td><?php echo $p['is_active'] ? '<span class="badge badge--good">Active</span>' : '<span class="badge badge--info">Inactive</span>'; ?></td>
							<td><?php echo $p['has_update'] ? '<span class="badge badge--warning">v' . esc_html( $p['update_version'] ) . '</span>' : ''; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</section>

	<!-- Themes -->
	<section class="wps-pdf__section">
		<h2 class="wps-pdf__section-title"><?php esc_html_e( 'Active Theme', 'site-audit-snapshot' ); ?></h2>
		<div class="wps-pdf__section-body">
			<?php $at = $themes['active_theme'] ?? []; ?>
			<table><tbody>
				<tr><th><?php esc_html_e( 'Name', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $at['name'] ?? '' ); ?></td><th><?php esc_html_e( 'Version', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $at['version'] ?? '' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Author', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $at['author'] ?? '' ); ?></td><th><?php esc_html_e( 'Type', 'site-audit-snapshot' ); ?></th><td><?php echo ! empty( $at['is_block_theme'] ) ? 'Block (FSE)' : 'Classic'; ?></td></tr>
				<tr><th><?php esc_html_e( 'Child Theme', 'site-audit-snapshot' ); ?></th><td colspan="3"><?php echo ! empty( $at['is_child_theme'] ) ? esc_html( $at['parent_theme'] ?? '' ) . ' (parent)' : 'No'; ?></td></tr>
			</tbody></table>
		</div>
	</section>

	<!-- Database -->
	<section class="wps-pdf__section">
		<h2 class="wps-pdf__section-title"><?php esc_html_e( 'Database', 'site-audit-snapshot' ); ?></h2>
		<div class="wps-pdf__section-body">
			<div class="wps-cards">
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'DB Size', 'site-audit-snapshot' ); ?></div><div class="wps-card__value" style="font-size:14px;"><?php echo esc_html( $db['total_db_size_human'] ?? '–' ); ?></div></div>
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'Tables', 'site-audit-snapshot' ); ?></div><div class="wps-card__value"><?php echo esc_html( $db['total_tables'] ?? 0 ); ?></div></div>
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'Autoloaded', 'site-audit-snapshot' ); ?></div><div class="wps-card__value" style="font-size:14px;"><?php echo esc_html( $db['autoload_size_human'] ?? '–' ); ?></div></div>
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'Revisions', 'site-audit-snapshot' ); ?></div><div class="wps-card__value"><?php echo esc_html( $db['revisions_count'] ?? 0 ); ?></div></div>
			</div>
			<table><tbody>
				<tr><th><?php esc_html_e( 'Prefix', 'site-audit-snapshot' ); ?></th><td><code><?php echo esc_html( $db['db_prefix'] ?? '' ); ?></code></td><th><?php esc_html_e( 'Charset', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $db['db_charset'] ?? '' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Orphaned Postmeta', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $db['orphaned_postmeta'] ?? 0 ); ?></td><th><?php esc_html_e( 'Trashed', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $db['trashed_count'] ?? 0 ); ?></td></tr>
			</tbody></table>
		</div>
	</section>

	<!-- Security -->
	<section class="wps-pdf__section">
		<h2 class="wps-pdf__section-title"><?php esc_html_e( 'Security', 'site-audit-snapshot' ); ?></h2>
		<div class="wps-pdf__section-body">
			<table>
				<thead><tr><th><?php esc_html_e( 'Check', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Status', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Note', 'site-audit-snapshot' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $security['checks'] ?? [] as $check ) : ?>
						<tr>
							<td><?php echo esc_html( $check['label'] ); ?></td>
							<td><span class="badge badge--<?php echo esc_attr( $check['status'] ); ?>"><?php echo esc_html( ucfirst( $check['status'] ) ); ?></span></td>
							<td><?php echo esc_html( $check['note'] ?? '' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>

	<!-- Cron -->
	<section class="wps-pdf__section">
		<h2 class="wps-pdf__section-title"><?php printf( esc_html__( 'Cron Jobs — %d events', 'site-audit-snapshot' ), (int) ( $cron['total_events'] ?? 0 ) ); ?></h2>
		<div class="wps-pdf__section-body">
			<?php if ( ! empty( $cron['events'] ) ) : ?>
			<table>
				<thead><tr><th><?php esc_html_e( 'Hook', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Next Run', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Schedule', 'site-audit-snapshot' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $cron['events'] as $ev ) : ?>
						<tr>
							<td><code><?php echo esc_html( $ev['hook'] ); ?></code></td>
							<td><?php echo esc_html( $ev['next_run_human'] ); ?></td>
							<td><?php echo esc_html( $ev['schedule_label'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</section>

	<!-- Post Types & Taxonomies -->
	<section class="wps-pdf__section">
		<h2 class="wps-pdf__section-title"><?php esc_html_e( 'Post Types & Taxonomies', 'site-audit-snapshot' ); ?></h2>
		<div class="wps-pdf__section-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
			<div>
				<strong><?php esc_html_e( 'Post Types', 'site-audit-snapshot' ); ?></strong>
				<table style="margin-top:6px;">
					<thead><tr><th><?php esc_html_e( 'Slug', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Published', 'site-audit-snapshot' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $post_types['post_types'] ?? [] as $t ) : ?>
							<tr><td><code><?php echo esc_html( $t['slug'] ); ?></code><?php echo ! $t['is_builtin'] ? ' <span class="badge badge--good">custom</span>' : ''; ?></td><td><?php echo esc_html( $t['published'] ); ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div>
				<strong><?php esc_html_e( 'Taxonomies', 'site-audit-snapshot' ); ?></strong>
				<table style="margin-top:6px;">
					<thead><tr><th><?php esc_html_e( 'Slug', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Terms', 'site-audit-snapshot' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $taxes['taxonomies'] ?? [] as $t ) : ?>
							<tr><td><code><?php echo esc_html( $t['slug'] ); ?></code><?php echo ! $t['is_builtin'] ? ' <span class="badge badge--good">custom</span>' : ''; ?></td><td><?php echo esc_html( $t['term_count'] ); ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<!-- Users -->
	<section class="wps-pdf__section">
		<h2 class="wps-pdf__section-title"><?php printf( esc_html__( 'Users & Roles — %d total users', 'site-audit-snapshot' ), (int) ( $users['total_users'] ?? 0 ) ); ?></h2>
		<div class="wps-pdf__section-body">
			<table>
				<thead><tr><th><?php esc_html_e( 'Role', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Slug', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Users', 'site-audit-snapshot' ); ?></th><th><?php esc_html_e( 'Capabilities', 'site-audit-snapshot' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $users['roles'] ?? [] as $r ) : ?>
						<tr><td><strong><?php echo esc_html( $r['name'] ); ?></strong></td><td><code><?php echo esc_html( $r['slug'] ); ?></code></td><td><?php echo esc_html( $r['user_count'] ); ?></td><td><?php echo esc_html( $r['cap_count'] ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>

	<!-- Media & Performance -->
	<section class="wps-pdf__section">
		<h2 class="wps-pdf__section-title"><?php esc_html_e( 'Media & Performance', 'site-audit-snapshot' ); ?></h2>
		<div class="wps-pdf__section-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
			<table><tbody>
				<tr><th><?php esc_html_e( 'Attachments', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( number_format_i18n( $media['total_attachments'] ?? 0 ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Uploads Size', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $media['upload_dir_size_human'] ?? '–' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Images', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $media['mime_summary']['images'] ?? 0 ); ?></td></tr>
			</tbody></table>
			<table><tbody>
				<tr><th><?php esc_html_e( 'Object Cache', 'site-audit-snapshot' ); ?></th><td><?php echo ! empty( $perf['object_cache_active'] ) ? '<span class="badge badge--good">' . esc_html( $perf['object_cache_type'] ) . '</span>' : 'None'; ?></td></tr>
				<tr><th><?php esc_html_e( 'OPcache', 'site-audit-snapshot' ); ?></th><td><?php echo ! empty( $perf['opcache_enabled'] ) ? '<span class="badge badge--good">On</span>' : 'Off'; ?></td></tr>
				<tr><th><?php esc_html_e( 'Image Editor', 'site-audit-snapshot' ); ?></th><td><?php echo esc_html( $perf['image_editor'] ?? '–' ); ?></td></tr>
			</tbody></table>
		</div>
	</section>

	<footer style="text-align:center;color:#646970;font-size:11px;margin-top:24px;padding-top:12px;border-top:1px solid #dcdcde;">
		<?php printf( esc_html__( 'Generated by Site Audit Snapshot v%s · %s · %s', 'site-audit-snapshot' ), esc_html( $snapshot['generator_version'] ?? '' ), esc_html( $snapshot['site_url'] ?? '' ), esc_html( $snapshot['generated_at'] ?? '' ) ); ?>
	</footer>

</div>
<script>
// Auto-open print dialog after a short delay.
window.addEventListener('load', function() {
	if (window.location.search.indexOf('autoprint=1') !== -1) {
		setTimeout(function() { window.print(); }, 800);
	}
});
</script>
</body>
</html>
