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
      	'time'                    => '', // manually entered
      	'time_custom_field'       => '', // get custom field value
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
    	
    	// time
    	// ISO 8601 datetime or Unix timestamp
    	if ( '' != $atts['time'] ) {
    	  $time = $atts['time'];
    	} elseif ( ! empty( $post_id ) && ! empty( $atts['time_custom_field'] ) ) {
      	$time = get_post_meta ( $post_id, $atts['lat_long_custom_field'], true );
    	} else {
      	$time = time(); // should we set to time()???
    	}
    	
      if ( '' != $time ) {
        // check ISO 8601 first because it's stricter
        if ( true === TkEventWeather_Functions::valid_iso_8601_date_time( $time, 'bool' ) ) {
          $time = TkEventWeather_Functions::valid_iso_8601_date_time( $time );
          $time_iso_8601 = $time;
          $time_timestamp = date( 'U', strtotime( $time ) );
        }
        // check timestamp
        elseif ( true === TkEventWeather_Functions::valid_timestamp( $time, 'bool' ) ) {
          $time = TkEventWeather_Functions::valid_timestamp( $time );
          $time_iso_8601 = date( DateTime::ATOM, $time ); // DateTime::ATOM is same as 'c'
          $time_timestamp = $time;
        }
        // not valid so clear out
        else {
          $time = '';
        }
      }
      
    	if( '' == $time ) {
      	return TkEventWeather_Functions::invalid_shortcode_message( 'Please enter a valid Time format' );
    	}
    	
    	// cutoff_past
    	// strtotime date relative to $time_timestamp
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
      
      if( ! empty( $min_timestamp ) && '' != $time_timestamp ) {
        if( $min_timestamp > $time_timestamp ) {
      	  return TkEventWeather_Functions::invalid_shortcode_message( 'Event Time needs to be more recent than the Past Cutoff Time' );
        }
      }
    	
    	
    	// cutoff_future
    	// strtotime date relative to $time_timestamp
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
      
      if( ! empty( $max_timestamp ) && '' != $time_timestamp ) {
        if( $time_timestamp > $max_timestamp ) {
      	  return TkEventWeather_Functions::invalid_shortcode_message( 'Future Cutoff Time needs to be further in the future than Event Time' );
        }
      }
      
      //
      // either $time_timestamp is equal to or greater than $min_timestamp
      // and less than or equal to $max_timestamp
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
    	
    	$exclude_default = apply_filters( 'tk_event_weather_forecast_io_exclude_default', 'alerts,daily,flags,hourly,minutely' );
    	
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
    	  substr( $time_timestamp, -5, 5 ) // last 5 of timestamp
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
       * result in:
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
    			$time
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
    	$output .= sprintf( '<!--%1$sTK Event Weather JSON Data%1$s%2$s%1$s-->', PHP_EOL, json_encode( $data, JSON_PRETTY_PRINT ) ); // requires PHP 5.4
    	$output .= '`tk-event-weather go ahead with API data`';
    	$output .= PHP_EOL;
    	$output .= PHP_EOL;
    	
    	
    	// before
    	
    	// after
    	
    	// class
    	
    	// $debug_vars = true;
    	$debug_vars = WP_DEBUG; // if WP_DEBUG is true, set $debug_vars to true
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
