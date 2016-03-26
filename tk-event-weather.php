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




/** TO DO:
  * FYI: the forecast.io "apparentTemperature" value is the "feels like" temperature
  * add_action() next to wp_enqueue_style ???
  * verify all timestamps get ran through timestamp cleanup method
    * truncate seconds off all timestamps? -- avoid 10pm hour + 10pm sunset, like http://cl.ly/430H1J0p2R07
  * use more data from API, like 'summary' text as a title element somewhere
  * handling of time zone offsets that aren't full hours -- e.g. Eucla Australia is UTC+8:45 -- https://en.wikipedia.org/wiki/List_of_UTC_time_offsets#UTC.2B08:45.2C_H.2A -- currently works well enough probably but outputs '4am' instead of '4:45am' -- does it really need to be fixed?
  * time of day versions of icons (night/day)
    * https://github.com/cliffordp/tk-event-weather/issues/3#issuecomment-174607313
    * https://github.com/cliffordp/tk-event-weather/issues/3#issuecomment-178440095
  * force debug report to be in English (i.e. not translatable)
  * add Debug Mode to output JSON, plugin settings, filters/actions in use, disable transients
  * "current" / "right now" if event is currently happening
  * allow single time instead of hourly (start + end times) to make shortcode more flexible and also maybe applicable for events without an end time (e.g. The Events Calendar)
  * end time just pick last hour of day if end time is out of bounds
  * handle multi-day events (e.g. Monday 8pm to Tuesday 2am or Monday 8pm to Wednesday 5pm)
  * add 'demo' option to output all icons (e.g. for styling/testing)
  * 12 or 24 hour time format (handled automatically by WP translation?)
  * weather advisories
  * color options for styling SVGs (e.g. yellow sun with gray cloud) -- not possible with as-is SVGs because they're flattened (no CSS classes to "fill")
  * all output in BEM method -- https://github.com/google/material-design-lite/wiki/Understanding-BEM
  * styling for shortcode error messages
  * Customizer link to Section if no add-on (make button filterable)
  */


$TkEventWeather__minimalRequiredPhpVersion = '5.0';

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying an error message on the Admin page
 */
function TkEventWeather__noticePhpVersionWrong() {
    global $TkEventWeather__minimalRequiredPhpVersion;
    echo '<div class="updated fade">' .
      __('Error: plugin "TK Event Weather" requires a newer version of PHP to be running.', 'tk-event-weather').
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


// Required for Template Loader. Also used elsewhere.
define( 'TK_EVENT_WEATHER_PLUGIN_ROOT_DIR', plugin_dir_path( __FILE__ ) ); // e.g. /Users/cmp/Documents/git/GitHub/tk-event-weather/
