<?php

namespace TKEventWeather;

/*
	Plugin Name: TK Event Weather
	Plugin URI: https://tourkick.com/plugins/tk-event-weather/?utm_source=plugin-uri-link&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather
	Version: 1.5.0
	Author: TourKick (Clifford Paulick)
	Author URI: https://tourkick.com/?utm_source=author-uri-link&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather
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
 * - uninstall deletes add-on's data too?, maybe via filter
 * - refresh screenshots
 * - wp-admin notice if you have a supported plugin active (e.g. The Events Calendar) but its applicable add-on is not (either to activate it or to buy it).
 * - add Customizer option to input a Post ID to default to when viewing the customizer from the plugin's Settings Button (could auto-set it if an Event exists)
 * - truncate seconds off all timestamps? -- avoid 10pm hour + 10pm sunset, like http://cl.ly/430H1J0p2R07 -- No.
 * - the Dark Sky API "apparentTemperature" value is the "feels like" temperature
 * - inspiration from http://darkskyapp.com/
 * - time of day versions of icons (night/day)
 * - UI: https://github.com/cliffordp/tk-event-weather/issues/3#issuecomment-174607313
 * - UI: https://github.com/cliffordp/tk-event-weather/issues/3#issuecomment-178440095
 * - force debug report to be in English (i.e. not translatable)
 * - Debug Mode enhancements: plugin settings, filters/actions in use
 * - add 'demo' option to output all icons (e.g. for styling/testing)
 * - weather advisory alerts (only happen in real-time so probably not going to happen)
 * - color options for styling SVGs (e.g. yellow sun with gray cloud) -- not possible with as-is SVGs because they're flattened (no CSS classes to "fill")
 * - Add support for https://wordpress.org/plugins/shortcode-ui/ and/or Gutenberg: https://wordpress.org/gutenberg/handbook/block-api/
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Defines
 * define() without prepending a namespace puts it in the global scope (i.e. non-namespaced). Must be prepended with a slash (global namespace) when getting it from outside the TKEventWeather namespace (even if in a sub namespace).
 */

/**
 * Used as the shortcode name, to prefix options, and elsewhere.
 *
 * Must be lower-case and underscores instead of hyphens.
 *
 * @since 1.5.0
 */
define( 'TK_EVENT_WEATHER_UNDERSCORES', 'tk_event_weather' );

/**
 * Used for file names and directories, HTML class names, etc.
 *
 * @since 1.5.0
 */
define( 'TK_EVENT_WEATHER_HYPHENS', 'tk-event-weather' );

// Required for Template Loader. Also used elsewhere.
define( 'TK_EVENT_WEATHER_PLUGIN_ROOT_DIR', plugin_dir_path( __FILE__ ) ); // e.g. /.../.../example-com/wp-content/plugins/tk-event-weather/

// added for consistency to match DIR
define( 'TK_EVENT_WEATHER_PLUGIN_ROOT_URL', plugin_dir_url( __FILE__ ) ); // e.g. http://example.com/wp-content/plugins/tk-event-weather/

// used for adding Settings link to plugins.php
// https://developer.wordpress.org/reference/functions/plugin_basename/
define( 'TK_EVENT_WEATHER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // e.g. tk-event-weather/tk-event-weather.php

// used by core plugin and by add-on implementations of Freemius
define( 'TK_EVENT_WEATHER_FREEMIUS_START_FILE', dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php' );

/**
 * Capability required to access the settings and be shown the shortcode errors.
 *
 * By default, 'customize' is mapped to 'edit_theme_options' (Administrator).
 *
 * @link  https://developer.wordpress.org/themes/customize-api/advanced-usage/
 *
 * @since 1.5.0
 */
function required_capability() {
	return apply_filters( TK_EVENT_WEATHER_UNDERSCORES . '_required_capability', 'customize' );
}

/**
 * Versions
 */

// adapted from http://wpbackoffice.com/get-current-woocommerce-version-number/
function tk_event_weather_version() {
	// If get_plugins() isn't available, require it
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	// Create the plugins folder and file variables
	$plugin_folder = get_plugins( '/' . TK_EVENT_WEATHER_HYPHENS );
	$plugin_file   = TK_EVENT_WEATHER_HYPHENS . '.php';

	// If the plugin version number is set, return it
	if ( isset( $plugin_folder[ $plugin_file ]['Version'] ) ) {
		return $plugin_folder[ $plugin_file ]['Version'];
	} else {
		// Otherwise return null
		return null;
	}

}

function tk_event_weather_min_php_version() {
	return '5.4';
}

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying an error message on the Admin page
 */
function tkeventweather_notice_wrong_php_version() {
	echo '<div class="updated fade">' .
	     __( 'Error: plugin "TK Event Weather" requires a newer version of PHP to be running.', 'tk-event-weather' ) .
	     '<br/>' . __( 'Minimum required PHP version: ', 'tk-event-weather' ) . '<strong>' . tk_event_weather_min_php_version() . '</strong>' .
	     '<br/>' . __( "Your server's PHP version: ", 'tk-event-weather' ) . '<strong>' . \phpversion() . '</strong>' .
	     '</div>';
}


function tk_event_weather_php_version_check() {
	if ( 0 > version_compare( \phpversion(), tk_event_weather_min_php_version() ) ) {
		add_action( 'admin_notices', 'TKEventWeather\tkeventweather_notice_wrong_php_version' );

		return false;
	}

	return true;
}


/**
 * Freemius setup
 */

function tk_event_weather_terms_agreement_text() {
	return sprintf(
		__( 'By using this plugin, you agree to %s and %s terms.', 'tk-event-weather' ),
		'<a target="_blank" href="https://tourkick.com/terms/?utm_source=terms_agreement_text&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather">TourKick\'s</a>',
		'<a target="_blank" href="https://freemius.com/terms/">Freemius\'</a>'
	);
}

// A helper function for easy Freemius SDK access.
function tk_event_weather_freemius() {
	global $tk_event_weather_freemius;

	if (
		! isset( $tk_event_weather_freemius )
		&& defined( 'TK_EVENT_WEATHER_FREEMIUS_START_FILE' )
		&& file_exists( TK_EVENT_WEATHER_FREEMIUS_START_FILE )
	) {
		// Include Freemius SDK.
		require_once( TK_EVENT_WEATHER_FREEMIUS_START_FILE );

		$tk_event_weather_freemius = \fs_dynamic_init(
			array(
				'id'             => '240',
				'slug'           => TK_EVENT_WEATHER_HYPHENS,
				'public_key'     => 'pk_b6902fc0051f10b5e36bea21fb0e7',
				'is_premium'     => false,
				'has_addons'     => true,
				'has_paid_plans' => false,
				'menu'           => array(
					/**
					 * The Freemius slug is to where we get redirected upon
					 * plugin activation (and to where submenu items get
					 * attached) so it should match the options page URL
					 * we generate.
					 *
					 * @see Life_Cycle::get_settings_slug()
					 */
					'slug'   => TK_EVENT_WEATHER_HYPHENS . '-settings',
					'parent' => array(
						'slug' => 'options-general.php',
					),
				),
			)
		);
	}

	return $tk_event_weather_freemius;
}

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
		__( 'Hi, %1$s.', 'tk-event-weather' ) . '<br><br>' . __( 'The <strong>%2$s</strong> plugin is ready to go! Want to help make %2$s more awesome? Securely share some data to get the best experience and stay informed.', 'tk-event-weather' ),
		$user_first_name,
		$plugin_title,
		'<strong>' . $user_login . '</strong>',
		$site_link,
		$freemius_link
	);

	$tk_custom_message .= '<br><small>' . tk_event_weather_terms_agreement_text() . '</small>';

	return $tk_custom_message;
}

function tk_event_weather_freemius_plugin_icon() {
	return TK_EVENT_WEATHER_PLUGIN_ROOT_DIR . 'images/icon.svg';
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
function tkeventweather_i18n_init() {
	$pluginDir = dirname(plugin_basename(__FILE__));
	load_plugin_textdomain('tk-event-weather', false, $pluginDir . '/languages/');
}
*/


//////////////////////////////////
// Run initialization
/////////////////////////////////

// old code (goes with above)?
// Initialize i18n
// add_action('plugins_loaded','TKEventWeather\tkeventweather_i18n_init');

// Run the version check.
// If it is successful, continue with initialization for this plugin
if ( tk_event_weather_php_version_check() ) {
	// Only load and run everything if we know PHP version can parse it
	tk_event_weather_freemius();
	tk_event_weather_freemius()->add_filter( 'connect_message', 'TKEventWeather\tk_event_weather_freemius_custom_connect_message', 10, 6 );
	tk_event_weather_freemius()->add_filter( 'plugin_icon', 'TKEventWeather\tk_event_weather_freemius_plugin_icon' );

	require_once( 'init.php' );
	tk_event_weather_init( __FILE__ );

	do_action( TK_EVENT_WEATHER_UNDERSCORES . '_loaded' );
}
