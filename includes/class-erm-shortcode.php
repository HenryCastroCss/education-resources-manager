<?php
/**
 * Shortcode handler.
 *
 * @package GlobalAuthenticity\EducationManager
 */

namespace GlobalAuthenticity\EducationManager;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the [education_resources] shortcode.
 *
 * Usage: [education_resources per_page="6" category="video" difficulty="beginner" featured="true"]
 */
class Shortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'education_resources';

	/**
	 * Register the shortcode and front-end assets.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( self::TAG, [ $this, 'render' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue front-end styles and scripts.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		wp_register_style(
			'erm-public-styles',
			ERM_PLUGIN_URL . 'public/css/public-styles.css',
			[],
			ERM_VERSION
		);

		wp_register_script(
			'erm-public-scripts',
			ERM_PLUGIN_URL . 'public/js/public-scripts.js',
			[ 'jquery' ],
			ERM_VERSION,
			true
		);

		wp_localize_script(
			'erm-public-scripts',
			'ermPublic',
			[
				'restUrl' => esc_url_raw( rest_url( Rest_Api::NAMESPACE . '/resources' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => [
					'loading'  => __( 'Loading resources…', 'education-resources-manager' ),
					'no_items' => __( 'No resources found.', 'education-resources-manager' ),
					'error'    => __( 'Could not load resources.', 'education-resources-manager' ),
					'prev'     => __( 'Previous', 'education-resources-manager' ),
					'next'     => __( 'Next', 'education-resources-manager' ),
				],
			]
		);
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array<string, string>|string $atts    Shortcode attributes.
	 * @param string|null                  $content Enclosed content (unused).
	 * @return string HTML output.
	 */
	public function render( array|string $atts, ?string $content = null ): string {
		$atts = shortcode_atts(
			[
				'per_page'   => get_option( 'erm_resources_per_page', 12 ),
				'category'   => '',
				'tag'        => '',
				'difficulty' => '',
				'featured'   => '',
				'orderby'    => 'date',
				'order'      => 'DESC',
			],
			$atts,
			self::TAG
		);

		// Enqueue assets only when shortcode is used.
		wp_enqueue_style( 'erm-public-styles' );
		wp_enqueue_script( 'erm-public-scripts' );

		$posts = $this->query_resources( $atts );

		if ( empty( $posts ) ) {
			return '<p class="erm-no-results">' . esc_html__( 'No resources found.', 'education-resources-manager' ) . '</p>';
		}

		$db  = new Database();
		$out = '<div class="erm-resources-grid">';

		foreach ( $posts as $post ) {
			$meta = $db->get_resource_meta( $post->ID );
			$out .= $this->render_resource_card( $post, $meta );
		}

		$out .= '</div>';

		return $out;
	}

	/**
	 * Run a WP_Query for resources based on shortcode attributes.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return \WP_Post[]
	 */
	private function query_resources( array $atts ): array {
		$args = [
			'post_type'      => Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['per_page'] ),
			'orderby'        => sanitize_text_field( $atts['orderby'] ),
			'order'          => 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC',
		];

		$tax_query = [];

		if ( ! empty( $atts['category'] ) ) {
			$tax_query[] = [
				'taxonomy' => Taxonomy::CATEGORY,
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $atts['category'] ),
			];
		}

		if ( ! empty( $atts['tag'] ) ) {
			$tax_query[] = [
				'taxonomy' => Taxonomy::TAG,
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $atts['tag'] ),
			];
		}

		if ( $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		if ( ! empty( $atts['difficulty'] ) ) {
			$args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'     => '_erm_difficulty',
					'value'   => sanitize_text_field( $atts['difficulty'] ),
					'compare' => '=',
				],
			];
		}

		return get_posts( $args );
	}

	/**
	 * Render a single resource card.
	 *
	 * @param \WP_Post    $post Post object.
	 * @param object|null $meta Database row.
	 * @return string HTML for one card.
	 */
	private function render_resource_card( \WP_Post $post, ?object $meta ): string {
		$thumbnail   = get_the_post_thumbnail( $post->ID, 'medium', [ 'class' => 'erm-card__thumbnail' ] );
		$title       = get_the_title( $post );
		$excerpt     = get_the_excerpt( $post );
		$permalink   = get_permalink( $post );
		$type        = $meta->resource_type ?? '';
		$difficulty  = $meta->difficulty_level ?? '';
		$duration    = $meta->duration_minutes ? (int) $meta->duration_minutes : 0;
		$is_featured = ! empty( $meta->is_featured );

		$badge = '';
		if ( $is_featured ) {
			$badge = '<span class="erm-card__badge erm-card__badge--featured">' . esc_html__( 'Featured', 'education-resources-manager' ) . '</span>';
		}

		$duration_text = $duration
			? sprintf(
				/* translators: %d: number of minutes */
				_n( '%d min', '%d mins', $duration, 'education-resources-manager' ),
				$duration
			)
			: '';

		$card  = '<article class="erm-card' . ( $is_featured ? ' erm-card--featured' : '' ) . '">';
		$card .= '<div class="erm-card__media">' . $thumbnail . $badge . '</div>';
		$card .= '<div class="erm-card__body">';
		$card .= '<h3 class="erm-card__title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></h3>';

		if ( $excerpt ) {
			$card .= '<p class="erm-card__excerpt">' . esc_html( wp_trim_words( $excerpt, 20 ) ) . '</p>';
		}

		$card .= '<div class="erm-card__meta">';

		if ( $type ) {
			$card .= '<span class="erm-card__type erm-card__type--' . esc_attr( $type ) . '">' . esc_html( $type ) . '</span>';
		}

		if ( $difficulty ) {
			$card .= '<span class="erm-card__difficulty erm-card__difficulty--' . esc_attr( $difficulty ) . '">' . esc_html( $difficulty ) . '</span>';
		}

		if ( $duration_text ) {
			$card .= '<span class="erm-card__duration">' . esc_html( $duration_text ) . '</span>';
		}

		$card .= '</div>'; // .erm-card__meta
		$card .= '</div>'; // .erm-card__body
		$card .= '</article>';

		return $card;
	}
}
