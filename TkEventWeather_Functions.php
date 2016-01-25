<?php

class TkEventWeather_Functions {
  // all variables and methods should be 'static'
  
  public static $transient_name_prepend = 'tkeventw';
  
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
    // with an optional time zone formatted as Z for GMT time or {+,-}[HH][MM] for an offset in hours or minutes
    // For the latter format, if no timezone is present, local time (at the provided latitude and longitude) is assumed.
/*
    @link https://regex101.com/r/fM7sG2/1
    Should match ISO 8601 datetime for Forecast.io API:
    [YYYY]-[MM]-[DD]T[HH]:[MM]:[SS]
    with an optional time zone formatted as Z for GMT time or {+,-}[HH][MM] (with or without separating colon) for an offset
    
    Does Match:
    2008-09-15T15:53:00
    2007-03-01T13:00:00Z
    2015-10-05T21:46:54-0800
    2015-10-05T21:46:54+00:00
    
    Does Not Match:
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
  
  
}