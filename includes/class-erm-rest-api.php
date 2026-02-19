<?php
/**
 * REST API endpoints.
 *
 * @package GlobalAuthenticity\EducationManager
 */

namespace GlobalAuthenticity\EducationManager;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles custom REST API routes for education resources.
 */
class Rest_Api {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'erm/v1';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		if ( ! get_option( 'erm_enable_rest_api', true ) ) {
			return;
		}

		register_rest_route(
			self::NAMESPACE,
			'/resources',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_resources' ],
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/resources/(?P<id>[\d]+)',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_resource' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'id' => [
							'required'          => true,
							'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
							'sanitize_callback' => 'absint',
							'description'       => __( 'Unique post ID of the resource.', 'education-resources-manager' ),
						],
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/resources/(?P<id>[\d]+)/download',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'record_download' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'id' => [
							'required'          => true,
							'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/stats',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_stats' ],
					'permission_callback' => [ $this, 'check_admin_permission' ],
				],
			]
		);
	}

	/**
	 * GET /erm/v1/resources — return paginated list of resources.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_resources( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$per_page  = $request->get_param( 'per_page' ) ?? get_option( 'erm_resources_per_page', 12 );
		$page      = $request->get_param( 'page' ) ?? 1;
		$type      = $request->get_param( 'resource_type' ) ?? '';
		$level     = $request->get_param( 'difficulty_level' ) ?? '';
		$featured  = $request->get_param( 'featured' );
		$category  = $request->get_param( 'category' ) ?? '';
		$orderby   = $request->get_param( 'orderby' ) ?? 'created_at';
		$order     = $request->get_param( 'order' ) ?? 'DESC';

		$query_args = [
			'post_type'      => Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $per_page,
			'paged'          => (int) $page,
		];

		if ( ! empty( $category ) ) {
			$query_args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => Taxonomy::CATEGORY,
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $category ),
				],
			];
		}

		$posts = get_posts( $query_args );
		$db    = new Database();

		$data = [];
		foreach ( $posts as $post ) {
			$meta   = $db->get_resource_meta( $post->ID );
			$data[] = $this->prepare_resource_response( $post, $meta );
		}

		$total = (int) ( new \WP_Query( array_merge( $query_args, [ 'posts_per_page' => -1, 'fields' => 'ids' ] ) ) )->found_posts;

		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', (int) ceil( $total / (int) $per_page ) );

		return $response;
	}

	/**
	 * GET /erm/v1/resources/:id — return a single resource.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_resource( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || Post_Type::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return new \WP_Error(
				'erm_resource_not_found',
				__( 'Resource not found.', 'education-resources-manager' ),
				[ 'status' => 404 ]
			);
		}

		$db   = new Database();
		$meta = $db->get_resource_meta( $post_id );

		return rest_ensure_response( $this->prepare_resource_response( $post, $meta ) );
	}

	/**
	 * POST /erm/v1/resources/:id/download — increment download counter.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function record_download( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		if ( ! get_option( 'erm_enable_download_count', true ) ) {
			return rest_ensure_response( [ 'recorded' => false ] );
		}

		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || Post_Type::POST_TYPE !== $post->post_type ) {
			return new \WP_Error(
				'erm_resource_not_found',
				__( 'Resource not found.', 'education-resources-manager' ),
				[ 'status' => 404 ]
			);
		}

		$db     = new Database();
		$result = $db->increment_download_count( $post_id );

		return rest_ensure_response( [ 'recorded' => false !== $result ] );
	}

	/**
	 * GET /erm/v1/stats — return aggregate stats (admin only).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_stats( \WP_REST_Request $request ): \WP_REST_Response {
		$db          = new Database();
		$post_counts = wp_count_posts( Post_Type::POST_TYPE );

		return rest_ensure_response(
			[
				'total_meta_records' => $db->count_resources(),
				'published'          => $post_counts->publish ?? 0,
				'draft'              => $post_counts->draft ?? 0,
			]
		);
	}

	/**
	 * Permission callback requiring manage_options capability.
	 *
	 * @return bool
	 */
	public function check_admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Build a standardised response array for a resource.
	 *
	 * @param \WP_Post    $post Post object.
	 * @param object|null $meta Database meta row.
	 * @return array<string, mixed>
	 */
	private function prepare_resource_response( \WP_Post $post, ?object $meta ): array {
		$thumbnail_url = get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null;
		$categories    = wp_get_post_terms( $post->ID, Taxonomy::CATEGORY, [ 'fields' => 'all' ] );
		$tags          = wp_get_post_terms( $post->ID, Taxonomy::TAG, [ 'fields' => 'all' ] );

		return [
			'id'               => $post->ID,
			'title'            => get_the_title( $post ),
			'excerpt'          => get_the_excerpt( $post ),
			'permalink'        => get_permalink( $post ),
			'thumbnail'        => $thumbnail_url,
			'date'             => $post->post_date,
			'modified'         => $post->post_modified,
			'resource_url'     => $meta->resource_url ?? null,
			'resource_type'    => $meta->resource_type ?? null,
			'difficulty_level' => $meta->difficulty_level ?? null,
			'duration_minutes' => $meta->duration_minutes ? (int) $meta->duration_minutes : null,
			'download_count'   => $meta->download_count ? (int) $meta->download_count : 0,
			'is_featured'      => ! empty( $meta->is_featured ),
			'categories'       => is_array( $categories ) ? array_map( fn( $t ) => [ 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug ], $categories ) : [],
			'tags'             => is_array( $tags ) ? array_map( fn( $t ) => [ 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug ], $tags ) : [],
		];
	}

	/**
	 * Define accepted query parameters for the collection endpoint.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_collection_params(): array {
		return [
			'page'             => [
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
				'description'       => __( 'Page number.', 'education-resources-manager' ),
			],
			'per_page'         => [
				'default'           => 12,
				'sanitize_callback' => 'absint',
				'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0 && $v <= 100,
				'description'       => __( 'Items per page (max 100).', 'education-resources-manager' ),
			],
			'resource_type'    => [
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Filter by resource type.', 'education-resources-manager' ),
			],
			'difficulty_level' => [
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Filter by difficulty level.', 'education-resources-manager' ),
			],
			'category'         => [
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Filter by category slug.', 'education-resources-manager' ),
			],
			'featured'         => [
				'default'           => null,
				'sanitize_callback' => fn( $v ) => null === $v ? null : (bool) $v,
				'description'       => __( 'Filter to featured resources only.', 'education-resources-manager' ),
			],
			'orderby'          => [
				'default'           => 'created_at',
				'sanitize_callback' => 'sanitize_text_field',
				'enum'              => [ 'created_at', 'download_count', 'duration_minutes', 'id' ],
				'description'       => __( 'Field to order results by.', 'education-resources-manager' ),
			],
			'order'            => [
				'default'           => 'DESC',
				'sanitize_callback' => 'sanitize_text_field',
				'enum'              => [ 'ASC', 'DESC' ],
				'description'       => __( 'Sort direction.', 'education-resources-manager' ),
			],
		];
	}
}
