<?php

// Option 3 from http://plugin.michael-simpson.com/?page_id=39

include_once('TkEventWeather_ShortCodeScriptLoader.php');
require_once('TkEventWeather_Functions.php');

class TkEventWeather_TkEventWeatherShortcode extends TkEventWeather_ShortCodeScriptLoader {
        
    private static $addedAlready = false;
    
    public static $shortcode_name = 'tk-event-weather'; // must be public
    
    
    public function handleShortcode($atts) {
      
      $output = '';
      
      $plugin_options = get_option( 'tk_event_weather' );
      
    	if( empty( $plugin_options ) ) {
      	return TkEventWeather_Functions::invalid_shortcode_message( 'Please complete the initial setup' );
    	} else {
      	$api_key_option = TkEventWeather_Functions::array_get_value_by_key ( $plugin_options, 'forecast_io_api_key' );
      	$units_option = TkEventWeather_Functions::array_get_value_by_key ( $plugin_options, 'forecast_io_units', 'auto' );
      	$transients_off_option = TkEventWeather_Functions::array_get_value_by_key ( $plugin_options, 'transients_off' );
      	$transients_expiration_hours_option = TkEventWeather_Functions::array_get_value_by_key ( $plugin_options, 'transients_expiration_hours', 12 );
      	$cutoff_past_days_option = TkEventWeather_Functions::array_get_value_by_key ( $plugin_options, 'cutoff_past_days', 30 );
      	$cutoff_future_days_option = TkEventWeather_Functions::array_get_value_by_key ( $plugin_options, 'cutoff_future_days', 365 );
      }
      
      /*
       * Required:
       * api_key
       * lat/long
       * start time
       * end time
       */
    	// Attributes
    	$defaults = array(
      	'api_key'                 => $api_key_option,
      	'post_id'                 => get_the_ID(), // The ID of the current post
      	// if lat_long is used, will override individual latitude and longitude arguments if all 3 are supplied
      	'lat_long'                => '', // manually entered
      	'lat_long_custom_field'   => '', // get custom field value
      	// separate latitude
      	'lat'                     => '', // manually entered
      	'lat_custom_field'        => '', // get custom field value
      	// separate longitude
      	'long'                    => '', // manually entered
      	'long_custom_field'       => '', // get custom field value
      	// time (ISO 8601 or Unix Timestamp)
      	'start_time'              => '', // manually entered
      	'start_time_custom_field' => '', // get custom field value
      	'end_time'                => '', // manually entered
      	'end_time_custom_field'   => '', // get custom field value
      	// time constraints in strtotime relative dates
      	'cutoff_past'             => $cutoff_past_days_option,
      	'cutoff_future'           => $cutoff_future_days_option,
      	// API options -- see https://developer.forecast.io/docs/v2
      	'units'                   => '', // default/fallback is $units_default
      	'exclude'                 => '', // comma-separated. Default/fallback is $exclude_default
      	'transients_off'          => $transients_off_option, // "true" is only valid value
      	'transients_expiration'   => $transients_expiration_hours_option, // "true" is only valid value
      	// HTML
      	'before'                  => '<li class="tk-event-weather">Weather estimate: ',
      	'after'                   => '</li>',
      	'class'                   => '', // custom class
    	);
    	
    	$atts = shortcode_atts( $defaults, $atts, 'tk-event-weather' );
    	
    	// extract( $atts ); // convert each array item to individual variable
    	
    	// Code
    	
    	// @link https://developer.wordpress.org/reference/functions/sanitize_key/
    	$api_key = sanitize_key( $atts['api_key'] );
    	
    	if( empty( $api_key ) ) {
        return TkEventWeather_Functions::invalid_shortcode_message( 'Please enter your Forecast.io API Key' );
    	}
    	
    	// manually entered override custom field
    	$post_id = $atts['post_id'];
    	
    	// if post does not exist by ID, clear out $post_id variable
    	// @link https://developer.wordpress.org/reference/functions/get_post_status/
    	if ( ! empty( $post_id ) ) {
        if ( false === get_post_status( $post_id ) ) {
          // The post does not exist
          $post_id = '';
        }
      }
      
    	// the variable to send to Forecast.io API -- to be built via the code below
    	$latitude_longitude = '';
    	
    	// only used temporarily if separate lat and long need to be combined into $latitude_longitude
    	$latitude = '';
    	$longitude = '';
    	
    	// combined lat,long (manual then custom field)
    	if ( ! empty ( $atts['lat_long'] ) ) {
    	  $latitude_longitude = $atts['lat_long'];
    	} elseif ( ! empty( $post_id ) && ! empty( $atts['lat_long_custom_field'] ) ) {
      	$latitude_longitude = get_post_meta ( $post_id, $atts['lat_long_custom_field'], true );
    	}
    	
    	$latitude_longitude = TkEventWeather_Functions::valid_lat_long( $latitude_longitude );
    	
    	// if no lat,long yet then build via separate lat and long
    	if ( empty ( $latitude_longitude ) ) {
        
        // latitude
      	if ( ! empty ( $atts['lat'] ) ) {
      	  $latitude = $atts['lat'];
      	} elseif ( ! empty( $post_id ) && ! empty( $atts['lat_custom_field'] ) ) {
        	$latitude = get_post_meta ( $post_id, $atts['lat_custom_field'], true );
      	}
      	
        // longitude
      	if ( ! empty ( $atts['long'] ) ) {
      	  $longitude = $atts['long'];
      	} elseif ( ! empty( $post_id ) && ! empty( $atts['long_custom_field'] ) ) {
        	$longitude = get_post_meta ( $post_id, $atts['long_custom_field'], true );
      	}
      	
      	// build comma-separated $latitude_longitude
      	$latitude_longitude = sprintf( '%F,%F', $latitude, $longitude );
      	$latitude_longitude = TkEventWeather_Functions::valid_lat_long( $latitude_longitude );
    	}

    	if( empty( $latitude_longitude ) ) {
      	return TkEventWeather_Functions::invalid_shortcode_message( 'Please enter valid Latitude and Longitude coordinates' );
    	}
    	
    	// Start Time
    	// ISO 8601 datetime or Unix timestamp
    	if ( '' != $atts['start_time'] ) {
    	  $start_time = $atts['start_time'];
    	} elseif ( ! empty( $post_id ) && ! empty( $atts['start_time_custom_field'] ) ) {
      	$start_time = get_post_meta ( $post_id, $atts['lat_long_custom_field'], true );
    	} else {
      	$start_time = '';
    	}
    	
      if ( '' != $start_time ) {
        // check ISO 8601 first because it's stricter
        if ( true === TkEventWeather_Functions::valid_iso_8601_date_time( $start_time, 'bool' ) ) {
          $start_time = TkEventWeather_Functions::valid_iso_8601_date_time( $start_time );
          $start_time_iso_8601 = $start_time;
          $start_time_timestamp = date( 'U', strtotime( $start_time ) );
        }
        // check timestamp
        elseif ( true === TkEventWeather_Functions::valid_timestamp( $start_time, 'bool' ) ) {
          $start_time = TkEventWeather_Functions::valid_timestamp( $start_time );
          $start_time_iso_8601 = date( DateTime::ATOM, $start_time ); // DateTime::ATOM is same as 'c'
          $start_time_timestamp = $start_time;
        }
        // not valid so clear out
        else {
          $start_time = '';
        }
      }
      
    	if( '' == $start_time ) {
      	return TkEventWeather_Functions::invalid_shortcode_message( 'Please enter a valid Start Time format' );
    	}
    	
    	// cutoff_past
    	// strtotime date relative to $start_time_timestamp
    	if ( ! empty( $atts['cutoff_past'] ) ) {
    	  $cutoff_past = $atts['cutoff_past'];
    	} else {
      	$cutoff_past = '';
    	}
    	
      if ( empty( $cutoff_past ) ) { // not set or set to zero (which means "no limit" per plugin option)
        $min_timestamp = '';
      } else {
        if( is_int( $cutoff_past ) ) {
          // if is_int, use that number of NEGATIVE (past) days, per plugin option
          $min_timestamp = strtotime( sprintf( '-%d days', absint( $cutoff_past ) ) );
        } else {
          // else, use raw input, hopefully formatted correctly (e.g. cutoff_past="2 weeks")
          $min_timestamp = strtotime( esc_html( $cutoff_past ) ); // returns false on bad input
        }
      }
      
      $min_timestamp = TkEventWeather_Functions::valid_timestamp ( $min_timestamp );
      
      if( ! empty( $min_timestamp ) && '' != $start_time_timestamp ) {
        if( $min_timestamp > $start_time_timestamp ) {
      	  return TkEventWeather_Functions::invalid_shortcode_message( 'Event Start Time needs to be more recent than the Past Cutoff Time' );
        }
      }
      
      // max 60 years in the past, per API docs
      if( strtotime( '-60 years' ) > $start_time_timestamp ) {
    	  return TkEventWeather_Functions::invalid_shortcode_message( 'Event Start Time needs to be more recent than 60 years in the past, per Forecast.io API docs,' );
      }
      
    	// End Time
    	// ISO 8601 datetime or Unix timestamp
    	if ( '' != $atts['end_time'] ) {
    	  $end_time = $atts['end_time'];
    	} elseif ( ! empty( $post_id ) && ! empty( $atts['end_time_custom_field'] ) ) {
      	$end_time = get_post_meta ( $post_id, $atts['lat_long_custom_field'], true );
    	} else {
      	$end_time = '';
    	}
    	
      if ( '' != $end_time ) {
        // check ISO 8601 first because it's stricter
        if ( true === TkEventWeather_Functions::valid_iso_8601_date_time( $end_time, 'bool' ) ) {
          $end_time = TkEventWeather_Functions::valid_iso_8601_date_time( $end_time );
          $end_time_iso_8601 = $end_time;
          $end_time_timestamp = date( 'U', strtotime( $end_time ) );
        }
        // check timestamp
        elseif ( true === TkEventWeather_Functions::valid_timestamp( $end_time, 'bool' ) ) {
          $end_time = TkEventWeather_Functions::valid_timestamp( $end_time );
          $end_time_iso_8601 = date( DateTime::ATOM, $end_time ); // DateTime::ATOM is same as 'c'
          $end_time_timestamp = $end_time;
        }
        // not valid so clear out
        else {
          $end_time = '';
        }
      }
      
    	if( '' == $end_time ) {
      	return TkEventWeather_Functions::invalid_shortcode_message( 'Please enter a valid End Time format' );
    	}
    	
    	// if Event Start and End times are the same
    	if( $start_time_timestamp == $end_time_timestamp ) {
      	return TkEventWeather_Functions::invalid_shortcode_message( 'Please make sure Event Start Time and Event End Time are not the same' );
    	}
    	
    	//
    	// cutoff_future
    	// strtotime date relative to $end_time_timestamp
    	if ( ! empty( $atts['cutoff_future'] ) ) {
    	  $cutoff_future = $atts['cutoff_future'];
    	} else {
      	$cutoff_future = '';
    	}
    	
      if ( empty( $cutoff_future ) ) { // not set or set to zero (which means "no limit" per plugin option)
        $max_timestamp = '';
      } else {
        if( is_int( $cutoff_future ) ) {
          // if is_int, use that number of POSITIVE (future) days, per plugin option
          $max_timestamp = strtotime( sprintf( '+%d days', absint( $cutoff_future ) ) );
        } else {
          // else, use raw input, hopefully formatted correctly (e.g. cutoff_future="2 weeks")
          $max_timestamp = strtotime( esc_html( $cutoff_future ) ); // returns false on bad input
        }
      }
      
      $max_timestamp = TkEventWeather_Functions::valid_timestamp ( $max_timestamp );
      
      if( ! empty( $max_timestamp ) && '' != $end_time_timestamp ) {
        if( $end_time_timestamp > $max_timestamp ) {
      	  return TkEventWeather_Functions::invalid_shortcode_message( 'Future Cutoff Time needs to be further in the future than Event Time' );
        }
      }
      
      // max 10 years future, per API docs
      if( $end_time_timestamp > strtotime( '+10 years' ) ) {
    	  return TkEventWeather_Functions::invalid_shortcode_message( 'Event Time needs to be more recent than 60 years in the past, per Forecast.io API docs,' );
      }
      
      
      //
      // $start_time_timestamp is equal to or greater than $min_timestamp and $end_time_timestamp is less than or equal to $max_timestamp
      // or $min_timestamp and/or $max_timestamp were set to zero (i.e. no limits)
      // so continue...
      //
    	
    	
    	// units
    	$units = TkEventWeather_Functions::remove_all_whitespace( strtolower( $atts['units'] ) );
    	
    	$units_default = apply_filters( 'tk_event_weather_forecast_io_units_default', $units_option );
    	
    	if( ! array_key_exists( $units, TkEventWeather_Functions::forecast_io_option_units() ) ) {
      	$units = $units_default;
    	}
    	
    	// exclude
    	$exclude = '';
    	
    	$exclude_default = apply_filters( 'tk_event_weather_forecast_io_exclude_default', 'minutely' );
    	
    	// shortcode argument's value
    	$exclude_arg = TkEventWeather_Functions::remove_all_whitespace( strtolower( $atts['exclude'] ) );
    	
    	
    	if( empty( $exclude_arg ) || $exclude_default == $exclude_arg ) {
      	$exclude = $exclude_default;
    	} else {
      	// array of shortcode argument's value
      	$exclude_arg_array = explode( ',', $exclude_arg );
      	
        if( is_array( $exclude_arg_array ) ) {
          sort( $exclude_arg_array );
        	$possible_excludes = TkEventWeather_Functions::forecast_io_option_exclude();
        	
        	foreach ( $exclude_arg_array as $key => $value ) {
          	// if valid 'exclude' then keep it, else ignore it
          	if( array_key_exists( $value, $possible_excludes ) ) {
            	if( empty( $exclude ) ) { // if first to be added to $exclude
              	$exclude .= $value;
            	} else { // if not first one added to $exclude
              	$exclude .= ',';
              	$exclude .= $value;
            	}
          	} else {
          	}
          } // foreach()
      	} // is_array()
    	} // else
    	
    	// Prepare $exclude_for_transient (first letter of each $exclude, not separated by any character)
    	$exclude_for_transient = '';
    	$exclude_array = explode( ',', $exclude );
    	if( is_array( $exclude_array ) ) {
      	sort( $exclude_array );
      	foreach ( $exclude_array as $key => $value ) {
        	if( empty( $exclude_for_transient ) ) {
            $exclude_for_transient .= substr( $value, 0, 1 );
        	} else {
          	// $exclude_for_transient .= '_';
          	$exclude_for_transient .= substr( $value, 0, 1 );
        	}
      	}
    	}
    	
    	
    	
    	// Transients
    	// @link https://codex.wordpress.org/Transients_API
    	// @link https://codex.wordpress.org/Easier_Expression_of_Time_Constants
    	
    	// build transient
    	$transient_name = sprintf( '%s_%d_%s_%s_%s_%s_%d',
    	  TkEventWeather_Functions::$transient_name_prepend,
    	  $post_id,
    	  substr( $units, 0, 2 ), // e.g. 'auto' becomes 'au'
    	  $exclude_for_transient,
    	  // latitude (before comma)
    	  substr( strstr( $latitude_longitude, ',', true ), 0, 6 ), // requires PHP 5.3.0+
    	    // first 6 (assuming period is in first 5, getting first 6 will result in 5 valid characters for transient name
        // longitude (after comma)
    	  substr( strstr( $latitude_longitude, ',', false ), 0, 6 ), // does not require PHP 5.3.0+
    	  substr( $start_time_timestamp, -5, 5 ) // last 5 of Start Time timestamp
    	  //substr( $end_time_timestamp, -5, 5 ) // last 5 of End Time timestamp
    	  // noticed in testing sometimes leading zero(s) get truncated, possibly due to sanitize_key()... but, as long as it is consistent we are ok.
      );
      
      // make sure no period, comma (e.g. from lat/long) or other unfriendly characters for the transients database field
      // @link https://codex.wordpress.org/Function_Reference/sanitize_key
      $transient_name = sanitize_key( $transient_name );
      
      // dashes to underscores
      $transient_name = str_replace( '-', '_', $transient_name );
      
    	// MUST keep transient names to 40 characters or less or will silently fail
    	$transient_name = substr( $transient_name, 0, 39 );
      
    	if( ! empty( $atts['transients_off'] )
    	  && 'true' == $atts['transients_off']
    	  // or if WP_DEBUG ???
      ) {
      	$transients = false;
    	} else {
      	$transients = true;
    	}
    	
    	// if false === $transients, don't USE EXISTING or SET NEW transient for this API call
    	
    	if ( false === $transients ) {
      	delete_transient( $transient_name );
      	$transient_value = '';
    	} else {
      	$transient_value = get_transient( $transient_name ); // false if not there, which will be the case if "time" == time()
    	}
    	
    	
    	
    	
    	// Make API call if nothing from Transients
      // e.g. https://api.forecast.io/forecast/APIKEY/LATITUDE,LONGITUDE,TIME
      // if invalid API key, returns 400 Bad Request
      // API does not want any querying wrapped in brackets, as may be shown in the documentation -- brackets indicates OPTIONAL parameters, not to actually wrap in brackets for your request
      // 
    	// $data->currently is either 'right now' if no TIMESTAMP is part of the API or is the weather for the given TIMESTAMP even if in the past or future (yes, it still is called 'currently')
    	// 
      /*
       *
       *
       * Example:
       * Weather for the White House on Feb 1 at 4:30pm Eastern Time (as of 2016-01-25T03:01:09-06:00)
       * API call in ISO 8601 format
       * https://api.forecast.io/forecast/_______API_KEY_______/36.281445,-75.794662,2016-02-01T16:30:00-05:00?units=auto&exclude=alerts,daily,flags,hourly,minutely
       * API call in Unix Timestamp format (same result)
       * https://api.forecast.io/forecast/_______API_KEY_______/36.281445,-75.794662,1454362200?units=auto&exclude=alerts,daily,flags,hourly,minutely
       * result:
<!--
TK Event Weather JSON Data
{
    "latitude": 36.281445,
    "longitude": -75.794662,
    "timezone": "America\/New_York",
    "offset": -5,
    "currently": {
        "time": 1454362200,
        "summary": "Clear",
        "icon": "clear-day",
        "precipIntensity": 0.0008,
        "precipProbability": 0.01,
        "precipType": "rain",
        "temperature": 56.9,
        "apparentTemperature": 56.9,
        "dewPoint": 48.64,
        "humidity": 0.74,
        "windSpeed": 1.91,
        "windBearing": 163,
        "cloudCover": 0,
        "pressure": 1016.99,
        "ozone": 280.52
    }
}
-->


https://api.forecast.io/forecast/_______API_KEY_______/36.281445,-75.794662,2016-02-01T16:30:00-05:00?units=auto&exclude=alerts,flags,minutely

<!--
TK Event Weather JSON Data
{
    "latitude": 36.281445,
    "longitude": -75.794662,
    "timezone": "America\/New_York",
    "offset": -5,
    "currently": {
        "time": 1454362200,
        "summary": "Clear",
        "icon": "clear-day",
        "precipIntensity": 0.0008,
        "precipProbability": 0.01,
        "precipType": "rain",
        "temperature": 59.97,
        "apparentTemperature": 59.97,
        "dewPoint": 53.86,
        "humidity": 0.8,
        "windSpeed": 10.45,
        "windBearing": 233,
        "cloudCover": 0.21,
        "pressure": 1013.48,
        "ozone": 285.89
    },
    "hourly": {
        "summary": "Partly cloudy in the evening.",
        "icon": "partly-cloudy-night",
        "data": [
            {
                "time": 1454302800,
                "summary": "Clear",
                "icon": "clear-night",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 47.57,
                "apparentTemperature": 44.68,
                "dewPoint": 44.67,
                "humidity": 0.9,
                "windSpeed": 6.19,
                "windBearing": 222,
                "cloudCover": 0,
                "pressure": 1016.87,
                "ozone": 318.03
            },
            {
                "time": 1454306400,
                "summary": "Clear",
                "icon": "clear-night",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 47.23,
                "apparentTemperature": 44.25,
                "dewPoint": 44.62,
                "humidity": 0.91,
                "windSpeed": 6.22,
                "windBearing": 222,
                "cloudCover": 0,
                "pressure": 1016.58,
                "ozone": 317.5
            },
            {
                "time": 1454310000,
                "summary": "Clear",
                "icon": "clear-night",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 47.14,
                "apparentTemperature": 44.04,
                "dewPoint": 44.88,
                "humidity": 0.92,
                "windSpeed": 6.41,
                "windBearing": 224,
                "cloudCover": 0,
                "pressure": 1016.35,
                "ozone": 316.39
            },
            {
                "time": 1454313600,
                "summary": "Clear",
                "icon": "clear-night",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 47.13,
                "apparentTemperature": 43.87,
                "dewPoint": 45.24,
                "humidity": 0.93,
                "windSpeed": 6.71,
                "windBearing": 227,
                "cloudCover": 0,
                "pressure": 1016.17,
                "ozone": 315.02
            },
            {
                "time": 1454317200,
                "summary": "Clear",
                "icon": "clear-night",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 47.1,
                "apparentTemperature": 43.68,
                "dewPoint": 45.46,
                "humidity": 0.94,
                "windSpeed": 7.04,
                "windBearing": 229,
                "cloudCover": 0,
                "pressure": 1016.08,
                "ozone": 313.5
            },
            {
                "time": 1454320800,
                "summary": "Clear",
                "icon": "clear-night",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 46.85,
                "apparentTemperature": 43.27,
                "dewPoint": 45.3,
                "humidity": 0.94,
                "windSpeed": 7.24,
                "windBearing": 228,
                "cloudCover": 0,
                "pressure": 1016.13,
                "ozone": 312.01
            },
            {
                "time": 1454324400,
                "summary": "Clear",
                "icon": "clear-night",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 46.63,
                "apparentTemperature": 42.92,
                "dewPoint": 45.03,
                "humidity": 0.94,
                "windSpeed": 7.44,
                "windBearing": 228,
                "cloudCover": 0,
                "pressure": 1016.27,
                "ozone": 310.37
            },
            {
                "time": 1454328000,
                "summary": "Clear",
                "icon": "clear-night",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 47.15,
                "apparentTemperature": 43.31,
                "dewPoint": 45.25,
                "humidity": 0.93,
                "windSpeed": 7.95,
                "windBearing": 228,
                "cloudCover": 0,
                "pressure": 1016.39,
                "ozone": 308.2
            },
            {
                "time": 1454331600,
                "summary": "Clear",
                "icon": "clear-day",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 48.9,
                "apparentTemperature": 45.08,
                "dewPoint": 46.26,
                "humidity": 0.91,
                "windSpeed": 8.88,
                "windBearing": 229,
                "cloudCover": 0,
                "pressure": 1016.52,
                "ozone": 305.1
            },
            {
                "time": 1454335200,
                "summary": "Clear",
                "icon": "clear-day",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 51.39,
                "apparentTemperature": 51.39,
                "dewPoint": 47.67,
                "humidity": 0.87,
                "windSpeed": 9.96,
                "windBearing": 230,
                "cloudCover": 0,
                "pressure": 1016.63,
                "ozone": 301.47
            },
            {
                "time": 1454338800,
                "summary": "Clear",
                "icon": "clear-day",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 53.94,
                "apparentTemperature": 53.94,
                "dewPoint": 49.15,
                "humidity": 0.84,
                "windSpeed": 10.98,
                "windBearing": 231,
                "cloudCover": 0,
                "pressure": 1016.48,
                "ozone": 298.1
            },
            {
                "time": 1454342400,
                "summary": "Clear",
                "icon": "clear-day",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 56.44,
                "apparentTemperature": 56.44,
                "dewPoint": 50.73,
                "humidity": 0.81,
                "windSpeed": 12.07,
                "windBearing": 231,
                "cloudCover": 0,
                "pressure": 1015.88,
                "ozone": 295.19
            },
            {
                "time": 1454346000,
                "summary": "Clear",
                "icon": "clear-day",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 58.83,
                "apparentTemperature": 58.83,
                "dewPoint": 52.32,
                "humidity": 0.79,
                "windSpeed": 13.09,
                "windBearing": 231,
                "cloudCover": 0,
                "pressure": 1015.09,
                "ozone": 292.54
            },
            {
                "time": 1454349600,
                "summary": "Clear",
                "icon": "clear-day",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 60.49,
                "apparentTemperature": 60.49,
                "dewPoint": 53.45,
                "humidity": 0.78,
                "windSpeed": 13.51,
                "windBearing": 231,
                "cloudCover": 0,
                "pressure": 1014.43,
                "ozone": 290.3
            },
            {
                "time": 1454353200,
                "summary": "Clear",
                "icon": "clear-day",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 61.2,
                "apparentTemperature": 61.2,
                "dewPoint": 53.95,
                "humidity": 0.77,
                "windSpeed": 12.94,
                "windBearing": 232,
                "cloudCover": 0.03,
                "pressure": 1013.94,
                "ozone": 288.55
            },
            {
                "time": 1454356800,
                "summary": "Clear",
                "icon": "clear-day",
                "precipIntensity": 0,
                "precipProbability": 0,
                "temperature": 61.2,
                "apparentTemperature": 61.2,
                "dewPoint": 54.04,
                "humidity": 0.77,
                "windSpeed": 11.75,
                "windBearing": 233,
                "cloudCover": 0.08,
                "pressure": 1013.55,
                "ozone": 287.21
            },
            {
                "time": 1454360400,
                "summary": "Clear",
                "icon": "clear-day",
                "precipIntensity": 0.0008,
                "precipProbability": 0.01,
                "precipType": "rain",
                "temperature": 60.59,
                "apparentTemperature": 60.59,
                "dewPoint": 53.92,
                "humidity": 0.79,
                "windSpeed": 10.7,
                "windBearing": 234,
                "cloudCover": 0.15,
                "pressure": 1013.38,
                "ozone": 286.2
            },
            {
                "time": 1454364000,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-day",
                "precipIntensity": 0.0009,
                "precipProbability": 0.01,
                "precipType": "rain",
                "temperature": 59.36,
                "apparentTemperature": 59.36,
                "dewPoint": 53.79,
                "humidity": 0.82,
                "windSpeed": 10.2,
                "windBearing": 233,
                "cloudCover": 0.26,
                "pressure": 1013.57,
                "ozone": 285.57
            },
            {
                "time": 1454367600,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipIntensity": 0.0009,
                "precipProbability": 0.01,
                "precipType": "rain",
                "temperature": 57.79,
                "apparentTemperature": 57.79,
                "dewPoint": 53.57,
                "humidity": 0.86,
                "windSpeed": 9.89,
                "windBearing": 230,
                "cloudCover": 0.39,
                "pressure": 1014.02,
                "ozone": 285.27
            },
            {
                "time": 1454371200,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipIntensity": 0.001,
                "precipProbability": 0.01,
                "precipType": "rain",
                "temperature": 56.47,
                "apparentTemperature": 56.47,
                "dewPoint": 53.29,
                "humidity": 0.89,
                "windSpeed": 9.5,
                "windBearing": 229,
                "cloudCover": 0.46,
                "pressure": 1014.44,
                "ozone": 285
            },
            {
                "time": 1454374800,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipIntensity": 0.0012,
                "precipProbability": 0.02,
                "precipType": "rain",
                "temperature": 55.67,
                "apparentTemperature": 55.67,
                "dewPoint": 53.05,
                "humidity": 0.91,
                "windSpeed": 8.88,
                "windBearing": 232,
                "cloudCover": 0.39,
                "pressure": 1014.77,
                "ozone": 284.73
            },
            {
                "time": 1454378400,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipIntensity": 0.0013,
                "precipProbability": 0.02,
                "precipType": "rain",
                "temperature": 55.11,
                "apparentTemperature": 55.11,
                "dewPoint": 52.84,
                "humidity": 0.92,
                "windSpeed": 8.2,
                "windBearing": 237,
                "cloudCover": 0.25,
                "pressure": 1015.08,
                "ozone": 284.5
            },
            {
                "time": 1454382000,
                "summary": "Clear",
                "icon": "clear-night",
                "precipIntensity": 0.0014,
                "precipProbability": 0.03,
                "precipType": "rain",
                "temperature": 54.72,
                "apparentTemperature": 54.72,
                "dewPoint": 52.7,
                "humidity": 0.93,
                "windSpeed": 7.64,
                "windBearing": 242,
                "cloudCover": 0.18,
                "pressure": 1015.31,
                "ozone": 284.1
            },
            {
                "time": 1454385600,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipIntensity": 0.0012,
                "precipProbability": 0.02,
                "precipType": "rain",
                "temperature": 54.64,
                "apparentTemperature": 54.64,
                "dewPoint": 52.72,
                "humidity": 0.93,
                "windSpeed": 7.31,
                "windBearing": 245,
                "cloudCover": 0.28,
                "pressure": 1015.41,
                "ozone": 283.3
            }
        ]
    },
    "daily": {
        "data": [
            {
                "time": 1454302800,
                "summary": "Partly cloudy starting in the evening.",
                "icon": "partly-cloudy-night",
                "sunriseTime": 1454328362,
                "sunsetTime": 1454365792,
                "moonPhase": 0.77,
                "precipIntensity": 0.0005,
                "precipIntensityMax": 0.0014,
                "precipIntensityMaxTime": 1454382000,
                "precipProbability": 0.03,
                "precipType": "rain",
                "temperatureMin": 46.63,
                "temperatureMinTime": 1454324400,
                "temperatureMax": 61.2,
                "temperatureMaxTime": 1454353200,
                "apparentTemperatureMin": 42.92,
                "apparentTemperatureMinTime": 1454324400,
                "apparentTemperatureMax": 61.2,
                "apparentTemperatureMaxTime": 1454353200,
                "dewPoint": 49.75,
                "humidity": 0.87,
                "windSpeed": 9.17,
                "windBearing": 231,
                "cloudCover": 0.1,
                "pressure": 1015.39,
                "ozone": 297.84
            }
        ]
    }
}
-->




      // if API doesn't TECHNICALLY error out but does return an API error message
      examples:
      {"code":400,"error":"An invalid time was specified."}
      {"code":400,"error":"An invalid units parameter was provided."}
       *
       *
      */
      
    	if( ! empty( $transient_value ) ) {
      	$data = $transient_value;
        if( empty( $data ) ) {
          delete_transient( $transient_name );
          // return TkEventWeather_Functions::invalid_shortcode_message( 'Data from Transient used but some sort of data inconsistency. Transient deleted. May or may not need to troubleshoot' );
        }
        
    		if( ! empty( $data->error ) ) {
      		delete_transient( $transient_name );
          // return TkEventWeather_Functions::invalid_shortcode_message( 'Data from Transient used but an error: ' . $data->error . '. Transient deleted. May or may not need to troubleshoot' );
    		}
    	}
    	
    	// $data not yet set because $transient_value was bad so just run through new API call as if transient did not exist (got deleted a few lines above)
    	if ( empty( $data ) ) {
      	delete_transient( $transient_name ); // delete any expired transient by this name
      	
    		$request_uri = sprintf( 'https://api.forecast.io/forecast/%s/%s,%s',
    			$api_key,
    			$latitude_longitude,
    			$start_time
    		);
    		
    		$request_uri_query_args = array();
    		if( ! empty( $units ) ) {
      		$request_uri_query_args['units'] = $units;
    		}
    		if( ! empty( $exclude ) ) {
      		$request_uri_query_args['exclude'] = $exclude;
    		}
    		
    		if( ! empty( $request_uri_query_args ) ) {
      		$request_uri = add_query_arg( $request_uri_query_args, $request_uri );
        }
        
    		// GET STUFF FROM API
    		// @link https://codex.wordpress.org/Function_Reference/esc_url_raw
    		// @link https://developer.wordpress.org/reference/functions/wp_safe_remote_get/
        $request = wp_safe_remote_get( esc_url_raw( $request_uri ) );
                
    		// @link https://developer.wordpress.org/reference/functions/wp_remote_retrieve_body/
    		$body = wp_remote_retrieve_body( $request );
    		
        if( empty( $body ) ) {
          return TkEventWeather_Functions::invalid_shortcode_message( 'Forecast.io API request sent but nothing received. Please troubleshoot' );
        }
        
        $data = json_decode( $body );
        
        if( empty( $data ) ) {
          return TkEventWeather_Functions::invalid_shortcode_message( 'Forecast.io API response received but some sort of data inconsistency. Please troubleshoot' );
        }
        
    		if( ! empty( $data->error ) ) {
          return TkEventWeather_Functions::invalid_shortcode_message( 'Forecast.io API responded with an error: ' . $data->error . ' - Please troubleshoot' );
    		}
        
        if( true === $transients ) {
          $transients_expiration_hours = absint( $atts['transients_expiration'] );
          if ( 0 >= $transients_expiration_hours ) {
            $transients_expiration_hours = absint( $transients_expiration_hours_option );
          }
          set_transient( $transient_name, $data, $transients_expiration_hours * HOUR_IN_SECONDS ); // e.g. 12 hours
        }
    	}
    	
    	// now $data is set for sure (better be to have gotten this far)
    	$output .= sprintf( '<!--%1$sTK Event Weather JSON Data%1$s%2$s%1$s-->%1$s', PHP_EOL, json_encode( $data, JSON_PRETTY_PRINT ) ); // requires PHP 5.4
    	
    	
    	// Build Weather data that we'll use
    	
    	// Start Time Weather
    	$weather_start_time = $data->currently;
    	
      
    	// End Time Weather
      foreach ( $data->hourly->data as $key => $value ) {
        if( intval( $value->time ) >= intval( $end_time_timestamp ) ) {
          $weather_end_time = $value;
          $weather_hourly_end_key = $key;
          break;
        }
      }
      
      // if none, just get last hour of the day (e.g. if Event End Time is next day 2am, just get 11pm same day as Event Start Time (not perfect but may be better than 2nd API call)
      if ( empty( $weather_end_time ) ) {
        $weather_end_time = array_slice( $data->hourly->data, -1, 1, true );
        $weather_hourly_end_key = key( $weather_end_time );
      }
      
      if( empty( $weather_end_time ) ) {
        return TkEventWeather_Functions::invalid_shortcode_message( 'Event End Time is out of range. Please troubleshoot' );
      }
      
    	// First hourly data at/after Start Time
      foreach ( $data->hourly->data as $key => $value ) {
        if( intval( $value->time ) >= intval( $start_time_timestamp ) ) {
          $weather_hourly_start = $value;
          $weather_hourly_start_key = $key; // bookmark for when we pull hourly weather
          break;
        }
      }
      
      $weather_hourly = array();
      
      // Add Start to Hourly (might be at 45 min in the hour though)
      $weather_hourly[$weather_hourly_start_key] = $weather_start_time;
      
      if ( ! empty( $weather_hourly_start ) && ! empty( $weather_hourly_start_key ) ) {
        foreach ( $data->hourly->data as $key => $value ) {

          if( $weather_hourly_start_key > $key ) {
            continue;
          }

          if( $key > $weather_hourly_start_key ) {
            break;
          }

          if( $weather_hourly_start_key == $key // not needed but is correct
            && $weather_hourly_end_key >= $key
            && intval( $weather_start_time->time ) !== intval( $value->time ) // don't re-include $data->current
            && intval( $weather_end_time->time ) !== intval( $value->time ) // we'll include it manually in next step
          ) {
            $weather_hourly[$weather_hourly_start_key] = $value;
            $weather_hourly_start_key++;
          }
        }
      }
      
      // Add End to Hourly
      $weather_hourly[$weather_hourly_end_key] = $weather_end_time;    	
    	
    	
    	// Get Low and High from Hourly
    	$weather_hourly_temps = array();
    	foreach ( $weather_hourly as $key=>$value ) {
      	$weather_hourly_temps[] = $value->temperature;
    	}
    	
    	$weather_hourly_high = max( $weather_hourly_temps );
    	$weather_hourly_low = min( $weather_hourly_temps );
    	
    	
    	$output .= '<span class="tk-event-weather tk-event-weather-temperature">';
    	
    	$output .= __( 'Event temperature:', 'tk-event-weather' );
      
    	
    	if ( $weather_hourly_high == $weather_hourly_low ) {
      	$output .= sprintf( ' %s%s',
      	  $weather_hourly_low,
      	  TkEventWeather_Functions::degrees_html()
        );
    	} else {
      	$output .= sprintf( ' %s%s%s%s%s',
      	  $weather_hourly_low,
      	  TkEventWeather_Functions::degrees_html(),
      	  TkEventWeather_Functions::temperature_separator_html(),
      	  $weather_hourly_high,
      	  TkEventWeather_Functions::degrees_html()
        );
      }
    	
    	$output .= '</span>';
    	
    	// before
    	
    	// after
    	
    	// class
    	
      $debug_vars = false;
    	//$debug_vars = WP_DEBUG; // if WP_DEBUG is true, set $debug_vars to true for admins only
    	if ( ! empty( $debug_vars ) && current_user_can( 'edit_theme_options' ) ) { // admins only
      	var_dump( get_defined_vars() );
    	}
    	
    	
    	
    	return $output;
    	
/*
    	// API RETURNED DATA, LET'S DO STUFF
    	
    	$summary = $data->currently->summary;
    	
    	$icon = $data->currently->icon;
    		$icon_list = array(
    			// JSON value => icon/image to use in HTML output
    			'clear-day' => 'fa fa-sun-o',
    			'clear-night' => 'fa fa-moon-o',
    			'rain' => 'fa fa-umbrella',
    			'snow' => 'fa fa-tint',
    			'sleet' => 'fa fa-tint',
    			'wind' => 'fa fa-send',
    			'fog' => 'fa fa-shield',
    			'cloudy' => 'fa fa-cloud',
    			'partly-cloudy-day' => 'fa fa-cloud',
    			'partly-cloudy-night' => 'fa fa-star-half',
    		);
    		
    		if(array_key_exists($icon, $icon_list)) {
    			$icon_code = $icon_list[$icon];
    		}
    	
    	$precip_probability = $data->currently->precipProbability;
    		$precip_probability = $precip_probability ? round($precip_probability) : '?';
    	
    	$temperature = $data->currently->temperature;
    		$temperature = $temperature ? round($temperature) : '?';
    	
    	$feelslike = $data->currently->apparentTemperature;
    		$feelslike = $feelslike ? round($feelslike) : '?';
    	
    	$full_report_text = sprintf('%s, %s%% precip, %s&deg; (feels like %s&deg;)', // %% escapes a single % character
    		$summary,
    		$precip_probability,
    		$temperature,
    		$feelslike
    	);
    	
    	// BUILD OUTPUT
    	$output = sprintf('
    		<span class="weather-api">
    			<span class="weather-before">
    				%1$s
    			</span>
    			<span class="weather-info">
    				<i class="%2$s" title="%3$s"></i> %4$s
    			</span>
    			<span class="weather-after">
    				%5$s
    			</span>
    		</span>',
    		$before,
    		$icon_code,
    		$full_report_text,
    		$summary,
    		$after
    	);
    	//plprint($output, 'cp_forecastio_first_child_api');
    	
    	return $output;

*/
    }
 
    // do not comment out -- needed because of extending an abstract class
    public function addScript() {
        if (!self::$addedAlready) {
            self::$addedAlready = true;
            //wp_register_script('my-script', plugins_url('js/my-script.js', __FILE__), array('jquery'), '1.0', true);
            //wp_print_scripts('my-script');
        }
    }
    
}
