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
	 * Activate plugin
	 */
	public static function activate() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . MTS_TABLE_SETTINGS;
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = $wpdb->prepare(
			"CREATE TABLE IF NOT EXISTS %i (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				setting_key varchar(191) NOT NULL,
				setting_value longtext,
				PRIMARY KEY (id),
				UNIQUE KEY setting_key (setting_key)
			) %s",
			$table_name,
			$charset_collate
		);
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		// Set default settings
		$instance = new self();
		$instance->save_setting( 'cleanup_on_uninstall', '0' );
		$instance->save_setting( 'disable_all', '0' );
		$instance->save_setting( 'disabled_sizes', array() );
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
		global $wpdb;
		
		$table = $wpdb->prefix . MTS_TABLE_SETTINGS;
		
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
		global $wpdb;
		
		$table = $wpdb->prefix . MTS_TABLE_SETTINGS;
		
		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT setting_value FROM %i WHERE setting_key = %s",
				$table,
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
		
		global $wpdb;
		
		$table = $wpdb->prefix . MTS_TABLE_SETTINGS;
		
		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT setting_key, setting_value FROM %i", $table ),
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
		global $wpdb;
		
		$table = $wpdb->prefix . MTS_TABLE_SETTINGS;
		
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
