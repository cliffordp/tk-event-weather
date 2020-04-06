<?php

namespace TKEventWeather;

class Functions {
	// all variables and methods should be 'static'

	/**
	 * Get the shortcode error message.
	 *
	 * @var string
	 */
	public static $shortcode_error_message = '';

	/**
	 * Get the 'tk_event_weather' database option.
	 *
	 * Note that this does not get the 'tk_event_weather_installed'
	 * or 'tk_event_weather_version' options.
	 *
	 * @return bool|mixed
	 */
	public static function plugin_options() {
		$plugin_options = get_option( TK_EVENT_WEATHER_UNDERSCORES );
		if ( ! empty( $plugin_options ) ) {
			return $plugin_options;
		} else {
			return false;
		}
	}


	public static function register_css() {
		wp_register_style( TK_EVENT_WEATHER_HYPHENS, TK_EVENT_WEATHER_PLUGIN_ROOT_URL . 'css/' . TK_EVENT_WEATHER_HYPHENS . '.css', [], get_tk_event_weather_version() );

		wp_register_style( TK_EVENT_WEATHER_HYPHENS . '-scroll-horizontal', TK_EVENT_WEATHER_PLUGIN_ROOT_URL . 'css/' . TK_EVENT_WEATHER_HYPHENS . '-scroll-horizontal.css', [], get_tk_event_weather_version() );

		wp_register_style( TK_EVENT_WEATHER_HYPHENS . '-vertical-to-columns', TK_EVENT_WEATHER_PLUGIN_ROOT_URL . 'css/' . TK_EVENT_WEATHER_HYPHENS . '-vertical-to-columns.css', [], get_tk_event_weather_version() );
	}


	/**
	 *
	 * Icons
	 *
	 */

	// Outdated: http://adamwhitcroft.com/climacons/font/
	// https://github.com/christiannaths/Climacons-Font
	// https://github.com/christiannaths/Climacons-Font/blob/master/webfont/demo.html
	//
	public static function register_climacons_css() {
		wp_register_style( TK_EVENT_WEATHER_HYPHENS . '-climacons', Setup::plugin_dir_url_vendor() . 'climacons-webfont/climacons-webfont/climacons-font.css', [], get_tk_event_weather_version() );
	}


	/**
	 * Clean variables using sanitize_text_field.
	 *
	 * @param string|array $var
	 *
	 * @return string|array
	 */
	public static function tk_clean_var( $var ) {
		return is_array( $var ) ? array_map( 'tk_clean_var', $var ) : sanitize_text_field( $var );
	}

	/**
	 * Get the current URL we are loading.
	 *
	 * Excerpted from wp_admin_bar_customize_menu().
	 *
	 * @return string
	 */
	public static function get_current_url() {
		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		return $current_url;
	}

	/**
	 * @param        $array
	 * @param        $key
	 * @param string $fallback
	 *
	 * @return mixed|string
	 */
	public static function array_get_value_by_key( $array, $key, $fallback = '' ) {
		if ( ! is_array( $array )
			|| empty( $array )
			|| ! isset( $array[ $key ] ) // use instead of array_key_exists()
			|| '' === $array[ $key ] // to allow resetting an option back to its blank default
		) {
			$result = $fallback;
		} else {
			$result = $array[ $key ];
		}

		return $result; // consider strval()?
	}

	/**
	 * @param string $input
	 * @param string $link
	 * @param string $link_text
	 *
	 * @return null
	 */
	public static function invalid_shortcode_message( $input = '', $link = '', $link_text = '' ) {
		// Avoid overwriting the current message if one already exists.
		if ( ! empty( self::$shortcode_error_message ) ) {
			return;
		}

		$capability = required_capability();

		if ( ! in_array( $capability, self::all_valid_wp_capabilities() ) ) {
			$capability = 'customize';
		}

		// escape single apostrophes
		$error_reason = str_replace( "'", "\'", $input );

		if ( ! empty( $error_reason ) ) {
			$message = sprintf( __( '%s for the `%s` shortcode to work correctly.', 'tk-event-weather' ), $error_reason, TK_EVENT_WEATHER_UNDERSCORES );
		} else {
			$message = sprintf( __( 'Invalid or incomplete usage of the `%s` shortcode.', 'tk-event-weather' ), TK_EVENT_WEATHER_UNDERSCORES );
		}

		$message = sprintf( __( '%s (Error message only displayed to users with the `%s` capability.)', 'tk-event-weather' ), $message, $capability );

		$result = '';
		if ( current_user_can( $capability ) ) {
			$result .= sprintf(
				'<div class="%s">%s</div>',
				self::shortcode_error_class_name(),
				esc_html( $message )
			);

			$link = esc_url( $link );

			if ( ! empty( $link ) ) {
				$link_text = esc_html__( $link_text, 'tk-event-weather' );

				if ( empty( $link_text ) ) {
					$link_text = $link;
				}

				$result .= sprintf(
					'%1$s<a href="%2$s">%3$s</a>%1$s',
					PHP_EOL,
					$link,
					$link_text
				);
			}
		}

		self::$shortcode_error_message = $result;
	}

	/**
	 * An array of all the valid WordPress capabilities.
	 *
	 * @see wp_roles()
	 *
	 * @return array
	 */
	public static function all_valid_wp_capabilities() {
		$all_roles = wp_roles();

		$all_capabilities = [];

		foreach ( $all_roles->roles as $key => $value ) {
			$all_capabilities[] = array_keys( $value['capabilities'], true, true );
		}

		$all_capabilities_flattened = [];

		foreach ( $all_capabilities as $key => $value ) {
			foreach ( $value as $key_a => $value_a ) {
				$all_capabilities_flattened[] = $value_a;
			}
		}

		$all_capabilities = array_unique( $all_capabilities_flattened );
		sort( $all_capabilities );

		return $all_capabilities;
	}

	/**
	 * @return string
	 */
	public static function shortcode_error_class_name() {
		$result = sprintf(
			'%s %s',
			sanitize_html_class( TK_EVENT_WEATHER_HYPHENS . '__wrapper' ), // so the customizer auto-links to it
			sanitize_html_class(
				TK_EVENT_WEATHER_HYPHENS . '__error' // for the custom CSS targeting
			)
		);

		return $result;
	}

	/**
	 * PHP size notation string to integer value.
	 *
	 * This function transforms the php.ini notation for numbers (like '64M')
	 * to an integer value (like `64`) of the number of bytes.
	 *
	 * @link https://github.com/woocommerce/woocommerce/blob/3.2.3/includes/wc-formatting-functions.php#L519-L543
	 *
	 * @param string $size Size value.
	 *
	 * @return int
	 */
	public static function php_size_string_to_integer( $size ) {
		$letter = substr( $size, - 1 ); // e.g. 'M'
		$number = substr( $size, 0, - 1 ); // e.g. '64'

		switch ( strtoupper( $letter ) ) {
			case 'P':
				$number *= 1024;
			case 'T':
				$number *= 1024;
			case 'G':
				$number *= 1024;
			case 'M':
				$number *= 1024;
			case 'K':
				$number *= 1024;
		}

		return $number;
	}

	/**
	 * Get the credit link(s) to comply with https://developer.darksky.net/
	 * and/or to spread the news about this plugin existing by linking to
	 * https://tourkick.com/plugins/tk-event-weather/
	 *
	 * @return string
	 */
	public static function get_credit_links() {
		$output = '';

		if (
			empty( Shortcode::$span_template_data['darksky_credit_link_enabled'] )
			&& empty( Shortcode::$span_template_data['plugin_credit_link_enabled'] )
		) {
			return $output;
		}

		$powered_by = _x( 'Powered by', 'plugin credit links', 'tk-event-weather' );

		$dark_sky_url = 'https://darksky.net/poweredby/';

		$dark_sky_link_text = 'Dark Sky';

		$plugin_link_url = 'https://tourkick.com/plugins/tk-event-weather/?utm_source=plugin-credit-link&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather';

		$plugin_link_text = _x( 'TK Event Weather plugin', 'plugin credit links', 'tk-event-weather' );

		if (
			// both
			! empty( Shortcode::$span_template_data['darksky_credit_link_enabled'] )
			&& ! empty( Shortcode::$span_template_data['plugin_credit_link_enabled'] )
		) {
			$output = sprintf(
				'<a href="%s" target="_blank">%s %s</a> %s <a href="%s" target="_blank">%s</a>',
				$dark_sky_url,
				$powered_by,
				$dark_sky_link_text,
				_x( 'and', 'plugin credit links', 'tk-event-weather' ),
				$plugin_link_url,
				$plugin_link_text
			);
		} elseif (
			// only Dark Sky link
		! empty( Shortcode::$span_template_data['darksky_credit_link_enabled'] )
		) {
			$output = sprintf(
				'<a href="%s" target="_blank">%s %s</a>',
				$dark_sky_url,
				$powered_by,
				$dark_sky_link_text
			);
		} elseif (
			// only plugin credit link
		! empty( Shortcode::$span_template_data['plugin_credit_link_enabled'] )
		) {
			$output = sprintf(
				'<a href="%s" target="_blank">%s %s</a>',
				$plugin_link_url,
				$powered_by,
				$plugin_link_text
			);
		} else {
			// nothing
		}

		if ( ! empty( $output ) ) {
			$output = sprintf( '<div class="%s__credit-links">%s</div>', TK_EVENT_WEATHER_HYPHENS, $output );
		}

		return $output;
	}

	/**
	 * @param $input
	 *
	 * @return int|string
	 */
	public static function sanitize_absint_allow_blank( $input ) {
		if ( 0 === $input || '0' === $input ) {
			return 0;
		} elseif ( 0 === absint( $input ) ) {
			return '';
		} else {
			return absint( $input );
		}
	}

	/**
	 * same as https://developer.wordpress.org/reference/functions/sanitize_key/ except without strlolower()
	 * used for Google Maps Geocoding API Keys
	 *
	 * @param string $input
	 *
	 * @return mixed
	 */
	public static function sanitize_key_allow_uppercase( $input = '' ) {
		$result = self::remove_all_whitespace( $input );

		$result = preg_replace( '/[^A-Za-z0-9_\-]/', '', $result );

		return $result;
	}

	/**
	 * @param $input
	 *
	 * @return mixed
	 */
	public static function remove_all_whitespace( $input ) {
		return preg_replace( '/\s+/', '', $input );
	}

	/**
	 * @param string $input
	 *
	 * @return mixed|string
	 */
	public static function sanitize_transient_name( $input = '' ) {
		$result = self::remove_all_whitespace( $input );

		// make sure no period, comma (e.g. from lat/long) or other unfriendly characters for the transients database field
		$result = sanitize_key( $result );

		// dashes to underscores (such as for a negative number longitude)
		$result = str_replace( '-', '_', $result );

		// remove repeat underscores, such as a space next to a dash and both got changed to underscores
		$result = str_replace( '__', '_', $result );

		// "Must be 172 characters or fewer in length." per https://developer.wordpress.org/reference/functions/set_transient/
		// also see https://core.trac.wordpress.org/ticket/15058
		// But older versions of WordPress used to have a 40 character limit or would silently fail
		// so if someone is using this plugin with an old version of WordPress, transients just will not be set (not ideal but they should update)
		$result = substr( $result, 0, 171 );


		return $result;
	}

	/**
	 * if transients are ON/TRUE, return transient value
	 * if transients are OFF/FALSE, delete transient
	 * if transients are ON/TRUE but no value, delete transient
	 *
	 * @param      $transient_name
	 * @param bool $transients_on
	 *
	 * @return bool|mixed|string
	 */
	public static function transient_get_or_delete( $transient_name, $transients_on = true ) {
		if ( ! isset( $transient_name ) || ! is_bool( $transients_on ) ) {
			return false;
		}

		if ( false === $transients_on ) {
			delete_transient( $transient_name );
			$result = '';
		} else {
			$result = get_transient( $transient_name );
		}

		if ( empty( $result ) ) {
			delete_transient( $transient_name );

			return false;
		}

		return $result;
	}

	/**
	 * Verify string is valid latitude,longitude
	 * Dark Sky does not allow certain lat,long -- such as 0,0
	 *
	 * @since  1.0.0
	 *
	 * @link   https://en.wikipedia.org/wiki/Decimal_degrees
	 * @link   https://regex101.com/r/fZ1oR3/1
	 *
	 * @param string $input         Comma-separated latitude and
	 *                              longitude in decimal degrees.
	 * @param string $return_format Optional.
	 *
	 * @return bool|string          If valid, returns string or bool true,
	 *                              based on $return_format. If invalid,
	 *                              returns empty string or bool false,
	 *                              based on $return_format.
	 */
	public static function valid_lat_long( $input, $return_format = '' ) {
		$input = self::remove_all_whitespace( $input );

		if ( ! empty( $return_format ) && 'bool' != $return_format ) {
			$return_format = '';
		}

		// is not valid lat,long FORMAT
		$match = preg_match( '/^([-+]?[1-8]?\d(?:\.\d+)?|90(?:\.0+)?),\s*([-+]?(?:180(?:\.0+)?|(?:(?:1[0-7]\d)|(?:[1-9]?\d))(?:\.\d+)?))$/', $input );
		if ( empty( $match ) ) {
			if ( '' == $return_format ) {
				return '';
			} else {
				return false;
			}
		}

		// separate lat,long
		$lat_long_array = explode( ',', $input );
		$latitude       = floatval( $lat_long_array[0] );
		$longitude      = floatval( $lat_long_array[1] );

		// is not valid Dark Sky lat or long
		if (
			empty( $latitude ) && empty( $longitude ) // e.g. 0.0000,0.0000
		) {
			if ( '' == $return_format ) {
				return '';
			} else {
				return false;
			}
		} else {
			if ( '' == $return_format ) {
				return $input;
			} else {
				return true;
			}
		}
	}

	// in case wanting to add sunrise/sunset to the hourly weather data and then re-sort by 'time' key -- but not used so commented out
	// adapted from http://www.firsttube.com/read/sorting-a-multi-dimensional-array-with-php/
	/*
		public static function sort_multidim_array_by_sub_key( $multidim_array, $sub_key ) {
			// first check if we have a multidimensional array
			if ( count( $multidim_array ) == count( $multidim_array, COUNT_RECURSIVE ) ) {
				return false;
			} else {
				$a = $multidim_array;
			}
	
			foreach( $a as $k => $v ) {
				$b[$k] = strtolower( $v[$subkey] );
			}
	
			asort( $b );
	
			foreach( $b as $key => $val ) {
				$c[] = $a[$key];
			}
			return $c;
		}
	*/

	/**
	 * @param        $input
	 * @param string $icon_type
	 *
	 * @return mixed|string
	 */
	public static function icon_html( $input, $icon_type = 'climacons_font' ) {
		$input = self::remove_all_whitespace( strtolower( $input ) );

		if ( ! in_array( $input, self::valid_api_icon() ) ) {
			return '';
		}

		if ( ! in_array( $icon_type, self::valid_icon_type() ) ) {
			$icon_type = 'climacons_font';
		}

		$result = '';

		$climacons_font = array(
			'clear-day'           => 'sun',
			'clear-night'         => 'moon',
			'rain'                => 'rain',
			'snow'                => 'snow',
			'sleet'               => 'sleet',
			'wind'                => 'wind',
			'fog'                 => 'fog',
			'cloudy'              => 'cloud',
			'partly-cloudy-day'   => 'cloud sun',
			'partly-cloudy-night' => 'cloud moon',
			'sunrise'             => 'sunrise',
			'sunset'              => 'sunset',
			'compass-north'       => 'compass north',
		);

		// If you use SVGs, you'll need to add your own styling to make them appear more inline.
		// https://github.com/christiannaths/Climacons-Font/tree/master/SVG
		$climacons_svg = array(
			'clear-day'           => self::climacons_svg_sun(),
			'clear-night'         => self::climacons_svg_moon(),
			'rain'                => self::climacons_svg_cloud_rain(),
			'snow'                => self::climacons_svg_cloud_snow(),
			'sleet'               => self::climacons_svg_cloud_hail(),
			'wind'                => self::climacons_svg_wind(),
			'fog'                 => self::climacons_svg_cloud_fog(),
			'cloudy'              => self::climacons_svg_cloud(),
			'partly-cloudy-day'   => self::climacons_svg_cloud_sun(),
			'partly-cloudy-night' => self::climacons_svg_cloud_moon(),
			'sunrise'             => self::climacons_svg_sunrise(),
			'sunset'              => self::climacons_svg_sunset(),
			'compass-north'       => self::climacons_svg_compass_north(),
		);

		// Font Awesome is really not usable (not enough weather-related icons). Plus, you would need to add the icon font yourself (e.g. via https://wordpress.org/plugins/better-font-awesome/ )
		/*
				$fa_icons = array(
					'clear-day'					=> 'fa-sun-o',
					'clear-night'				=> 'fa-moon-o',
					'rain'								=> 'fa-umbrella',
					'snow'								=> 'fa-tint',
					'sleet'							=> 'fa-tint',
					'wind'								=> 'fa-send',
					'fog'								=> 'fa-shield',
					'cloudy'							=> 'fa-cloud',
					'partly-cloudy-day'	=> 'fa-cloud',
					'partly-cloudy-night' => 'fa-star-half',
					'sunrise'						=> 'fa-arrow-up',
					'sunset'							=> 'fa-arrow-down',
					'compass-north'			=> 'fa-arrow-circle-o-up',
				);
		*/

		if ( 'climacons_font' == $icon_type ) {
			$icon   = $climacons_font[ $input ];
			$result = sprintf( '<i class="climacon %s"></i>', $icon );
		} elseif ( 'climacons_svg' == $icon_type ) {
			$icon   = $climacons_svg[ $input ];
			$result = $icon;
		} /*
		elseif ( 'font_awesome' == $icon_type ) {
			$icon = $fa_icons[$input];
			$result = sprintf( '<i class="fa %s"></i>', $icon );
		}
		*/
		else {
			// nothing
		}

		return $result;
	}

	/**
	 * @param string $prepend_empty
	 *
	 * @return array|bool
	 */
	public static function valid_api_icon( $prepend_empty = 'false' ) {
		$result = array(
			'clear-day',
			'clear-night',
			'rain',
			'snow',
			'sleet',
			'wind',
			'fog',
			'cloudy',
			'partly-cloudy-day',
			'partly-cloudy-night',
			'sunrise',
			'sunset',
			'compass-north',
		);

		if ( 'true' == $prepend_empty ) {
			$result = self::array_prepend_empty( $result );
		}

		return $result;
	}

	/**
	 * @param $input
	 *
	 * @return array|bool
	 */
	public static function array_prepend_empty( $input ) {
		if ( ! is_array( $input ) ) {
			$result = false;
		} else {
			$result = array( '' => '' ) + $input;
		}

		return $result;
	}

	// 
	// Climacons SVGs
	// https://github.com/christiannaths/Climacons-Font/tree/master/SVG
	// 

	/**
	 * @param string $prepend_empty
	 *
	 * @return array|bool
	 */
	public static function valid_icon_type( $prepend_empty = 'false' ) {
		$result = array(
			'climacons_font',
			'climacons_svg',
			//'font_awesome',
			'off',
		);

		if ( 'true' == $prepend_empty ) {
			$result = self::array_prepend_empty( $result );
		}

		return $result;
	}

	public static function climacons_svg_sun() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<path fill-rule="evenodd" clip-rule="evenodd" d="M71.997,51.999h-3.998c-1.105,0-2-0.895-2-1.999s0.895-2,2-2h3.998
	c1.105,0,2,0.896,2,2S73.103,51.999,71.997,51.999z M64.142,38.688c-0.781,0.781-2.049,0.781-2.828,0
	c-0.781-0.781-0.781-2.047,0-2.828l2.828-2.828c0.779-0.781,2.047-0.781,2.828,0c0.779,0.781,0.779,2.047,0,2.828L64.142,38.688z
	M50.001,61.998c-6.627,0-12-5.372-12-11.998c0-6.627,5.372-11.999,12-11.999c6.627,0,11.998,5.372,11.998,11.999
	C61.999,56.626,56.628,61.998,50.001,61.998z M50.001,42.001c-4.418,0-8,3.581-8,7.999c0,4.417,3.583,7.999,8,7.999
	s7.998-3.582,7.998-7.999C57.999,45.582,54.419,42.001,50.001,42.001z M50.001,34.002c-1.105,0-2-0.896-2-2v-3.999
	c0-1.104,0.895-2,2-2c1.104,0,2,0.896,2,2v3.999C52.001,33.106,51.104,34.002,50.001,34.002z M35.86,38.688l-2.828-2.828
	c-0.781-0.781-0.781-2.047,0-2.828s2.047-0.781,2.828,0l2.828,2.828c0.781,0.781,0.781,2.047,0,2.828S36.641,39.469,35.86,38.688z
	M34.002,50c0,1.104-0.896,1.999-2,1.999h-4c-1.104,0-1.999-0.895-1.999-1.999s0.896-2,1.999-2h4C33.107,48,34.002,48.896,34.002,50
	z M35.86,61.312c0.781-0.78,2.047-0.78,2.828,0c0.781,0.781,0.781,2.048,0,2.828l-2.828,2.828c-0.781,0.781-2.047,0.781-2.828,0
	c-0.781-0.78-0.781-2.047,0-2.828L35.86,61.312z M50.001,65.998c1.104,0,2,0.895,2,1.999v4c0,1.104-0.896,2-2,2
	c-1.105,0-2-0.896-2-2v-4C48.001,66.893,48.896,65.998,50.001,65.998z M64.142,61.312l2.828,2.828c0.779,0.781,0.779,2.048,0,2.828
	c-0.781,0.781-2.049,0.781-2.828,0l-2.828-2.828c-0.781-0.78-0.781-2.047,0-2.828C62.093,60.531,63.36,60.531,64.142,61.312z"/>
</svg>';
	}

	public static function climacons_svg_moon() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<path fill-rule="evenodd" clip-rule="evenodd" d="M50,61.998c-6.627,0-11.999-5.372-11.999-11.998
	c0-6.627,5.372-11.999,11.999-11.999c0.755,0,1.491,0.078,2.207,0.212c-0.132,0.576-0.208,1.173-0.208,1.788
	c0,4.418,3.582,7.999,8,7.999c0.615,0,1.212-0.076,1.788-0.208c0.133,0.717,0.211,1.452,0.211,2.208
	C61.998,56.626,56.626,61.998,50,61.998z M48.212,42.208c-3.556,0.813-6.211,3.989-6.211,7.792c0,4.417,3.581,7.999,7.999,7.999
	c3.802,0,6.978-2.655,7.791-6.211C52.937,50.884,49.115,47.062,48.212,42.208z"/>
</svg>';
	}

	public static function climacons_svg_cloud_rain() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<path fill-rule="evenodd" clip-rule="evenodd" d="M63.943,64.941v-4.381c2.389-1.383,4-3.961,4-6.92c0-4.417-3.582-7.999-8-7.999
	c-1.6,0-3.082,0.48-4.333,1.291c-1.231-5.317-5.974-9.29-11.665-9.29c-6.626,0-11.998,5.372-11.998,11.998
	c0,3.55,1.551,6.728,4,8.925v4.916c-4.777-2.768-8-7.922-8-13.841c0-8.835,7.163-15.997,15.998-15.997
	c6.004,0,11.229,3.311,13.965,8.203c0.664-0.113,1.338-0.205,2.033-0.205c6.627,0,11.999,5.372,11.999,11.999
	C71.942,58.863,68.601,63.293,63.943,64.941z M41.946,53.641c1.104,0,1.999,0.896,1.999,2v15.998c0,1.105-0.895,2-1.999,2
	s-2-0.895-2-2V55.641C39.946,54.537,40.842,53.641,41.946,53.641z M49.945,57.641c1.104,0,2,0.895,2,2v15.998c0,1.104-0.896,2-2,2
	s-2-0.896-2-2V59.641C47.945,58.535,48.841,57.641,49.945,57.641z M57.944,53.641c1.104,0,1.999,0.896,1.999,2v15.998
	c0,1.105-0.895,2-1.999,2s-2-0.895-2-2V55.641C55.944,54.537,56.84,53.641,57.944,53.641z"/>
</svg>';
	}

	// used for Sleet

	public static function climacons_svg_cloud_snow() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<path fill-rule="evenodd" clip-rule="evenodd" d="M63.999,64.943v-4.381c2.389-1.385,3.999-3.963,3.999-6.922
	c0-4.416-3.581-7.998-7.999-7.998c-1.6,0-3.083,0.48-4.333,1.291c-1.231-5.317-5.974-9.291-11.665-9.291
	c-6.627,0-11.998,5.373-11.998,12c0,3.549,1.55,6.729,4,8.924v4.916c-4.777-2.768-8-7.922-8-13.84
	c0-8.836,7.163-15.999,15.998-15.999c6.004,0,11.229,3.312,13.965,8.204c0.664-0.113,1.337-0.205,2.033-0.205
	c6.627,0,11.999,5.373,11.999,11.998C71.998,58.863,68.655,63.293,63.999,64.943z M42.001,57.641c1.105,0,2,0.896,2,2
	c0,1.105-0.895,2-2,2c-1.104,0-1.999-0.895-1.999-2C40.002,58.537,40.897,57.641,42.001,57.641z M42.001,65.641c1.105,0,2,0.895,2,2
	c0,1.104-0.895,1.998-2,1.998c-1.104,0-1.999-0.895-1.999-1.998C40.002,66.535,40.897,65.641,42.001,65.641z M50.001,61.641
	c1.104,0,2,0.895,2,2c0,1.104-0.896,2-2,2c-1.105,0-2-0.896-2-2C48.001,62.535,48.896,61.641,50.001,61.641z M50.001,69.639
	c1.104,0,2,0.896,2,2c0,1.105-0.896,2-2,2c-1.105,0-2-0.895-2-2C48.001,70.535,48.896,69.639,50.001,69.639z M57.999,57.641
	c1.105,0,2,0.896,2,2c0,1.105-0.895,2-2,2c-1.104,0-1.999-0.895-1.999-2C56,58.537,56.896,57.641,57.999,57.641z M57.999,65.641
	c1.105,0,2,0.895,2,2c0,1.104-0.895,1.998-2,1.998c-1.104,0-1.999-0.895-1.999-1.998C56,66.535,56.896,65.641,57.999,65.641z"/>
</svg>';
	}

	public static function climacons_svg_cloud_hail() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<path fill-rule="evenodd" clip-rule="evenodd" d="M63.999,64.941v-4.381c2.389-1.383,3.999-3.961,3.999-6.92
	c0-4.417-3.581-7.999-7.998-7.999c-1.601,0-3.084,0.48-4.334,1.291c-1.231-5.317-5.974-9.29-11.665-9.29
	c-6.626,0-11.998,5.372-11.998,11.998c0,3.55,1.55,6.728,3.999,8.925v4.916c-4.776-2.768-7.998-7.922-7.998-13.841
	c0-8.835,7.162-15.997,15.997-15.997c6.004,0,11.229,3.311,13.966,8.203c0.663-0.113,1.336-0.205,2.033-0.205
	c6.626,0,11.998,5.372,11.998,11.999C71.998,58.863,68.656,63.293,63.999,64.941z M42.002,65.639c-1.104,0-1-0.895-1-1.998v-8
	c0-1.104-0.104-2,1-2s1,0.896,1,2v8C43.002,64.744,43.106,65.639,42.002,65.639z M42.002,69.639c1.104,0,1.999,0.896,1.999,2
	c0,1.105-0.895,2-1.999,2s-2-0.895-2-2C40.002,70.535,40.897,69.639,42.002,69.639z M50.001,69.639c-1.104,0-1-0.895-1-2v-7.998
	c0-1.105-0.104-2,1-2s1,0.895,1,2v7.998C51.001,68.744,51.105,69.639,50.001,69.639z M50.001,73.639c1.104,0,1.999,0.895,1.999,2
	c0,1.104-0.895,2-1.999,2s-2-0.896-2-2C48.001,74.533,48.896,73.639,50.001,73.639z M58,65.639c-1.104,0-1-0.895-1-1.998v-8
	c0-1.104-0.104-2,1-2s1,0.896,1,2v8C59,64.744,59.104,65.639,58,65.639z M58,69.639c1.104,0,2,0.896,2,2c0,1.105-0.896,2-2,2
	s-2-0.895-2-2C56,70.535,56.896,69.639,58,69.639z"/>
</svg>';
	}

	public static function climacons_svg_wind() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<path fill-rule="evenodd" clip-rule="evenodd" d="M65.999,52L65.999,52h-3c-1.105,0-2-0.895-2-1.999s0.895-2,2-2h3
	c1.104,0,2-0.896,2-1.999c0-1.105-0.896-2-2-2c-1.105,0-2-0.896-2-2s0.895-2,2-2c0.137,0,0.271,0.014,0.402,0.041
	c3.121,0.211,5.596,2.783,5.596,5.959C71.997,49.314,69.312,52,65.999,52z M55.999,48.001h-2h-6.998H34.002
	c-1.104,0-1.999,0.896-1.999,2S32.898,52,34.002,52h2h3.999h3h4h3h3.998h2c3.314,0,6,2.687,6,6c0,3.176-2.475,5.748-5.596,5.959
	C56.272,63.986,56.138,64,55.999,64c-1.104,0-2-0.896-2-2c0-1.105,0.896-2,2-2c1.105,0,2-0.896,2-2s-0.895-2-2-2h-2h-3.998h-3h-4h-3
	h-3.999h-2c-3.313,0-5.999-2.686-5.999-5.999c0-3.175,2.475-5.747,5.596-5.959c0.131-0.026,0.266-0.04,0.403-0.04l0,0h12.999h6.998
	h2c1.105,0,2-0.896,2-2s-0.895-2-2-2c-1.104,0-2-0.895-2-2c0-1.104,0.896-2,2-2c0.139,0,0.273,0.015,0.404,0.041
	c3.121,0.211,5.596,2.783,5.596,5.959C61.999,45.314,59.313,48.001,55.999,48.001z"/>
</svg>';
	}

	public static function climacons_svg_cloud_fog() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<path fill-rule="evenodd" clip-rule="evenodd" d="M69.998,65.641H30.003c-1.104,0-2-0.896-2-2c0-1.105,0.896-2,2-2h39.995
	c1.104,0,2,0.895,2,2C71.998,64.744,71.103,65.641,69.998,65.641z M69.998,57.641H30.003c-1.104,0-2-0.895-2-2c0-1.104,0.896-2,2-2
	h39.995c1.104,0,2,0.896,2,2C71.998,56.746,71.103,57.641,69.998,57.641z M59.999,45.643c-1.601,0-3.083,0.48-4.333,1.291
	c-1.232-5.317-5.974-9.291-11.665-9.291c-6.626,0-11.998,5.373-11.998,12h-4c0-8.835,7.163-15.999,15.998-15.999
	c6.004,0,11.229,3.312,13.965,8.204c0.664-0.113,1.337-0.205,2.033-0.205c5.222,0,9.652,3.342,11.301,8h-4.381
	C65.535,47.253,62.958,45.643,59.999,45.643z M30.003,69.639h39.995c1.104,0,2,0.896,2,2c0,1.105-0.896,2-2,2H30.003
	c-1.104,0-2-0.895-2-2C28.003,70.535,28.898,69.639,30.003,69.639z"/>
</svg>';
	}

	public static function climacons_svg_cloud() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<path fill-rule="evenodd" clip-rule="evenodd" d="M43.945,65.639c-8.835,0-15.998-7.162-15.998-15.998
	c0-8.836,7.163-15.998,15.998-15.998c6.004,0,11.229,3.312,13.965,8.203c0.664-0.113,1.338-0.205,2.033-0.205
	c6.627,0,11.999,5.373,11.999,12c0,6.625-5.372,11.998-11.999,11.998C57.168,65.639,47.143,65.639,43.945,65.639z M59.943,61.639
	c4.418,0,8-3.582,8-7.998c0-4.418-3.582-8-8-8c-1.6,0-3.082,0.481-4.333,1.291c-1.231-5.316-5.974-9.29-11.665-9.29
	c-6.626,0-11.998,5.372-11.998,11.999c0,6.626,5.372,11.998,11.998,11.998C47.562,61.639,56.924,61.639,59.943,61.639z"/>
</svg>';
	}

	public static function climacons_svg_cloud_sun() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<path fill-rule="evenodd" clip-rule="evenodd" d="M79.941,43.641h-4c-1.104,0-2-0.895-2-2c0-1.104,0.896-1.998,2-1.998h4
	c1.104,0,2,0.895,2,1.998C81.941,42.746,81.045,43.641,79.941,43.641z M72.084,30.329c-0.781,0.781-2.047,0.781-2.828,0
	c-0.781-0.78-0.781-2.047,0-2.827l2.828-2.828c0.781-0.781,2.047-0.781,2.828,0c0.781,0.78,0.781,2.047,0,2.828L72.084,30.329z
	M69.137,45.936L69.137,45.936c1.749,2.086,2.806,4.77,2.806,7.705c0,6.625-5.372,11.998-11.999,11.998c-2.775,0-12.801,0-15.998,0
	c-8.835,0-15.998-7.162-15.998-15.998s7.163-15.998,15.998-15.998c1.572,0,3.09,0.232,4.523,0.654
	c2.195-2.827,5.618-4.654,9.475-4.654c6.627,0,11.999,5.373,11.999,11.998C69.942,43.156,69.649,44.602,69.137,45.936z
	M31.947,49.641c0,6.627,5.371,11.998,11.998,11.998c3.616,0,12.979,0,15.998,0c4.418,0,7.999-3.582,7.999-7.998
	c0-4.418-3.581-8-7.999-8c-1.6,0-3.083,0.482-4.333,1.291c-1.231-5.316-5.974-9.289-11.665-9.289
	C37.318,37.643,31.947,43.014,31.947,49.641z M57.943,33.643c-2.212,0-4.215,0.898-5.662,2.349c2.34,1.436,4.285,3.453,5.629,5.854
	c0.664-0.113,1.337-0.205,2.033-0.205c2.125,0,4.119,0.559,5.85,1.527l0,0c0.096-0.494,0.15-1.004,0.15-1.527
	C65.943,37.225,62.361,33.643,57.943,33.643z M57.943,25.643c-1.104,0-1.999-0.895-1.999-1.999v-3.999c0-1.105,0.896-2,1.999-2
	c1.105,0,2,0.895,2,2v3.999C59.943,24.749,59.049,25.643,57.943,25.643z M43.803,30.329l-2.827-2.827
	c-0.781-0.781-0.781-2.048,0-2.828c0.78-0.781,2.047-0.781,2.827,0l2.828,2.828c0.781,0.78,0.781,2.047,0,2.827
	C45.851,31.11,44.584,31.11,43.803,30.329z"/>
</svg>';
	}

	public static function climacons_svg_cloud_moon() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<path fill-rule="evenodd" clip-rule="evenodd" d="M69.763,46.758L69.763,46.758c1.368,1.949,2.179,4.318,2.179,6.883
	c0,6.625-5.371,11.998-11.998,11.998c-2.775,0-12.801,0-15.998,0c-8.836,0-15.998-7.162-15.998-15.998s7.162-15.998,15.998-15.998
	c2.002,0,3.914,0.375,5.68,1.047l0,0c1.635-4.682,6.078-8.047,11.318-8.047c0.755,0,1.491,0.078,2.207,0.212
	c-0.131,0.575-0.207,1.173-0.207,1.788c0,4.418,3.581,7.999,7.998,7.999c0.616,0,1.213-0.076,1.789-0.208
	c0.133,0.717,0.211,1.453,0.211,2.208C72.941,41.775,71.73,44.621,69.763,46.758z M31.947,49.641
	c0,6.627,5.371,11.998,11.998,11.998c3.616,0,12.979,0,15.998,0c4.418,0,7.999-3.582,7.999-7.998c0-4.418-3.581-8-7.999-8
	c-1.6,0-3.083,0.482-4.334,1.291c-1.231-5.316-5.973-9.29-11.664-9.29C37.318,37.642,31.947,43.014,31.947,49.641z M51.496,35.545
	c0.001,0,0.002,0,0.002,0S51.497,35.545,51.496,35.545z M59.155,30.85c-2.9,0.664-5.175,2.91-5.925,5.775l0,0
	c1.918,1.372,3.523,3.152,4.68,5.22c0.664-0.113,1.337-0.205,2.033-0.205c2.618,0,5.033,0.85,7.005,2.271l0,0
	c0.858-0.979,1.485-2.168,1.786-3.482C63.881,39.525,60.059,35.706,59.155,30.85z"/>
</svg>';
	}

	public static function climacons_svg_sunrise() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<g>
	<g>
		<path fill-rule="evenodd" clip-rule="evenodd" d="M38.688,41.859l-2.828-2.828c-0.781-0.78-2.047-0.78-2.828,0
			c-0.781,0.781-0.781,2.047,0,2.828l2.828,2.828c0.781,0.781,2.047,0.781,2.828,0C39.469,43.906,39.469,42.641,38.688,41.859z
			M71.997,54h-3.999c-1.104,0-1.999,0.896-1.999,2s0.895,2,1.999,2h3.999c1.105,0,2-0.896,2-2S73.103,54,71.997,54z M32.003,54h-4
			c-1.104,0-2,0.896-2,2s0.896,2,2,2h4c1.104,0,2-0.896,2-2S33.106,54,32.003,54z M59.999,63.999H40.001
			c-1.104,0-1.999,0.896-1.999,2s0.896,1.999,1.999,1.999h19.998c1.104,0,2-0.895,2-1.999S61.104,63.999,59.999,63.999z
			M66.969,39.031c-0.78-0.78-2.048-0.78-2.828,0l-2.828,2.828c-0.78,0.781-0.78,2.047,0,2.828c0.781,0.781,2.048,0.781,2.828,0
			l2.828-2.828C67.749,41.078,67.749,39.812,66.969,39.031z M50.001,40.002c1.104,0,1.999-0.896,1.999-2v-3.999
			c0-1.104-0.896-2-1.999-2c-1.105,0-2,0.896-2,2v3.999C48.001,39.106,48.896,40.002,50.001,40.002z M50.001,44.002
			c-6.627,0-11.999,5.371-11.999,11.998c0,1.404,0.254,2.747,0.697,3.999h4.381c-0.683-1.177-1.079-2.54-1.079-3.999
			c0-4.418,3.582-7.999,8-7.999c4.417,0,7.998,3.581,7.998,7.999c0,1.459-0.396,2.822-1.078,3.999h4.381
			c0.443-1.252,0.697-2.595,0.697-3.999C61.999,49.373,56.627,44.002,50.001,44.002z M50.001,60.249c0.552,0,0.999-0.447,0.999-1
			v-3.827l2.536,2.535c0.39,0.391,1.023,0.391,1.414,0c0.39-0.391,0.39-1.023,0-1.414l-4.242-4.242
			c-0.391-0.391-1.024-0.391-1.414,0l-4.242,4.242c-0.391,0.391-0.391,1.023,0,1.414s1.023,0.391,1.414,0l2.535-2.535v3.827
			C49.001,59.802,49.448,60.249,50.001,60.249z"/>
	</g>
</g>
</svg>';
	}

	public static function climacons_svg_sunset() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<path fill-rule="evenodd" clip-rule="evenodd" d="M71.998,58h-4c-1.104,0-1.999-0.896-1.999-2s0.895-2,1.999-2h4
	c1.104,0,1.999,0.896,1.999,2S73.103,58,71.998,58z M64.142,44.688c-0.781,0.781-2.048,0.781-2.828,0
	c-0.781-0.781-0.781-2.047,0-2.828l2.828-2.828c0.78-0.78,2.047-0.78,2.827,0c0.781,0.781,0.781,2.047,0,2.828L64.142,44.688z
	M61.302,59.999h-4.381c0.682-1.177,1.078-2.54,1.078-3.999c0-4.418-3.581-7.999-7.998-7.999c-4.418,0-8,3.581-8,7.999
	c0,1.459,0.397,2.822,1.08,3.999h-4.382c-0.443-1.252-0.697-2.595-0.697-3.999c0-6.627,5.372-11.998,11.999-11.998
	c6.626,0,11.998,5.371,11.998,11.998C61.999,57.404,61.745,58.747,61.302,59.999z M50.001,40.002c-1.105,0-2-0.896-2-2v-3.999
	c0-1.104,0.895-2,2-2c1.104,0,2,0.896,2,2v3.999C52.001,39.106,51.104,40.002,50.001,40.002z M35.86,44.688l-2.828-2.828
	c-0.781-0.781-0.781-2.047,0-2.828c0.781-0.78,2.047-0.78,2.828,0l2.828,2.828c0.781,0.781,0.781,2.047,0,2.828
	S36.642,45.469,35.86,44.688z M34.003,56c0,1.104-0.896,2-2,2h-4c-1.104,0-2-0.896-2-2s0.896-2,2-2h4
	C33.107,54,34.003,54.896,34.003,56z M50.001,52c0.552,0,1,0.448,1,1v3.828l2.535-2.535c0.391-0.391,1.023-0.391,1.414,0
	s0.391,1.023,0,1.414l-4.242,4.242c-0.391,0.391-1.023,0.391-1.414,0l-4.242-4.242c-0.391-0.391-0.391-1.023,0-1.414
	s1.023-0.391,1.414,0l2.535,2.535V53C49.001,52.448,49.448,52,50.001,52z M40.002,63.999h19.997c1.104,0,2,0.896,2,2
	s-0.896,1.999-2,1.999H40.002c-1.104,0-2-0.895-2-1.999S38.897,63.999,40.002,63.999z"/>
</svg>';
	}

	public static function climacons_svg_compass_north() {
		return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)	-->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
	width="100px" height="100px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<g>
	<g>
		<path fill-rule="evenodd" clip-rule="evenodd" d="M50,36.002c-7.731,0-13.999,6.268-13.999,13.998
			c0,7.731,6.268,13.999,13.999,13.999c7.73,0,13.998-6.268,13.998-13.999C63.998,42.27,57.73,36.002,50,36.002z M50,59.999
			c-5.522,0-9.999-4.477-9.999-9.999c0-5.521,4.477-9.998,9.999-9.998c5.521,0,9.999,4.477,9.999,9.998
			C59.999,55.522,55.521,59.999,50,59.999z M46,50c0,2.209,1.791,4,4,4s3.999-1.791,3.999-4S50,42.001,50,42.001S46,47.791,46,50z
			M51,50c0,0.553-0.448,1-1,1c-0.553,0-1-0.447-1-1c0-0.552,0.447-1,1-1C50.552,49,51,49.448,51,50z"/>
	</g>
</g>
</svg>';
	}

	/**
	 * @param        $input
	 * @param string $icon_type
	 *
	 * @return bool|string
	 */
	public static function wind_bearing_to_icon( $input, $icon_type = 'climacons_font' ) {
		if ( ! is_integer( $input ) ) { // not empty() because of allowable Zero
			return false;
		}

		if ( ! in_array( $icon_type, self::valid_icon_type() ) ) {
			$icon_type = 'climacons_font';
		}

		if ( 'climacons_font' == $icon_type ) {
			$result = sprintf( '<i style="-ms-transform: rotate(%1$ddeg); -webkit-transform: rotate(%1$ddeg); transform: rotate(%1$ddeg);" class="%2$s__wind-direction-icon climacon compass north"></i>', $input, TK_EVENT_WEATHER_HYPHENS );
		} elseif ( 'climacons_svg' == $icon_type ) {
			// $result = $climacons_svg;
		} else {
			// nothing
		}

		return $result;
	}

	/**
	 * @param $input
	 *
	 * @return bool|string|void
	 */
	public static function temperature_units( $input ) {
		if ( empty( $input ) || ! array_key_exists( $input, API_Dark_Sky::valid_units() ) ) {
			return false;
		}

		if ( 'us' == $input ) {
			$result = __( 'F', 'tk-event-weather' ); // Fahrenheit
		} else {
			$result = __( 'C', 'tk-event-weather' ); // Celsius
		}

		return $result;
	}

	/**
	 * @param        $temperature
	 * @param int    $temperature_decimals
	 * @param string $degree
	 *
	 * @return bool|float|string
	 */
	public static function temperature_to_display( $temperature, $temperature_decimals = 0, $degree = '&deg;' ) {
		if ( ! is_numeric( $temperature ) ) {
			return false;
		}

		$result = self::rounded_float_value( $temperature, $temperature_decimals );

		if ( ! empty( $degree ) ) {
			$degree = sprintf( '<span class="degree-symbol">%s</span>', $degree );
		}

		$result .= $degree;

		return $result;
	}

	/**
	 * round value to zero or custom decimals (e.g. used for temperatures)
	 * does not "pad with zeros" if rounded to 5 decimals and $input is only 2 decimals, will output as 2 decimals
	 *
	 * @param     $input
	 * @param int $decimals
	 *
	 * @return float
	 */
	public static function rounded_float_value( $input, $decimals = 0 ) {
		$input = self::remove_all_whitespace( strtolower( $input ) );

		$input = floatval( $input );

		$decimals = intval( $decimals );
		if ( 0 > $decimals ) {
			$decimals = 0;
		}

		$result = round( $input, $decimals );

		return $result;
	}


	/**
	 *
	 * https://en.wikipedia.org/wiki/Cardinal_direction
	 * https://en.wikipedia.org/wiki/Points_of_the_compass
	 * https://stackoverflow.com/questions/7490660/converting-wind-direction-in-angles-to-text-words
	 * http://climate.umn.edu/snow_fence/components/winddirectionanddegreeswithouttable3.htm (2019-08-04 does not work)
	 *
	 **/
	// required to be an integer
	/**
	 *
	 * https://www.weather.gov/epz/wxcalc_windconvert
	 *
	 * if 'us' or 'uk2', MPH
	 * if 'si', meters per second
	 * if 'ca', km/h
	 *
	 **/
	public static function wind_speed_units( $input ) {
		if ( empty( $input ) || ! array_key_exists( $input, API_Dark_Sky::valid_units() ) ) {
			return false;
		}

		if ( 'us' == $input || 'uk2' == $input ) {
			$result = __( 'mph', 'tk-event-weather' ); // miles per hour
		} elseif ( 'si' == $input ) {
			$result = __( 'm/s', 'tk-event-weather' ); // meters per second
		} else {
			$result = __( 'km/h', 'tk-event-weather' ); // kilometers per hour
		}

		return $result;
	}


	public static function wind_bearing_to_direction( $input, $direction_initials = true, $precision = 8 ) {
		if ( ! is_integer( $input ) ) { // not empty() because of allowable Zero
			return false;
		}

		$result = '';

		// 8 = 360 / 45
		// 16 = 360 / 22.5
		if ( 8 !== $precision && 16 !== $precision ) {
			$precision = 8;
		}

		if ( 8 === $precision ) {
			$bearing_index = $input / 45;
		} elseif ( 16 === $precision ) {
			$bearing_index = $input / 22.5;
		}

		if ( ! isset( $bearing_index ) ) {
			return false;
		}

		$bearing_index = intval( round( $bearing_index ) );

		if ( 8 == $precision ) {
			if ( 8 == $bearing_index || 0 == $bearing_index ) {
				$result = __( 'N', 'tk-event-weather' );
			} elseif ( 1 == $bearing_index ) {
				$result = __( 'NE', 'tk-event-weather' );
			} elseif ( 2 == $bearing_index ) {
				$result = __( 'E', 'tk-event-weather' );
			} elseif ( 3 == $bearing_index ) {
				$result = __( 'SE', 'tk-event-weather' );
			} elseif ( 4 == $bearing_index ) {
				$result = __( 'S', 'tk-event-weather' );
			} elseif ( 5 == $bearing_index ) {
				$result = __( 'SW', 'tk-event-weather' );
			} elseif ( 6 == $bearing_index ) {
				$result = __( 'W', 'tk-event-weather' );
			} elseif ( 7 == $bearing_index ) {
				$result = __( 'NW', 'tk-event-weather' );
			} else {
				// should not happen
			}
		} elseif ( 16 == $precision ) {
			if ( 16 == $bearing_index || 0 == $bearing_index ) {
				$result = __( 'N', 'tk-event-weather' );
			} elseif ( 1 == $bearing_index ) {
				$result = __( 'NNE', 'tk-event-weather' );
			} elseif ( 2 == $bearing_index ) {
				$result = __( 'NE', 'tk-event-weather' );
			} elseif ( 3 == $bearing_index ) {
				$result = __( 'ENE', 'tk-event-weather' );
			} elseif ( 4 == $bearing_index ) {
				$result = __( 'E', 'tk-event-weather' );
			} elseif ( 5 == $bearing_index ) {
				$result = __( 'ESE', 'tk-event-weather' );
			} elseif ( 6 == $bearing_index ) {
				$result = __( 'SE', 'tk-event-weather' );
			} elseif ( 7 == $bearing_index ) {
				$result = __( 'SSE', 'tk-event-weather' );
			} elseif ( 8 == $bearing_index ) {
				$result = __( 'S', 'tk-event-weather' );
			} elseif ( 9 == $bearing_index ) {
				$result = __( 'SSW', 'tk-event-weather' );
			} elseif ( 10 == $bearing_index ) {
				$result = __( 'SW', 'tk-event-weather' );
			} elseif ( 11 == $bearing_index ) {
				$result = __( 'WSW', 'tk-event-weather' );
			} elseif ( 12 == $bearing_index ) {
				$result = __( 'W', 'tk-event-weather' );
			} elseif ( 13 == $bearing_index ) {
				$result = __( 'WNW', 'tk-event-weather' );
			} elseif ( 14 == $bearing_index ) {
				$result = __( 'NW', 'tk-event-weather' );
			} elseif ( 15 == $bearing_index ) {
				$result = __( 'NNW', 'tk-event-weather' );
			} else {
				// should not happen
			}
		} else {
			//
		}

		if ( false === (bool) $direction_initials ) {
			if ( 'N' == $result ) {
				$result = __( 'north', 'tk-event-weather' );
			} elseif ( 'NNE' == $result ) {
				$result = __( 'north-northeast', 'tk-event-weather' );
			} elseif ( 'NE' == $result ) {
				$result = __( 'northeast', 'tk-event-weather' );
			} elseif ( 'ENE' == $result ) {
				$result = __( 'east-northeast', 'tk-event-weather' );
			} elseif ( 'E' == $result ) {
				$result = __( 'east', 'tk-event-weather' );
			} elseif ( 'ESE' == $result ) {
				$result = __( 'east-southeast', 'tk-event-weather' );
			} elseif ( 'SE' == $result ) {
				$result = __( 'southeast', 'tk-event-weather' );
			} elseif ( 'SSE' == $result ) {
				$result = __( 'south-southeast', 'tk-event-weather' );
			} elseif ( 'S' == $result ) {
				$result = __( 'south', 'tk-event-weather' );
			} elseif ( 'SSW' == $result ) {
				$result = __( 'south-southwest', 'tk-event-weather' );
			} elseif ( 'SW' == $result ) {
				$result = __( 'southwest', 'tk-event-weather' );
			} elseif ( 'WSW' == $result ) {
				$result = __( 'west-southwest', 'tk-event-weather' );
			} elseif ( 'W' == $result ) {
				$result = __( 'west', 'tk-event-weather' );
			} elseif ( 'WNW' == $result ) {
				$result = __( 'west-northwest', 'tk-event-weather' );
			} elseif ( 'NW' == $result ) {
				$result = __( 'northwest', 'tk-event-weather' );
			} elseif ( 'NNW' == $result ) {
				$result = __( 'north-northwest', 'tk-event-weather' );
			} else {
				// should not happen
			}
		}

		return $result;
	}

}