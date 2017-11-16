<?php

namespace TKEventWeather;

class Setup {
	// all variables and methods should be 'static'

	public static $transient_name_prepend = 'tkeventw';

	// https://wordpress.org/about/requirements/

	public static $min_allowed_version_mysql = '5.5';

	public static $min_allowed_version_wordpress = '4.3.0';

	public static $support_email_address = 'tko+tkeventw@tourkick.com';

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