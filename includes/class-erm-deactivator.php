<?php
/**
 * Plugin deactivator.
 *
 * @package GlobalAuthenticity\EducationManager
 */

namespace GlobalAuthenticity\EducationManager;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles tasks that run on plugin deactivation.
 */
class Deactivator {

	/**
	 * Run deactivation routines.
	 *
	 * Flushes rewrite rules and clears scheduled events.
	 * Does NOT remove data — that is reserved for uninstall.php.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		flush_rewrite_rules();
		self::clear_scheduled_events();
	}

	/**
	 * Remove any cron events registered by this plugin.
	 *
	 * @return void
	 */
	private static function clear_scheduled_events(): void {
		$hooks = [
			'erm_daily_cleanup',
			'erm_sync_resource_counts',
		];

		foreach ( $hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}
}
