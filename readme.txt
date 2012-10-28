=== DUB Facebook Albums ===
Contributors: dubtools
Tags: facebook, photo, album, gallery
Requires at least: 3.0.1
Tested up to: 3.4.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Displays public Photos and Albums from a specified Facebook page.

== Description ==

DUB Facebook Albums displays the latest publicly accessible photo albums from a specified Facebook page.

The latest photo album data is automatically retrieved, cached and used to render a photo gallery.

Options include:

* Caching Schedule (Daily, Twice Daily, Hourly)
* Number of Albums (Maximum 25)
* Number of Photos per Album (Maximum 25)
* Thumbnail Width
* Thumbnail Height
* Mimic Facebook CSS Styles
* Use ColorBox JS Plugin to display Photos
* Show "View More" link to your Facebook page
* Manual Cache Flushing

== Installation ==

1. Upload the 'dub-facebook-albums' folder to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Enter your Facebook profile details in Settings > Facebook Albums
4. Place the [dub-facebook-albums] shortcode in the Page content where you want to display the photo albums.

== Frequently Asked Questions ==

= Saving the DUB Facebook Albums Settings page, or using the "Flush Cache" functionality results in an PHP Memory Error? =

This plugin probably requires more memory than your server has allocated. Please ask your server administrator to increase the PHP Memory Limit.

= No Images are displayed? =

This plugin can only access Photos from publicly accessible Albums.

Please consult the 'Help and Troubleshooting' section on the Settings > Facebook Albums page.
