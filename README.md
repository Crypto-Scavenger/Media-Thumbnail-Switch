# Media Thumbnail Switch

A WordPress plugin that gives you complete control over thumbnail generation, allowing you to selectively disable thumbnail sizes from WordPress core, themes, and plugins.

## Version 1.0.1 - Bug Fixes

This version includes critical bug fixes for database table creation and improved error handling.

## Features

- **Selective Thumbnail Control**: Choose exactly which thumbnail sizes to generate
- **Source Detection**: Automatically categorizes thumbnails by source (WordPress, Theme, or Plugin)
- **Disable All Option**: Completely stop all thumbnail generation with one click
- **Thumbnail Regeneration**: Batch regenerate thumbnails after changing settings
- **Clean Uninstall**: Option to remove all plugin data on uninstall
- **Performance Optimized**: Uses custom database table to avoid bloating wp_options
- **Security First**: Built following WordPress coding standards with proper nonce verification and capability checks
- **Auto-Recovery**: Automatically creates database table if missing

## What It Does

The plugin hooks into WordPress's thumbnail generation process and prevents selected thumbnail sizes from being created when images are uploaded. This includes:

- **WordPress Core Thumbnails**: thumbnail, medium, medium_large, large
- **Theme Thumbnails**: Any sizes registered by your active theme
- **Plugin Thumbnails**: Any sizes registered by installed plugins (WooCommerce, Elementor, etc.)

This can significantly reduce:
- Server storage usage
- Upload processing time
- Server CPU/memory usage during uploads
- Number of files in your uploads directory

## Installation

1. Upload the `media-thumbnail-switch` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Tools > Thumbnail Switch** to configure settings

**Note**: On activation, the plugin will automatically create its database table. If you see any errors, try:
1. Deactivating and reactivating the plugin
2. Checking your database permissions
3. Manually visiting the plugin's settings page (which will trigger table creation)

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

## How Plugin Thumbnail Detection Works

The plugin uses several methods to identify plugin-generated thumbnail sizes:

1. **Name Pattern Matching**: Looks for plugin names in the size name (e.g., "woocommerce_thumbnail")
2. **Active Plugin Check**: Compares size names against active plugins
3. **Common Patterns**: Recognizes common plugin size patterns (gallery, slider, portfolio, etc.)
4. **Fallback**: Sizes that can't be identified as WordPress or Plugin are categorized as Theme

### Supported Plugin Patterns

The plugin automatically detects thumbnails from popular plugins including:
- WooCommerce
- Elementor
- Jetpack
- BuddyPress
- bbPress
- Yoast SEO
- Gallery plugins
- Slider plugins
- Portfolio plugins

## Troubleshooting

### Database Table Errors

If you see errors like "Table 'wp_mts_settings' doesn't exist":

1. **Automatic Fix**: The plugin will attempt to create the table automatically on the next page load
2. **Manual Fix**: Deactivate and reactivate the plugin
3. **Force Creation**: Visit the plugin's settings page at Tools > Thumbnail Switch
4. **Check Permissions**: Ensure your WordPress database user has CREATE TABLE permissions

### Thumbnails Still Being Generated

If thumbnails are still being created after disabling them:

1. **Clear Cache**: Clear any caching plugins you have installed
2. **Check Settings**: Verify the size is actually checked as disabled
3. **Test Upload**: Upload a new image (existing images won't be affected)
4. **Priority Issue**: Some plugins may add thumbnails with higher priority - try disabling the plugin temporarily
5. **Regenerate**: Use the regenerate function to remove existing thumbnails

### Plugin Thumbnails Not Detected

If plugin-generated thumbnails aren't showing in the Plugin category:

1. **Check Naming**: The plugin uses the size name to detect the source
2. **Manual Check**: Look at the size name - if it contains the plugin name, it should be detected
3. **Theme Category**: Some plugin sizes may appear in the Theme category if detection fails
4. **Still Works**: Even if categorized incorrectly, disabling the size will still prevent generation

### Settings Not Saving

If settings don't persist:

1. **Check Permissions**: Verify you have admin/manage_options capability
2. **Database Error**: Check WordPress debug log for database errors
3. **Browser Cache**: Clear your browser cache and try again
4. **Conflict**: Temporarily disable other plugins to check for conflicts

## File Structure

```
media-thumbnail-switch/
├── media-thumbnail-switch.php    # Main plugin file with auto-recovery
├── README.md                      # This file
├── uninstall.php                  # Handles plugin uninstallation
├── index.php                      # Security stub
├── assets/                        # Plugin assets
│   ├── admin.css                  # Admin page styles
│   ├── admin.js                   # Admin page JavaScript
│   └── index.php                  # Security stub
└── includes/                      # Plugin classes
    ├── class-database.php         # Database operations with auto-creation
    ├── class-core.php             # Core thumbnail filtering logic
    ├── class-admin.php            # Admin interface and AJAX handlers
    └── index.php                  # Security stub
```

## Requirements

- **WordPress**: 6.2 or higher
- **PHP**: 7.4 or higher
- **Permissions**: `manage_options` capability required
- **Database**: CREATE TABLE permissions for installation

## Changelog

### 1.0.1 (Current)
- Fixed database table creation issues
- Added automatic table creation on plugin load
- Improved error handling and logging
- Added table existence checks before all database operations
- Enhanced plugin thumbnail detection with more patterns
- Better fallback handling for missing tables

### 1.0.0
- Initial release
- Selective thumbnail disabling by source
- Disable all thumbnails option
- Batch thumbnail regeneration
- Custom database table for settings
- AJAX-powered admin interface
- Configurable cleanup on uninstall

## License

This plugin is released under the GPL v2 or later license.

## Support

If you encounter issues:

1. Check the Troubleshooting section above
2. Enable WordPress debug logging (WP_DEBUG and WP_DEBUG_LOG)
3. Check your PHP error logs
4. Verify database permissions
5. Try deactivating other plugins to check for conflicts
