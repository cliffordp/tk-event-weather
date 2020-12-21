<?php

namespace TKEventWeather;

use Freemius;

/*
	Plugin Name: TK Event Weather
	Plugin URI: https://tourkick.com/plugins/tk-event-weather/?utm_source=plugin-uri-link&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather
	Version: 1.6.6
	Author: TourKick (Clifford Paulick)
	Author URI: https://tourkick.com/?utm_source=author-uri-link&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather
	Description: Display beautiful, accurate, and free hourly weather forecasts between a start and end time. Perfect for event calendars.
	Text Domain: tk-event-weather
	License: GPL version 3 or any later version
	License URI: https://www.gnu.org/licenses/gpl-3.0.html
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
	If not, see https://www.gnu.org/licenses/gpl-3.0.html
*/

/** TODO:
 * - sign up for newsletter
 * - add Customizer option to input a Post ID to default to when viewing the customizer from the plugin's Settings Button (could auto-set it if an Event exists)
 * - truncate seconds off all timestamps? -- avoid 10pm hour + 10pm sunset, like https://cl.ly/430H1J0p2R07 -- No.
 * - the Dark Sky API "apparentTemperature" value is the "feels like" temperature
 * - inspiration from https://darkskyapp.com/
 * - time of day versions of icons (night/day)
 * - UI: https://github.com/cliffordp/tk-event-weather/issues/3#issuecomment-174607313
 * - UI: https://github.com/cliffordp/tk-event-weather/issues/3#issuecomment-178440095
 * - force debug report to be in English (i.e. not translatable)
 * - Debug Mode enhancements: plugin settings, filters/actions in use
 * - add 'demo' option to output all icons (e.g. for styling/testing)
 * - weather advisory alerts (only happen in real-time so probably not going to happen)
 * - color options for styling SVGs (e.g. yellow sun with gray cloud) -- not possible with as-is SVGs because they're flattened (no CSS classes to "fill")
 * - Build Gutenberg block: https://wordpress.org/gutenberg/handbook/block-api/
 * - poor UX (apparent data discrepancy) when shortcode timezone is not same as API timezone. Example: All Day event on Oct 14, 2017 Central Time... but event is located in Eastern Time... current code will correctly display Midnight through 10pm, not 11pm, because the API actually returned Oct 13 11pm - Oct 14 10pm... so we need to get Oct 13 and Oct 14 from the API
 * Proposed eventual solution:
 * If shortcode's TZ != API's TZ (after stripslashes) {
 * Get midnight from shortcode's tz
 * Get midnight from API's tz
 * Get 23:59:59 from shortcode's tz
 * Get 23:59:59 from API's tz
 * Both midnight and 23:59:59 and then get the min/max of each -- so maybe API's is the min and shortcode's is the max -- then use the calendar days based on API's timezone to determine how many days to send to API call loop.
 * }
 * Rework multi-day, likely in a new class:
 * An "all hours" array to loop through Dark Sky and dump each day's hours into it (along with summary) and go off the aggregated timestamps with our own walker determining when one day starts and ends, based on shortcode's timezone, not API's.
 * And update template data to pull from here instead.
 */


// Exit if accessed directly
if ( ! defined( '\ABSPATH' ) ) {
	exit;
}

// Composer's autoloader
require_once( 'vendor/autoload.php' );

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
if ( ! defined( '\TK_EVENT_WEATHER_UNDERSCORES' ) ) {
	define( 'TK_EVENT_WEATHER_UNDERSCORES', 'tk_event_weather' );
}

/**
 * Used for file names and directories, HTML class names, etc.
 *
 * @since 1.5.0
 */
if ( ! defined( '\TK_EVENT_WEATHER_HYPHENS' ) ) {
	define( 'TK_EVENT_WEATHER_HYPHENS', 'tk-event-weather' );
}

// Required for Template Loader. Also used elsewhere.
if ( ! defined( '\TK_EVENT_WEATHER_PLUGIN_ROOT_DIR' ) ) {
	define( 'TK_EVENT_WEATHER_PLUGIN_ROOT_DIR', plugin_dir_path( __FILE__ ) ); // e.g. /.../.../example-com/wp-content/plugins/tk-event-weather/
}

// added for consistency to match DIR
if ( ! defined( '\TK_EVENT_WEATHER_PLUGIN_ROOT_URL' ) ) {
	define( 'TK_EVENT_WEATHER_PLUGIN_ROOT_URL', plugin_dir_url( __FILE__ ) ); // e.g. https://example.com/wp-content/plugins/tk-event-weather/
}

// used by core plugin and by add-on implementations of Freemius
if ( ! defined( '\TK_EVENT_WEATHER_FREEMIUS_START_FILE' ) ) {
	define( 'TK_EVENT_WEATHER_FREEMIUS_START_FILE', dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php' );
}

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
	return apply_filters( \TK_EVENT_WEATHER_UNDERSCORES . '_required_capability', 'customize' );
}

/**
 * Versions
 */

/**
 * Output the notice of WordPress minimum version not met.
 */
function notice_wrong_wp_version() {
	echo '<div class="notice notice-error">' .
	     __( 'Error: plugin "TK Event Weather" requires a newer version of WordPress core.', 'tk-event-weather' ) .
	     '<br/>' . __( 'Minimum required WordPress core version: ', 'tk-event-weather' ) . '<strong>' . Setup::min_wp_version() . '</strong>' .
	     '<br/>' . __( "Your WordPress version: ", 'tk-event-weather' ) . '<strong>' . get_bloginfo( 'version' ) . '</strong>' .
	     '</div>';
}

/**
 * Check the WP core version and give a useful error message if the user's version is less than the required version.
 *
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying an error message on the Admin page.
 */
function wp_version_check() {
	if ( version_compare( get_bloginfo( 'version' ), Setup::min_wp_version(), '<' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\notice_wrong_wp_version' );

		return false;
	}

	return true;
}

/**
 * Output the notice of PHP minimum version not met.
 */
function notice_wrong_php_version() {
	echo '<div class="notice notice-error">' .
	     __( 'Error: plugin "TK Event Weather" requires a newer version of PHP.', 'tk-event-weather' ) .
	     '<br/>' . __( 'Minimum required PHP version: ', 'tk-event-weather' ) . '<strong>' . Setup::min_php_version() . '</strong>' .
	     '<br/>' . __( "Your server's PHP version: ", 'tk-event-weather' ) . '<strong>' . phpversion() . '</strong>' .
	     '</div>';
}

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version.
 *
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying an error message on the Admin page
 */
function php_version_check() {
	if ( version_compare( phpversion(), Setup::min_php_version(), '<' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\notice_wrong_php_version' );

		return false;
	}

	return true;
}

// adapted from https://wpbackoffice.com/get-current-woocommerce-version-number/
function get_tk_event_weather_version() {
	// If get_plugins() isn't available, require it
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once( \ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	// Create the plugins folder and file variables
	$plugin_folder = get_plugins( '/' . \TK_EVENT_WEATHER_HYPHENS );
	$plugin_file   = \TK_EVENT_WEATHER_HYPHENS . '.php';

	// If the plugin version number is set, return it
	if ( isset( $plugin_folder[ $plugin_file ]['Version'] ) ) {
		return $plugin_folder[ $plugin_file ]['Version'];
	}
}

function terms_agreement_text() {
	return sprintf(
		__( 'By using this plugin, you agree to %s and %s terms.', 'tk-event-weather' ),
		'<a target="_blank" href="https://tourkick.com/terms/?utm_source=terms_agreement_text&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather">TourKick\'s</a>',
		'<a target="_blank" href="https://freemius.com/terms/">Freemius\'</a>'
	);
}

/**
 * A helper function for easy Freemius SDK access.
 *
 * @return Freemius|false
 */
function tk_event_weather_freemius() {
	global $tk_event_weather_freemius;

	if (
		! isset( $tk_event_weather_freemius )
		&& defined( '\TK_EVENT_WEATHER_FREEMIUS_START_FILE' )
		&& file_exists( \TK_EVENT_WEATHER_FREEMIUS_START_FILE )
	) {
		// Include Freemius SDK.
		require_once( \TK_EVENT_WEATHER_FREEMIUS_START_FILE );

		try {
			$tk_event_weather_freemius = fs_dynamic_init(
				array(
					'id'             => '240',
					'slug'           => \TK_EVENT_WEATHER_HYPHENS,
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
						'slug'   => \TK_EVENT_WEATHER_HYPHENS . '-settings',
						'parent' => array(
							'slug' => 'options-general.php',
						),
					),
					'navigation' => 'tabs',
				)
			);
		}
		catch ( \Freemius_Exception $e ) {
			echo $e;
			return false;
		}
	}

	return $tk_event_weather_freemius;
}

// Freemius: customize the new user message
function freemius_custom_connect_message(
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

	$tk_custom_message .= '<br><small>' . terms_agreement_text() . '</small>';

	return $tk_custom_message;
}

function freemius_plugin_icon() {
	return \TK_EVENT_WEATHER_PLUGIN_ROOT_DIR . 'images/icon.svg';
}

/**
 * Run the uninstall routine, ran via Freemius' uninstall hook.
 */
function freemius_uninstall() {
	require_once( 'init.php' );
	tk_event_weather_init( __FILE__ );
	$life_cyle = new Life_Cycle;
	$life_cyle->uninstall();
}

/**
 * Initialize internationalization (i18n) for this plugin is not needed because
 * it's hosted at WordPress.org. No more load_plugin_textdomain().
 *
 * @link https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain
 */

//////////////////////////////////
// Run initialization
/////////////////////////////////

tk_event_weather_freemius();
do_action( \TK_EVENT_WEATHER_UNDERSCORES . '_freemius_loaded' );

tk_event_weather_freemius()->add_filter( 'connect_message', __NAMESPACE__ . '\freemius_custom_connect_message', 10, 6 );
tk_event_weather_freemius()->add_filter( 'plugin_icon', __NAMESPACE__ . '\freemius_plugin_icon' );

/**
 * Implement uninstall routine upon deleting, leveraging Freemius' hook.
 *
 * Freemius does not allow having an uninstall.php file in add-ons because it
 * tracks uninstalls, but it has its own uninstall action to hook to -- which
 * is required for add-ons (if any uninstall routine at all) and is best
 * practice for core/.org plugins implementing Freemius.
 *
 * @link https://developer.wordpress.org/plugins/the-basics/uninstall-methods/
 * @link https://developer.wordpress.org/reference/functions/register_uninstall_hook/
 */
tk_event_weather_freemius()->add_action( 'after_uninstall', __NAMESPACE__ . '\freemius_uninstall' );

/**
 * Only run the initialization code if we have the proper WordPress core and
 * PHP versions.
 */
if (
	wp_version_check()
	&& php_version_check()
) {
	require_once( 'init.php' );
	tk_event_weather_init( __FILE__ );

	do_action( \TK_EVENT_WEATHER_UNDERSCORES . '_loaded' );
} else {
	do_action( \TK_EVENT_WEATHER_UNDERSCORES . '_not_loaded' );
}