<?php
/**
 * Admin area functionality.
 *
 * @package GlobalAuthenticity\EducationManager
 */

namespace GlobalAuthenticity\EducationManager;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the plugin admin page, menus, and asset enqueuing.
 */
class Admin {

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'education-resources-manager';

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_erm_save_settings', [ $this, 'ajax_save_settings' ] );
		add_action( 'wp_ajax_erm_get_stats', [ $this, 'ajax_get_stats' ] );
		add_filter( 'plugin_action_links_' . ERM_PLUGIN_BASENAME, [ $this, 'add_action_links' ] );
		add_action( 'before_delete_post', [ $this, 'cleanup_on_delete' ] );
	}

	/**
	 * Register admin menu pages.
	 *
	 * @return void
	 */
	public function add_menu_pages(): void {
		add_menu_page(
			__( 'Education Manager', 'education-resources-manager' ),
			__( 'Education Manager', 'education-resources-manager' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_settings_page' ],
			'dashicons-welcome-learn-more',
			25
		);
	}

	/**
	 * Enqueue admin CSS and JS on plugin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$allowed_hooks = [
			'toplevel_page_' . self::PAGE_SLUG,
			'post.php',
			'post-new.php',
		];

		if ( ! in_array( $hook_suffix, $allowed_hooks, true ) ) {
			return;
		}

		// Only enqueue on our post type screens.
		$screen = get_current_screen();
		if ( in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true )
			&& ( ! $screen || Post_Type::POST_TYPE !== $screen->post_type ) ) {
			return;
		}

		wp_enqueue_style(
			'erm-admin-styles',
			ERM_PLUGIN_URL . 'admin/css/admin-styles.css',
			[],
			ERM_VERSION
		);

		wp_enqueue_script(
			'erm-admin-scripts',
			ERM_PLUGIN_URL . 'admin/js/admin-scripts.js',
			[ 'jquery' ],
			ERM_VERSION,
			true
		);

		wp_localize_script(
			'erm-admin-scripts',
			'ermAdmin',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'erm_admin_nonce' ),
				'i18n'    => [
					'saved'   => __( 'Settings saved.', 'education-resources-manager' ),
					'error'   => __( 'An error occurred. Please try again.', 'education-resources-manager' ),
					'confirm' => __( 'Are you sure?', 'education-resources-manager' ),
				],
			]
		);
	}

	/**
	 * Render the settings admin page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'education-resources-manager' ) );
		}

		require_once ERM_PLUGIN_DIR . 'admin/views/admin-page.php';
	}

	/**
	 * Handle AJAX settings save request.
	 *
	 * @return void
	 */
	public function ajax_save_settings(): void {
		check_ajax_referer( 'erm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'education-resources-manager' ) ], 403 );
		}

		$per_page    = isset( $_POST['resources_per_page'] ) ? absint( $_POST['resources_per_page'] ) : 12;
		$enable_api  = isset( $_POST['enable_rest_api'] ) ? (bool) $_POST['enable_rest_api'] : true;
		$difficulty  = isset( $_POST['default_difficulty'] ) ? sanitize_text_field( wp_unslash( $_POST['default_difficulty'] ) ) : 'beginner';
		$dl_count    = isset( $_POST['enable_download_count'] ) ? (bool) $_POST['enable_download_count'] : true;

		update_option( 'erm_resources_per_page', max( 1, min( 100, $per_page ) ) );
		update_option( 'erm_enable_rest_api', $enable_api );
		update_option( 'erm_default_difficulty', $difficulty );
		update_option( 'erm_enable_download_count', $dl_count );

		wp_send_json_success( [ 'message' => __( 'Settings saved successfully.', 'education-resources-manager' ) ] );
	}

	/**
	 * Handle AJAX stats request.
	 *
	 * @return void
	 */
	public function ajax_get_stats(): void {
		check_ajax_referer( 'erm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'education-resources-manager' ) ], 403 );
		}

		$db    = new Database();
		$total = $db->count_resources();

		$post_counts = wp_count_posts( Post_Type::POST_TYPE );
		$published   = $post_counts->publish ?? 0;

		wp_send_json_success(
			[
				'total_resources'    => $total,
				'published_resources' => $published,
			]
		);
	}

	/**
	 * Add "Settings" link to the plugins list.
	 *
	 * @param array<int, string> $links Existing plugin action links.
	 * @return array<int, string>
	 */
	public function add_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ),
			esc_html__( 'Settings', 'education-resources-manager' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Clean up resource meta when a post is permanently deleted.
	 *
	 * @param int $post_id Post ID being deleted.
	 * @return void
	 */
	public function cleanup_on_delete( int $post_id ): void {
		if ( Post_Type::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		( new Database() )->delete_resource_meta( $post_id );
	}
}
