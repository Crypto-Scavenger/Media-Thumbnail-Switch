<?php
/**
 * Uninstall handler for Media Thumbnail Switch
 *
 * @package MediaThumbnailSwitch
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Get cleanup preference
$table = $wpdb->prefix . 'mts_settings';
$cleanup = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT setting_value FROM %i WHERE setting_key = %s",
		$table,
		'cleanup_on_uninstall'
	)
);

if ( '1' === $cleanup ) {
	// Drop plugin table
	$wpdb->query(
		$wpdb->prepare( "DROP TABLE IF EXISTS %i", $table )
	);
	
	// Clean transients
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE %s 
			OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_mts_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_mts_' ) . '%'
		)
	);
	
	// Clear object cache
	wp_cache_flush();
}
