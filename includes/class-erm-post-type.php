<?php
/**
 * Custom post type registration.
 *
 * @package GlobalAuthenticity\EducationManager
 */

namespace GlobalAuthenticity\EducationManager;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the 'erm_resource' custom post type.
 */
class Post_Type {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'erm_resource';

	/**
	 * Register the post type and its meta boxes.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_post_type();
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta_box' ], 10, 2 );
	}

	/**
	 * Register the post type with WordPress.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$labels = [
			'name'                  => _x( 'Education Resources', 'Post type general name', 'education-resources-manager' ),
			'singular_name'         => _x( 'Education Resource', 'Post type singular name', 'education-resources-manager' ),
			'menu_name'             => _x( 'Edu Resources', 'Admin Menu text', 'education-resources-manager' ),
			'name_admin_bar'        => _x( 'Education Resource', 'Add New on Toolbar', 'education-resources-manager' ),
			'add_new'               => __( 'Add New', 'education-resources-manager' ),
			'add_new_item'          => __( 'Add New Resource', 'education-resources-manager' ),
			'new_item'              => __( 'New Resource', 'education-resources-manager' ),
			'edit_item'             => __( 'Edit Resource', 'education-resources-manager' ),
			'view_item'             => __( 'View Resource', 'education-resources-manager' ),
			'all_items'             => __( 'All Resources', 'education-resources-manager' ),
			'search_items'          => __( 'Search Resources', 'education-resources-manager' ),
			'parent_item_colon'     => __( 'Parent Resources:', 'education-resources-manager' ),
			'not_found'             => __( 'No resources found.', 'education-resources-manager' ),
			'not_found_in_trash'    => __( 'No resources found in Trash.', 'education-resources-manager' ),
			'featured_image'        => __( 'Resource Cover Image', 'education-resources-manager' ),
			'set_featured_image'    => __( 'Set cover image', 'education-resources-manager' ),
			'remove_featured_image' => __( 'Remove cover image', 'education-resources-manager' ),
			'use_featured_image'    => __( 'Use as cover image', 'education-resources-manager' ),
			'archives'              => __( 'Resource archives', 'education-resources-manager' ),
			'insert_into_item'      => __( 'Insert into resource', 'education-resources-manager' ),
			'uploaded_to_this_item' => __( 'Uploaded to this resource', 'education-resources-manager' ),
			'filter_items_list'     => __( 'Filter resources list', 'education-resources-manager' ),
			'items_list_navigation' => __( 'Resources list navigation', 'education-resources-manager' ),
			'items_list'            => __( 'Resources list', 'education-resources-manager' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => [ 'slug' => 'education-resources' ],
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-welcome-learn-more',
			'supports'           => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions' ],
			'show_in_rest'       => true,
			'rest_base'          => 'education-resources',
		];

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register meta boxes for the post type.
	 *
	 * @return void
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'erm_resource_details',
			__( 'Resource Details', 'education-resources-manager' ),
			[ $this, 'render_meta_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the resource details meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'erm_save_resource_meta', 'erm_resource_nonce' );

		$db          = new Database();
		$meta        = $db->get_resource_meta( $post->ID );
		$url         = $meta->resource_url ?? '';
		$type        = $meta->resource_type ?? '';
		$difficulty  = $meta->difficulty_level ?? 'beginner';
		$duration    = $meta->duration_minutes ?? 0;
		$is_featured = $meta->is_featured ?? 0;

		$resource_types  = $this->get_resource_types();
		$difficulty_opts = $this->get_difficulty_levels();
		?>
		<table class="form-table erm-meta-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="erm_resource_url"><?php esc_html_e( 'Resource URL', 'education-resources-manager' ); ?></label>
					</th>
					<td>
						<input
							type="url"
							id="erm_resource_url"
							name="erm_resource_url"
							value="<?php echo esc_url( $url ); ?>"
							class="regular-text"
							placeholder="https://"
						/>
						<p class="description"><?php esc_html_e( 'External URL for this resource (PDF, video, article, etc.).', 'education-resources-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="erm_resource_type"><?php esc_html_e( 'Resource Type', 'education-resources-manager' ); ?></label>
					</th>
					<td>
						<select id="erm_resource_type" name="erm_resource_type">
							<option value=""><?php esc_html_e( '— Select type —', 'education-resources-manager' ); ?></option>
							<?php foreach ( $resource_types as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="erm_difficulty_level"><?php esc_html_e( 'Difficulty Level', 'education-resources-manager' ); ?></label>
					</th>
					<td>
						<select id="erm_difficulty_level" name="erm_difficulty_level">
							<?php foreach ( $difficulty_opts as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $difficulty, $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="erm_duration_minutes"><?php esc_html_e( 'Duration (minutes)', 'education-resources-manager' ); ?></label>
					</th>
					<td>
						<input
							type="number"
							id="erm_duration_minutes"
							name="erm_duration_minutes"
							value="<?php echo esc_attr( $duration ); ?>"
							min="0"
							class="small-text"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Featured Resource', 'education-resources-manager' ); ?></th>
					<td>
						<label>
							<input
								type="checkbox"
								name="erm_is_featured"
								value="1"
								<?php checked( $is_featured, 1 ); ?>
							/>
							<?php esc_html_e( 'Mark as a featured resource', 'education-resources-manager' ); ?>
						</label>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save meta box data on post save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save_meta_box( int $post_id, \WP_Post $post ): void {
		// Security checks.
		if ( ! isset( $_POST['erm_resource_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['erm_resource_nonce'] ) ), 'erm_save_resource_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$data = [
			'resource_url'     => isset( $_POST['erm_resource_url'] ) ? esc_url_raw( wp_unslash( $_POST['erm_resource_url'] ) ) : '',
			'resource_type'    => isset( $_POST['erm_resource_type'] ) ? sanitize_text_field( wp_unslash( $_POST['erm_resource_type'] ) ) : '',
			'difficulty_level' => isset( $_POST['erm_difficulty_level'] ) ? sanitize_text_field( wp_unslash( $_POST['erm_difficulty_level'] ) ) : 'beginner',
			'duration_minutes' => isset( $_POST['erm_duration_minutes'] ) ? absint( $_POST['erm_duration_minutes'] ) : 0,
			'is_featured'      => isset( $_POST['erm_is_featured'] ) ? 1 : 0,
		];

		// Validate difficulty level.
		if ( ! array_key_exists( $data['difficulty_level'], $this->get_difficulty_levels() ) ) {
			$data['difficulty_level'] = 'beginner';
		}

		// Validate resource type.
		if ( ! empty( $data['resource_type'] ) && ! array_key_exists( $data['resource_type'], $this->get_resource_types() ) ) {
			$data['resource_type'] = '';
		}

		( new Database() )->upsert_resource_meta( $post_id, $data );
	}

	/**
	 * Get allowed resource types.
	 *
	 * @return array<string, string>
	 */
	private function get_resource_types(): array {
		return [
			'article'     => __( 'Article', 'education-resources-manager' ),
			'video'       => __( 'Video', 'education-resources-manager' ),
			'podcast'     => __( 'Podcast', 'education-resources-manager' ),
			'pdf'         => __( 'PDF / Document', 'education-resources-manager' ),
			'course'      => __( 'Online Course', 'education-resources-manager' ),
			'book'        => __( 'Book', 'education-resources-manager' ),
			'infographic' => __( 'Infographic', 'education-resources-manager' ),
			'tool'        => __( 'Tool / Software', 'education-resources-manager' ),
			'other'       => __( 'Other', 'education-resources-manager' ),
		];
	}

	/**
	 * Get allowed difficulty levels.
	 *
	 * @return array<string, string>
	 */
	private function get_difficulty_levels(): array {
		return [
			'beginner'     => __( 'Beginner', 'education-resources-manager' ),
			'intermediate' => __( 'Intermediate', 'education-resources-manager' ),
			'advanced'     => __( 'Advanced', 'education-resources-manager' ),
		];
	}
}
