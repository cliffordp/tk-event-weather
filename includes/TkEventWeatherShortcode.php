<?php

// Option 3 from http://plugin.michael-simpson.com/?page_id=39


include_once( 'ShortCodeScriptLoader.php' );
require_once( 'Functions.php' );

class TkEventWeather__TkEventWeatherShortcode extends TkEventWeather__ShortCodeScriptLoader {
        
    private static $addedAlready = false;
    
    // must be public
    public static function shortcode_name() {
      return TkEventWeather__FuncSetup::$shortcode_name;
    }
    
    public function handleShortcode($atts) {
      
      $output = '';
      
      $template_data = array(); // sent to view template to render output
      
      $plugin_options = TkEventWeather__Functions::plugin_options();
      
    	if( empty( $plugin_options ) ) {
      	return TkEventWeather__Functions::invalid_shortcode_message( 'Please complete the initial setup' );
    	} else {
      	$api_key_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'forecast_io_api_key' );
      	
      	$display_template_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'display_template' );
      	
      	$cutoff_past_days_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'cutoff_past_days', 30 );
      	$cutoff_future_days_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'cutoff_future_days', 365 );
      	
      	$units_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'forecast_io_units', 'auto' );
      	
      	$transients_off_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'transients_off' );
      	$transients_expiration_hours_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'transients_expiration_hours', 12 );
      	
      	$utc_offset_type_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'utc_offset_type' );
      	
      	$sunrise_sunset_off_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'sunrise_sunset_off' );
      	
        //$icons_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'icons' );
        
      	$plugin_credit_link_on_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'plugin_credit_link_on' );
      	$forecast_io_credit_link_off_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'forecast_io_credit_link_off' );
      	
      	$debug_on_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'debug_on' );
      	
      }
      
      /*
       * Required:
       * api_key
       * lat/long
       * start time
       */
    	// Attributes
    	$defaults = array(
      	'api_key'                       => $api_key_option,
      	'post_id'                       => get_the_ID(), // The ID of the current post
      	// if lat_long is used, will override individual latitude and longitude arguments if all 3 are supplied
      	'lat_long'                      => '', // manually entered
      	'lat_long_custom_field'         => '', // get custom field value
      	// separate latitude            
      	'lat'                           => '', // manually entered
      	'lat_custom_field'              => '', // get custom field value
      	// separate longitude           
      	'long'                          => '', // manually entered
      	'long_custom_field'             => '', // get custom field value
      	// time (ISO 8601 or Unix Timestamp)
      	'start_time'                    => '', // manually entered
      	'start_time_custom_field'       => '', // get custom field value
      	'end_time'                      => '', // manually entered
      	'end_time_custom_field'         => '', // get custom field value
      	// time constraints in strtotime relative dates
      	'cutoff_past'                   => $cutoff_past_days_option,
      	'cutoff_future'                 => $cutoff_future_days_option,
      	// API options -- see https://developer.forecast.io/docs/v2
      	'exclude'                       => '', // comma-separated. Default/fallback is $exclude_default
      	'transients_off'                => $transients_off_option, // "true" is the only valid value
      	'transients_expiration'         => $transients_expiration_hours_option,
      	// Display Customizations       
      	'units'                         => '', // default/fallback is $units_default
      	'utc_offset_type'               => $utc_offset_type_option,
          // could add a utc_offset_hours sort of parameter to allow manually outputting as '-5'
      	'sunrise_sunset_off'            => $sunrise_sunset_off_option, // "true" is the only valid value
      	'icons'                         => '',
      	'plugin_credit_link_on'         => $plugin_credit_link_on_option, // "true" is the only valid value
      	'forecast_io_credit_link_off'   => $forecast_io_credit_link_off_option, // anything !empty()
      	// HTML
      	'class'                         => '', // custom class
      	'template'                      => $display_template_option,
      	// Debug Mode
      	'debug_on'                      => $debug_on_option, // anything !empty()
    	);
    	
    	$atts = shortcode_atts( $defaults, $atts, 'tk-event-weather' );
    	
    	// extract( $atts ); // convert each array item to individual variable
    	
    	// Code
    	
    	$debug = boolval( $atts['debug_on'] );
    	
    	// @link https://developer.wordpress.org/reference/functions/sanitize_key/
    	$api_key = sanitize_key( $atts['api_key'] );
    	
    	if( empty( $api_key ) ) {
        return TkEventWeather__Functions::invalid_shortcode_message( 'Please enter your Forecast.io API Key' );
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
      
      $template_data['post_id'] = $post_id;
      
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
    	
    	$latitude_longitude = TkEventWeather__Functions::valid_lat_long( $latitude_longitude );
    	
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
      	$latitude_longitude = TkEventWeather__Functions::valid_lat_long( $latitude_longitude );
    	}

    	if( empty( $latitude_longitude ) ) {
      	return TkEventWeather__Functions::invalid_shortcode_message( 'Please enter valid Latitude and Longitude coordinates' );
    	}
    	
    	$template_data['latitude_longitude'] = $latitude_longitude;
    	
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
        if ( true === TkEventWeather__Functions::valid_iso_8601_date_time( $start_time, 'bool' ) ) {
          $start_time = TkEventWeather__Functions::valid_iso_8601_date_time( $start_time );
          $start_time_iso_8601 = $start_time;
          $start_time_timestamp = date( 'U', strtotime( $start_time ) );
        }
        // check timestamp
        elseif ( true === TkEventWeather__Functions::valid_timestamp( $start_time, 'bool' ) ) {
          $start_time = TkEventWeather__Functions::valid_timestamp( $start_time );
          $start_time_iso_8601 = date( DateTime::ATOM, $start_time ); // DateTime::ATOM is same as 'c'
          $start_time_timestamp = $start_time;
        }
        // not valid so clear out
        else {
          $start_time = '';
        }
      }
      
    	if( empty( $start_time_timestamp ) ) {
      	return TkEventWeather__Functions::invalid_shortcode_message( 'Please enter a valid Start Time format' );
    	}
    	
    	$template_data['start_time_timestamp'] = $start_time_timestamp;
    	
    	
    	$weather_first_hour_timestamp = TkEventWeather__Functions::timestamp_truncate_minutes( $start_time_timestamp );
    	
    	$template_data['weather_first_hour_timestamp'] = $weather_first_hour_timestamp;
    	
    	
    	
    	// cutoff_past
    	// strtotime date relative to $weather_first_hour_timestamp
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
      
      $min_timestamp = TkEventWeather__Functions::valid_timestamp ( $min_timestamp );
      
      if( ! empty( $min_timestamp ) && '' != $weather_first_hour_timestamp ) {
        if( $min_timestamp > $weather_first_hour_timestamp ) {
      	  return TkEventWeather__Functions::invalid_shortcode_message( 'Event Start Time needs to be more recent than the Past Cutoff Time' );
        }
      }
      
      // max 60 years in the past, per API docs
      if( strtotime( '-60 years' ) > $weather_first_hour_timestamp ) {
    	  return TkEventWeather__Functions::invalid_shortcode_message( 'Event Start Time needs to be more recent than 60 years in the past, per Forecast.io API docs,' );
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
        if ( true === TkEventWeather__Functions::valid_iso_8601_date_time( $end_time, 'bool' ) ) {
          $end_time = TkEventWeather__Functions::valid_iso_8601_date_time( $end_time );
          $end_time_iso_8601 = $end_time;
          $end_time_timestamp = TkEventWeather__Functions::valid_timestamp( date( 'U', strtotime( $end_time ) ) ); // date() returns a string
        }
        // check timestamp
        elseif ( true === TkEventWeather__Functions::valid_timestamp( $end_time, 'bool' ) ) {
          $end_time = TkEventWeather__Functions::valid_timestamp( $end_time );
          $end_time_iso_8601 = date( DateTime::ATOM, $end_time ); // DateTime::ATOM is same as 'c'
          $end_time_timestamp = $end_time;
        }
        // not valid so clear out
        else {
          $end_time = '';
        }
      }
      
    	if( '' == $end_time_timestamp ) {
      	return TkEventWeather__Functions::invalid_shortcode_message( 'Please enter a valid End Time format' );
    	}
    	
    	// if Event Start and End times are the same
    	if( $weather_first_hour_timestamp == $end_time_timestamp ) {
      	return TkEventWeather__Functions::invalid_shortcode_message( 'Please make sure Event Start Time and Event End Time are not the same' );
    	}
    	
    	$template_data['end_time_timestamp'] = $end_time_timestamp;
    	
    	
    	/**
      	* $weather_last_hour_timestamp helps with setting 'sunset_to_be_inserted'
      	* 
      	* if event ends at 7:52pm, set $weather_last_hour_timestamp to 8pm
        * if event ends at 7:00:00pm, set $weather_last_hour_timestamp to 7pm
        * 
        **/
    	$end_time_hour_timestamp = TkEventWeather__Functions::timestamp_truncate_minutes( $end_time_timestamp ); // e.g. 7pm instead of 7:52pm
    	$end_time_hour_timestamp_plus_one_hour = 3600 + $end_time_hour_timestamp; // e.g. 8pm
    	
    	if ( $end_time_timestamp == $end_time_hour_timestamp ) { // e.g. event ends at 7:00:00
      	$weather_last_hour_timestamp = $end_time_hour_timestamp;
    	} else {
      	$weather_last_hour_timestamp = $end_time_hour_timestamp_plus_one_hour;
    	}
    	
    	$template_data['weather_last_hour_timestamp'] = $weather_last_hour_timestamp;
    	
    	
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
      
      $max_timestamp = TkEventWeather__Functions::valid_timestamp ( $max_timestamp );
      
      if( ! empty( $max_timestamp ) && '' != $end_time_timestamp ) {
        if( $end_time_timestamp > $max_timestamp ) {
      	  return TkEventWeather__Functions::invalid_shortcode_message( 'Event End Time needs to be more recent than Future Cutoff Time' );
        }
      }
      
      // max 10 years future, per API docs
      if( $end_time_timestamp > strtotime( '+10 years' ) ) {
    	  return TkEventWeather__Functions::invalid_shortcode_message( 'Event End Time needs to be less than 10 years in the future, per Forecast.io API docs,' );
      }
      
      
      //
      // $weather_first_hour_timestamp is equal to or greater than $min_timestamp and $end_time_timestamp is less than or equal to $max_timestamp
      // or $min_timestamp and/or $max_timestamp were set to zero (i.e. no limits)
      // so continue...
      //
    	
    	
    	// units
    	$units = TkEventWeather__Functions::remove_all_whitespace( strtolower( $atts['units'] ) );
    	
    	$units_default = apply_filters( 'tk_event_weather_forecast_io_units_default', $units_option );
    	
    	if( ! array_key_exists( $units, TkEventWeather__Functions::forecast_io_option_units() ) ) {
      	$units = $units_default;
    	}
    	
    	// exclude
    	$exclude = '';
    	
    	$exclude_default = apply_filters( 'tk_event_weather_forecast_io_exclude_default', 'minutely,alerts' );
    	
    	// shortcode argument's value
    	$exclude_arg = TkEventWeather__Functions::remove_all_whitespace( strtolower( $atts['exclude'] ) );
    	
    	
    	if( empty( $exclude_arg ) || $exclude_default == $exclude_arg ) {
      	$exclude = $exclude_default;
    	} else {
      	// array of shortcode argument's value
      	$exclude_arg_array = explode( ',', $exclude_arg );
      	
        if( is_array( $exclude_arg_array ) ) {
          sort( $exclude_arg_array );
        	$possible_excludes = TkEventWeather__Functions::forecast_io_option_exclude();
        	
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
    	  TkEventWeather__FuncSetup::$transient_name_prepend,
    	  $post_id,
    	  substr( $units, 0, 2 ), // e.g. 'auto' becomes 'au'
    	  $exclude_for_transient,
    	  // latitude (before comma)
    	  substr( strstr( $latitude_longitude, ',', true ), 0, 6 ), // requires PHP 5.3.0+
    	    // first 6 (assuming period is in first 5, getting first 6 will result in 5 valid characters for transient name
        // longitude (after comma)
    	  substr( strstr( $latitude_longitude, ',', false ), 0, 6 ), // does not require PHP 5.3.0+
    	  substr( $weather_first_hour_timestamp, -5, 5 ) // last 5 of Start Time timestamp
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
    	// $api_data->currently is either 'right now' if no TIMESTAMP is part of the API or is the weather for the given TIMESTAMP even if in the past or future (yes, it still is called 'currently')
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
      	$api_data = $transient_value;
        if( empty( $api_data ) ) {
          delete_transient( $transient_name );
          // return TkEventWeather__Functions::invalid_shortcode_message( 'Data from Transient used but some sort of data inconsistency. Transient deleted. May or may not need to troubleshoot' );
        }
        
    		if( ! empty( $api_data->error ) ) {
      		delete_transient( $transient_name );
          // return TkEventWeather__Functions::invalid_shortcode_message( 'Data from Transient used but an error: ' . $api_data->error . '. Transient deleted. May or may not need to troubleshoot' );
    		}
    	}
    	
    	// $api_data not yet set because $transient_value was bad so just run through new API call as if transient did not exist (got deleted a few lines above)
    	if ( empty( $api_data ) ) {
      	delete_transient( $transient_name ); // delete any expired transient by this name
      	
    		$request_uri = sprintf( 'https://api.forecast.io/forecast/%s/%s,%s',
    			$api_key,
    			$latitude_longitude,
    			$start_time_timestamp
    			// $start_time_iso_8601
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
          return TkEventWeather__Functions::invalid_shortcode_message( 'Forecast.io API request sent but nothing received. Please troubleshoot' );
        }
        
        $api_data = json_decode( $body );
        
        if( empty( $api_data ) ) {
          return TkEventWeather__Functions::invalid_shortcode_message( 'Forecast.io API response received but some sort of data inconsistency. Please troubleshoot' );
        }
        
    		if( ! empty( $api_data->error ) ) {
          return TkEventWeather__Functions::invalid_shortcode_message( 'Forecast.io API responded with an error: ' . $api_data->error . ' - Please troubleshoot' );
    		}
    		
    		if( empty( $api_data->hourly->data ) ) {
          return TkEventWeather__Functions::invalid_shortcode_message( 'Forecast.io API responded but without hourly data. Please troubleshoot' );
    		}
        
        if( true === $transients ) {
          $transients_expiration_hours = absint( $atts['transients_expiration'] );
          if ( 0 >= $transients_expiration_hours ) {
            $transients_expiration_hours = absint( $transients_expiration_hours_option );
          }
          set_transient( $transient_name, $api_data, $transients_expiration_hours * HOUR_IN_SECONDS ); // e.g. 12 hours
        }
    	}
    	
/*
  Example var_dump($request) when bad data, like https://api.forecast.io/forecast/___API_KEY___/0.000000,0.000000,1466199900?exclude=minutely
object(stdClass)[100]
public 'latitude' => int 0
public 'longitude' => int 0
public 'timezone' => string 'Etc/GMT' (length=7)
public 'offset' => int 0
public 'currently' => 
  object(stdClass)[677]
    public 'time' => int 1466199900
public 'flags' => 
  object(stdClass)[96]
    public 'sources' => 
      array (size=0)
        empty
    public 'units' => string 'us' (length=2)
*/
    	
    	// now $api_data is set for sure (better be to have gotten this far)
    	if ( ! empty( $debug ) ) {
      	$output .= sprintf( '<!--%1$sTK Event Weather JSON Data%1$s%2$s%1$s-->%1$s', PHP_EOL, json_encode( $api_data, JSON_PRETTY_PRINT ) ); // requires PHP 5.4
      }
    	
    	
    	// Build Weather data that we'll use    	
    	
    	// https://developer.wordpress.org/reference/functions/wp_list_pluck/
      $api_data_houly_hours = wp_list_pluck( $api_data->hourly->data, 'time' );
      
      $api_data_houly_hours_keys = array_keys( $api_data_houly_hours );
      
    	// First hour to start pulling for Hourly Data
      foreach ( $api_data_houly_hours as $key => $value ) {
        if( intval( $value ) == intval( $weather_first_hour_timestamp ) ) {
          $weather_hourly_start_key = $key; // so we know where to start when pulling hourly weather
          break;
        }
      }
      
      // Protect against odd hourly weather scenarios like location only having data from midnight to 8am and event start time is 9am
      if( ! isset( $weather_hourly_start_key ) ) { // need to allow for zero due to numeric array
        return TkEventWeather__Functions::invalid_shortcode_message( 'Event Start Time error. API did not return enough hourly data. Please troubleshoot' );
      }
      
    	// End Time Weather
      foreach ( $api_data_houly_hours as $key => $value ) {
        if( intval( $value ) >= intval( $end_time_timestamp ) ) {
          $weather_hourly_end_key = $key;
          break;
        }
      }
      
      // if none, just get last hour of the day (e.g. if Event End Time is next day 2am, just get 11pm same day as Event Start Time (not perfect but may be better than 2nd API call)
      if ( ! isset( $weather_hourly_end_key ) ) { // need to allow for zero due to numeric array
        $weather_hourly_end_key = end ( $api_data_houly_hours_keys );
      }
      
      if( ! isset( $weather_hourly_end_key ) ) { // need to allow for zero due to numeric array
        return TkEventWeather__Functions::invalid_shortcode_message( 'Event End Time is out of range. Please troubleshoot' );
      }
      
      
      // UTC Offset -- only use is when displaying a timestamp
      $utc_offset = TkEventWeather__Functions::remove_all_whitespace( strtolower( $atts['utc_offset_type'] ) );
      
      if ( 'wp' == $utc_offset ) {
        $utc_offset = 'wordpress';
      }
      
      if ( ! array_key_exists( $utc_offset, TkEventWeather__Functions::valid_utc_offset_types() ) ) {
        $utc_offset = 'api';
      }
      
      $utc_offset_hours = '';
      
      if ( 'api' == $utc_offset ) {
        $utc_offset_hours = $api_data->offset;
      }
      
      $template_data['utc_offset_hours'] = $utc_offset_hours;
      
      
      $sunrise_sunset = array(
        'on'                      => false,
        'sunrise_timestamp'       => false,
        'sunrise_hour_timestamp'  => false,
        'sunset_timestamp'        => false,
        'sunset_hour_timestamp'   => false,
        'sunrise_to_be_inserted'  => false,
        'sunset_to_be_inserted'   => false,
      );
      
    	if( empty( $atts['sunrise_sunset_off'] )
    	  || 'true' != $atts['sunrise_sunset_off']
      ) {
      	$sunrise_sunset['on'] = true;
    	}
    	
    	if ( true === $sunrise_sunset['on'] ) {
      	$sunrise_sunset['sunrise_timestamp'] = TkEventWeather__Functions::valid_timestamp( $api_data->daily->data[0]->sunriseTime );
      	$sunrise_sunset['sunrise_hour_timestamp'] = TkEventWeather__Functions::timestamp_truncate_minutes( $sunrise_sunset['sunrise_timestamp'] );
      	if ( $sunrise_sunset['sunrise_timestamp'] >= $weather_first_hour_timestamp ) {
        	$sunrise_sunset['sunrise_to_be_inserted'] = true;
        }
      	
      	$sunrise_sunset['sunset_timestamp'] = TkEventWeather__Functions::valid_timestamp( $api_data->daily->data[0]->sunsetTime );
      	$sunrise_sunset['sunset_hour_timestamp'] = TkEventWeather__Functions::timestamp_truncate_minutes( $sunrise_sunset['sunset_timestamp'] );
      	if ( $weather_last_hour_timestamp >= $sunrise_sunset['sunset_timestamp'] ) {
        	$sunrise_sunset['sunset_to_be_inserted'] = true;
        }
    	}
    	    	
    	$template_data['sunrise_sunset'] = $sunrise_sunset;
    	
    	
    	// Icons
    	$icons = $atts['icons'];
    	
      if ( 'climacons' == $icons ) {
        $icons = 'climacons_font';
      }
      
    	if ( empty( $icons ) || ! in_array( $icons, TkEventWeather__Functions::valid_icon_type() ) ) {
      	$icons = 'climacons_font';
    	}      
      
      // enqueue CSS file if using Climacons Icon Font
      if ( 'climacons_font' == $icons ) {
        TkEventWeather__Functions::register_climacons_css();
        wp_enqueue_style( 'tkeventw-climacons' );
      }
      
      
      // Hourly Weather
      // any internal pointers to reset first?
            
      $weather_hourly = array();
      
      $index = $weather_hourly_start_key;
      
      if ( is_integer( $index ) ) {
        foreach ( $api_data->hourly->data as $key => $value ) {

          if( $key > $weather_hourly_end_key ) {
            break;
          }
          
          if( $index == $key ) {
            $weather_hourly[$index] = $value;
            $index++;
          }
        }
      }
      
      //$weather_hourly = TkEventWeather__Functions::sort_multidim_array_by_sub_key( $weather_hourly, 'time' );
      
      $template_data['weather_hourly'] = $weather_hourly;
    	
    	// Get Low and High from Hourly
    	// https://developer.wordpress.org/reference/functions/wp_list_pluck/
    	$weather_hourly_temperatures = wp_list_pluck( $weather_hourly, 'temperature' ); // if nothing, will be an empty array
    	
    	$template_data['weather_hourly_temperatures'] = $weather_hourly_temperatures;
    	
    	$weather_hourly_high = '';
    	if ( ! empty( $weather_hourly_temperatures ) && is_array( $weather_hourly_temperatures ) ) {
      	$weather_hourly_high = max( $weather_hourly_temperatures );
    	}
    	$template_data['weather_hourly_high'] = $weather_hourly_high;
      
      $weather_hourly_low = '';
    	if ( ! empty( $weather_hourly_temperatures ) && is_array( $weather_hourly_temperatures ) ) {
      	$weather_hourly_low = min( $weather_hourly_temperatures );
      }
    	$template_data['weather_hourly_low'] = $weather_hourly_low;

    	
    	$temperature_units = TkEventWeather__Functions::temperature_units( $api_data->flags->units );
    	
    	$template_data['temperature_units'] = $temperature_units;
    	
    	$wind_speed_units = TkEventWeather__Functions::wind_speed_units( $api_data->flags->units );
    	
    	$template_data['wind_speed_units'] = $wind_speed_units;
      
    	// class
    	$class = sanitize_html_class( $atts['class'] );
    	
    	$display_template = TkEventWeather__Functions::remove_all_whitespace( strtolower( $atts['template'] ) );
    	
    	if ( ! array_key_exists( $display_template, TkEventWeather__Functions::valid_display_templates() ) ) {
      	$display_template = 'hourly_horizontal';
    	}
    	
    	$template_data['template'] = $display_template;
    	
    	$template_class_name = TkEventWeather__Functions::template_class_name( $display_template );
    	
    	$template_data['template_class_name'] = $template_class_name;
    	
    	// if Debug Mode is true, set $debug_vars to true for admins only
    	if ( ! empty( $debug ) && current_user_can( 'edit_theme_options' ) ) {
      	// var_dump( get_defined_vars() ); // uncomment if you REALLY want to display this information
    	}
    	
    	
      TkEventWeather__Functions::register_css();
      wp_enqueue_style( sanitize_html_class( TkEventWeather__FuncSetup::shortcode_name_hyphenated() ) );
      
    	/**
      	* Start Building Output!!!
      	* All data should be set by now!!!
      	*/
    	
    	// cannot do <style> tags inside template because it will break any open div (e.g. wrapper div)
    	$output .= '<div class="tk-event-weather__wrapper">';
      $output .= PHP_EOL;
      
      $output .= sprintf( '<div class="tk-event-weather-template %s">', $template_data['template_class_name'] );
      $output .= PHP_EOL;
      
      	// https://github.com/GaryJones/Gamajo-Template-Loader/issues/13#issuecomment-196046201
      	ob_start();
      	TkEventWeather__Functions::load_template( $display_template, $template_data );
      	$output .= ob_get_clean();
      
        if ( 'true' == $atts['plugin_credit_link_on'] ) {
          $output .= TkEventWeather__Functions::plugin_credit_link();
        }
        
        if ( empty( $atts['forecast_io_credit_link_off'] ) ) {
          $output .= TkEventWeather__Functions::forecast_io_credit_link();
        }
      
    	$output .= '</div>'; // .tk-event-weather-template
      $output .= PHP_EOL;
      
    	$output .= '</div>'; // .tk-event-weather--wrapper
      $output .= PHP_EOL;
      
    	return $output;
    	
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
