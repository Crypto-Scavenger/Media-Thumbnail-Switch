<?php
/**
 * Admin functionality for Media Thumbnail Switch
 *
 * @package MediaThumbnailSwitch
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin interface and operations
 */
class MTS_Admin {

	/**
	 * Core instance
	 *
	 * @var MTS_Core
	 */
	private $core;

	/**
	 * Database instance
	 *
	 * @var MTS_Database
	 */
	private $database;

	/**
	 * Constructor
	 *
	 * @param MTS_Core     $core     Core instance
	 * @param MTS_Database $database Database instance
	 */
	public function __construct( $core, $database ) {
		$this->core = $core;
		$this->database = $database;
		
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_mts_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_mts_regenerate_thumbnails', array( $this, 'ajax_regenerate_thumbnails' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_management_page(
			__( 'Media Thumbnail Switch', 'media-thumbnail-switch' ),
			__( 'Thumbnail Switch', 'media-thumbnail-switch' ),
			'manage_options',
			'media-thumbnail-switch',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'tools_page_media-thumbnail-switch' !== $hook ) {
			return;
		}
		
		wp_enqueue_style(
			'mts-admin',
			MTS_URL . 'assets/admin.css',
			array(),
			MTS_VERSION
		);
		
		wp_enqueue_script(
			'mts-admin',
			MTS_URL . 'assets/admin.js',
			array( 'jquery' ),
			MTS_VERSION,
			true
		);
		
		wp_localize_script( 'mts-admin', 'mtsData', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'mts_ajax' ),
			'strings' => array(
				'saving' => __( 'Saving...', 'media-thumbnail-switch' ),
				'saved' => __( 'Settings saved successfully!', 'media-thumbnail-switch' ),
				'error' => __( 'An error occurred. Please try again.', 'media-thumbnail-switch' ),
				'regenerating' => __( 'Regenerating thumbnails...', 'media-thumbnail-switch' ),
				'regenerated' => __( 'Thumbnails regenerated successfully!', 'media-thumbnail-switch' ),
				'confirm' => __( 'This will regenerate all thumbnails. This may take a while. Continue?', 'media-thumbnail-switch' ),
			),
		) );
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'media-thumbnail-switch' ) );
		}
		
		$sizes = $this->core->get_all_image_sizes();
		$disabled_sizes = $this->database->get_setting( 'disabled_sizes', array() );
		$disable_all = $this->database->get_setting( 'disable_all', '0' );
		$cleanup_on_uninstall = $this->database->get_setting( 'cleanup_on_uninstall', '0' );
		
		?>
		<div class="wrap">
			<h1><i class="fa fa-image" aria-hidden="true"></i> <?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<div id="mts-message" style="display:none;" class="notice"></div>
			
			<form id="mts-settings-form" method="post" action="">
				<?php wp_nonce_field( 'mts_save_settings', 'mts_nonce' ); ?>
				
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Disable All Thumbnail Generation', 'media-thumbnail-switch' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="disable_all" value="1" <?php checked( '1', $disable_all ); ?> />
								<?php esc_html_e( 'Completely stop WordPress from generating any thumbnails for new uploads', 'media-thumbnail-switch' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, no thumbnails will be generated regardless of other settings.', 'media-thumbnail-switch' ); ?>
							</p>
						</td>
					</tr>
				</table>
				
				<?php if ( ! empty( $sizes['wordpress'] ) ) : ?>
				<h2><?php esc_html_e( 'WordPress Core Thumbnails', 'media-thumbnail-switch' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $sizes['wordpress'] as $name => $data ) : ?>
					<tr>
						<th scope="row">
							<?php echo esc_html( $name ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="disabled_sizes[]" value="<?php echo esc_attr( $name ); ?>" 
									<?php checked( in_array( $name, (array) $disabled_sizes, true ) ); ?> />
								<?php esc_html_e( 'Disable', 'media-thumbnail-switch' ); ?>
							</label>
							<p class="description">
								<?php
								printf(
									/* translators: 1: width, 2: height, 3: crop status */
									esc_html__( 'Size: %1$d x %2$d px, Crop: %3$s', 'media-thumbnail-switch' ),
									$data['width'],
									$data['height'],
									$data['crop'] ? esc_html__( 'Yes', 'media-thumbnail-switch' ) : esc_html__( 'No', 'media-thumbnail-switch' )
								);
								?>
							</p>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
				<?php endif; ?>
				
				<?php if ( ! empty( $sizes['theme'] ) ) : ?>
				<h2><?php esc_html_e( 'Theme Thumbnails', 'media-thumbnail-switch' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $sizes['theme'] as $name => $data ) : ?>
					<tr>
						<th scope="row">
							<?php echo esc_html( $name ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="disabled_sizes[]" value="<?php echo esc_attr( $name ); ?>" 
									<?php checked( in_array( $name, (array) $disabled_sizes, true ) ); ?> />
								<?php esc_html_e( 'Disable', 'media-thumbnail-switch' ); ?>
							</label>
							<p class="description">
								<?php
								printf(
									/* translators: 1: width, 2: height, 3: crop status */
									esc_html__( 'Size: %1$d x %2$d px, Crop: %3$s', 'media-thumbnail-switch' ),
									$data['width'],
									$data['height'],
									$data['crop'] ? esc_html__( 'Yes', 'media-thumbnail-switch' ) : esc_html__( 'No', 'media-thumbnail-switch' )
								);
								?>
							</p>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
				<?php endif; ?>
				
				<?php if ( ! empty( $sizes['plugins'] ) ) : ?>
				<h2><?php esc_html_e( 'Plugin Thumbnails', 'media-thumbnail-switch' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $sizes['plugins'] as $name => $data ) : ?>
					<tr>
						<th scope="row">
							<?php echo esc_html( $name ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="disabled_sizes[]" value="<?php echo esc_attr( $name ); ?>" 
									<?php checked( in_array( $name, (array) $disabled_sizes, true ) ); ?> />
								<?php esc_html_e( 'Disable', 'media-thumbnail-switch' ); ?>
							</label>
							<p class="description">
								<?php
								printf(
									/* translators: 1: width, 2: height, 3: crop status */
									esc_html__( 'Size: %1$d x %2$d px, Crop: %3$s', 'media-thumbnail-switch' ),
									$data['width'],
									$data['height'],
									$data['crop'] ? esc_html__( 'Yes', 'media-thumbnail-switch' ) : esc_html__( 'No', 'media-thumbnail-switch' )
								);
								?>
							</p>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
				<?php endif; ?>
				
				<h2><?php esc_html_e( 'Other Settings', 'media-thumbnail-switch' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Cleanup on Uninstall', 'media-thumbnail-switch' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="cleanup_on_uninstall" value="1" <?php checked( '1', $cleanup_on_uninstall ); ?> />
								<?php esc_html_e( 'Remove all plugin data when uninstalled', 'media-thumbnail-switch' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'If enabled, all settings will be deleted when the plugin is uninstalled.', 'media-thumbnail-switch' ); ?>
							</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button( __( 'Save Settings', 'media-thumbnail-switch' ), 'primary', 'submit', true, array( 'id' => 'mts-save-button' ) ); ?>
			</form>
			
			<hr />
			
			<h2><?php esc_html_e( 'Regenerate Thumbnails', 'media-thumbnail-switch' ); ?></h2>
			<p><?php esc_html_e( 'After changing thumbnail settings, you can regenerate existing thumbnails to apply the changes.', 'media-thumbnail-switch' ); ?></p>
			<p>
				<button type="button" id="mts-regenerate-button" class="button button-secondary">
					<i class="fa fa-refresh" aria-hidden="true"></i> <?php esc_html_e( 'Regenerate All Thumbnails', 'media-thumbnail-switch' ); ?>
				</button>
			</p>
			<div id="mts-regenerate-progress" style="display:none;">
				<p><strong><?php esc_html_e( 'Progress:', 'media-thumbnail-switch' ); ?></strong> <span id="mts-progress-text">0%</span></p>
				<progress id="mts-progress-bar" max="100" value="0" style="width:100%;"></progress>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for saving settings
	 */
	public function ajax_save_settings() {
		if ( ! check_ajax_referer( 'mts_ajax', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed', 'media-thumbnail-switch' ) );
		}
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'media-thumbnail-switch' ) );
		}
		
		// Sanitize and save settings
		$disable_all = isset( $_POST['disable_all'] ) ? '1' : '0';
		$cleanup_on_uninstall = isset( $_POST['cleanup_on_uninstall'] ) ? '1' : '0';
		$disabled_sizes = isset( $_POST['disabled_sizes'] ) && is_array( $_POST['disabled_sizes'] ) 
			? array_map( 'sanitize_text_field', $_POST['disabled_sizes'] ) 
			: array();
		
		$this->database->save_setting( 'disable_all', $disable_all );
		$this->database->save_setting( 'cleanup_on_uninstall', $cleanup_on_uninstall );
		$this->database->save_setting( 'disabled_sizes', $disabled_sizes );
		
		// Clear cache
		$this->core->clear_sizes_cache();
		
		wp_send_json_success( array(
			'message' => __( 'Settings saved successfully!', 'media-thumbnail-switch' ),
		) );
	}

	/**
	 * AJAX handler for regenerating thumbnails
	 */
	public function ajax_regenerate_thumbnails() {
		if ( ! check_ajax_referer( 'mts_ajax', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed', 'media-thumbnail-switch' ) );
		}
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'media-thumbnail-switch' ) );
		}
		
		$batch = isset( $_POST['batch'] ) ? intval( $_POST['batch'] ) : 0;
		$per_page = 10;
		$offset = $batch * $per_page;
		
		// Get attachments
		$args = array(
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'post_status' => 'inherit',
			'posts_per_page' => $per_page,
			'offset' => $offset,
			'fields' => 'ids',
		);
		
		$attachments = get_posts( $args );
		
		if ( empty( $attachments ) ) {
			// Get total count
			$total_args = array(
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'post_status' => 'inherit',
				'posts_per_page' => -1,
				'fields' => 'ids',
			);
			$total = count( get_posts( $total_args ) );
			
			wp_send_json_success( array(
				'done' => true,
				'message' => sprintf(
					/* translators: %d: number of images */
					esc_html__( 'Regenerated thumbnails for %d images.', 'media-thumbnail-switch' ),
					$total
				),
			) );
		}
		
		// Regenerate thumbnails for this batch
		foreach ( $attachments as $attachment_id ) {
			$file = get_attached_file( $attachment_id );
			if ( $file && file_exists( $file ) ) {
				wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file ) );
			}
		}
		
		// Get total count for progress
		$total_args = array(
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'post_status' => 'inherit',
			'posts_per_page' => -1,
			'fields' => 'ids',
		);
		$total = count( get_posts( $total_args ) );
		$processed = min( ( $batch + 1 ) * $per_page, $total );
		$percentage = $total > 0 ? round( ( $processed / $total ) * 100 ) : 100;
		
		wp_send_json_success( array(
			'done' => false,
			'batch' => $batch + 1,
			'percentage' => $percentage,
			'processed' => $processed,
			'total' => $total,
		) );
	}
}
