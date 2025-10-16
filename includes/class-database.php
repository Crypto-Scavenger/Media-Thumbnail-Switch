<?php
/**
 * Database operations for Media Thumbnail Switch
 *
 * @package MediaThumbnailSwitch
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all database operations
 */
class MTS_Database {

	/**
	 * Settings cache
	 *
	 * @var array|null
	 */
	private $settings_cache = null;

	/**
	 * Table exists flag
	 *
	 * @var bool|null
	 */
	private static $table_exists = null;

	/**
	 * Get table name
	 *
	 * @return string
	 */
	private function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . MTS_TABLE_SETTINGS;
	}

	/**
	 * Check if table exists
	 *
	 * @return bool
	 */
	public static function table_exists() {
		if ( null !== self::$table_exists ) {
			return self::$table_exists;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . MTS_TABLE_SETTINGS;
		
		$exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;
		
		self::$table_exists = $exists;
		return $exists;
	}

	/**
	 * Ensure table exists, create if it doesn't
	 */
	public static function ensure_table_exists() {
		if ( ! self::table_exists() ) {
			self::create_table();
			self::$table_exists = null; // Reset cache
		}
	}

	/**
	 * Create database table
	 */
	public static function create_table() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . MTS_TABLE_SETTINGS;
		$charset_collate = $wpdb->get_charset_collate();
		
		// Use direct SQL for table creation (dbDelta can be finicky)
		$sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			setting_key varchar(191) NOT NULL,
			setting_value longtext,
			PRIMARY KEY (id),
			UNIQUE KEY setting_key (setting_key)
		) {$charset_collate};";
		
		$wpdb->query( $sql );
		
		// Verify table was created
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;
		
		if ( ! $table_exists ) {
			error_log( 'MTS Error: Failed to create table ' . $table_name );
		}
		
		self::$table_exists = $table_exists;
		
		return $table_exists;
	}

	/**
	 * Activate plugin
	 */
	public static function activate() {
		// Create table
		$table_created = self::create_table();
		
		if ( ! $table_created ) {
			// Log error but don't block activation
			error_log( 'MTS Activation Error: Could not create database table' );
			return;
		}
		
		// Set default settings only if table was created successfully
		$instance = new self();
		
		// Check if settings already exist before creating defaults
		$existing_settings = $instance->get_all_settings();
		
		if ( empty( $existing_settings ) ) {
			$instance->save_setting( 'cleanup_on_uninstall', '0' );
			$instance->save_setting( 'disable_all', '0' );
			$instance->save_setting( 'disabled_sizes', array() );
		}
	}

	/**
	 * Deactivate plugin
	 */
	public static function deactivate() {
		// Clear transients
		delete_transient( 'mts_image_sizes' );
	}

	/**
	 * Save a setting
	 *
	 * @param string $key   Setting key
	 * @param mixed  $value Setting value
	 * @return bool|WP_Error Success or error object
	 */
	public function save_setting( $key, $value ) {
		// Ensure table exists
		self::ensure_table_exists();
		
		global $wpdb;
		
		$table = $this->get_table_name();
		
		$result = $wpdb->replace(
			$table,
			array(
				'setting_key' => $key,
				'setting_value' => maybe_serialize( $value ),
			),
			array( '%s', '%s' )
		);
		
		if ( false === $result ) {
			error_log( 'MTS DB Error: ' . $wpdb->last_error );
			return new WP_Error( 'db_error', __( 'Failed to save setting', 'media-thumbnail-switch' ) );
		}
		
		// Clear cache
		$this->settings_cache = null;
		
		return true;
	}

	/**
	 * Get a setting
	 *
	 * @param string $key     Setting key
	 * @param mixed  $default Default value
	 * @return mixed Setting value or default
	 */
	public function get_setting( $key, $default = '' ) {
		// Ensure table exists
		if ( ! self::table_exists() ) {
			self::ensure_table_exists();
			
			// If still doesn't exist, return default
			if ( ! self::table_exists() ) {
				error_log( 'MTS Error: Cannot access settings table' );
				return $default;
			}
		}
		
		global $wpdb;
		
		$table = $this->get_table_name();
		
		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT setting_value FROM `{$table}` WHERE setting_key = %s",
				$key
			)
		);
		
		if ( null === $value ) {
			return $default;
		}
		
		return maybe_unserialize( $value );
	}

	/**
	 * Get all settings
	 *
	 * @return array All settings
	 */
	public function get_all_settings() {
		if ( null !== $this->settings_cache ) {
			return $this->settings_cache;
		}
		
		// Ensure table exists
		if ( ! self::table_exists() ) {
			self::ensure_table_exists();
			
			// If still doesn't exist, return empty array
			if ( ! self::table_exists() ) {
				error_log( 'MTS Error: Cannot access settings table' );
				return array();
			}
		}
		
		global $wpdb;
		
		$table = $this->get_table_name();
		
		$results = $wpdb->get_results(
			"SELECT setting_key, setting_value FROM `{$table}`",
			ARRAY_A
		);
		
		$settings = array();
		if ( $results ) {
			foreach ( $results as $row ) {
				$settings[ $row['setting_key'] ] = maybe_unserialize( $row['setting_value'] );
			}
		}
		
		$this->settings_cache = $settings;
		return $settings;
	}

	/**
	 * Delete a setting
	 *
	 * @param string $key Setting key
	 * @return bool Success
	 */
	public function delete_setting( $key ) {
		// Ensure table exists
		self::ensure_table_exists();
		
		global $wpdb;
		
		$table = $this->get_table_name();
		
		$result = $wpdb->delete(
			$table,
			array( 'setting_key' => $key ),
			array( '%s' )
		);
		
		if ( false === $result ) {
			error_log( 'MTS DB Error: ' . $wpdb->last_error );
			return false;
		}
		
		// Clear cache
		$this->settings_cache = null;
		
		return true;
	}

	/**
	 * Clear all settings cache
	 */
	public function clear_cache() {
		$this->settings_cache = null;
	}
}