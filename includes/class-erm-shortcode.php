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
 *
 * The PHP renders a filter UI scaffold and empty containers. All data
 * fetching, card rendering, and pagination are handled client-side via
 * fetch() calls to the REST API (public/js/public-scripts.js).
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
			[],
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
					'loading'     => __( 'Loading resources…', 'education-resources-manager' ),
					'no_items'    => __( 'No resources found.', 'education-resources-manager' ),
					'error'       => __( 'Could not load resources. Please try again.', 'education-resources-manager' ),
					'prev'        => __( 'Previous', 'education-resources-manager' ),
					'next'        => __( 'Next', 'education-resources-manager' ),
					'ver_recurso' => __( 'Ver recurso', 'education-resources-manager' ),
					'featured'    => __( 'Featured', 'education-resources-manager' ),
					'page_of'     => __( 'of', 'education-resources-manager' ),
				],
			]
		);
	}

	/**
	 * Render the shortcode output.
	 *
	 * Outputs a filter UI scaffold. JavaScript (public-scripts.js) fetches
	 * data from the REST API and populates the .erm-grid and .erm-pagination
	 * containers without page reload.
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
				'difficulty' => '',
				'featured'   => '',
				'orderby'    => 'date',
				'order'      => 'DESC',
			],
			$atts,
			self::TAG
		);

		// Enqueue assets only when shortcode is used on this page.
		wp_enqueue_style( 'erm-public-styles' );
		wp_enqueue_script( 'erm-public-scripts' );

		// Fetch taxonomy terms server-side for the category select.
		$categories = get_terms(
			[
				'taxonomy'   => Taxonomy::CATEGORY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		// Sanitise shortcode attrs before emitting as data-attributes.
		$per_page   = absint( $atts['per_page'] );
		$category   = sanitize_text_field( $atts['category'] );
		$difficulty = sanitize_text_field( $atts['difficulty'] );
		$featured   = sanitize_text_field( $atts['featured'] );
		$orderby    = sanitize_text_field( $atts['orderby'] );
		$order      = 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC';

		$out  = sprintf(
			'<div class="erm-app" data-per-page="%d" data-category="%s" data-difficulty="%s" data-featured="%s" data-orderby="%s" data-order="%s">',
			$per_page,
			esc_attr( $category ),
			esc_attr( $difficulty ),
			esc_attr( $featured ),
			esc_attr( $orderby ),
			esc_attr( $order )
		);

		// ── Filter bar ────────────────────────────────────────────────────────
		$out .= '<div class="erm-filters" role="search" aria-label="' . esc_attr__( 'Filter resources', 'education-resources-manager' ) . '">';

		// Search input.
		$out .= '<div class="erm-filters__field erm-filters__field--search">';
		$out .= '<label class="erm-filters__label" for="erm-search-' . esc_attr( (string) $per_page ) . '">' . esc_html__( 'Search', 'education-resources-manager' ) . '</label>';
		$out .= '<div class="erm-filters__search-wrap">';
		$out .= '<input type="search" id="erm-search-' . esc_attr( (string) $per_page ) . '" class="erm-filter__search" placeholder="' . esc_attr__( 'Search resources…', 'education-resources-manager' ) . '" autocomplete="off" />';
		$out .= '<span class="erm-filters__search-icon" aria-hidden="true">&#128269;</span>';
		$out .= '</div>';
		$out .= '</div>';

		// Resource type select.
		$out .= '<div class="erm-filters__field">';
		$out .= '<label class="erm-filters__label" for="erm-type">' . esc_html__( 'Type', 'education-resources-manager' ) . '</label>';
		$out .= '<select id="erm-type" class="erm-filter__type">';
		$out .= '<option value="">' . esc_html__( 'All types', 'education-resources-manager' ) . '</option>';
		foreach ( $this->get_filter_resource_types() as $value => $label ) {
			$out .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
		}
		$out .= '</select>';
		$out .= '</div>';

		// Difficulty select.
		$out .= '<div class="erm-filters__field">';
		$out .= '<label class="erm-filters__label" for="erm-difficulty">' . esc_html__( 'Difficulty', 'education-resources-manager' ) . '</label>';
		$out .= '<select id="erm-difficulty" class="erm-filter__difficulty">';
		$out .= '<option value="">' . esc_html__( 'All levels', 'education-resources-manager' ) . '</option>';
		foreach ( $this->get_filter_difficulty_levels() as $value => $label ) {
			$selected = selected( $difficulty, $value, false );
			$out     .= '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
		}
		$out .= '</select>';
		$out .= '</div>';

		// Category select — populated from erm_category taxonomy.
		$out .= '<div class="erm-filters__field">';
		$out .= '<label class="erm-filters__label" for="erm-category">' . esc_html__( 'Category', 'education-resources-manager' ) . '</label>';
		$out .= '<select id="erm-category" class="erm-filter__category">';
		$out .= '<option value="">' . esc_html__( 'All categories', 'education-resources-manager' ) . '</option>';
		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			foreach ( $categories as $term ) {
				$selected = selected( $category, $term->slug, false );
				$out     .= '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) . '</option>';
			}
		}
		$out .= '</select>';
		$out .= '</div>';

		$out .= '</div>'; // .erm-filters

		// ── Loading overlay ───────────────────────────────────────────────────
		$out .= '<div class="erm-loading-overlay" hidden aria-hidden="true">';
		$out .= '<span class="erm-loading-overlay__spinner"></span>';
		$out .= '<span class="erm-loading-overlay__text">' . esc_html__( 'Loading resources…', 'education-resources-manager' ) . '</span>';
		$out .= '</div>';

		// ── Grid container — JS populates this ────────────────────────────────
		$out .= '<div class="erm-grid" aria-live="polite" aria-busy="true"></div>';

		// ── Pagination container — JS populates this ──────────────────────────
		$out .= '<div class="erm-pagination"></div>';

		$out .= '</div>'; // .erm-app

		return $out;
	}

	/**
	 * Resource type options shown in the front-end filter select.
	 *
	 * @return array<string, string>
	 */
	private function get_filter_resource_types(): array {
		return [
			'course'   => __( 'Course', 'education-resources-manager' ),
			'tutorial' => __( 'Tutorial', 'education-resources-manager' ),
			'ebook'    => __( 'eBook', 'education-resources-manager' ),
			'video'    => __( 'Video', 'education-resources-manager' ),
		];
	}

	/**
	 * Difficulty level options shown in the front-end filter select.
	 *
	 * @return array<string, string>
	 */
	private function get_filter_difficulty_levels(): array {
		return [
			'beginner'     => __( 'Beginner', 'education-resources-manager' ),
			'intermediate' => __( 'Intermediate', 'education-resources-manager' ),
			'advanced'     => __( 'Advanced', 'education-resources-manager' ),
		];
	}
}
