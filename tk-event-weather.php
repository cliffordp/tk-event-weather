<?php
/*
   Plugin Name: TK Event Weather
   Plugin URI: http://tourkick.com/plugins/tk-event-weather/?utm_source=plugin-uri-link&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather
   Version: 1.0
   Author: TourKick (Clifford Paulick)
   Author URI: http://tourkick.com/?utm_source=author-uri-link&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather
   Description: Display beautiful, accurate, and free weather forecasts between a start and end time on the same day. Perfect for event calendars.
   Text Domain: tk-event-weather
   License: GPLv3
   License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/*
    "WordPress Plugin Template" Copyright (C) 2016 Michael Simpson  (email : michael.d.simpson@gmail.com)

    This following part of this file is part of WordPress Plugin Template for WordPress.

    WordPress Plugin Template is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    WordPress Plugin Template is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Contact Form to Database Extension.
    If not, see http://www.gnu.org/licenses/gpl-3.0.html
*/

define( 'TK_EVENT_WEATHER_PLUGIN_ROOT_DIR', plugin_dir_path( __FILE__ ) ); // e.g. /Users/cmp/Documents/git/GitHub/tk-event-weather/
define( 'TK_EVENT_WEATHER_PLUGIN_ROOT_URL', plugin_dir_url( __FILE__ ) );  // e.g. http://some.site/wp-content/plugins/tk-event-weather/

$TkEventWeather__minimalRequiredPhpVersion = '5.0';

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying
 * an error message on the Admin page
 */
function TkEventWeather__noticePhpVersionWrong() {
    global $TkEventWeather__minimalRequiredPhpVersion;
    echo '<div class="updated fade">' .
      __('Error: plugin "TK Event Weather" requires a newer version of PHP to be running.',  'tk-event-weather').
            '<br/>' . __('Minimal version of PHP required: ', 'tk-event-weather') . '<strong>' . $TkEventWeather__minimalRequiredPhpVersion . '</strong>' .
            '<br/>' . __('Your server\'s PHP version: ', 'tk-event-weather') . '<strong>' . phpversion() . '</strong>' .
         '</div>';
}


function TkEventWeather__PhpVersionCheck() {
    global $TkEventWeather__minimalRequiredPhpVersion;
    if (version_compare(phpversion(), $TkEventWeather__minimalRequiredPhpVersion) < 0) {
        add_action('admin_notices', 'TkEventWeather__noticePhpVersionWrong');
        return false;
    }
    return true;
}


/**
 * Initialize internationalization (i18n) for this plugin.
 * References:
 *      http://codex.wordpress.org/I18n_for_WordPress_Developers
 *      http://www.wdmac.com/how-to-create-a-po-language-translation#more-631
 * @return void
 */
function TkEventWeather__i18n_init() {
    $pluginDir = dirname(plugin_basename(__FILE__));
    load_plugin_textdomain('tk-event-weather', false, $pluginDir . '/languages/');
}


//////////////////////////////////
// Run initialization
/////////////////////////////////

// Initialize i18n
add_action('plugins_loadedi','TkEventWeather__i18n_init');

// Run the version check.
// If it is successful, continue with initialization for this plugin
if (TkEventWeather__PhpVersionCheck()) {
    // Only load and run the init function if we know PHP version can parse it
    include_once('includes/TkEventWeather__init.php');
    TkEventWeather__init(__FILE__);
}