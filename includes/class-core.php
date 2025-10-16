<?php
/**
 * Core functionality for Media Thumbnail Switch
 *
 * @package MediaThumbnailSwitch
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles core thumbnail filtering functionality
 */
class MTS_Core {

	/**
	 * Database instance
	 *
	 * @var MTS_Database
	 */
	private $database;

	/**
	 * Constructor
	 *
	 * @param MTS_Database $database Database instance
	 */
	public function __construct( $database ) {
		$this->database = $database;
		
		// High priority to ensure we filter before other plugins
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'filter_image_sizes' ), 999, 2 );
		
		// Also hook into intermediate_image_sizes (for simpler filtering)
		add_filter( 'intermediate_image_sizes', array( $this, 'filter_simple_image_sizes' ), 999 );
	}

	/**
	 * Filter image sizes during thumbnail generation (advanced)
	 *
	 * @param array $sizes    Registered image sizes
	 * @param array $metadata Image metadata
	 * @return array Filtered image sizes
	 */
	public function filter_image_sizes( $sizes, $metadata ) {
		$disable_all = $this->database->get_setting( 'disable_all', '0' );
		
		// If disable all is enabled, return empty array
		if ( '1' === $disable_all ) {
			return array();
		}
		
		$disabled_sizes = $this->database->get_setting( 'disabled_sizes', array() );
		
		if ( ! is_array( $disabled_sizes ) || empty( $disabled_sizes ) ) {
			return $sizes;
		}
		
		// Remove disabled sizes (works for WordPress, theme, and plugin sizes)
		foreach ( $disabled_sizes as $size_name ) {
			if ( isset( $sizes[ $size_name ] ) ) {
				unset( $sizes[ $size_name ] );
			}
		}
		
		return $sizes;
	}

	/**
	 * Filter simple image size names
	 *
	 * @param array $sizes Size names
	 * @return array Filtered size names
	 */
	public function filter_simple_image_sizes( $sizes ) {
		$disable_all = $this->database->get_setting( 'disable_all', '0' );
		
		// If disable all is enabled, return empty array
		if ( '1' === $disable_all ) {
			return array();
		}
		
		$disabled_sizes = $this->database->get_setting( 'disabled_sizes', array() );
		
		if ( ! is_array( $disabled_sizes ) || empty( $disabled_sizes ) ) {
			return $sizes;
		}
		
		// Remove disabled size names
		return array_diff( $sizes, $disabled_sizes );
	}

	/**
	 * Get all registered image sizes with source information
	 *
	 * @return array Image sizes grouped by source
	 */
	public function get_all_image_sizes() {
		$cached = get_transient( 'mts_image_sizes' );
		if ( false !== $cached ) {
			return $cached;
		}
		
		global $_wp_additional_image_sizes;
		
		$sizes = array(
			'wordpress' => array(),
			'theme' => array(),
			'plugins' => array(),
		);
		
		// WordPress core sizes
		$core_sizes = array( 'thumbnail', 'medium', 'medium_large', 'large' );
		foreach ( $core_sizes as $size ) {
			$width = intval( get_option( "{$size}_size_w" ) );
			$height = intval( get_option( "{$size}_size_h" ) );
			$crop = (bool) get_option( "{$size}_crop" );
			
			$sizes['wordpress'][ $size ] = array(
				'width' => $width,
				'height' => $height,
				'crop' => $crop,
			);
		}
		
		// Additional sizes (theme and plugins)
		if ( isset( $_wp_additional_image_sizes ) && is_array( $_wp_additional_image_sizes ) ) {
			foreach ( $_wp_additional_image_sizes as $name => $size_data ) {
				// Try to determine source
				$source = $this->determine_size_source( $name );
				
				$sizes[ $source ][ $name ] = array(
					'width' => isset( $size_data['width'] ) ? intval( $size_data['width'] ) : 0,
					'height' => isset( $size_data['height'] ) ? intval( $size_data['height'] ) : 0,
					'crop' => isset( $size_data['crop'] ) ? (bool) $size_data['crop'] : false,
				);
			}
		}
		
		// Cache for 1 hour
		set_transient( 'mts_image_sizes', $sizes, HOUR_IN_SECONDS );
		
		return $sizes;
	}

	/**
	 * Determine the source of an image size
	 *
	 * @param string $size_name Size name
	 * @return string Source (theme or plugins)
	 */
	private function determine_size_source( $size_name ) {
		// Get the current theme
		$theme = wp_get_theme();
		$theme_name = strtolower( $theme->get( 'TextDomain' ) );
		$theme_slug = strtolower( str_replace( ' ', '-', $theme->get( 'Name' ) ) );
		
		// Check if size name contains theme identifier
		$size_lower = strtolower( $size_name );
		if ( false !== strpos( $size_lower, $theme_name ) || false !== strpos( $size_lower, $theme_slug ) ) {
			return 'theme';
		}
		
		// Check against active plugins
		$active_plugins = get_option( 'active_plugins', array() );
		foreach ( $active_plugins as $plugin ) {
			$plugin_slug = dirname( $plugin );
			if ( empty( $plugin_slug ) || '.' === $plugin_slug ) {
				$plugin_slug = basename( $plugin, '.php' );
			}
			
			if ( false !== strpos( $size_lower, strtolower( $plugin_slug ) ) ) {
				return 'plugins';
			}
		}
		
		// Common plugin patterns
		$plugin_patterns = array(
			'woocommerce' => 'woocommerce',
			'yoast' => 'yoast',
			'elementor' => 'elementor',
			'jetpack' => 'jetpack',
			'buddypress' => 'buddypress',
			'bbpress' => 'bbpress',
			'gallery' => 'plugins',
			'slider' => 'plugins',
			'portfolio' => 'plugins',
		);
		
		foreach ( $plugin_patterns as $pattern => $type ) {
			if ( false !== strpos( $size_lower, $pattern ) ) {
				return 'plugins';
			}
		}
		
		// Default to theme if uncertain (safer assumption)
		return 'theme';
	}

	/**
	 * Clear image sizes cache
	 */
	public function clear_sizes_cache() {
		delete_transient( 'mts_image_sizes' );
	}
}