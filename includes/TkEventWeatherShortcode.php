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
	  	
      	$gmaps_api_key_option = TkEventWeather__Functions::array_get_value_by_key ( $plugin_options, 'google_maps_api_key' );
      	
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
      	'gmaps_api_key'                 => $gmaps_api_key_option,
      	'post_id'                       => get_the_ID(), // The ID of the current post
      	// if lat_long argument is used, it will override the 2 individual latitude and longitude arguments if all 3 arguments exist.
      	'lat_long'                      => '', // manually entered
      	'lat_long_custom_field'         => '', // get custom field value
      	// separate latitude            
      	'lat'                           => '', // manually entered
      	'lat_custom_field'              => '', // get custom field value
      	// separate longitude           
      	'long'                          => '', // manually entered
      	'long_custom_field'             => '', // get custom field value
      	// location/address -- to be geocoded by Google Maps
      	'location'						=> '', // manually entered
      	'location_custom_field'         => '', // get custom field value
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
    	
		
		// load CSS file for Administrators so error messages get styled even if there are no valid shortcodes on page
		// if non-Administrator and no valid shortcode, CSS file will not load
		if ( current_user_can( 'edit_theme_options' ) ) {
			TkEventWeather__Functions::register_css();
			wp_enqueue_style( sanitize_html_class( TkEventWeather__FuncSetup::shortcode_name_hyphenated() ) );
		}
	  	
    	// Code
    	
    	$debug = boolval( $atts['debug_on'] );
    	
    	
		// if false === $transients, clear existing and set new transients
		if( ! empty( $atts['transients_off'] )
			&& 'true' == $atts['transients_off']
		) {
			$transients = false;
		} else {
			$transients = true;
		}
		
    	
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
		
		
		// Fetch from Google Maps Geocoding API
	    $location = '';
	    $location_api_data = '';
	    
    	if( empty( $latitude_longitude ) ) {
	    	
	      	if ( ! empty ( $atts['location'] ) ) {
	      	  $location = $atts['location'];
	      	} elseif ( ! empty( $post_id ) && ! empty( $atts['location_custom_field'] ) ) {
	        	$location = get_post_meta ( $post_id, $atts['location_custom_field'], true );
	      	}
			
			$location = trim( $location );
	    }
	    
		// Google Maps Transient
		if ( ! empty( $location ) ) {
	    	// build transient
	    	$location_transient_name = sprintf( '%s_gmaps_%s',
				TkEventWeather__FuncSetup::$transient_name_prepend,
				TkEventWeather__Functions::remove_all_whitespace( $location )
			);
				
			$location_transient_name = TkEventWeather__Functions::sanitize_transient_name( $location_transient_name );
			$location_transient_value = TkEventWeather__Functions::transient_get_or_delete( $location_transient_name, $transients );
			
			if ( ! empty( $location_transient_value ) ) {
				if ( ! is_object( $location_transient_value ) || is_wp_error( $location_transient_value ) ) {
					$location_transient_value = '';
					delete_transient( $location_transient_name );
				}
			}
			
			if ( ! empty( $location_transient_value ) ) {
				$location_api_data = $location_transient_value;
			} else {
				// make an API call
				$location_request_uri = sprintf( 'https://maps.googleapis.com/maps/api/geocode/json?address=%s', urlencode( $location ) );
				
				$location_request_uri_query_args = array();
				
				$gmaps_api_key = TkEventWeather__Functions::sanitize_key_allow_uppercase( $atts['gmaps_api_key'] );
				if ( ! empty( $gmaps_api_key ) ) {
					$location_request_uri_query_args['key'] = urlencode( $gmaps_api_key );
				}
	    		if ( ! empty( $location_request_uri_query_args ) ) {
	      			$location_request_uri = add_query_arg( $location_request_uri_query_args, $location_request_uri );
		  		}
		  		
		        $location_request = wp_safe_remote_get( esc_url_raw( $location_request_uri ) );
		        
		        if ( is_wp_error( $location_request ) ) {
		          return TkEventWeather__Functions::invalid_shortcode_message( 'Google Maps Geocoding API request sent but resulted in a WordPress Error. Please troubleshoot' );
		        }
		        
	    		// @link https://developer.wordpress.org/reference/functions/wp_remote_retrieve_body/
	    		$location_body = wp_remote_retrieve_body( $location_request );
		    		
		        if( empty( $location_body ) ) {
		          return TkEventWeather__Functions::invalid_shortcode_message( 'Google Maps Geocoding API request sent but nothing received. Please troubleshoot' );
		        }
		        
		        $location_api_data = json_decode( $location_body );
		        
		        if( empty( $location_api_data ) ) {
		          return TkEventWeather__Functions::invalid_shortcode_message( 'Google Maps Geocoding API response received but some sort of data inconsistency. Please troubleshoot' );
		        }
				
				// inside here because if using transient, $location_request will not be set
				if ( ! empty( $debug ) ) {
					$output .= sprintf( '<!--%1$sTK Event Weather -- Google Maps Geocoding API -- Request URI%1$s%2$s%1$s-->%1$s', PHP_EOL, $location_request_uri );
				}
/* Example Debug Output:
<!--
TK Event Weather -- Google Maps Geocoding API -- Request URI
https://maps.googleapis.com/maps/api/geocode/json?address=The+White+House
-->
*/
			}
			
			// do stuff with Google Maps Geocoding API data
			
			// see https://developers.google.com/maps/documentation/geocoding/intro#StatusCodes
			if ( 'OK' != $location_api_data->status ) {
				return TkEventWeather__Functions::invalid_shortcode_message( 'The Google Maps Geocoding API resulted in an error: ' . $location_api_data->status . '. See https://developers.google.com/maps/documentation/geocoding/intro#StatusCodes' );
			}
			
			if ( ! empty ( $location_api_data->results[0]->geometry->location->lat ) ) {
				$latitude = $location_api_data->results[0]->geometry->location->lat;
				$longitude = $location_api_data->results[0]->geometry->location->lng;
			}
			
			// build comma-separated $latitude_longitude
			$latitude_longitude = sprintf( '%F,%F', $latitude, $longitude );
			$latitude_longitude = TkEventWeather__Functions::valid_lat_long( $latitude_longitude );
			
			// now $api_data is set for sure (better be to have gotten this far)
			if ( ! empty( $debug ) ) {
				$output .= sprintf( '<!--%1$sTK Event Weather -- Google Maps Geocoding API -- JSON Data%1$s%2$s%1$s-->%1$s', PHP_EOL, json_encode( $location_api_data, JSON_PRETTY_PRINT ) ); // requires PHP 5.4
			}
			
/* Example Debug Output:
<!--
TK Event Weather -- Google Maps Geocoding API -- JSON Data
{
    "results": [
        {
            "address_components": [
                {
                    "long_name": "The White House",
                    "short_name": "The White House",
                    "types": [
                        "point_of_interest",
                        "establishment"
                    ]
                },
                {
                    "long_name": "1600",
                    "short_name": "1600",
                    "types": [
                        "street_number"
                    ]
                },
                {
                    "long_name": "Pennsylvania Avenue Northwest",
                    "short_name": "Pennsylvania Ave NW",
                    "types": [
                        "route"
                    ]
                },
                {
                    "long_name": "Northwest Washington",
                    "short_name": "Northwest Washington",
                    "types": [
                        "neighborhood",
                        "political"
                    ]
                },
                {
                    "long_name": "Washington",
                    "short_name": "Washington",
                    "types": [
                        "locality",
                        "political"
                    ]
                },
                {
                    "long_name": "District of Columbia",
                    "short_name": "DC",
                    "types": [
                        "administrative_area_level_1",
                        "political"
                    ]
                },
                {
                    "long_name": "United States",
                    "short_name": "US",
                    "types": [
                        "country",
                        "political"
                    ]
                },
                {
                    "long_name": "20500",
                    "short_name": "20500",
                    "types": [
                        "postal_code"
                    ]
                }
            ],
            "formatted_address": "The White House, 1600 Pennsylvania Ave NW, Washington, DC 20500, USA",
            "geometry": {
                "location": {
                    "lat": 38.8976763,
                    "lng": -77.0365298
                },
                "location_type": "APPROXIMATE",
                "viewport": {
                    "northeast": {
                        "lat": 38.899025280291,
                        "lng": -77.035180819709
                    },
                    "southwest": {
                        "lat": 38.896327319708,
                        "lng": -77.037878780292
                    }
                }
            },
            "partial_match": true,
            "place_id": "ChIJ37HL3ry3t4kRv3YLbdhpWXE",
            "types": [
                "point_of_interest",
                "establishment"
            ]
        }
    ],
    "status": "OK"
}
-->
*/
			// set transient if API call resulted in usable data
	        if( true === $transients
	        	&& ! empty( $latitude_longitude ) // API resulted in usable data
	        ) {
	          set_transient( $location_transient_name, $location_api_data, 30 * DAY_IN_SECONDS ); // allowed to store for up to 30 calendar days, per https://developers.google.com/maps/terms#10-license-restrictions
	        }
		}
	    
    	if( empty( $latitude_longitude ) ) {
			return TkEventWeather__Functions::invalid_shortcode_message( 'Please enter valid Latitude and Longitude coordinates (or a Location that Google Maps can get coordinates for)' );
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
	
	    $start_time_timestamp = TkEventWeather__Functions::valid_timestamp( $start_time_timestamp );
	    
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
      
      $min_timestamp = TkEventWeather__Functions::valid_timestamp( $min_timestamp );
      
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
	    
	    $end_time_timestamp = TkEventWeather__Functions::valid_timestamp( $end_time_timestamp );
      
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
    	
    	$template_data['weather_last_hour_timestamp'] = TkEventWeather__Functions::valid_timestamp( $weather_last_hour_timestamp );
    	
    	
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
      
      $max_timestamp = TkEventWeather__Functions::valid_timestamp( $max_timestamp );
      
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
    	$transient_name = sprintf( '%s_fio_%s_%s_%s_%s_%d',
    	  TkEventWeather__FuncSetup::$transient_name_prepend,
    	  $units,
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
	  	
	  	$transient_name = TkEventWeather__Functions::sanitize_transient_name( $transient_name );
	  	
	  	$transient_value = TkEventWeather__Functions::transient_get_or_delete( $transient_name, $transients );
    	
    	
    	
    	
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
        
        // @link https://developer.wordpress.org/reference/functions/is_wp_error/
        if ( is_wp_error( $request ) ) {
          return TkEventWeather__Functions::invalid_shortcode_message( 'Forecast.io API request sent but resulted in a WordPress Error. Please troubleshoot' );
        }
        
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
        
		// inside here because if using transient, $request will not be set
		if ( ! empty( $debug ) ) {
			$output .= sprintf( '<!--%1$sTK Event Weather -- Forecast.io API -- Request URI%1$s%2$s%1$s-->%1$s', PHP_EOL, $request_uri );
		}
/* Example Debug Output:
<!--
TK Event Weather -- Forecast.io API -- Request URI
https://api.forecast.io/forecast/___API_KEY___/38.897676,-77.036530,1464604200?units=auto&exclude=minutely,alerts
-->
*/
		
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
			$output .= sprintf( '<!--%1$sTK Event Weather -- Forecast.io API -- JSON Data%1$s%2$s%1$s-->%1$s', PHP_EOL, json_encode( $api_data, JSON_PRETTY_PRINT ) ); // requires PHP 5.4
      }
    	
/* Example Debug Output:
<!--
TK Event Weather -- Forecast.io API -- JSON Data
{
    "latitude": 38.897676,
    "longitude": -77.03653,
    "timezone": "America\/New_York",
    "offset": -4,
    "currently": {
        "time": 1464604200,
        "summary": "Partly Cloudy",
        "icon": "partly-cloudy-day",
        "precipType": "rain",
        "temperature": 65.66,
        "temperatureError": 6.32,
        "apparentTemperature": 65.66,
        "dewPoint": 58.39,
        "dewPointError": 5,
        "humidity": 0.77,
        "humidityError": 0.14,
        "windSpeed": 4.08,
        "windSpeedError": 4.12,
        "windBearing": 234,
        "windBearingError": 45.29,
        "visibility": 9.46,
        "visibilityError": 6.28,
        "cloudCover": 0.58,
        "cloudCoverError": 0.4,
        "pressure": 1015.95,
        "pressureError": 5.5
    },
    "hourly": {
        "summary": "Mostly cloudy throughout the day.",
        "icon": "partly-cloudy-day",
        "data": [
            {
                "time": 1464580800,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipType": "rain",
                "temperature": 69.52,
                "temperatureError": 4.89,
                "apparentTemperature": 69.52,
                "dewPoint": 59.34,
                "dewPointError": 4.05,
                "humidity": 0.7,
                "humidityError": 0.1,
                "windSpeed": 4.37,
                "windSpeedError": 3.09,
                "windBearing": 193,
                "windBearingError": 35.28,
                "visibility": 10,
                "visibilityError": 4.97,
                "cloudCover": 0.55,
                "cloudCoverError": 0.28,
                "pressure": 1015.86,
                "pressureError": 5.51
            },
            {
                "time": 1464584400,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipType": "rain",
                "temperature": 68.56,
                "temperatureError": 4.88,
                "apparentTemperature": 68.56,
                "dewPoint": 59.29,
                "dewPointError": 3.85,
                "humidity": 0.72,
                "humidityError": 0.1,
                "windSpeed": 4.62,
                "windSpeedError": 3.12,
                "windBearing": 205,
                "windBearingError": 34.05,
                "visibility": 10,
                "visibilityError": 4.87,
                "cloudCover": 0.54,
                "cloudCoverError": 0.29,
                "pressure": 1015.76,
                "pressureError": 5.51
            },
            {
                "time": 1464588000,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipType": "rain",
                "temperature": 67.67,
                "temperatureError": 5.04,
                "apparentTemperature": 67.67,
                "dewPoint": 59.2,
                "dewPointError": 3.86,
                "humidity": 0.74,
                "humidityError": 0.1,
                "windSpeed": 4.63,
                "windSpeedError": 3.24,
                "windBearing": 218,
                "windBearingError": 35,
                "visibility": 10,
                "visibilityError": 4.95,
                "cloudCover": 0.54,
                "cloudCoverError": 0.31,
                "pressure": 1015.6,
                "pressureError": 5.51
            },
            {
                "time": 1464591600,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipType": "rain",
                "temperature": 66.85,
                "temperatureError": 5.31,
                "apparentTemperature": 66.85,
                "dewPoint": 59.05,
                "dewPointError": 4.03,
                "humidity": 0.76,
                "humidityError": 0.11,
                "windSpeed": 4.45,
                "windSpeedError": 3.43,
                "windBearing": 226,
                "windBearingError": 37.58,
                "visibility": 10,
                "visibilityError": 5.16,
                "cloudCover": 0.54,
                "cloudCoverError": 0.33,
                "pressure": 1015.47,
                "pressureError": 5.51
            },
            {
                "time": 1464595200,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipType": "rain",
                "temperature": 66.14,
                "temperatureError": 5.63,
                "apparentTemperature": 66.14,
                "dewPoint": 58.86,
                "dewPointError": 4.28,
                "humidity": 0.77,
                "humidityError": 0.12,
                "windSpeed": 4.28,
                "windSpeedError": 3.65,
                "windBearing": 230,
                "windBearingError": 40.47,
                "visibility": 10,
                "visibilityError": 5.47,
                "cloudCover": 0.55,
                "cloudCoverError": 0.36,
                "pressure": 1015.43,
                "pressureError": 5.5
            },
            {
                "time": 1464598800,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipType": "rain",
                "temperature": 65.65,
                "temperatureError": 5.95,
                "apparentTemperature": 65.65,
                "dewPoint": 58.65,
                "dewPointError": 4.57,
                "humidity": 0.78,
                "humidityError": 0.13,
                "windSpeed": 4.13,
                "windSpeedError": 3.87,
                "windBearing": 233,
                "windBearingError": 43.12,
                "visibility": 9.87,
                "visibilityError": 5.81,
                "cloudCover": 0.56,
                "cloudCoverError": 0.39,
                "pressure": 1015.54,
                "pressureError": 5.5
            },
            {
                "time": 1464602400,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 65.5,
                "temperatureError": 6.22,
                "apparentTemperature": 65.5,
                "dewPoint": 58.46,
                "dewPointError": 4.86,
                "humidity": 0.78,
                "humidityError": 0.13,
                "windSpeed": 4.05,
                "windSpeedError": 4.05,
                "windBearing": 234,
                "windBearingError": 45.05,
                "visibility": 9.56,
                "visibilityError": 6.14,
                "cloudCover": 0.57,
                "cloudCoverError": 0.4,
                "pressure": 1015.78,
                "pressureError": 5.5
            },
            {
                "time": 1464606000,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 65.82,
                "temperatureError": 6.42,
                "apparentTemperature": 65.82,
                "dewPoint": 58.33,
                "dewPointError": 5.15,
                "humidity": 0.77,
                "humidityError": 0.14,
                "windSpeed": 4.12,
                "windSpeedError": 4.2,
                "windBearing": 234,
                "windBearingError": 45.53,
                "visibility": 9.35,
                "visibilityError": 6.42,
                "cloudCover": 0.59,
                "cloudCoverError": 0.4,
                "pressure": 1016.12,
                "pressureError": 5.5
            },
            {
                "time": 1464609600,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 66.67,
                "temperatureError": 6.56,
                "apparentTemperature": 66.67,
                "dewPoint": 58.27,
                "dewPointError": 5.48,
                "humidity": 0.74,
                "humidityError": 0.14,
                "windSpeed": 4.58,
                "windSpeedError": 4.29,
                "windBearing": 231,
                "windBearingError": 43.15,
                "visibility": 9.3,
                "visibilityError": 6.64,
                "cloudCover": 0.61,
                "cloudCoverError": 0.4,
                "pressure": 1016.48,
                "pressureError": 5.5
            },
            {
                "time": 1464613200,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 68.07,
                "temperatureError": 6.67,
                "apparentTemperature": 68.07,
                "dewPoint": 58.26,
                "dewPointError": 5.88,
                "humidity": 0.71,
                "humidityError": 0.15,
                "windSpeed": 6.02,
                "windSpeedError": 4.36,
                "windBearing": 234,
                "windBearingError": 35.88,
                "visibility": 9.48,
                "visibilityError": 6.81,
                "cloudCover": 0.62,
                "cloudCoverError": 0.38,
                "pressure": 1016.77,
                "pressureError": 5.5
            },
            {
                "time": 1464616800,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 69.93,
                "temperatureError": 6.78,
                "apparentTemperature": 69.93,
                "dewPoint": 58.24,
                "dewPointError": 6.42,
                "humidity": 0.66,
                "humidityError": 0.15,
                "windSpeed": 6.84,
                "windSpeedError": 4.41,
                "windBearing": 241,
                "windBearingError": 32.79,
                "visibility": 9.87,
                "visibilityError": 6.94,
                "cloudCover": 0.64,
                "cloudCoverError": 0.37,
                "pressure": 1016.89,
                "pressureError": 5.5
            },
            {
                "time": 1464620400,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 72.07,
                "temperatureError": 6.92,
                "apparentTemperature": 72.07,
                "dewPoint": 58.16,
                "dewPointError": 7.14,
                "humidity": 0.62,
                "humidityError": 0.15,
                "windSpeed": 7.48,
                "windSpeedError": 4.46,
                "windBearing": 243,
                "windBearingError": 30.78,
                "visibility": 10,
                "visibilityError": 7.07,
                "cloudCover": 0.65,
                "cloudCoverError": 0.35,
                "pressure": 1016.8,
                "pressureError": 5.5
            },
            {
                "time": 1464624000,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 74.26,
                "temperatureError": 7.11,
                "apparentTemperature": 74.26,
                "dewPoint": 57.96,
                "dewPointError": 8.07,
                "humidity": 0.57,
                "humidityError": 0.16,
                "windSpeed": 8.09,
                "windSpeedError": 4.52,
                "windBearing": 244,
                "windBearingError": 29.16,
                "visibility": 10,
                "visibilityError": 7.21,
                "cloudCover": 0.66,
                "cloudCoverError": 0.34,
                "pressure": 1016.51,
                "pressureError": 5.5
            },
            {
                "time": 1464627600,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 76.22,
                "temperatureError": 7.33,
                "apparentTemperature": 76.22,
                "dewPoint": 57.69,
                "dewPointError": 9.14,
                "humidity": 0.53,
                "humidityError": 0.17,
                "windSpeed": 8.57,
                "windSpeedError": 4.58,
                "windBearing": 243,
                "windBearingError": 28.13,
                "visibility": 10,
                "visibilityError": 7.37,
                "cloudCover": 0.66,
                "cloudCoverError": 0.33,
                "pressure": 1016.05,
                "pressureError": 5.5
            },
            {
                "time": 1464631200,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 77.74,
                "temperatureError": 7.54,
                "apparentTemperature": 77.74,
                "dewPoint": 57.46,
                "dewPointError": 10.19,
                "humidity": 0.5,
                "humidityError": 0.18,
                "windSpeed": 8.84,
                "windSpeedError": 4.64,
                "windBearing": 240,
                "windBearingError": 27.69,
                "visibility": 10,
                "visibilityError": 7.52,
                "cloudCover": 0.66,
                "cloudCoverError": 0.34,
                "pressure": 1015.52,
                "pressureError": 5.5
            },
            {
                "time": 1464634800,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 78.65,
                "temperatureError": 7.71,
                "apparentTemperature": 78.65,
                "dewPoint": 57.39,
                "dewPointError": 10.97,
                "humidity": 0.48,
                "humidityError": 0.18,
                "windSpeed": 8.87,
                "windSpeedError": 4.67,
                "windBearing": 235,
                "windBearingError": 27.78,
                "visibility": 10,
                "visibilityError": 7.63,
                "cloudCover": 0.66,
                "cloudCoverError": 0.35,
                "pressure": 1015.01,
                "pressureError": 5.5
            },
            {
                "time": 1464638400,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 78.87,
                "temperatureError": 7.76,
                "apparentTemperature": 78.87,
                "dewPoint": 57.56,
                "dewPointError": 11.22,
                "humidity": 0.48,
                "humidityError": 0.19,
                "windSpeed": 8.63,
                "windSpeedError": 4.65,
                "windBearing": 228,
                "windBearingError": 28.31,
                "visibility": 10,
                "visibilityError": 7.67,
                "cloudCover": 0.65,
                "cloudCoverError": 0.35,
                "pressure": 1014.64,
                "pressureError": 5.5
            },
            {
                "time": 1464642000,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 78.45,
                "temperatureError": 7.66,
                "apparentTemperature": 78.45,
                "dewPoint": 57.93,
                "dewPointError": 10.84,
                "humidity": 0.49,
                "humidityError": 0.18,
                "windSpeed": 8.13,
                "windSpeedError": 4.56,
                "windBearing": 219,
                "windBearingError": 29.27,
                "visibility": 10,
                "visibilityError": 7.6,
                "cloudCover": 0.64,
                "cloudCoverError": 0.36,
                "pressure": 1014.46,
                "pressureError": 5.5
            },
            {
                "time": 1464645600,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 77.5,
                "temperatureError": 7.39,
                "apparentTemperature": 77.5,
                "dewPoint": 58.4,
                "dewPointError": 9.94,
                "humidity": 0.52,
                "humidityError": 0.18,
                "windSpeed": 7.42,
                "windSpeedError": 4.38,
                "windBearing": 208,
                "windBearingError": 30.58,
                "visibility": 10,
                "visibilityError": 7.39,
                "cloudCover": 0.63,
                "cloudCoverError": 0.35,
                "pressure": 1014.5,
                "pressureError": 5.51
            },
            {
                "time": 1464649200,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 76.21,
                "temperatureError": 6.98,
                "apparentTemperature": 76.21,
                "dewPoint": 58.84,
                "dewPointError": 8.73,
                "humidity": 0.55,
                "humidityError": 0.17,
                "windSpeed": 6.69,
                "windSpeedError": 4.15,
                "windBearing": 198,
                "windBearingError": 31.77,
                "visibility": 10,
                "visibilityError": 7.04,
                "cloudCover": 0.61,
                "cloudCoverError": 0.34,
                "pressure": 1014.72,
                "pressureError": 5.51
            },
            {
                "time": 1464652800,
                "summary": "Mostly Cloudy",
                "icon": "partly-cloudy-day",
                "precipType": "rain",
                "temperature": 74.76,
                "temperatureError": 6.46,
                "apparentTemperature": 74.76,
                "dewPoint": 59.18,
                "dewPointError": 7.44,
                "humidity": 0.58,
                "humidityError": 0.15,
                "windSpeed": 6.09,
                "windSpeedError": 3.87,
                "windBearing": 191,
                "windBearingError": 32.41,
                "visibility": 10,
                "visibilityError": 6.59,
                "cloudCover": 0.6,
                "cloudCoverError": 0.33,
                "pressure": 1015.06,
                "pressureError": 5.51
            },
            {
                "time": 1464656400,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipType": "rain",
                "temperature": 73.31,
                "temperatureError": 5.92,
                "apparentTemperature": 73.31,
                "dewPoint": 59.41,
                "dewPointError": 6.25,
                "humidity": 0.62,
                "humidityError": 0.14,
                "windSpeed": 5.66,
                "windSpeedError": 3.58,
                "windBearing": 187,
                "windBearingError": 32.32,
                "visibility": 10,
                "visibilityError": 6.1,
                "cloudCover": 0.58,
                "cloudCoverError": 0.31,
                "pressure": 1015.41,
                "pressureError": 5.51
            },
            {
                "time": 1464660000,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipType": "rain",
                "temperature": 71.97,
                "temperatureError": 5.43,
                "apparentTemperature": 71.97,
                "dewPoint": 59.53,
                "dewPointError": 5.25,
                "humidity": 0.65,
                "humidityError": 0.12,
                "windSpeed": 5.41,
                "windSpeedError": 3.33,
                "windBearing": 187,
                "windBearingError": 31.62,
                "visibility": 10,
                "visibilityError": 5.62,
                "cloudCover": 0.57,
                "cloudCoverError": 0.29,
                "pressure": 1015.7,
                "pressureError": 5.52
            },
            {
                "time": 1464663600,
                "summary": "Partly Cloudy",
                "icon": "partly-cloudy-night",
                "precipType": "rain",
                "temperature": 70.78,
                "temperatureError": 5.06,
                "apparentTemperature": 70.78,
                "dewPoint": 59.58,
                "dewPointError": 4.5,
                "humidity": 0.68,
                "humidityError": 0.11,
                "windSpeed": 5.32,
                "windSpeedError": 3.16,
                "windBearing": 188,
                "windBearingError": 30.7,
                "visibility": 10,
                "visibilityError": 5.22,
                "cloudCover": 0.55,
                "cloudCoverError": 0.28,
                "pressure": 1015.87,
                "pressureError": 5.52
            }
        ]
    },
    "daily": {
        "data": [
            {
                "time": 1464580800,
                "summary": "Mostly cloudy throughout the day.",
                "icon": "partly-cloudy-day",
                "sunriseTime": 1464601606,
                "sunsetTime": 1464654461,
                "moonPhase": 0.8,
                "precipType": "rain",
                "temperatureMin": 65.5,
                "temperatureMinError": 6.22,
                "temperatureMinTime": 1464602400,
                "temperatureMax": 78.87,
                "temperatureMaxError": 7.76,
                "temperatureMaxTime": 1464638400,
                "apparentTemperatureMin": 65.5,
                "apparentTemperatureMinTime": 1464602400,
                "apparentTemperatureMax": 78.87,
                "apparentTemperatureMaxTime": 1464638400,
                "dewPoint": 58.54,
                "dewPointError": 7.19,
                "humidity": 0.64,
                "humidityError": 0.15,
                "windSpeed": 5.78,
                "windSpeedError": 4.05,
                "windBearing": 222,
                "windBearingError": 34.98,
                "visibility": 10,
                "visibilityError": 6.49,
                "cloudCover": 0.6,
                "cloudCoverError": 0.34,
                "pressure": 1015.67,
                "pressureError": 5.5
            }
        ]
    },
    "flags": {
        "sources": [
            "isd"
        ],
        "isd-stations": [
            "724050-13743",
            "997314-99999",
            "999999-13710",
            "999999-13751",
            "999999-93725"
        ],
        "units": "us"
    }
}
-->
*/    	
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
