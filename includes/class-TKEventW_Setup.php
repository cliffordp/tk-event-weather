<?php

class TKEventW_Setup {
	// all variables and methods should be 'static'

	public static $shortcode_name = 'tk_event_weather'; // doesn't really allow for array, as possible per http://plugin.michael-simpson.com/?page_id=39, but we only have one shortcode in this entire plugin

	public static function shortcode_name_hyphenated() {
		return sanitize_html_class( str_replace( '_', '-', self::$shortcode_name ) );
	}

	public static $transient_name_prepend = 'tkeventw';

	// https://wordpress.org/about/requirements/

	public static $min_allowed_version_mysql = '5.5';

	public static $min_allowed_version_wordpress = '4.3.0';

	public static $support_email_address = 'tko+tkeventw@tourkick.com';

	/**
	 *
	 * Plugin Directories
	 *
	 */

	public static function plugin_dir_path_root() {
		return TK_EVENT_WEATHER_PLUGIN_ROOT_DIR; // from root plugin file
	}

	public static function plugin_dir_path_images() {
		return self::plugin_dir_path_root() . 'images/'; // e.g. /Users/cmp/Documents/git/GitHub/tk-event-weather/images/
	}

	public static function plugin_dir_path_includes() {
		return self::plugin_dir_path_root() . 'includes/'; // e.g. /Users/cmp/Documents/git/GitHub/tk-event-weather/includes/
	}

	public static function plugin_dir_path_vendor() {
		return self::plugin_dir_path_includes() . 'vendor/'; // e.g. /Users/cmp/Documents/git/GitHub/tk-event-weather/includes/vendor/
	}

	public static function plugin_dir_path_views() {
		return self::plugin_dir_path_includes() . 'views/'; // e.g. /Users/cmp/Documents/git/GitHub/tk-event-weather/includes/views/
	}

	/**
	 *
	 * Plugin URLs
	 *
	 */

	public static function plugin_dir_url_root() {
		return TK_EVENT_WEATHER_PLUGIN_ROOT_URL; // from root plugin file
	}

	public static function plugin_dir_url_images() {
		return self::plugin_dir_url_root() . 'images/'; // e.g. http://example.com/wp-content/plugins/tk-event-weather/images/
	}

	public static function plugin_dir_url_includes() {
		return self::plugin_dir_url_root() . 'includes/'; // e.g. http://example.com/wp-content/plugins/tk-event-weather/includes/
	}

	public static function plugin_dir_url_vendor() {
		return self::plugin_dir_url_includes() . 'vendor/';
	}

	// no need for URL to 'views' directory

}