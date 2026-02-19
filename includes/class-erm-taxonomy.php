<?php
/**
 * Custom taxonomy registration.
 *
 * @package GlobalAuthenticity\EducationManager
 */

namespace GlobalAuthenticity\EducationManager;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers custom taxonomies for the erm_resource post type.
 */
class Taxonomy {

	/**
	 * Category taxonomy slug.
	 *
	 * @var string
	 */
	const CATEGORY = 'erm_category';

	/**
	 * Tag taxonomy slug.
	 *
	 * @var string
	 */
	const TAG = 'erm_tag';

	/**
	 * Register taxonomy hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_category' ] );
		add_action( 'init', [ $this, 'register_tag' ] );
	}

	/**
	 * Register the hierarchical resource category taxonomy.
	 *
	 * @return void
	 */
	public function register_category(): void {
		$labels = [
			'name'                       => _x( 'Resource Categories', 'Taxonomy general name', 'education-resources-manager' ),
			'singular_name'              => _x( 'Resource Category', 'Taxonomy singular name', 'education-resources-manager' ),
			'search_items'               => __( 'Search Categories', 'education-resources-manager' ),
			'all_items'                  => __( 'All Categories', 'education-resources-manager' ),
			'parent_item'                => __( 'Parent Category', 'education-resources-manager' ),
			'parent_item_colon'          => __( 'Parent Category:', 'education-resources-manager' ),
			'edit_item'                  => __( 'Edit Category', 'education-resources-manager' ),
			'update_item'                => __( 'Update Category', 'education-resources-manager' ),
			'add_new_item'               => __( 'Add New Category', 'education-resources-manager' ),
			'new_item_name'              => __( 'New Category Name', 'education-resources-manager' ),
			'menu_name'                  => __( 'Categories', 'education-resources-manager' ),
			'not_found'                  => __( 'No categories found.', 'education-resources-manager' ),
			'no_terms'                   => __( 'No categories', 'education-resources-manager' ),
			'items_list_navigation'      => __( 'Categories list navigation', 'education-resources-manager' ),
			'items_list'                 => __( 'Categories list', 'education-resources-manager' ),
			'back_to_items'              => __( '&larr; Go to Categories', 'education-resources-manager' ),
		];

		$args = [
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'resource-category' ],
			'show_in_rest'      => true,
			'rest_base'         => 'resource-categories',
		];

		register_taxonomy( self::CATEGORY, [ Post_Type::POST_TYPE ], $args );
	}

	/**
	 * Register the flat resource tag taxonomy.
	 *
	 * @return void
	 */
	public function register_tag(): void {
		$labels = [
			'name'                       => _x( 'Resource Tags', 'Taxonomy general name', 'education-resources-manager' ),
			'singular_name'              => _x( 'Resource Tag', 'Taxonomy singular name', 'education-resources-manager' ),
			'search_items'               => __( 'Search Tags', 'education-resources-manager' ),
			'popular_items'              => __( 'Popular Tags', 'education-resources-manager' ),
			'all_items'                  => __( 'All Tags', 'education-resources-manager' ),
			'edit_item'                  => __( 'Edit Tag', 'education-resources-manager' ),
			'update_item'                => __( 'Update Tag', 'education-resources-manager' ),
			'add_new_item'               => __( 'Add New Tag', 'education-resources-manager' ),
			'new_item_name'              => __( 'New Tag Name', 'education-resources-manager' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'education-resources-manager' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'education-resources-manager' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'education-resources-manager' ),
			'not_found'                  => __( 'No tags found.', 'education-resources-manager' ),
			'menu_name'                  => __( 'Tags', 'education-resources-manager' ),
			'back_to_items'              => __( '&larr; Go to Tags', 'education-resources-manager' ),
		];

		$args = [
			'hierarchical'          => false,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => [ 'slug' => 'resource-tag' ],
			'show_in_rest'          => true,
			'rest_base'             => 'resource-tags',
		];

		register_taxonomy( self::TAG, [ Post_Type::POST_TYPE ], $args );
	}
}
