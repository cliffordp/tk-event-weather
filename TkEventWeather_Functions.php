<?php

class TkEventWeather_Functions {
  // all variables and methods should be 'static'
  
  public static $transient_name_prepend = 'tkeventw';
  
  // https://wordpress.org/about/requirements/
  
  public static $min_allowed_version_php = '5.4';
  
  public static $min_allowed_version_mysql = '5.0';
  
  public static $min_allowed_version_wordpress = '4.3.0';
  
  public static $support_email_address = 'tko+support-tk-event-weather@tourkick.com';
  
  
  public static function plugin_options() {
    return get_option( 'tk_event_weather' );
  }
  
  
  // Outdated: http://adamwhitcroft.com/climacons/font/
  // https://github.com/christiannaths/Climacons-Font
  // https://github.com/christiannaths/Climacons-Font/blob/master/webfont/demo.html
  //
  // https://developer.wordpress.org/reference/functions/plugin_dir_url/ does respect HTTPS
  public static function register_climacons_css() {
    wp_register_style( 'tkeventw-climacons', plugin_dir_url( __FILE__ ) . 'climacons/climacons-font.css', array(), null );
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
  
  public static function invalid_shortcode_message( $input = '', $capability = 'edit_theme_options', $shortcode_name = 'tk-event-weather' ) {
    $capability = apply_filters ( 'tk_event_weather_shortcode_msg_cap', $capability );
    
    if( ! in_array( $capability, self::all_valid_wp_capabilities() ) ) {
      $capability = 'edit_theme_options';
    }
    
    // escape single apostrophes
    $error_reason = str_replace( "'", "\'", $input );
    
    if( ! empty( $error_reason ) ) {
      $message = sprintf( '%s for the `%s` shortcode to work correctly.', $error_reason, $shortcode_name );
    } else {
      $message = sprintf( 'Invalid or incomplete usage of the `%s` shortcode.', $shortcode_name );
    }
    
    $message = sprintf( '%s (Error message only displayed to users with the `%s` capability.)', $message, $capability );
    
    $result = '';
  	if( current_user_can( $capability ) ) {
    	$result .= sprintf( '<span class="%s">%s</span>',
    	  sanitize_html_class( strtolower( $shortcode_name ) ),
    	  esc_html__( $message, 'tk-event-weather' ) // probably wrong way to do i18n translation so test and possibly fix
      );
  	}
  	
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
  
  /**
    * Verify string is valid latitude,longitude
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
    $result = self::remove_all_whitespace ( $input );
    
    if ( ! empty( $return_format ) && 'bool' != $return_format ) {
      $return_format = '';
    }
    
    // is valid lat,long
    if( 1 == preg_match( '/^([-+]?[1-8]?\d(?:\.\d+)?|90(?:\.0+)?),\s*([-+]?(?:180(?:\.0+)?|(?:(?:1[0-7]\d)|(?:[1-9]?\d))(?:\.\d+)?))$/', $result ) ) {
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
  
  public static function valid_timestamp( $input, $return_format = '' ) {
    $result = self::remove_all_whitespace( $input );
    
    if ( ! empty( $return_format ) && 'bool' != $return_format ) {
      $return_format = '';
    }
    
    // is valid timestamp
    if ( is_numeric( $result ) && (int) $result == $result && date( 'U', $result ) == $result ) {
    // any count of 0-9 numbers with optional leading minus sign
    // if( 1 == preg_match( '/^[-]?\d*$/', $result ) ) {
      
      // regex technically allows "-0" so just remove the minus sign
      // if( '-0' == $result ) {
        // $result = '0';
      // }
      
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
  
  public static function valid_iso_8601_date_time( $input, $return_format = '' ) {
    $result = self::remove_all_whitespace( $input );
    
    if ( ! empty( $return_format ) && 'bool' != $return_format ) {
      $return_format = '';
    }
    
    // is valid ISO 8601 time (i.e. we do not want valid ISO 8601 Duration, Time Interval, etc.)
    // API requires [YYYY]-[MM]-[DD]T[HH]:[MM]:[SS] -- https://en.wikipedia.org/wiki/ISO_8601#Combined_date_and_time_representations
    // with an optional time zone formatted as Z for GMT time or {+,-}[HH]:[MM] (with or without separating colon) for an offset in hours or minutes
    // For the latter format, if no timezone is present, local time (at the provided latitude and longitude) is assumed.
/*
    @link https://regex101.com/r/mL0xZ4/1
    Should match ISO 8601 datetime for Forecast.io API:
    [YYYY]-[MM]-[DD]T[HH]:[MM]:[SS]
    with an optional time zone formatted as Z for GMT time or {+,-}[HH]:[MM] (with or without separating colon) for an offset
    
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
      'sunset'
    );
    
    if ( 'true' == $prepend_empty ) {
      $result = self::array_prepend_empty( $result );
    }
    
    return $result;
  }
  
  public static function icon_html( $input, $icon_type = 'climacons' ) {
    $input = self::remove_all_whitespace ( strtolower( $input ) );
    
    if ( ! in_array( $input, self::valid_api_icon() ) ) {
      return '';
    }
    
    $result = '';
    
    // Font Awesome (really not usable, plus you will need to add the icon font yourself (e.g. via https://wordpress.org/plugins/better-font-awesome/)
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
		);
		
		$climacons = array(
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
		);
		
    if ( 'climacons' == $icon_type ) {
      $icon = $climacons[$input];
      $result = sprintf( '<i class="climacon %s"></i>', $icon );
    }
        
    if ( 'font-awesome' == $icon_type ) {
      $icon = $fa_icons[$input];
      $result = sprintf( '<i class="fa %s"></i>', $icon );
    }
        
    return $result;
  }
  
  public static function temperature_units( $input ) {
  	if( empty( $input ) || ! array_key_exists( $input, TkEventWeather_Functions::forecast_io_option_units() ) ) {
    	$result = apply_filters( 'tk_event_weather_indeterminate_units', '' );
  	} else {
    	if( 'us' == $input ) {
      	$result = __( 'F', 'tk-event-weather' ); //Fahrenheit
    	} else {
      	$result = __( 'C', 'tk-event-weather' ); //Celsius
    	}
  	}
  	
  	return $result;
  }
  
  public static function temperature_separator_html() {
    return wp_kses_post ( apply_filters( 'tk_event_weather_temperature_separator_html', '&ndash;' ) );
  }
  
  public static function timestamp_to_display( $timestamp = '', $date_format = '' ) {
    // timestamp
    if ( false === self::valid_timestamp( $timestamp, 'bool' ) ) {
      return '';
    } else {
      $timestamp = $timestamp + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
    }
    
    if ( empty ( $date_format ) ) {
      /* translators: hourly display time format, see https://developer.wordpress.org/reference/functions/date_i18n/#comment-972 */
      $date_format = __( 'g:i a T' );
    }
    
    // return date ( $date_format, $timestamp );
    return date_i18n ( $date_format, $timestamp, false );
  }
  
  
}