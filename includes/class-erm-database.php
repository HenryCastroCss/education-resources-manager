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
	 * Full table name with WordPress prefix.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . ERM_TABLE_RESOURCES;
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
