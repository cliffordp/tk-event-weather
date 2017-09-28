<?php
/*
	Plugin Name: TK Event Weather
	Plugin URI: http://tourkick.com/plugins/tk-event-weather/?utm_source=plugin-uri-link&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather
	Version: 1.4.6
	Author: TourKick (Clifford Paulick)
	Author URI: http://tourkick.com/?utm_source=author-uri-link&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather
	Description: Display beautiful, accurate, and free hourly weather forecasts between a start and end time. Perfect for event calendars.
	Text Domain: tk-event-weather
	License: GPL version 3 or any later version
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/*
	"WordPress Plugin Template" Copyright (C) 2016 Michael Simpson	(email : michael.d.simpson@gmail.com)

	This following part of this file is part of WordPress Plugin Template for WordPress.

	WordPress Plugin Template is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	WordPress Plugin Template is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Contact Form to Database Extension.
	If not, see http://www.gnu.org/licenses/gpl-3.0.html
*/


/** TODO:
 * - sign up for newsletter
 * - refresh screenshots
 * - delete leftover TkEventW__Plugin::$customizer_flag array keys like ^forecast_io%
 * - https://github.com/cliffordp/tk-event-weather/issues/9 -- Add option to query weather API for more than a single day for events that span more than 1 calendar day. NOTE: for the current version of the weather API, each calendar day costs 1 API request. For example, an event spanning Jan 1 at 10pm through Jan 3 at 7am will cost 3 API calls. -- maybe make it an option
 * - add Customizer option to input a Post ID to default to when viewing the customizer from the plugin's Settings Button (could auto-set it if an Event exists)
 * - look into https://developer.wordpress.org/plugins/the-basics/uninstall-methods/
 * - why it currently states "will also delete its data"
 * - should we add an option to "delete its data" on uninstall?
 * - truncate seconds off all timestamps? -- avoid 10pm hour + 10pm sunset, like http://cl.ly/430H1J0p2R07
 * - use more data from API, like 'summary' text as a title element somewhere
 * - the Dark Sky API "apparentTemperature" value is the "feels like" temperature
 * - inspiration from http://darkskyapp.com/
 * - Should be taken care of as of v1.3 because of using timezones instead of offsets :) -- Handling of timezone offsets that aren't full hours -- e.g. Eucla Australia is UTC+8:45 -- https://en.wikipedia.org/wiki/List_of_UTC_time_offsets#UTC.2B08:45.2C_H.2A -- currently works well enough probably but outputs '4am' instead of '4:45am' -- does it really need to be fixed?
 * - time of day versions of icons (night/day)
 * - https://github.com/cliffordp/tk-event-weather/issues/3#issuecomment-174607313
 * - https://github.com/cliffordp/tk-event-weather/issues/3#issuecomment-178440095
 * - force debug report to be in English (i.e. not translatable)
 * - Debug Mode enhancements: plugin settings, filters/actions in use
 * - "current" / "right now" if event is currently happening
 * - add 'demo' option to output all icons (e.g. for styling/testing)
 * - weather advisory alerts (only happen in real-time so probably not going to happen)
 * - color options for styling SVGs (e.g. yellow sun with gray cloud) -- not possible with as-is SVGs because they're flattened (no CSS classes to "fill")
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Defines
 */
// Required for Template Loader. Also used elsewhere.
define( 'TK_EVENT_WEATHER_PLUGIN_ROOT_DIR', plugin_dir_path( __FILE__ ) ); // e.g. /.../.../example-com/wp-content/plugins/tk-event-weather/

// added for consistency to match DIR
define( 'TK_EVENT_WEATHER_PLUGIN_ROOT_URL', plugin_dir_url( __FILE__ ) ); // e.g. http://example.com/wp-content/plugins/tk-event-weather/includes/

// used for adding Settings link to plugins.php
// https://developer.wordpress.org/reference/functions/plugin_basename/
define( 'TK_EVENT_WEATHER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // e.g. tk-event-weather/tk-event-weather.php

// used by core plugin and by add-on implementations of Freemius
define( 'TK_EVENT_WEATHER_FREEMIUS_START_FILE', dirname( __FILE__ ) . '/includes/vendor/freemius/start.php' );


// adapted from http://wpbackoffice.com/get-current-woocommerce-version-number/
function tk_event_weather_version() {
	// If get_plugins() isn't available, require it
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	// Create the plugins folder and file variables
	$plugin_folder = get_plugins( '/tk-event-weather' );
	$plugin_file   = 'tk-event-weather.php';

	// If the plugin version number is set, return it
	if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
		return $plugin_folder[$plugin_file]['Version'];
	} else {
		// Otherwise return null
		return null;
	}

}


function tk_event_weather_terms_agreement_text() {
	return sprintf(
		__( 'By using this plugin, you agree to %s and %s Terms.', 'tk-event-weather' ),
		'<a target="_blank" href="http://tourkick.com/terms/?utm_source=terms_agreement_text&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather">TourKick\'s</a>',
		'<a target="_blank" href="https://freemius.com/terms/">Freemius\'</a>'
	);
}

// Implement Freemius
//
// Create a helper function for easy SDK access.
function tk_event_weather_freemius() {
	global $tk_event_weather_freemius;

	if ( ! isset( $tk_event_weather_freemius ) && defined( 'TK_EVENT_WEATHER_FREEMIUS_START_FILE' ) && file_exists( TK_EVENT_WEATHER_FREEMIUS_START_FILE ) ) {
		// Include Freemius SDK.
		require_once TK_EVENT_WEATHER_FREEMIUS_START_FILE;

		$tk_event_weather_freemius = fs_dynamic_init(
			array(
				'id'             => '240',
				'slug'           => 'tk-event-weather',
				'public_key'     => 'pk_b6902fc0051f10b5e36bea21fb0e7',
				'is_premium'     => false,
				'has_addons'     => true,
				'has_paid_plans' => false,
				'menu'           => array(
					'slug'   => 'tkeventweather__pluginsettings',
					'parent' => array(
						'slug' => 'options-general.php',
					),
				),
			)
		);
	}

	return $tk_event_weather_freemius;
}

// Init Freemius.
tk_event_weather_freemius();
do_action( 'tk_event_weather_loaded' );

// Freemius: customize the new user message
function tk_event_weather_freemius_custom_connect_message(
	$message,
	$user_first_name,
	$plugin_title,
	$user_login,
	$site_link,
	$freemius_link
) {
	$tk_custom_message = sprintf(
		__fs( 'hey-x' ) . '<br><br>' . __( 'The <strong>%2$s</strong> plugin is ready to go! Want to help make %2$s more awesome? Securely share some data to get the best experience and stay informed.', 'tk-event-weather' ),
		$user_first_name,
		$plugin_title,
		'<strong>' . $user_login . '</strong>',
		$site_link,
		$freemius_link
	);

	$tk_custom_message .= '<br><small>' . tk_event_weather_terms_agreement_text() . '</small>';

	return $tk_custom_message;
}

tk_event_weather_freemius()->add_filter( 'connect_message', 'tk_event_weather_freemius_custom_connect_message', 10, 6 );


function tk_event_weather_freemius_plugin_icon() {
	return TK_EVENT_WEATHER_PLUGIN_ROOT_DIR . 'images/icon.svg';
}

tk_event_weather_freemius()->add_filter( 'plugin_icon', 'tk_event_weather_freemius_plugin_icon' );


function TkEventW__minimalRequiredPhpVersion() {
	return '5.4';
}

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying an error message on the Admin page
 */
function TkEventW__noticePhpVersionWrong() {
	echo '<div class="updated fade">' .
		__( 'Error: plugin "TK Event Weather" requires a newer version of PHP to be running.', 'tk-event-weather' ) .
		'<br/>' . __( 'Minimal version of PHP required: ', 'tk-event-weather' ) . '<strong>' . TkEventW__minimalRequiredPhpVersion() . '</strong>' .
		'<br/>' . __( 'Your server\'s PHP version: ', 'tk-event-weather' ) . '<strong>' . phpversion() . '</strong>' .
		'</div>';
}


function TkEventW__PhpVersionCheck() {
	if ( version_compare( phpversion(), TkEventW__minimalRequiredPhpVersion() ) < 0 ) {
		add_action( 'admin_notices', 'TkEventW__noticePhpVersionWrong' );

		return false;
	}

	return true;
}


// old code?
/**
 * Initialize internationalization (i18n) for this plugin.
 * References:
 * http://codex.wordpress.org/I18n_for_WordPress_Developers
 * http://www.wdmac.com/how-to-create-a-po-language-translation#more-631
 * @return void
 */
/*
function TkEventW__i18n_init() {
	$pluginDir = dirname(plugin_basename(__FILE__));
	load_plugin_textdomain('tk-event-weather', false, $pluginDir . '/languages/');
}
*/


//////////////////////////////////
// Run initialization
/////////////////////////////////

// old code (goes with above)?
// Initialize i18n
// add_action('plugins_loaded','TkEventW__i18n_init');

// Run the version check.
// If it is successful, continue with initialization for this plugin
if ( TkEventW__PhpVersionCheck() ) {
	// Only load and run the init function if we know PHP version can parse it
	include_once( 'includes/init.php' );
	TkEventW__init( __FILE__ );
}
