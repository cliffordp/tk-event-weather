<?php

namespace TKEventWeather;

class Setup {
	// all variables and methods should be 'static'

	public static $transient_name_prepend = 'tkeventw';

	public static $support_email_address = 'tko+tkeventw@tourkick.com';

	// https://wordpress.org/about/requirements/

	/**
	 * The required minimum version of MySQL.
	 *
	 * There is no accompanying readme.txt entry to maintain.
	 *
	 * @return string
	 */
	public static function min_mysql_version() {
		return '5.5'; // not sure why but it's from the original plugin template
	}

	/**
	 * The required minimum version of PHP.
	 *
	 * TODO: Make sure this stays in sync with readme.txt header's "Requires PHP:"
	 * TODO: Make sure this stays in sync with composer.json
	 *
	 * @return string
	 */
	public static function min_php_version() {
		return '5.4';
	}

	/**
	 * The required minimum version of WordPress core.
	 *
	 * TODO: Make sure this stays in sync with the readme.txt header's "Requires at least:".
	 *
	 * The 'customize' capability was added in WP 4.0 (September 4, 2014).
	 * The Customizer API's Selective Refresh (which, in our case, adds quick-link
	 * to each shortcode that's on the page) was added in WP 4.5 (April 12, 2016).
	 * To avoid needing load_plugin_textdomain(), you have to set the "Requires
	 * at least:" readme.txt header field to 4.6 (August 16, 2016).
	 *
	 * @link https://codex.wordpress.org/WordPress_Versions
	 * @link https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain
	 *
	 * @return string
	 */
	public static function min_wp_version() {
		return '4.6';
	}

	/**
	 * 'TK Event Weather'
	 *
	 * Just a helper. Useful when passing to sprintf(), for example.
	 *
	 * @return string
	 */
	public static function plugin_display_name() {
		$this_plugin = new Plugin;

		return $this_plugin->get_plugin_display_name();
	}

	public static function plugin_dir_path_images( $append = '' ) {
		$path = TK_EVENT_WEATHER_PLUGIN_ROOT_DIR . 'images/'; // e.g. /Users/cmp/Documents/git/GitHub/tk-event-weather/images/

		if (
			! empty( $append )
			&& is_string( $append )
		) {
			$path .= $append;
		}

		return trailingslashit( $path );
	}

	/**
	 *
	 * Plugin Directories
	 *
	 */

	public static function plugin_dir_path_vendor( $append = '' ) {
		$path = TK_EVENT_WEATHER_PLUGIN_ROOT_DIR . 'vendor/'; // e.g. /Users/cmp/Documents/git/GitHub/tk-event-weather/vendor/

		if (
			! empty( $append )
			&& is_string( $append )
		) {
			$path .= $append;
		}

		return trailingslashit( $path );
	}

	/**
	 *
	 * Plugin URLs
	 *
	 */

	/**
	 * Example: http://example.com/wp-content/plugins/tk-event-weather/images/
	 *
	 * @return string
	 */
	public static function plugin_dir_url_images() {
		return TK_EVENT_WEATHER_PLUGIN_ROOT_URL . 'images/';
	}

	/**
	 * Example: http://example.com/wp-content/plugins/tk-event-weather/vendor/
	 *
	 * @return string
	 */
	public static function plugin_dir_url_vendor() {
		return TK_EVENT_WEATHER_PLUGIN_ROOT_URL . 'vendor/';
	}
}