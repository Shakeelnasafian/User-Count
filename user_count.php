<?php
/**
 * Plugin Name: User Count
 * Plugin URI:
 * Description: Displays a post count summary for all Editor-role users in the WordPress admin.
 * Version: 2.0.0
 * Author: Pixako
 * Author URI:
 * License: GPLv2 or later
 * Text Domain: user-count
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'USER_COUNT_VERSION', '2.0.0' );

class User_Count_Plugin {
	/**
	 * Initialize the plugin instance and attach its WordPress hooks.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Registers the plugin's WordPress action hooks.
	 *
	 * Attaches the plugin's asset loader to the `admin_enqueue_scripts` action
	 * and registers the admin menu via the `admin_menu` action.
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
	}

	/**
	 * Enqueues the plugin stylesheet for the User Count admin page.
	 *
	 * Loads the assets/user-count.css stylesheet when the current admin page hook
	 * equals 'toplevel_page_editor_counter'.
	 *
	 * @param string $hook The current admin page hook suffix (from admin_enqueue_scripts).
	 */
	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_editor_counter' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'user-count-style',
			plugin_dir_url( __FILE__ ) . 'assets/user-count.css',
			array(),
			USER_COUNT_VERSION
		);
	}

	/**
	 * Register the plugin's top-level "User Count" admin menu page.
	 *
	 * Adds an admin menu entry with the page title and menu title "User Count",
	 * required capability "manage_options", menu slug "editor_counter",
	 * the class's render_admin_page callback, the "dashicons-admin-users" icon,
	 * and position 110.
	 */
	public function register_admin_menu() {
		add_menu_page(
			__( 'User Count', 'user-count' ),
			__( 'User Count', 'user-count' ),
			'manage_options',
			'editor_counter',
			array( $this, 'render_admin_page' ),
			'dashicons-admin-users',
			110
		);
	}

	/**
	 * Render the plugin's admin page.
	 *
	 * Loads the list table class and includes the admin page template used to display
	 * the User Count admin interface.
	 */
	public function render_admin_page() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-user-count-list-table.php';
		require_once plugin_dir_path( __FILE__ ) . 'templates/admin.php';
	}
}

/**
 * Bootstrap the User Count plugin by instantiating the plugin class.
 *
 * Creates a new User_Count_Plugin instance which sets up the plugin's hooks
 * and prepares its admin UI and asset loading.
 */
function user_count_plugin_init() {
	new User_Count_Plugin();
}
add_action( 'plugins_loaded', 'user_count_plugin_init' );
