<?php
/**
 * Plugin activator.
 *
 * @package GlobalAuthenticity\EducationManager
 */

namespace GlobalAuthenticity\EducationManager;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles tasks that run on plugin activation.
 */
class Activator {

	/**
	 * Run activation routines.
	 *
	 * Creates database tables, sets default options, and flushes rewrite rules.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		self::create_tables();
		self::set_default_options();

		// Register post type so rewrite rules are available to flush.
		( new Post_Type() )->register();
		( new Taxonomy() )->register();
		flush_rewrite_rules();

		update_option( 'erm_version', ERM_VERSION );
	}

	/**
	 * Create custom database tables using dbDelta().
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . ERM_TABLE_RESOURCES;

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			resource_url varchar(2083) NOT NULL DEFAULT '',
			resource_type varchar(50) NOT NULL DEFAULT '',
			difficulty_level varchar(20) NOT NULL DEFAULT 'beginner',
			duration_minutes int(11) unsigned NOT NULL DEFAULT 0,
			download_count bigint(20) unsigned NOT NULL DEFAULT 0,
			is_featured tinyint(1) NOT NULL DEFAULT 0,
			meta_json longtext DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY post_id (post_id),
			KEY resource_type (resource_type),
			KEY difficulty_level (difficulty_level),
			KEY is_featured (is_featured)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Set default plugin options if not already configured.
	 *
	 * @return void
	 */
	private static function set_default_options(): void {
		$defaults = [
			'erm_resources_per_page'     => 12,
			'erm_enable_rest_api'        => true,
			'erm_default_difficulty'     => 'beginner',
			'erm_enable_download_count'  => true,
		];

		foreach ( $defaults as $option => $value ) {
			if ( false === get_option( $option ) ) {
				add_option( $option, $value );
			}
		}
	}
}
