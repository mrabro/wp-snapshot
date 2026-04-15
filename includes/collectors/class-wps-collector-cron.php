<?php
/**
 * Cron Jobs Collector
 *
 * @package WPSnapshot
 */

namespace WPSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Class Collector_Cron
 *
 * Collects information about scheduled WordPress cron jobs.
 */
class Collector_Cron implements Collector_Interface {

	public function get_key(): string {
		return 'cron';
	}

	public function get_title(): string {
		return __( 'Cron Jobs', 'wp-snapshot' );
	}

	public function collect(): array {
		$schedules = wp_get_schedules();
		$cron_jobs = [];

		// _get_cron_array() is a private WordPress function but is the only way
		// to retrieve all scheduled events. It has been stable since WP 2.1.
		// WordPress core itself uses it in Site Health checks and WP-CLI.
		// No fully-public equivalent exists that returns the complete event list.
		$cron_array = function_exists( '_get_cron_array' ) ? _get_cron_array() : [];

		if ( is_array( $cron_array ) ) {
			foreach ( $cron_array as $timestamp => $cron_hooks ) {
				if ( ! is_array( $cron_hooks ) ) {
					continue;
				}
				foreach ( $cron_hooks as $hook_name => $events ) {
					if ( ! is_array( $events ) ) {
						continue;
					}
					foreach ( $events as $event ) {
						$interval_label = '';
						if ( ! empty( $event['schedule'] ) && isset( $schedules[ $event['schedule'] ]['display'] ) ) {
							$interval_label = $schedules[ $event['schedule'] ]['display'];
						}

						$cron_jobs[] = [
							'hook'            => $hook_name,
							'next_run'        => $timestamp,
							'next_run_human'  => wp_date( 'Y-m-d H:i:s', $timestamp ),
							'next_run_diff'   => $timestamp > time()
								? human_time_diff( time(), $timestamp ) . ' ' . __( 'from now', 'wp-snapshot' )
								: human_time_diff( $timestamp, time() ) . ' ' . __( 'ago', 'wp-snapshot' ),
							'overdue'         => $timestamp < time(),
							'schedule'        => $event['schedule'] ?: 'one-time',
							'schedule_label'  => $interval_label ?: __( 'One-time', 'wp-snapshot' ),
							'interval'        => $event['interval'] ?? null,
							'args'            => $event['args'],
						];
					}
				}
			}
		}

		// Sort by next run time ascending.
		usort( $cron_jobs, fn( $a, $b ) => $a['next_run'] <=> $b['next_run'] );

		$overdue_count = count( array_filter( $cron_jobs, fn( $j ) => $j['overdue'] ) );

		return [
			'total_events'        => count( $cron_jobs ),
			'overdue_count'       => $overdue_count,
			'wp_cron_disabled'    => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			'alternate_cron'      => defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON,
			'available_schedules' => $schedules,
			'events'              => $cron_jobs,
		];
	}
}
