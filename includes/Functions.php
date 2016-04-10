<?php

require_once( 'FuncSetup.php' );
require_once( 'TemplateLoader.php' );

class TkEventWeather__Functions {
  // all variables and methods should be 'static'
    
  public static function plugin_options() {
	  $plugin_options = get_option( 'tk_event_weather' );
    if ( ! empty( $plugin_options ) ) {
      return $plugin_options;
    } else {
      return false;
    }
  }
  
  /**
    *
    * Views / Templates
    * Reference: https://pippinsplugins.com/template-file-loaders-plugins/
    * Reference: https://github.com/GaryJones/Gamajo-Template-Loader/tree/template-data
    *
    */
  
  public static function new_template_loader() {
    return new TkEventWeather__TemplateLoader();
  }
  
  public static function load_template( $slug, $data = array(), $name = null, $load = true ) {
    $template_loader = self::new_template_loader();
    $template_loader->set_template_data( $data, 'context' ); // passed-through data becomes accessible as $context->piece_of_data within template
    $template_loader->get_template_part( $slug, $name, $load );
  }
  
  public static function valid_display_templates( $prepend_empty = 'false' ) {
    $result = array(
      'hourly_horizontal' => __( 'Hourly (Horizontal)', 'tk-event-weather' ),
      'hourly_vertical'   => __( 'Hourly (Vertical)', 'tk-event-weather' ),
      'low_high'          => __( 'Low-High Temperature', 'tk-event-weather' ),
    );
    
    $custom_display_templates = apply_filters( 'tk_event_weather_custom_display_templates', array() );
    
    if ( ! empty( $custom_display_templates ) ) {
      $result = array_merge( $result, $custom_display_templates );
    }
    
    if ( 'true' == $prepend_empty ) {
      $result = self::array_prepend_empty( $result );
    }
    
    return $result;
  }
  
  
  public static function register_css() {
    wp_register_style( TkEventWeather__FuncSetup::shortcode_name_hyphenated(), TkEventWeather__FuncSetup::plugin_dir_url_root() . 'css/tk-event-weather.css', array(), null );
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
    wp_register_style( 'tkeventw-climacons', TkEventWeather__FuncSetup::plugin_dir_url_vendor() . 'climacons/climacons-font.css', array(), null );
  }
  
  
  /**
   * Clean variables using sanitize_text_field.
   * @param string|array $var
   * @return string|array
   */
  public static function tk_clean_var( $var ) {
    return is_array( $var ) ? array_map( 'tk_clean_var', $var ) : sanitize_text_field( $var );
  }
  
  public static function array_get_value_by_key( $array, $key, $fallback = '' ) {
    if( ! is_array( $array )
      || empty( $array )
      || ! isset( $array[$key] ) // use instead of array_key_exists()
    ) {
      $result = $fallback;
    } else {
      $result = $array[$key];
    }
    
    return $result; // consider strval()?
  }
  
  public static function array_prepend_empty( $input ) {
    if( ! is_array( $input ) ) {
      $result = false;
    } else {
      $result = array( '' => '' ) + $input;
    }
    
    return $result;
  }
  
  public static function all_valid_wp_capabilities() {
    $all_roles = wp_roles();
    
    $all_capabilities = array();
    
    foreach ( $all_roles->roles as $key => $value ) {
      $all_capabilities[] = array_keys( $value['capabilities'], true, true );
    }
    
    $all_capabilities_flattened = array();
    
    foreach ( $all_capabilities as $key => $value ) {
      foreach ( $value as $key_a => $value_a ) {
        $all_capabilities_flattened[] = $value_a;
      }
    }
    
    $all_capabilities = array_unique( $all_capabilities_flattened );
    sort( $all_capabilities );
    
    return $all_capabilities;
  }
  
  public static function invalid_shortcode_message( $input = '', $capability = 'edit_theme_options' ) {
    $capability = apply_filters ( 'tk_event_weather_shortcode_msg_cap', $capability );
    
    if( ! in_array( $capability, self::all_valid_wp_capabilities() ) ) {
      $capability = 'edit_theme_options';
    }
        
    // escape single apostrophes
    $error_reason = str_replace( "'", "\'", $input );
    
    $shortcode_name = TkEventWeather__FuncSetup::$shortcode_name;
    
    if( ! empty( $error_reason ) ) {
      $message = sprintf( __( '%s for the `%s` shortcode to work correctly.', 'tk-event-weather' ), $error_reason, $shortcode_name );
    } else {
      $message = sprintf( __( 'Invalid or incomplete usage of the `%s` shortcode.', 'tk-event-weather' ), $shortcode_name );
    }
    
    $message = sprintf( __( '%s (Error message only displayed to users with the `%s` capability.)', 'tk-event-weather' ), $message, $capability );
    
    $result = '';
  	if( current_user_can( $capability ) ) {
    	$result .= sprintf( '<div class="%s">%s</div>',
    	  self::shortcode_error_class_name(),
    	  esc_html ( $message )
      );
  	}
  	
  	return $result;
  }
  
  // round value to zero or custom decimals (e.g. used for temperatures)
  // does not "pad with zeros" if rounded to 5 decimals and $input is only 2 decimals, will output as 2 decimals
  public static function rounded_float_value( $input, $decimals = 0 ) {
    $input = self::remove_all_whitespace ( strtolower( $input ) );
    
    $input = floatval( $input );
    
    $result = '';
    
  	$decimals = intval( $decimals );
  	if ( 0 > $decimals ) {
    	$decimals = 0;
  	}
  	
  	$result = round( $input, $decimals );
  	
    return $result;
  }
  
  // START Forecast.io valid options
  // @link https://developer.forecast.io/docs/v2
  
  // may select only one per API call
  public static function forecast_io_option_units( $prepend_empty = 'false' ) {
    $result = array(
      'auto'  => __( 'Auto (Default)', 'tk-event-weather' ),
      'ca'    => __( 'Canada', 'tk-event-weather' ),
      'si'    => __( 'SI (International System of Units)', 'tk-event-weather' ),
      'uk2'   => __( 'UK', 'tk-event-weather' ),
      'us'    => __( 'USA', 'tk-event-weather' ),
    );
    
    if ( 'true' == $prepend_empty ) {
      $result = self::array_prepend_empty( $result );
    }
    
    return $result;
  }
  
  // may select one or multiple (but not all since that would be valid but return no data)
  public static function forecast_io_option_exclude( $prepend_empty = 'false' ) {
    $result = array(
      'currently'  => __( 'Currently', 'tk-event-weather' ),
      'minutely'   => __( 'Minutely', 'tk-event-weather' ),
      'hourly'     => __( 'Hourly', 'tk-event-weather' ),
      'daily'      => __( 'Daily', 'tk-event-weather' ),
      'alerts'     => __( 'Alerts', 'tk-event-weather' ),
      'flags'      => __( 'Flags', 'tk-event-weather' ),
    );
    
    if ( 'true' == $prepend_empty ) {
      $result = self::array_prepend_empty( $result );
    }
    
    return $result;
  }
  //
  // END Forecast.io valid options
  //
  
  
  // UTC Offset options
  public static function valid_utc_offset_types( $prepend_empty = 'false' ) {
    $result = array(
      'api'       => __( 'From API (i.e. Location-specific)', 'tk-event-weather' ),
      'wordpress' => __( 'From WordPress General Settings', 'tk-event-weather' ),
    );
    
    if ( 'true' == $prepend_empty ) {
      $result = self::array_prepend_empty( $result );
    }
    
    return $result;
  }
  
  
  // to be nice and link to http://tourkick.com/plugins/tk-event-weather/
  public static function plugin_credit_link() {
    $url = 'http://tourkick.com/plugins/tk-event-weather/?utm_source=plugin-credit-link&utm_medium=free-plugin&utm_term=Event%20Weather%20plugin&utm_campaign=TK%20Event%20Weather';
    
    $anchor_text = __( 'TK Event Weather plugin', 'tk-event-weather' );
    
    $result = sprintf( '<div class="tk-event-weather__plugin-credit">
      <a href="%s" target="_blank">%s</a>
    </div>',
      $url,
      $anchor_text
    );
    
    return $result;
  }
  
  // to comply with https://developer.forecast.io/
  public static function forecast_io_credit_link() {
    $url = 'http://forecast.io/';
    
    $anchor_text = __( 'Powered by Forecast', 'tk-event-weather' );
    
    $result = sprintf( '<div class="tk-event-weather__forecast-io-credit">
      <a href="%s" target="_blank">%s</a>
    </div>',
      $url,
      $anchor_text
    );
    
    return $result;
  }
  
  
  // Valid strtotime() relative time
  // @link http://php.net/manual/en/function.strtotime.php
  // @link http://php.net/manual/en/datetime.formats.relative.php -- plural "s" is optional for all except "weeks" so just used plural for all
  public static function valid_strtotime_units( $prepend_empty = 'false' ) {
    $result = array(
      'hours'  => __( 'Hours', 'tk-event-weather' ),
      'days'   => __( 'Days (Default)', 'tk-event-weather' ),
      'weeks'  => __( 'Weeks', 'tk-event-weather' ),
      'months' => __( 'Months', 'tk-event-weather' ),
      'years'  => __( 'Years', 'tk-event-weather' ),
    );
    
    if ( 'true' == $prepend_empty ) {
      $result = self::array_prepend_empty( $result );
    }
    
    return $result;
  }
  
  
  public static function remove_all_whitespace( $input ) {
    return preg_replace( '/\s+/', '', $input );
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
    * Verify string is valid latitude,longitude
    * Forecast.io does not allow certain lat,long -- such as 0,0
    * 
    * @since 1.0.0
    * 
    * @link https://en.wikipedia.org/wiki/Decimal_degrees
    * @link https://regex101.com/r/fZ1oR3/1
    * 
    * @param string $input Comma-separated latitude and longitude in decimal degrees.
    * @param string $return_format Optional. 
    * 
    * @return $result If valid returns string or bool true, based on $return_format. If invalid, returns '' string or bool false, based on $return_format.
    */
  public static function valid_lat_long( $input, $return_format = '' ) {
    $input = self::remove_all_whitespace ( $input );
    
    if ( ! empty( $return_format ) && 'bool' != $return_format ) {
      $return_format = '';
    }
    
    // is not valid lat,long FORMAT
	  $match = preg_match( '/^([-+]?[1-8]?\d(?:\.\d+)?|90(?:\.0+)?),\s*([-+]?(?:180(?:\.0+)?|(?:(?:1[0-7]\d)|(?:[1-9]?\d))(?:\.\d+)?))$/', $input );
    if( empty( $match ) ) {
      if ( '' == $return_format ) {
        return '';
      } else {
        return false;
      }
    }
    
    // separate lat,long
    $lat_long_array = explode( ',', $input );
    $latitude = floatval( $lat_long_array[0] );
    $longitude = floatval( $lat_long_array[1] );
    
    // is not valid Forecast.io lat or long
    if(
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
  
  /**
    * if valid timestamp, returns integer timestamp
    * or returns boolean if $return_format = 'bool' and is valid timestamp
    * else returns empty string
    */
  public static function valid_timestamp( $input, $return_format = '' ) {
    $result = self::remove_all_whitespace( $input ); // converts to string
    
    if ( is_numeric( $result ) ) {
      $result = intval( $result ); // convert to integer
    }
    
    if ( ! empty( $return_format ) && 'bool' != $return_format ) {
      $return_format = '';
    }
    
    // is valid timestamp
    if ( is_int( $result ) && date( 'U', $result ) == intval ( $result ) ) {      
      if( '' == $return_format ) {
        return intval( $result );
      } else {
        return true;
      }
    }
    // is NOT valid
    else {
      if ( '' == $return_format ) {
        return '';
      } else {
        return false;
      }
    }
  }
  
  public static function valid_iso_8601_date_time( $input, $return_format = '' ) {
    $result = self::remove_all_whitespace( $input );
    
    if ( ! empty( $return_format ) && 'bool' != $return_format ) {
      $return_format = '';
    }
    
    // is valid ISO 8601 time (i.e. we do not want valid ISO 8601 Duration, Time Interval, etc.)
    // API requires [YYYY]-[MM]-[DD]T[HH]:[MM]:[SS] -- https://en.wikipedia.org/wiki/ISO_8601#Combined_date_and_time_representations
    // with an optional time zone formatted as Z for UTC time or {+,-}[HH]:[MM] (with or without separating colon) for an offset in hours or minutes
    // For the latter format, if no timezone is present, local time (at the provided latitude and longitude) is assumed.
/*
    @link https://regex101.com/r/mL0xZ4/1
    Should match ISO 8601 datetime for Forecast.io API:
    [YYYY]-[MM]-[DD]T[HH]:[MM]:[SS]
    with an optional time zone formatted as Z for UTC time or {+,-}[HH]:[MM] (with or without separating colon) for an offset
    
    Does Match:
    2008-09-15T15:53:00
    2007-03-01T13:00:00Z
    2015-10-05T21:46:54-1500
    2015-10-05T21:46:54+07:00
    
    Does Not Match:
    2015-10-05T21:46:54-02
    0
    2008
    2008-09
    2008-09-15
    2008-09-15 11:12:13
    2008-09-15 11:12
    1988-05-26T23:00:00.000Z
*/
    if( 1 == preg_match( '/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(Z|[\+-]\d{2}:?\d{2})?$/', $result ) ) {
      if( '' == $return_format ) {
        return $result;
      } else {
        return true;
      }
    }
    // is NOT valid
    else {
      if ( '' == $return_format ) {
        return '';
      } else {
        return false;
      }
    }
  }
  
  
  
  /**
   * Check if a string is a valid PHP timezone
   *
   * timezone_identifiers_list() requires PHP >= 5.2
   *
   * @param string $input
   * @return bool
   * @link http://www.pontikis.net/tip/?id=28
   */
/*
  public static function valid_php_timezone_string( $input = '' ) {
    if ( empty ( $input ) || ! in_array( $input, timezone_identifiers_list() ) ) {
      $result = false;
    } else {
      $result = true;
    }
    
    return $result;
  }
*/
  
  
  
  /**
   * Returns the timezone string for a site, even if it's set to a UTC offset
   *
   * Adapted from http://www.php.net/manual/en/function.timezone-name-from-abbr.php#89155
   *
   * @link https://www.skyverge.com/blog/down-the-rabbit-hole-wordpress-and-timezones/
   * @return string valid PHP timezone string
   */
/*
  public static function wp_get_timezone_string() {
 
    // if site timezone string exists, return it
    if ( $timezone = get_option( 'timezone_string' ) ) {
      return $timezone;
    }
 
    // get UTC offset, if it is not set then return UTC
    $utc_offset = get_option( 'gmt_offset', 0 );
    
    if ( 0 === $utc_offset ) {
      return 'UTC';
    }
 
    // adjust UTC offset from hours to seconds
    $utc_offset *= 3600;
 
    // attempt to guess the timezone string from the UTC offset
    if ( $timezone = timezone_name_from_abbr( '', $utc_offset, 0 ) ) {
      return $timezone;
    }
 
    // last try, guess timezone string manually
    $is_dst = date( 'I' );
 
    foreach ( timezone_abbreviations_list() as $abbr ) {
      foreach ( $abbr as $city ) {
        if ( $is_dst == $city['dst'] && $utc_offset == $city['offset'] )
          return $city['timezone_id'];
      }
    }
     
    // fallback to UTC
    return 'UTC';
  }  
*/
  
  
  //public static function 
  
  
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
  
  // 
  // Climacons SVGs
  // https://github.com/christiannaths/Climacons-Font/tree/master/SVG
  // 
  public static function climacons_svg_sun() {
    return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
  
  public static function climacons_svg_cloud_snow() {
    return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
  
  // used for Sleet
  public static function climacons_svg_cloud_hail() {
    return '<?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
<!-- Generator: Adobe Illustrator 15.1.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
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
  
  public static function icon_html( $input, $icon_type = 'climacons_font' ) {
    $input = self::remove_all_whitespace ( strtolower( $input ) );
    
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
			'clear-day'           => 'fa-sun-o',
			'clear-night'         => 'fa-moon-o',
			'rain'                => 'fa-umbrella',
			'snow'                => 'fa-tint',
			'sleet'               => 'fa-tint',
			'wind'                => 'fa-send',
			'fog'                 => 'fa-shield',
			'cloudy'              => 'fa-cloud',
			'partly-cloudy-day'   => 'fa-cloud',
			'partly-cloudy-night' => 'fa-star-half',
			'sunrise'             => 'fa-arrow-up',
			'sunset'              => 'fa-arrow-down',
			'compass-north'       => 'fa-arrow-circle-o-up',
		);
*/
		
    if ( 'climacons_font' == $icon_type ) {
      $icon = $climacons_font[$input];
      $result = sprintf( '<i class="climacon %s"></i>', $icon );
    } elseif ( 'climacons_svg' == $icon_type ) {
      $icon = $climacons_svg[$input];
      $result = $icon;
    }
    /*
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
  
  public static function wind_bearing_to_icon( $input, $icon_type = 'climacons_font' ) {
  	if( ! is_integer( $input ) ) { // not empty() because of allowable Zero
    	return false;
  	}
    
    if ( ! in_array( $icon_type, self::valid_icon_type() ) ) {
      $icon_type = 'climacons_font';
    }
    
    
    if ( 'climacons_font' == $icon_type ) {
      $result = sprintf( '<i style="-ms-transform: rotate(%1$ddeg); -webkit-transform: rotate(%1$ddeg); transform: rotate(%1$ddeg);" class="tk-event-weather__wind-direction-icon climacon compass north"></i>', $input );
    } elseif ( 'climacons_svg' == $icon_type ) {
      $result = $climacons_svg;
    } else {
      // nothing
    }
        
    return $result;
  }
    
  public static function temperature_units( $input ) {
  	if( empty( $input ) || ! array_key_exists( $input, self::forecast_io_option_units() ) ) {
    	return false;
  	}
  	
  	if( 'us' == $input ) {
    	$result = __( 'F', 'tk-event-weather' ); // Fahrenheit
  	} else {
    	$result = __( 'C', 'tk-event-weather' ); // Celsius
  	}
  	
  	return $result;
  }
  
  public static function temperature_to_display( $temperature, $temperature_decimals = 0, $degree = '&deg;' ) {
  	if( ! is_numeric( $temperature ) ) {
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
    *
    * http://www.srh.noaa.gov/epz/?n=wxcalc_windconvert
    *
    * if 'us' or 'uk2', MPH
    * if 'si', meters per second
    * if 'ca', km/h
    *
  **/
  public static function wind_speed_units( $input ) {
  	if ( empty( $input ) || ! array_key_exists( $input, self::forecast_io_option_units() ) ) {
    	return false;
  	}
  	
  	if ( 'us' == $input || 'uk2' == $input ) {
    	$result = __( 'mph', 'tk-event-weather' ); // miles per hour
  	} elseif( 'si' == $input ) {
    	$result = __( 'm/s', 'tk-event-weather' ); // meters per second
  	} else {
    	$result = __( 'km/h', 'tk-event-weather' ); // kilometers per hour
  	}
  	
  	return $result;
  }
  
  
  /**
    * 
    * https://en.wikipedia.org/wiki/Cardinal_direction
    * https://en.wikipedia.org/wiki/Points_of_the_compass
    * http://stackoverflow.com/questions/7490660/converting-wind-direction-in-angles-to-text-words
    * http://climate.umn.edu/snow_fence/components/winddirectionanddegreeswithouttable3.htm
    *
  **/
  // required to be an integer
  public static function wind_bearing_to_direction( $input, $direction_initials = true, $precision = 8 ) {
  	if( ! is_integer( $input ) ) { // not empty() because of allowable Zero
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
  	
    $bearing_index = intval ( round ( $bearing_index ) );
    
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
    }
  	
  	if ( 16 == $precision ) {
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
    }
    
    if ( false === boolval( $direction_initials ) ) {
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
  
  // e.g. 4:45pm -> 4:00pm
  public static function timestamp_truncate_minutes( $timestamp = '' ) {
    // timestamp
    if ( false === self::valid_timestamp( $timestamp, 'bool' ) ) {
      return false;
    } else {
      // modulus
      // 1 hour = 3600 seconds
      // e.g. 2:30 --> 30 minutes = 1800 seconds --> $timestamp = $timestamp - 1800;
      $timestamp -= $timestamp % 3600;
      
      return $timestamp;
    }
  }
  
  
  public static function timestamp_to_display( $timestamp = '', $utc_offset = '', $date_format = '' ) {
    // timestamp
    if ( false === self::valid_timestamp( $timestamp, 'bool' ) ) {
      return '';
    }
    
    
    if ( is_numeric ( $utc_offset ) ) {
      $utc_offset = floatval( $utc_offset );
    } else {
      $utc_offset = get_option( 'gmt_offset' );
    }
    
    $timestamp = $timestamp + ( $utc_offset * HOUR_IN_SECONDS ); // becomes a float
    $timestamp = intval( $timestamp );
    
    if ( empty ( $date_format ) ) {
      /* translators: hourly display time format, see https://developer.wordpress.org/reference/functions/date_i18n/#comment-972 */
      $date_format = __( 'ga' );
    }
    
    // return date ( $date_format, $timestamp );
    return date_i18n ( $date_format, $timestamp, false ); // false means use UTC -- to use PHP date(). true means use GMT -- to use PHP gmdate().
  }
  
  public static function template_class_name( $template_name = '' ) {
    $result = '';
    
    if ( array_key_exists( $template_name, self::valid_display_templates() ) ) {
      $result = sanitize_html_class ( sprintf( 'template-%s', $template_name ) );
    }
    
    return $result;
  }
  
  public static function shortcode_error_class_name() {
    $result = sanitize_html_class( strtolower( TkEventWeather__FuncSetup::$shortcode_name ) ) . '__error';
    return $result;
  }
  
  // does NOT close the opening DIV tag
  // could add optional argument to customize element (div, span, etc)
  public static function template_start_of_each_item( $template_class_name = '', $index = '' ) {
    $result = '<div class="';
    
    $template_class_name = sanitize_html_class( $template_class_name );
    
    if ( ! empty( $template_class_name ) && is_integer( $index ) ) {
      $result = sprintf( '<div class="%1$s__index-%2$d %1$s__item', $template_class_name, $index );
    } elseif ( ! empty( $template_class_name ) && ! is_integer( $index ) ) {
      $result = sprintf( '<div class="%1$s %1$s__item ', $template_class_name );
    } else {
      // nothing to do
    }
    
    return $result;
  }
  
}