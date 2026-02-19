<?php
/**
 * Database abstraction layer.
 *
 * @package GlobalAuthenticity\EducationManager
 */

namespace GlobalAuthenticity\EducationManager;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all direct database interactions using prepared statements.
 */
class Database {

	/**
	 * Resource meta table name with WordPress prefix.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Tracking event log table name with WordPress prefix.
	 *
	 * @var string
	 */
	private string $tracking_table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table          = $wpdb->prefix . ERM_TABLE_RESOURCES;
		$this->tracking_table = $wpdb->prefix . ERM_TABLE_TRACKING;
	}

	/**
	 * Retrieve resource meta for a given post ID.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return object|null Row object or null if not found.
	 */
	public function get_resource_meta( int $post_id ): ?object {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$this->table}` WHERE post_id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$post_id
			)
		);

		return $row ?: null;
	}

	/**
	 * Insert or update resource meta for a given post.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $data    Associative array of column => value pairs.
	 * @return int|false Number of rows affected or false on failure.
	 */
	public function upsert_resource_meta( int $post_id, array $data ): int|false {
		global $wpdb;

		$existing = $this->get_resource_meta( $post_id );

		$data['post_id'] = $post_id;

		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->update(
				$this->table,
				$data,
				[ 'post_id' => $post_id ],
				$this->get_format_map( $data ),
				[ '%d' ]
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->insert(
				$this->table,
				$data,
				$this->get_format_map( $data )
			);
		}

		return $result;
	}

	/**
	 * Delete resource meta for a given post.
	 *
	 * @param int $post_id Post ID.
	 * @return int|false Number of rows deleted or false on failure.
	 */
	public function delete_resource_meta( int $post_id ): int|false {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->delete(
			$this->table,
			[ 'post_id' => $post_id ],
			[ '%d' ]
		);
	}

	/**
	 * Increment the download counter for a resource.
	 *
	 * @param int $post_id Post ID.
	 * @return int|false Number of rows updated or false on failure.
	 */
	public function increment_download_count( int $post_id ): int|false {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$this->table}` SET download_count = download_count + 1 WHERE post_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$post_id
			)
		);
	}

	/**
	 * Query resources with filters, pagination, and ordering.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, object> Array of row objects.
	 */
	public function get_resources( array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'resource_type'    => '',
			'difficulty_level' => '',
			'is_featured'      => null,
			'per_page'         => 12,
			'page'             => 1,
			'orderby'          => 'created_at',
			'order'            => 'DESC',
		];

		$args     = wp_parse_args( $args, $defaults );
		$where    = [];
		$values   = [];

		if ( ! empty( $args['resource_type'] ) ) {
			$where[]  = 'resource_type = %s';
			$values[] = sanitize_text_field( $args['resource_type'] );
		}

		if ( ! empty( $args['difficulty_level'] ) ) {
			$where[]  = 'difficulty_level = %s';
			$values[] = sanitize_text_field( $args['difficulty_level'] );
		}

		if ( null !== $args['is_featured'] ) {
			$where[]  = 'is_featured = %d';
			$values[] = (int) $args['is_featured'];
		}

		$where_clause = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$allowed_orderby = [ 'created_at', 'download_count', 'duration_minutes', 'id' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$per_page = max( 1, (int) $args['per_page'] );
		$offset   = max( 0, ( (int) $args['page'] - 1 ) * $per_page );

		$values[] = $per_page;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$this->table}` {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				...$values
			)
		);

		return $rows ?: [];
	}

	/**
	 * Count resources matching optional filters.
	 *
	 * @param array<string, mixed> $args Filter args (same keys as get_resources).
	 * @return int Total count.
	 */
	public function count_resources( array $args = [] ): int {
		global $wpdb;

		$where  = [];
		$values = [];

		if ( ! empty( $args['resource_type'] ) ) {
			$where[]  = 'resource_type = %s';
			$values[] = sanitize_text_field( $args['resource_type'] );
		}

		if ( ! empty( $args['difficulty_level'] ) ) {
			$where[]  = 'difficulty_level = %s';
			$values[] = sanitize_text_field( $args['difficulty_level'] );
		}

		if ( isset( $args['is_featured'] ) && null !== $args['is_featured'] ) {
			$where[]  = 'is_featured = %d';
			$values[] = (int) $args['is_featured'];
		}

		$where_clause = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		if ( $values ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$this->table}` {$where_clause}",
					...$values
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$this->table}`" );
		}

		return (int) $count;
	}

	/**
	 * Insert a view or download event into the tracking log.
	 *
	 * The visitor IP is anonymised before storage (last IPv4 octet or last
	 * 80 bits of IPv6 are zeroed) to align with GDPR minimisation principles.
	 *
	 * @param int    $resource_id WordPress post ID of the resource.
	 * @param string $action_type Event type — 'view' or 'download'.
	 * @return int|false Inserted row ID, or false on failure.
	 */
	public function log_action( int $resource_id, string $action_type ): int|false {
		global $wpdb;

		$allowed_types = [ 'view', 'download' ];
		if ( ! in_array( $action_type, $allowed_types, true ) ) {
			return false;
		}

		$user_id = get_current_user_id(); // 0 for logged-out visitors.
		$raw_ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			$this->tracking_table,
			[
				'resource_id' => $resource_id,
				'user_id'     => $user_id > 0 ? $user_id : null,
				'action_date' => current_time( 'mysql', true ), // UTC.
				'action_type' => $action_type,
				'user_ip'     => $this->anonymize_ip( $raw_ip ),
			],
			[ '%d', '%d', '%s', '%s', '%s' ]
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Anonymise an IP address by zeroing the host portion.
	 *
	 * - IPv4: last octet set to 0  (e.g. 192.168.1.55  → 192.168.1.0)
	 * - IPv6: last 80 bits zeroed  (keeps the /48 network prefix)
	 *
	 * @param string $ip Raw IP address string.
	 * @return string Anonymised IP, or empty string if invalid.
	 */
	private function anonymize_ip( string $ip ): string {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$binary = inet_pton( $ip );
			$mask   = inet_pton( '255.255.255.0' );
			return inet_ntop( $binary & $mask );
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$binary = inet_pton( $ip );
			// Keep first 6 bytes (/48 prefix), zero remaining 10 bytes.
			$mask = str_repeat( "\xff", 6 ) . str_repeat( "\x00", 10 );
			return inet_ntop( $binary & $mask );
		}

		return '';
	}

	/**
	 * Return total view and download counts from the tracking table.
	 *
	 * @return array{views: int, downloads: int}
	 */
	public function get_tracking_summary(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			"SELECT
				SUM( action_type = 'view' )     AS views,
				SUM( action_type = 'download' ) AS downloads
			FROM `{$this->tracking_table}`"
		);

		return [
			'views'     => isset( $row->views )     ? (int) $row->views     : 0,
			'downloads' => isset( $row->downloads ) ? (int) $row->downloads : 0,
		];
	}

	/**
	 * Return the top N most viewed resources from the tracking table.
	 *
	 * @param int $limit Number of results to return (default 5).
	 * @return array<int, object> Each object has resource_id (int), view_count (int), post_title (string).
	 */
	public function get_top_viewed_resources( int $limit = 5 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					t.resource_id,
					COUNT(*) AS view_count,
					p.post_title
				FROM `{$this->tracking_table}` t
				LEFT JOIN `{$wpdb->posts}` p ON p.ID = t.resource_id
				WHERE t.action_type = 'view'
				GROUP BY t.resource_id, p.post_title
				ORDER BY view_count DESC
				LIMIT %d",
				$limit
			)
		);

		return $rows ?: [];
	}

	/**
	 * Return published resource counts grouped by month for the last N months.
	 *
	 * Missing months are filled in with a count of 0 so the caller always
	 * receives a complete, ordered array of exactly $months entries.
	 *
	 * @param int $months Number of months to look back (default 6).
	 * @return array<string, int> Keys are 'YYYY-MM' strings, values are counts.
	 */
	public function get_resources_per_month( int $months = 6 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					DATE_FORMAT( post_date, '%%Y-%%m' ) AS month,
					COUNT(*) AS count
				FROM `{$wpdb->posts}`
				WHERE post_type   = %s
				  AND post_status = 'publish'
				  AND post_date  >= DATE_SUB( NOW(), INTERVAL %d MONTH )
				GROUP BY DATE_FORMAT( post_date, '%%Y-%%m' )
				ORDER BY month ASC",
				Post_Type::POST_TYPE,
				$months
			)
		);

		// Build a keyed map from the DB result.
		$db_map = [];
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$db_map[ $row->month ] = (int) $row->count;
			}
		}

		// Generate all months in range so none are missing.
		$result = [];
		for ( $i = $months - 1; $i >= 0; $i-- ) {
			$key            = gmdate( 'Y-m', strtotime( "-{$i} months" ) );
			$result[ $key ] = $db_map[ $key ] ?? 0;
		}

		return $result;
	}

	/**
	 * Map data values to wpdb format strings.
	 *
	 * @param array<string, mixed> $data Data to format.
	 * @return array<int, string>
	 */
	private function get_format_map( array $data ): array {
		$int_fields = [ 'post_id', 'duration_minutes', 'download_count', 'is_featured' ];
		$formats    = [];

		foreach ( $data as $key => $value ) {
			$formats[] = in_array( $key, $int_fields, true ) ? '%d' : '%s';
		}

		return $formats;
	}
}
