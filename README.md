# Blockr Helper Plugin

Plugin Name: Blockr Helper Plugin<br>
Description: This helper plugin is meant to use with Blockr Photo App to extend some the native REST API functions in WordPress<br>
Version: 1.0.6<br>
Requires PHP: 7.4<br>
Author: Henri Tikkanen<br>
Author URI: https://github.com/henritik/<br>
License: License: GPLv2<br>
Tested up to: WordPress 6.4.1<br>
<br>

### Description

This tiny helper plugin is meant to use with my **[Blockr Vue App](https://github.com/henritik/blockr-vue-app)** to extend native Rest API and some other functionalities on the WordPress side.

Also an another plugin called **[Attachment Taxonomies](https://wordpress.org/plugins/attachment-taxonomies/)** by **Felix Arntz** is required (version 1.2.0 or newer) to ensure that **Blockr Vue App** works properly. Optionally, a plugin called **[Media Library Assistant](https://wordpress.org/plugins/media-library-assistant/)** by **David Lingren** can be very useful when there is need to handle bigger amount of photos.

When you want to use OriginStamp timestamping service to proof the originality of your photos, please also check this plugin: **[OriginStamp attachments for WordPress](https://github.com/henritik/osawp-plugin)**

### Installation

1. Download zipped plugin files.
2. Visit **Plugins > Add New > Upload Plugin**, search the zip file from your computer and click **Install Now**.
3. Activate the plugin.

### Upgrade Notice
In order to update the plugin form an earlier version, please do the installation steps 1-2 and allow WordPress to replace existing files.

### Changelog

#### 1.0.6
- Returned accidentally removed REST API route for category image

#### 1.0.5
- Some unnecessary REST API functions removed due to major changes in the Attachment Taxonomies WordPress plugin (versions older than 1.2.0 are no longer supported)

#### 1.0.4
- Added permission callbacks for REST routes
- Tested with WP 6.4.1
- Some minor fixes and cleaning

#### 1.0.3
- Attachment taxonomy data added in search results response

#### 1.0.2
- Changes on Rest route for search media
- Changed attachment taxonomy images to be medium size thumbnails
- Some other minor changes

#### 1.0.1
- Minor fixes
  
#### 1.0.0
- Initial release
