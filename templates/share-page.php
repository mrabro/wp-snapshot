<?php
/**
 * Public Share Page Template
 *
 * Rendered when a visitor accesses a valid share link.
 * Standalone page — no wp-admin chrome.
 *
 * Variables available from Share_Manager::handle_share_request():
 * @var array $snapshot The sanitized snapshot data.
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
$perf     = $sec( 'performance' );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php
		printf(
			/* translators: 1: site name, 2: date */
			esc_html__( 'WP Snapshot — %1$s — %2$s', 'wp-snapshot' ),
			esc_html( $snapshot['site_name'] ?? '' ),
			esc_html( $snapshot['generated_at'] ?? '' )
		);
	?></title>
	<style>
		*, *::before, *::after { box-sizing: border-box; }
		body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; font-size: 14px; line-height: 1.6; color: #1e1e1e; background: #f0f0f1; margin: 0; padding: 0; }
		.wps-share { max-width: 960px; margin: 0 auto; padding: 24px 16px 48px; }
		.wps-share__header { background: #1d2327; color: #fff; padding: 20px 24px; border-radius: 6px; margin-bottom: 24px; }
		.wps-share__header h1 { margin: 0 0 4px; font-size: 20px; }
		.wps-share__meta { opacity: .7; font-size: 13px; }
		.wps-share__notice { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px 14px; margin-bottom: 24px; border-radius: 4px; font-size: 13px; }
		.wps-share__section { background: #fff; border: 1px solid #dcdcde; border-radius: 6px; margin-bottom: 20px; overflow: hidden; }
		.wps-share__section-title { background: #f6f7f7; border-bottom: 1px solid #dcdcde; padding: 10px 16px; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: #3c434a; margin: 0; }
		.wps-share__section-body { padding: 16px; }
		.wps-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-bottom: 16px; }
		.wps-card { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; padding: 12px; text-align: center; }
		.wps-card__label { font-size: 11px; color: #646970; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
		.wps-card__value { font-size: 18px; font-weight: 700; color: #1d2327; }
		table { width: 100%; border-collapse: collapse; font-size: 13px; }
		th { background: #f6f7f7; text-align: left; padding: 6px 10px; border-bottom: 1px solid #dcdcde; font-weight: 600; white-space: nowrap; }
		td { padding: 6px 10px; border-bottom: 1px solid #f0f0f1; vertical-align: top; word-break: break-word; }
		tr:last-child td { border-bottom: none; }
		.badge { display: inline-block; padding: 2px 7px; border-radius: 3px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
		.badge--good { background: #d1fae5; color: #065f46; }
		.badge--warning { background: #fef3c7; color: #92400e; }
		.badge--critical { background: #fee2e2; color: #991b1b; }
		.badge--info { background: #e0f2fe; color: #075985; }
		code { background: #f0f0f1; padding: 1px 5px; border-radius: 3px; font-size: 12px; }
		@media print { body { background: #fff; } .wps-share { max-width: 100%; padding: 0; } .wps-share__notice { display: none; } }
	</style>
</head>
<body>
<div class="wps-share">

	<header class="wps-share__header">
		<h1><?php printf( esc_html__( 'WP Snapshot — %s', 'wp-snapshot' ), esc_html( $snapshot['site_name'] ?? '' ) ); ?></h1>
		<p class="wps-share__meta">
			<?php
			printf(
				/* translators: 1: site URL, 2: generated date */
				esc_html__( '%1$s · Generated %2$s · WP Snapshot v%3$s', 'wp-snapshot' ),
				esc_html( $snapshot['site_url'] ?? '' ),
				esc_html( $snapshot['generated_at'] ?? '' ),
				esc_html( $snapshot['generator_version'] ?? '' )
			);
			?>
		</p>
	</header>

	<div class="wps-share__notice">
		<?php esc_html_e( 'This snapshot was shared with you via a temporary link. It may expire. Data is read-only.', 'wp-snapshot' ); ?>
	</div>

	<!-- Environment -->
	<section class="wps-share__section">
		<h2 class="wps-share__section-title"><?php esc_html_e( 'Environment', 'wp-snapshot' ); ?></h2>
		<div class="wps-share__section-body">
			<div class="wps-cards">
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'WordPress', 'wp-snapshot' ); ?></div><div class="wps-card__value"><?php echo esc_html( $env['wp_version'] ?? '–' ); ?></div></div>
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'PHP', 'wp-snapshot' ); ?></div><div class="wps-card__value"><?php echo esc_html( $env['php_version'] ?? '–' ); ?></div></div>
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'Database', 'wp-snapshot' ); ?></div><div class="wps-card__value" style="font-size:13px;"><?php echo esc_html( ( $env['db_type'] ?? '' ) . ' ' . ( $env['db_version'] ?? '' ) ); ?></div></div>
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'HTTPS', 'wp-snapshot' ); ?></div><div class="wps-card__value"><?php echo ! empty( $env['is_https'] ) ? '<span class="badge badge--good">Yes</span>' : '<span class="badge badge--critical">No</span>'; ?></div></div>
			</div>
			<table>
				<tbody>
					<tr><th><?php esc_html_e( 'Server', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $env['server_software'] ?? '–' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'OS', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $env['server_os'] ?? '–' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Timezone', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $env['wp_timezone'] ?? '–' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Locale', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $env['wp_locale'] ?? '–' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'PHP Memory Limit', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $env['php_memory_limit'] ?? '–' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Max Upload', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $env['php_max_upload'] ?? '–' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Multisite', 'wp-snapshot' ); ?></th><td><?php echo ! empty( $env['is_multisite'] ) ? esc_html__( 'Yes', 'wp-snapshot' ) : esc_html__( 'No', 'wp-snapshot' ); ?></td></tr>
				</tbody>
			</table>
		</div>
	</section>

	<!-- Plugins -->
	<section class="wps-share__section">
		<h2 class="wps-share__section-title"><?php
			printf(
				/* translators: 1: active count, 2: total count */
				esc_html__( 'Plugins — %1$d active / %2$d total', 'wp-snapshot' ),
				(int) ( $plug['active_count'] ?? 0 ),
				(int) ( $plug['total_plugins'] ?? 0 )
			);
		?></h2>
		<div class="wps-share__section-body">
			<?php if ( ! empty( $plug['plugins'] ) ) : ?>
			<table>
				<thead><tr><th><?php esc_html_e( 'Plugin', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Version', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Status', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Update', 'wp-snapshot' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $plug['plugins'] as $p ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $p['name'] ); ?></strong><br><small><?php echo esc_html( $p['author'] ); ?></small></td>
							<td><?php echo esc_html( $p['version'] ); ?></td>
							<td><?php echo $p['is_active'] ? '<span class="badge badge--good">' . esc_html__( 'Active', 'wp-snapshot' ) . '</span>' : '<span class="badge badge--info">' . esc_html__( 'Inactive', 'wp-snapshot' ) . '</span>'; ?></td>
							<td><?php echo $p['has_update'] ? '<span class="badge badge--warning">' . esc_html( 'v' . $p['update_version'] ) . '</span>' : ''; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</section>

	<!-- Themes -->
	<section class="wps-share__section">
		<h2 class="wps-share__section-title"><?php esc_html_e( 'Active Theme', 'wp-snapshot' ); ?></h2>
		<div class="wps-share__section-body">
			<?php $active_theme = $themes['active_theme'] ?? []; ?>
			<table>
				<tbody>
					<tr><th><?php esc_html_e( 'Name', 'wp-snapshot' ); ?></th><td><strong><?php echo esc_html( $active_theme['name'] ?? '–' ); ?></strong></td></tr>
					<tr><th><?php esc_html_e( 'Version', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $active_theme['version'] ?? '–' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Author', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $active_theme['author'] ?? '–' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Type', 'wp-snapshot' ); ?></th><td><?php echo ! empty( $active_theme['is_block_theme'] ) ? esc_html__( 'Block Theme (FSE)', 'wp-snapshot' ) : esc_html__( 'Classic Theme', 'wp-snapshot' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Child Theme', 'wp-snapshot' ); ?></th><td><?php echo ! empty( $active_theme['is_child_theme'] ) ? esc_html( $active_theme['parent_theme'] ?? '' ) : esc_html__( 'No', 'wp-snapshot' ); ?></td></tr>
				</tbody>
			</table>
		</div>
	</section>

	<!-- Database -->
	<section class="wps-share__section">
		<h2 class="wps-share__section-title"><?php esc_html_e( 'Database', 'wp-snapshot' ); ?></h2>
		<div class="wps-share__section-body">
			<div class="wps-cards">
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'DB Size', 'wp-snapshot' ); ?></div><div class="wps-card__value" style="font-size:15px;"><?php echo esc_html( $db['total_db_size_human'] ?? '–' ); ?></div></div>
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'Tables', 'wp-snapshot' ); ?></div><div class="wps-card__value"><?php echo esc_html( $db['total_tables'] ?? 0 ); ?></div></div>
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'Autoloaded', 'wp-snapshot' ); ?></div><div class="wps-card__value" style="font-size:15px;"><?php echo esc_html( $db['autoload_size_human'] ?? '–' ); ?></div></div>
				<div class="wps-card"><div class="wps-card__label"><?php esc_html_e( 'Revisions', 'wp-snapshot' ); ?></div><div class="wps-card__value"><?php echo esc_html( $db['revisions_count'] ?? 0 ); ?></div></div>
			</div>
		</div>
	</section>

	<!-- Security -->
	<section class="wps-share__section">
		<h2 class="wps-share__section-title"><?php esc_html_e( 'Security', 'wp-snapshot' ); ?></h2>
		<div class="wps-share__section-body">
			<table>
				<thead><tr><th><?php esc_html_e( 'Check', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Status', 'wp-snapshot' ); ?></th><th><?php esc_html_e( 'Note', 'wp-snapshot' ); ?></th></tr></thead>
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

	<!-- Performance -->
	<section class="wps-share__section">
		<h2 class="wps-share__section-title"><?php esc_html_e( 'Performance', 'wp-snapshot' ); ?></h2>
		<div class="wps-share__section-body">
			<table>
				<tbody>
					<tr><th><?php esc_html_e( 'Object Cache', 'wp-snapshot' ); ?></th><td><?php echo ! empty( $perf['object_cache_active'] ) ? '<span class="badge badge--good">' . esc_html( $perf['object_cache_type'] ) . '</span>' : '<span class="badge badge--info">' . esc_html__( 'None', 'wp-snapshot' ) . '</span>'; ?></td></tr>
					<tr><th><?php esc_html_e( 'Page Cache', 'wp-snapshot' ); ?></th><td><?php echo ! empty( $perf['page_cache_likely'] ) ? '<span class="badge badge--good">' . esc_html__( 'Likely active', 'wp-snapshot' ) . '</span>' : '<span class="badge badge--info">' . esc_html__( 'Not detected', 'wp-snapshot' ) . '</span>'; ?></td></tr>
					<tr><th><?php esc_html_e( 'OPcache', 'wp-snapshot' ); ?></th><td><?php echo ! empty( $perf['opcache_enabled'] ) ? '<span class="badge badge--good">' . esc_html__( 'Enabled', 'wp-snapshot' ) . '</span>' : '<span class="badge badge--info">' . esc_html__( 'Disabled', 'wp-snapshot' ) . '</span>'; ?></td></tr>
					<tr><th><?php esc_html_e( 'Image Editor', 'wp-snapshot' ); ?></th><td><?php echo esc_html( $perf['image_editor'] ?? '–' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Permalink Structure', 'wp-snapshot' ); ?></th><td><code><?php echo esc_html( $perf['permalink_structure'] ?? '–' ); ?></code></td></tr>
				</tbody>
			</table>
		</div>
	</section>

	<footer style="text-align:center; color:#646970; font-size:12px; margin-top:32px;">
		<?php
		printf(
			/* translators: 1: plugin name */
			esc_html__( 'Generated by %s', 'wp-snapshot' ),
			'<strong>WP Snapshot</strong>'
		);
		?>
	</footer>
</div>
</body>
</html>
