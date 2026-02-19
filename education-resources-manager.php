<?php
/**
 * Education Resources Manager
 *
 * @package           GlobalAuthenticity\EducationManager
 * @author            Global Authenticity
 * @copyright         2024 Global Authenticity
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Education Resources Manager
 * Plugin URI:        https://global-authenticity.com/education-resources-manager
 * Description:       Manages educational resources including custom post types, taxonomies, and a REST API for the Global Authenticity platform.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Global Authenticity
 * Author URI:        https://global-authenticity.com
 * Text Domain:       education-resources-manager
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace GlobalAuthenticity\EducationManager;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'ERM_VERSION', '1.0.0' );
define( 'ERM_PLUGIN_FILE', __FILE__ );
define( 'ERM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ERM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ERM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'ERM_TABLE_RESOURCES', 'erm_resource_meta' );

// Autoload classes.
spl_autoload_register(
	function ( $class ) {
		$prefix    = 'GlobalAuthenticity\\EducationManager\\';
		$base_dir  = ERM_PLUGIN_DIR . 'includes/';
		$len       = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . 'class-erm-' . strtolower( str_replace( [ '_', '\\' ], [ '-', '/' ], $relative_class ) ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get plugin instance (singleton).
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — registers hooks.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->register_hooks();
	}

	/**
	 * Load required class files that are not autoloaded.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		require_once ERM_PLUGIN_DIR . 'includes/class-erm-activator.php';
		require_once ERM_PLUGIN_DIR . 'includes/class-erm-deactivator.php';
	}

	/**
	 * Register all plugin hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		register_activation_hook( ERM_PLUGIN_FILE, [ Activator::class, 'activate' ] );
		register_deactivation_hook( ERM_PLUGIN_FILE, [ Deactivator::class, 'deactivate' ] );

		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_action( 'init', [ $this, 'init_components' ] );
		add_action( 'rest_api_init', [ $this, 'init_rest_api' ] );

		if ( is_admin() ) {
			$this->init_admin();
		}
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'education-resources-manager',
			false,
			dirname( ERM_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Initialise post types, taxonomies, and shortcodes.
	 *
	 * @return void
	 */
	public function init_components(): void {
		( new Post_Type() )->register();
		( new Taxonomy() )->register();
		( new Shortcode() )->register();
	}

	/**
	 * Initialise REST API routes.
	 *
	 * @return void
	 */
	public function init_rest_api(): void {
		( new Rest_Api() )->register_routes();
	}

	/**
	 * Initialise admin components.
	 *
	 * @return void
	 */
	private function init_admin(): void {
		( new Admin() )->register();
	}
}

// Boot the plugin.
Plugin::get_instance();
