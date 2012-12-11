<?php
	
	/*
	Plugin Name: DUB Facebook Albums
	Description: Displays public Photos and Albums from a specified Facebook page.
	Version: 0.1.2
	Author: DUB Tools
	Author URI: http://www.dubtools.com/
	License: GPLv2 or later
	*/
	
	/*
	Copyright 2012 DUB Tools (email: mail@dubtools.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
	*/
	
	//Function to run when the plugin is activated...
	function dub_fa_activate() {
		
		//If the settings have not been set...
		if (get_option('dub_fa_settings') == false) {
			
			//Set the default settings...
			$dub_fa_defaults = array(
				'dub_fa_user_id' => '',
				'dub_fa_cron' => 'daily',
				'dub_fa_album_count' => 25,
				'dub_fa_photo_count' => 25,
				'dub_fa_thumbnail_width' => 100,
				'dub_fa_thumbnail_height' => 100,
				'dub_fa_styles' => 1,
				'dub_fa_colorbox' => 1,
				'dub_fa_link' => 1
			);
			
			//Save the defaults...
			update_option('dub_fa_settings', $dub_fa_defaults);
			
		}
		
		//Set the album cache...
		update_option('dub_fa_albums', '');
		
	}
	
	//Function to run when the plugin is activated...
	register_activation_hook(__FILE__, 'dub_fa_activate');
	
	//Function to run when the plugin is deactivated...
	function dub_fa_deactivate() {
		
		//Remove all the saved options...
		delete_option('dub_fa_settings');
		delete_option('dub_fa_albums');
		
		//Deschedule any existing updates...
		dub_fa_deschedule();
		
	}
	
	//Function to run when the plugin is deactivated...
	register_deactivation_hook(__FILE__, 'dub_fa_deactivate');
	
	//Function to validate and sanitise the settings...
	function dub_fa_validate($settings) {
		
		//Get the current settings from the database (use them if validation or sanitisation fails)...
		$dub_fa_settings_old = get_option('dub_fa_settings');
		
		//Validate and sanitise the User ID setting...
		if ($settings['dub_fa_user_id'] !== preg_replace("/[^A-Za-z0-9]/", '', $settings['dub_fa_user_id']) || $settings['dub_fa_user_id'] == '') {
			add_settings_error('dub_fa_settings', 'dub_fa_user_id_error', 'Facebook User ID must contain alphanumeric characters - no spaces.', 'error');
			$settings['dub_fa_user_id'] = $dub_fa_settings_old['dub_fa_user_id'];
		}
		
		//Validate and sanitise the Cache setting...
		if ($settings['dub_fa_cron'] == '' || !in_array($settings['dub_fa_cron'], array('daily', 'twicedaily', 'hourly'))) {
			$settings['dub_fa_cron'] = $dub_fa_settings_old['dub_fa_cron'];
		}
		
		//Validate and sanitise the Album Count setting...
		if ($settings['dub_fa_album_count'] !== preg_replace("/[^0-9]/", '', $settings['dub_fa_album_count']) || $settings['dub_fa_album_count'] == '' || $settings['dub_fa_album_count'] < 1 || $settings['dub_fa_album_count'] > 25) {
			add_settings_error('dub_fa_settings', 'dub_fa_album_count_error', 'Number of Albums must be a number between 1 and 25.', 'error');
			$settings['dub_fa_album_count'] = $dub_fa_settings_old['dub_fa_album_count'];
		}
		
		//Validate and sanitise the Photo Count setting...
		if ($settings['dub_fa_photo_count'] !== preg_replace("/[^0-9]/", '', $settings['dub_fa_photo_count']) || $settings['dub_fa_photo_count'] == '' || $settings['dub_fa_photo_count'] < 1 || $settings['dub_fa_photo_count'] > 25) {
			add_settings_error('dub_fa_settings', 'dub_fa_photo_count_error', 'Number of Photos must be a number between 1 and 25.', 'error');
			$settings['dub_fa_photo_count'] = $dub_fa_settings_old['dub_fa_photo_count'];
		}
		
		//Validate and sanitise the Thumbnail Width setting...
		if ($settings['dub_fa_thumbnail_width'] !== preg_replace("/[^0-9]/", '', $settings['dub_fa_thumbnail_width']) || $settings['dub_fa_thumbnail_width'] == '' || $settings['dub_fa_thumbnail_width'] < 10 || $settings['dub_fa_thumbnail_width'] > 130) {
			add_settings_error('dub_fa_settings', 'dub_fa_thumbnail_width_error', 'Thumbnail Width must be a number between 10 and 130.', 'error');
			$settings['dub_fa_thumbnail_width'] = $dub_fa_settings_old['dub_fa_thumbnail_width'];
		}
		
		//Validate and sanitise the Thumbnail Height setting...
		if ($settings['dub_fa_thumbnail_height'] !== preg_replace("/[^0-9]/", '', $settings['dub_fa_thumbnail_height']) || $settings['dub_fa_thumbnail_height'] == '' || $settings['dub_fa_thumbnail_height'] < 10 || $settings['dub_fa_thumbnail_height'] > 130) {
			add_settings_error('dub_fa_settings', 'dub_fa_thumbnail_height_error', 'Thumbnail Height must be a number between 10 and 130.', 'error');
			$settings['dub_fa_thumbnail_height'] = $dub_fa_settings_old['dub_fa_thumbnail_height'];
		}
		
		//Validate and sanitise the Mimic Styles setting...
		if ($settings['dub_fa_styles'] !== '1') {
			$settings['dub_fa_styles'] = 0;
		}
		
		//Validate and sanitise the ColorBox setting...
		if ($settings['dub_fa_colorbox'] !== '1') {
			$settings['dub_fa_colorbox'] = 0;
		}
		
		//Validate and sanitise the Facebook Link setting...
		if ($settings['dub_fa_link'] !== '1') {
			$settings['dub_fa_link'] = 0;
		}
		
		//Validate and sanitise the Flush Cache setting...
		if ($settings['dub_fa_flush'] !== 'flush') {
			$settings['dub_fa_flush'] = '';
		}
		
		//If the user ID has changed...
		if ($settings['dub_fa_user_id'] !== $dub_fa_settings_old['dub_fa_user_id']) {
			
			//Flush the cache...
			$settings['dub_fa_flush'] = 'flush';
			
		}
		
		//Return the validated and sanitised fields...
		return $settings;
		
	}
	
	//Function to register all the available options...
	function dub_fa_settings_init() {
		
		//Register the plugin settings with the WordPress Settings API...
		register_setting('dub_fa_settings', 'dub_fa_settings', 'dub_fa_validate');
		
		//Add the Getting Started section...
		add_settings_section('dub_fa_section_start', 'Getting Started', 'dub_fa_section_start_callback', 'dub-facebook-albums');
		
		//Add the General Settings section...
		add_settings_section('dub_fa_section_general', 'General Settings', '', 'dub-facebook-albums');
		
		//Add the General Settings fields...
		add_settings_field('dub_fa_user_id', 'Facebook ID', 'dub_fa_field_user_id_callback', 'dub-facebook-albums', 'dub_fa_section_general');
		add_settings_field('dub_fa_cron', 'Cache Albums', 'dub_fa_field_cron_callback', 'dub-facebook-albums', 'dub_fa_section_general');
		add_settings_field('dub_fa_album_count', 'Number of Albums', 'dub_fa_field_albums_callback', 'dub-facebook-albums', 'dub_fa_section_general');
		add_settings_field('dub_fa_photo_count', 'Number of Photos per Album', 'dub_fa_field_photos_callback', 'dub-facebook-albums', 'dub_fa_section_general');
		add_settings_field('dub_fa_thumbnail_width', 'Thumbnail Width', 'dub_fa_field_thumbw_callback', 'dub-facebook-albums', 'dub_fa_section_general');
		add_settings_field('dub_fa_thumbnail_height', 'Thumbnail Height', 'dub_fa_field_thumbh_callback', 'dub-facebook-albums', 'dub_fa_section_general');
		add_settings_field('dub_fa_styles', 'Mimic Facebook CSS Styles', 'dub_fa_field_styles_callback', 'dub-facebook-albums', 'dub_fa_section_general');
		add_settings_field('dub_fa_colorbox', 'Use <a href="http://www.jacklmoore.com/colorbox" target="_blank">ColorBox</a> to display Photos', 'dub_fa_field_colorbox_callback', 'dub-facebook-albums', 'dub_fa_section_general');
		add_settings_field('dub_fa_link', 'Show "View More" Link to Facebook', 'dub_fa_field_link_callback', 'dub-facebook-albums', 'dub_fa_section_general');
		
		//Add the Flush Cache section...
		add_settings_section('dub_fa_section_flush', 'Flush Cache', 'dub_fa_section_flush_callback', 'dub-facebook-albums');
		
		//Add the Flush Cache fields...
		add_settings_field('dub_fa_flush', 'Flush Cache', 'dub_fa_field_flush_callback', 'dub-facebook-albums', 'dub_fa_section_flush');
		
		//Add the Troubleshooting section...
		add_settings_section('dub_fa_section_trouble', 'Help and Troubleshooting', 'dub_fa_section_trouble_callback', 'dub-facebook-albums');
		
	}
	
	//Register the options when initialising the admin area...
	add_action('admin_init', 'dub_fa_settings_init');
	
	//Function to output the Getting Started section...
	function dub_fa_section_start_callback() {
		
		//Print an explanation...
		print '<p><strong>Please Note:</strong> This plugin can only access Photos from <em>publicly accessible</em> Albums.</p>';
		print '<p>To insert your Facebook Photo Albums into a page:</p>';
		print '<ol>';
		print '<li>Get your Facebook ID - it should be the text between the 3<sup>rd</sup> and 4<sup>th</sup> slashes within the URL of your Facebook Profile page - Example: <code>http://www.facebook.com/<span style="opacity: 0.4; text-decoration: underline;">YourFacebookID</span>/photo_stream</code></li>';
		print '<li>Enter your Facebook ID into the field below.<br /></li>';
		print '<li>Place the following code into the content of the Page where you want to display the Albums and Photos: <code>[dub-facebook-albums]</code></li>';
		print '</ol>';
		
	}
	
	//Function to output the User ID form field...
	function dub_fa_field_user_id_callback($args) {
		$dub_fa_settings = get_option('dub_fa_settings');
		print 'http://www.facebook.com/<input name="dub_fa_settings[dub_fa_user_id]" type="text" value="' . $dub_fa_settings['dub_fa_user_id'] . '" class="regular-text" />';
	}
	
	//Function to output the Update Time form field...
	function dub_fa_field_cron_callback($args) {
		$dub_fa_settings = get_option('dub_fa_settings');
		print '<input name="dub_fa_settings[dub_fa_cron]" id="cron_daily" type="radio" value="daily" ' . checked($dub_fa_settings['dub_fa_cron'], 'daily', 0) . ' /> <label for="cron_daily">Daily</label><br />';
		print '<input name="dub_fa_settings[dub_fa_cron]" id="cron_twicedaily" type="radio" value="twicedaily" ' . checked($dub_fa_settings['dub_fa_cron'], 'twicedaily', 0) . ' /> <label for="cron_twicedaily">Twice Daily</label><br />';
		print '<input name="dub_fa_settings[dub_fa_cron]" id="cron_hourly" type="radio" value="hourly" ' . checked($dub_fa_settings['dub_fa_cron'], 'hourly', 0) . ' /> <label for="cron_hourly">Hourly</label>';
	}
	
	//Function to output the Album Count form field...
	function dub_fa_field_albums_callback($args) {
		$dub_fa_settings = get_option('dub_fa_settings');
		print '<input name="dub_fa_settings[dub_fa_album_count]" type="text" value="' . $dub_fa_settings['dub_fa_album_count'] . '" class="small-text" /> - Maximum: 25';
	}
	
	//Function to output the Photo Count form field...
	function dub_fa_field_photos_callback($args) {
		$dub_fa_settings = get_option('dub_fa_settings');
		print '<input name="dub_fa_settings[dub_fa_photo_count]" type="text" value="' . $dub_fa_settings['dub_fa_photo_count'] . '" class="small-text" /> - Maximum: 25';
	}
	
	//Function to output the Thumbnail Width form field...
	function dub_fa_field_thumbw_callback($args) {
		$dub_fa_settings = get_option('dub_fa_settings');
		print '<input name="dub_fa_settings[dub_fa_thumbnail_width]" type="text" value="' . $dub_fa_settings['dub_fa_thumbnail_width'] . '" class="small-text" /> pixels - Maximum: 130';
	}
	
	//Function to output the Thumbnail Height form field...
	function dub_fa_field_thumbh_callback($args) {
		$dub_fa_settings = get_option('dub_fa_settings');
		print '<input name="dub_fa_settings[dub_fa_thumbnail_height]" type="text" value="' . $dub_fa_settings['dub_fa_thumbnail_height'] . '" class="small-text" /> pixels - Maximum: 130';
	}
	
	//Function to output the Mimic Styles form field...
	function dub_fa_field_styles_callback($args) {
		$dub_fa_settings = get_option('dub_fa_settings');
		print '<input name="dub_fa_settings[dub_fa_styles]" type="checkbox" value="1" ' . checked($dub_fa_settings['dub_fa_styles'], 1, 0) . ' />';
	}
	
	//Function to output the ColorBox form field...
	function dub_fa_field_colorbox_callback($args) {
		$dub_fa_settings = get_option('dub_fa_settings');
		print '<input name="dub_fa_settings[dub_fa_colorbox]" type="checkbox" value="1" ' . checked($dub_fa_settings['dub_fa_colorbox'], 1, 0) . ' />';
	}
	
	//Function to output the Link form field...
	function dub_fa_field_link_callback($args) {
		$dub_fa_settings = get_option('dub_fa_settings');
		print '<input name="dub_fa_settings[dub_fa_link]" type="checkbox" value="1" ' . checked($dub_fa_settings['dub_fa_link'], 1, 0) . ' />';
	}
	
	//Function to output the Flush Cache section...
	function dub_fa_section_flush_callback() {
		
		//Print an explanation...
		print '<p>This plugin uses caching to minimise the number of requests made to Facebook\'s API.</p>';
		print '<p>Every time the "Cache Albums" interval is reached, the albums will be updated or cached in the database.</p>';
		print '<p>Alternatively, you can flush the album cache manually by ticking the box below and saving this page.</p>';
		
	}
	
	//Function to output the Flush form field...
	function dub_fa_field_flush_callback($args) {
		print '<input name="dub_fa_settings[dub_fa_flush]" type="checkbox" value="flush" />';
	}
	
	//Function to output the Troubleshooting section...
	function dub_fa_section_trouble_callback() {
		
		//Get the plugin settings...
		$dub_fa_settings = get_option('dub_fa_settings');
		
		//Print an explanation...
		print '<p><strong>Saving this page, or using the "Flush Cache" functionality results in an PHP Memory Error?</strong></p>';
		print '<p>This plugin probably requires more memory than your server has allocated. Please ask your server administrator to increase the PHP Memory Limit.</p>';
		print '<p><strong>No Images are displayed?</strong></p>';
		
		//Check the Facebook ID has been set...
		if ($dub_fa_settings['dub_fa_user_id'] !== '' && !empty($dub_fa_settings['dub_fa_user_id'])) {
			
			//Generate a test url...
			$dub_fa_test_profile = 'http://www.facebook.com/' . preg_replace("/[^A-Za-z0-9]/", '', $dub_fa_settings['dub_fa_user_id']);
			$dub_fa_test_albums = 'http://graph.facebook.com/' . preg_replace("/[^A-Za-z0-9]/", '', $dub_fa_settings['dub_fa_user_id']) . '/albums';
			
			//Create a list of troubleshooting points...
			print '<p><strong>Please Note:</strong> This plugin can only access Photos from <em>publicly accessible</em> Albums.</p>';
			print '<p>If no photos are appearing on the page where you have placed the shortcode:</p>';
			print '<ol>';
			print '<li>If you can see the shortcode displayed within the content of the page, please ensure it has been entered correctly: <code>[dub-facebook-albums]</code></li>';
			print '<li>Try using the "Flush Cache" functionality above.</li>';
			print '<li>Ensure you are logged out of your Facebook account, then:<br />';
			print 'Click or open the following URL in new window: <a href="' . $dub_fa_test_albums . '" target="_blank">' . $dub_fa_test_albums . '</a><br />';
			print 'If you recieve an error message, your Photo Albums are most likely <em>not publicly accessible</em>. This plugin can only access Photos from <em>publicly accessible</em> Albums.</li>';
			print '</ol>';
			
		//If the user hasn't set a Facebook ID...
		} else {
			
			//Inform the user to complete the Getting Started instructions...
			print '<p style="color: #CC0000;">Please enter your Facebook ID as per the <strong>Getting Started</strong> section before troubleshooting.</p>';
			
		}
		
	}
	
	//Function to add a menu item for the settings page...
	function dub_fa_menu() {
		add_options_page('DUB Facebook Albums', 'Facebook Albums', 'manage_options', 'dub-facebook-albums', 'dub_fa_settings');
	}
	
	//Add the menu item to the admin area...
	add_action('admin_menu', 'dub_fa_menu');
	
	//Function to generate a settings page...
	function dub_fa_settings() {
		
		//Remove any user who should not be able to access this page...
		if (!current_user_can('manage_options')) { wp_die(__('You do not have sufficient permissions to access this page.')); }
		
		//Get the plugin settings...
		$dub_fa_settings = get_option('dub_fa_settings');
		
		//If the Flush Cache function was used...
		if ($dub_fa_settings['dub_fa_flush'] == 'flush') {
			
			//Flush the cache by updating the albums...
			dub_fa_update_albums();
			
		}
		
		//Deschedule and reschedule any existing updates (can't do it on form validation, because the cron variable will not have saved)...
		dub_fa_deschedule();
		dub_fa_schedule();
		
		//Start generating the settings page...
		print '<div class="wrap">';
		
		//Add an icon and page heading...
		print '<div id="icon-options-general" class="icon32"><br /></div><h2>DUB Facebook Albums</h2>';
		
		//If the server does not have PHP's curl extension installed, show a warning message...
		if (!in_array('curl', get_loaded_extensions())) { add_settings_error('dub_fa_settings', 'dub_fa_curl_error', 'Your server does not appear to have the required PHP cURL Extension installed.', 'error'); }
		
		//Output any validation or sanitisation errors...
		settings_errors();
		
		//Create a form...
		print '<form method="post" action="options.php">';
		
		//Output the Settings API fields...
		settings_fields('dub_fa_settings');
		
		//Output the sections and fields...
		do_settings_sections('dub-facebook-albums');
		
		//Output the Save Changes button...
		submit_button();
		
		//Close the form and finalise the html...
		print '</form></div>';
		
	}
	
	//Function to use PHP's cURL extension and return the result...
	function dub_fa_curl($url) {
		
		//If the server has PHP's curl extension installed...
		if (in_array('curl', get_loaded_extensions())) {
			
			//Initiate the cURL session...
			$dub_fa_curl_session = curl_init();
			
			//Set the cURL url...
			curl_setopt($dub_fa_curl_session, CURLOPT_URL, $url);
			
			//Set the cURL request to return the data rather than display it...
			curl_setopt($dub_fa_curl_session, CURLOPT_RETURNTRANSFER, 1);
			
			//Make the request...
			$dub_fa_curl_result = curl_exec($dub_fa_curl_session);
			
			//Close the session...
			curl_close($dub_fa_curl_session);
			
			//Return the results decode as into PHP...
			return json_decode($dub_fa_curl_result, true);
			
		}
		
	}
	
	//Function to get the latest albums from a Facebook user, and cache them in the database...
	function dub_fa_update_albums() {
		
		//Get the plugin settings...
		$dub_fa_settings = get_option('dub_fa_settings');
		
		//If the plugin settings have been set...
		if ($dub_fa_settings !== false) {
			
			//If the User ID setting has been set...
			if (!empty($dub_fa_settings['dub_fa_user_id'])) {
				
				//Create the JSON request URL (sanitize the User ID again just to be sure) - (add an extra 3 albums so we can remove the system albums below)...
				$dub_fa_json_url = 'http://graph.facebook.com/' . preg_replace("/[^A-Za-z0-9]/", '', $dub_fa_settings['dub_fa_user_id']) . '/albums?fields=photos.limit(' . preg_replace("/[^0-9]/", '', $dub_fa_settings['dub_fa_photo_count']) . ').fields(source,picture,id),name,id,type&limit=' . (preg_replace("/[^0-9]/", '', $dub_fa_settings['dub_fa_album_count']) + 3);
				
				//Start a collection of requests...
				$dub_fa_requests = array();
				
				//Get the data from the JSON url...
				$dub_fa_request = dub_fa_curl($dub_fa_json_url);
				
				//Start a list of albums...
				$dub_fa_albums = array();
				
				//Start a count of albums...
				$dub_fa_album_count = 0;
					
				//For each album in the request...
				foreach($dub_fa_request['data'] as $album_index => $album_value) {
					
					//If this album isn't one of the system albums, and it's still under the limit...
					if (!in_array($album_value['type'], array('wall', 'friends_walls', 'profile')) && $dub_fa_album_count < preg_replace("/[^0-9]/", '', $dub_fa_settings['dub_fa_album_count'])) {
						
						//Increase the counter...
						$dub_fa_album_count ++;
						
						//Start a list of photos...
						$dub_fa_photos = array();
						
						//For each photo in the album...
						foreach($album_value['photos']['data'] as $photo_index => $photo_value) {
							
							//Add the photo to the list of photos...
							$dub_fa_photos[$photo_value['id']] = array(
								'source' => $photo_value['source'],
								'picture' => $photo_value['picture']
							);
							
						}
						
						//Add the album to the list of albums...
						$dub_fa_albums[$album_value['id']] = array(
							'name' => $album_value['name'],
							'type' => $album_value['type'],
							'photos' => $dub_fa_photos
						);
						
					}
					
				}
				
				//Cache the albums...
				update_option('dub_fa_albums', $dub_fa_albums);
				
			}
			
		}
		
	}
	
	//Function to add the scheduled update...
	function dub_fa_schedule() {
		
		//Get the plugin settings...
		$dub_fa_settings = get_option('dub_fa_settings');
		
		//Schedule the update...
		wp_schedule_event(current_time('timestamp'), $dub_fa_settings['dub_fa_cron'], 'dub_fa_update_albums');
		
	}
	
	//Function to remove the scheduled update...
	function dub_fa_deschedule() {
		
		//Deschedule the update...
		wp_clear_scheduled_hook('dub_fa_update_albums');
		
	}
	
	//Function to enqueue CSS and JS files...
	function dub_fa_enqueue() {
		
		//Get the plugin settings...
		$dub_fa_settings = get_option('dub_fa_settings');
		
		//If CSS Styles have been requested...
		if ($dub_fa_settings['dub_fa_styles'] == 1) {
			
			//Load the plugin's CSS...
			wp_register_style('dub-facebook-albums', plugins_url('dub-facebook-albums.css', __FILE__));
			wp_enqueue_style('dub-facebook-albums');
			
		}
		
		//If the Javascript ColorBox has been requested...
		if ($dub_fa_settings['dub_fa_colorbox'] == 1) {
			
			//Load jQuery into the site...
			wp_enqueue_script('jquery');
			
			//Load ColorBox's JS and CSS...
			wp_register_script('colorbox', plugins_url('colorbox/jquery.colorbox-min.js', __FILE__), array('jquery'));
			wp_register_style('colorbox', plugins_url('colorbox/colorbox.css', __FILE__));
			wp_enqueue_script('colorbox');
			wp_enqueue_style('colorbox');
			
			//Load this plugin's JS file...
			wp_register_script('dub-facebook-albums', plugins_url('dub-facebook-albums.js', __FILE__), array('jquery'));
			wp_enqueue_script('dub-facebook-albums');
			
		}
		
	}
	
	//Add the enqueue styles hook...
	add_action('wp_enqueue_scripts', 'dub_fa_enqueue');
	
	//Function to present the albums on a page...
	function dub_fa_display() {
		
		//Get the plugin settings...
		$dub_fa_settings = get_option('dub_fa_settings');
		
		//Get the cached albums...
		$dub_fa_albums = get_option('dub_fa_albums');
		
		//Start a container...
		print '<div class="dub-fa">';
		
		//If this is an album page...
		if ($_GET['album']) {
			
			//Sanitise the album number...
			$dub_fa_album_id = preg_replace("/[^0-9]/", '', $_GET['album']);
			
			//Get the requested album...
			$dub_fa_album = $dub_fa_albums[$dub_fa_album_id];
			
			//Insert a title and a back link...
			print '<p class="dub-fa-name">' . $dub_fa_album['name'] . '</p>';
			print '<p class="dub-fa-back"><a href="javascript:history.go(-1)">&laquo; Back to Albums</a></p>';
			
			//For each photo...
			foreach ($dub_fa_album['photos'] as $photo_index => $photo_value) {
				
				//Generate the photo items...
				print '<div class="dub-fa-item">';
				print '<a class="dub-fa-thumb dub-fa-photo" rel="dub-fa-photos" href="' . $photo_value['source'] . '" style="background-image: url(\'' . $photo_value['picture'] . '\'); display: block; height: ' . $dub_fa_settings['dub_fa_thumbnail_height'] . 'px; width: ' . $dub_fa_settings['dub_fa_thumbnail_width'] . 'px;"></a>';
				print '</div>';
				
			}
			
			//Clear the floats...
			print '<div class="dub-fa-clear" style="clear: both; height: 0px; line-height: 0; overflow: hidden;"></div>';
			
			//Insert a back link...
			print '<p class="dub-fa-back"><a href="javascript:history.go(-1)">&laquo; Back to Albums</a></p>';
			
		//Or, if this is the main page...
		} else {
			
			//For each album...
			foreach ($dub_fa_albums as $album_index => $album_value) {
				
				//Get the first image as a cover photo...
				$dub_fa_thumbnail = reset($album_value['photos']);
				
				//Generate a link to the album page...
				$dub_fa_thumblink = $_SERVER["REQUEST_URI"] . '?album=' . $album_index;
				
				//Generate the photo albums...
				print '<div class="dub-fa-item">';
				print '<a class="dub-fa-thumb" href="' . $dub_fa_thumblink . '" style="background-image: url(\'' . $dub_fa_thumbnail['picture'] . '\'); display: block; height: ' . $dub_fa_settings['dub_fa_thumbnail_height'] . 'px; width: ' . $dub_fa_settings['dub_fa_thumbnail_width'] . 'px;" title="' . $album_value['name'] . '">' . $album_value['name'] . '</a>';
				print '<div class="dub-fa-title" style="width: ' . $dub_fa_settings['dub_fa_thumbnail_width'] . 'px;"><a href="' . $dub_fa_thumblink . '">' . $album_value['name'] . '</a></div>';
				print '</div>';
				
			}
			
			//Clear the floats...
			print '<div class="dub-fa-clear" style="clear: both; height: 0px; line-height: 0; overflow: hidden;"></div>';
			
		}
		
		//If the Link to Facebook was enabled...
		if ($dub_fa_settings['dub_fa_link'] == '1') {
			
			//Output the Facebook link...
			print '<p class="dub-fa-link"><a href="http://www.facebook.com/' . preg_replace("/[^A-Za-z0-9]/", '', $dub_fa_settings['dub_fa_user_id']) . '"><img src="' . plugin_dir_url(__FILE__) . '/dub-fa-link.png" />View more Albums and Photos on Facebook</a></p>';
			
		}
		
		//Close the container...
		print '</div>';
		
	}
	
	//Add the shortcode to WordPress so it can be used on a page...
	add_shortcode('dub-facebook-albums', 'dub_fa_display');
	
?>