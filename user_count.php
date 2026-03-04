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
	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
	}

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

	public function render_admin_page() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-user-count-list-table.php';
		require_once plugin_dir_path( __FILE__ ) . 'templates/admin.php';
	}
}

function user_count_plugin_init() {
	new User_Count_Plugin();
}
add_action( 'plugins_loaded', 'user_count_plugin_init' );
