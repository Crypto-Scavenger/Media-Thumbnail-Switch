# Media Thumbnail Switch

A WordPress plugin that gives you complete control over thumbnail generation, allowing you to selectively disable thumbnail sizes from WordPress core, themes, and plugins.

## Features

- **Selective Thumbnail Control**: Choose exactly which thumbnail sizes to generate
- **Source Detection**: Automatically categorizes thumbnails by source (WordPress, Theme, or Plugin)
- **Disable All Option**: Completely stop all thumbnail generation with one click
- **Thumbnail Regeneration**: Batch regenerate thumbnails after changing settings
- **Clean Uninstall**: Option to remove all plugin data on uninstall
- **Performance Optimized**: Uses custom database table to avoid bloating wp_options
- **Security First**: Built following WordPress coding standards with proper nonce verification and capability checks

## What It Does

The plugin hooks into WordPress's thumbnail generation process (`intermediate_image_sizes_advanced` filter) and prevents selected thumbnail sizes from being created when images are uploaded. This can significantly reduce:

- Server storage usage
- Upload processing time
- Server CPU/memory usage during uploads
- Number of files in your uploads directory

### How It Works

1. **Detection**: Scans all registered image sizes from WordPress core, active theme, and plugins
2. **Categorization**: Automatically groups sizes by their source for easy management
3. **Filtering**: During image upload, filters out disabled sizes before thumbnail generation
4. **Regeneration**: Provides batch processing to regenerate existing thumbnails based on new settings

## Installation

1. Upload the `media-thumbnail-switch` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Tools > Thumbnail Switch** to configure settings

## Usage

### Configuring Thumbnail Settings

1. Go to **Tools > Thumbnail Switch** in your WordPress admin
2. Review the three categories of thumbnails:
   - **WordPress Core Thumbnails**: Standard sizes (thumbnail, medium, large, etc.)
   - **Theme Thumbnails**: Sizes registered by your active theme
   - **Plugin Thumbnails**: Sizes registered by installed plugins
3. Check the boxes next to sizes you want to disable
4. Click "Save Settings"

### Disabling All Thumbnails

To completely stop thumbnail generation:

1. Enable the "Disable All Thumbnail Generation" checkbox
2. Save settings
3. No thumbnails will be generated for new uploads

### Regenerating Thumbnails

After changing settings, you can regenerate existing thumbnails:

1. Click the "Regenerate All Thumbnails" button
2. Confirm the action
3. Wait for the batch process to complete
4. Progress is shown with a percentage indicator

### Uninstall Options

The plugin includes a cleanup setting:

- **Enabled**: All plugin data (database table and settings) will be removed when the plugin is uninstalled
- **Disabled** (default): Plugin data is preserved after uninstallation

## File Structure

```
media-thumbnail-switch/
├── media-thumbnail-switch.php    # Main plugin file (initialization only)
├── README.md                      # This file
├── uninstall.php                  # Handles plugin uninstallation
├── index.php                      # Security stub
├── assets/                        # Plugin assets
│   ├── admin.css                  # Admin page styles
│   ├── admin.js                   # Admin page JavaScript
│   └── index.php                  # Security stub
└── includes/                      # Plugin classes
    ├── class-database.php         # All database operations
    ├── class-core.php             # Core thumbnail filtering logic
    ├── class-admin.php            # Admin interface and AJAX handlers
    └── index.php                  # Security stub
```

## Database Structure

The plugin creates a custom table `{prefix}_mts_settings` to store configuration:

```sql
CREATE TABLE {prefix}_mts_settings (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    setting_key varchar(191) NOT NULL,
    setting_value longtext,
    PRIMARY KEY (id),
    UNIQUE KEY setting_key (setting_key)
)
```

### Stored Settings

- `cleanup_on_uninstall`: Whether to remove data on uninstall (0 or 1)
- `disable_all`: Whether all thumbnail generation is disabled (0 or 1)
- `disabled_sizes`: Serialized array of disabled thumbnail size names

## Technical Details

### Hooks Used

- `intermediate_image_sizes_advanced`: Filters image sizes during generation
- `admin_menu`: Adds admin menu item
- `admin_enqueue_scripts`: Loads admin assets conditionally
- `wp_ajax_mts_save_settings`: AJAX handler for saving settings
- `wp_ajax_mts_regenerate_thumbnails`: AJAX handler for thumbnail regeneration

### WordPress APIs Used

- **Plugin API**: Action and filter hooks
- **Database API**: All database operations use `$wpdb` with prepared statements
- **Settings API**: Integrated with WordPress admin interface
- **HTTP API**: AJAX requests for async operations
- **Filesystem API**: Image file operations during regeneration

### Security Features

- Nonce verification for all form submissions and AJAX requests
- Capability checks (`manage_options`) on both render and processing
- SQL injection prevention using `$wpdb->prepare()` with placeholders
- Output escaping with appropriate context functions
- Input sanitization for all user data
- CSRF protection on all state-changing operations

### Performance Optimizations

- Lazy loading: Settings loaded only when needed (not in constructor)
- Conditional asset loading: Admin assets only load on plugin pages
- Transient caching: Image size detection cached for 1 hour
- Batch processing: Thumbnail regeneration processes 10 images at a time
- Query optimization: Uses WordPress object cache where available

## Requirements

- **WordPress**: 6.2 or higher (uses `%i` placeholder for table names)
- **PHP**: 7.4 or higher
- **Permissions**: `manage_options` capability required

## Compatibility

- Works with all themes and plugins that properly register image sizes
- Compatible with WordPress multisite installations
- No conflicts with major caching plugins
- Follows WordPress coding standards and best practices

## Frequently Asked Questions

### Will this delete existing thumbnails?

No, the plugin only prevents new thumbnails from being generated. To remove existing thumbnails, use the regenerate function after disabling sizes.

### Can I re-enable sizes later?

Yes, simply uncheck the disabled sizes and save settings. You may want to regenerate thumbnails to create the previously disabled sizes for existing images.

### Does this affect the Media Library?

The Media Library will continue to work normally. Images that don't have certain thumbnail sizes will fall back to the full-size image or the closest available size.

### What happens if I disable sizes my theme needs?

Your theme will display images using the next closest available size or the full-size image. This may affect layout in some cases, so test your site after disabling sizes.

### Is this safe to use on production sites?

Yes, the plugin follows WordPress security best practices. However, always test on a staging site first and ensure your theme doesn't break without specific thumbnail sizes.

## License

This plugin is released under the GPL v2 or later license.

## Changelog

### 1.0.0
- Initial release
- Selective thumbnail disabling by source (WordPress, Theme, Plugin)
- Disable all thumbnails option
- Batch thumbnail regeneration
- Custom database table for settings
- AJAX-powered admin interface
- Configurable cleanup on uninstall
