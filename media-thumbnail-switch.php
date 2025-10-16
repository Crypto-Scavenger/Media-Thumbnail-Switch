<?php
/**
 * Plugin Name: Media Thumbnail Switch
 * Description: Selectively disable thumbnail generation from WordPress core, themes, and plugins
 * Version: 1.0.1
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: media-thumbnail-switch
 * Domain Path: /languages
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'MTS_VERSION', '1.0.1' );
define( 'MTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'MTS_URL', plugin_dir_url( __FILE__ ) );
define( 'MTS_TABLE_SETTINGS', 'mts_settings' );

// Include classes
require_once MTS_DIR . 'includes/class-database.php';
require_once MTS_DIR . 'includes/class-core.php';
require_once MTS_DIR . 'includes/class-admin.php';

/**
 * Initialize plugin
 */
function mts_init() {
	// Ensure table exists on every load (safety check)
	MTS_Database::ensure_table_exists();
	
	$database = new MTS_Database();
	$core = new MTS_Core( $database );
	
	if ( is_admin() ) {
		$admin = new MTS_Admin( $core, $database );
	}
}
add_action( 'plugins_loaded', 'mts_init' );

/**
 * Activation hook
 */
function mts_activate() {
	MTS_Database::activate();
	
	// Force table creation and verify
	global $wpdb;
	$table_name = $wpdb->prefix . MTS_TABLE_SETTINGS;
	
	if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
		// Try one more time if table doesn't exist
		MTS_Database::create_table();
	}
}
register_activation_hook( __FILE__, 'mts_activate' );

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, array( 'MTS_Database', 'deactivate' ) );