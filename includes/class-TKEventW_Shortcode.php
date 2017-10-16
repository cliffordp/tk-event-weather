<?php

// Option 3 from http://plugin.michael-simpson.com/?page_id=39

include_once( 'ShortCodeScriptLoader.php' );
require_once( 'class-TKEventW_Setup.php' );
require_once( 'class-TKEventW_Functions.php' );
require_once( 'class-TKEventW_API_Google_Maps.php' );
require_once( 'class-TKEventW_API_Dark_Sky.php' );
require_once( 'class-TKEventW_Single_Day.php' );
require_once( 'class-TKEventW_Template.php' );
require_once( 'class-TKEventW_Template_Loader.php' );
require_once( 'class-TKEventW_Time.php' );

class TKEventW_Shortcode extends TkEventW__ShortCodeScriptLoader {

	private static $addedAlready = false;

	public static $dark_sky_api_key = '';
	public static $dark_sky_api_units = '';
	public static $dark_sky_api_exclude = '';
	public static $dark_sky_api_uri_query_args = array();
	public static $dark_sky_api_transient_used = 'FALSE';

	public static $google_maps_api_key = '';
	public static $google_maps_api_transient_used = 'FALSE';

	public static $debug_enabled = false;
	public static $transients_enabled = true;
	public static $transients_expiration_hours = 0;

	// ONLY set via TKEventW_Time::set_timezone_and_source_from_shortcode_args()
	public static $timezone_source = ''; // can be blank if timezone is set manually via shortcode argument
	public static $timezone = ''; // cannot be blank, possibly set via TKEventW_Time::set_timezone_from_api()

	public static $time_format_day = '';
	public static $time_format_hours = '';
	public static $time_format_minutes = '';

	// Variables named with "span" are for the entire timespan, such as multiday.
	public static $span_start_time_timestamp = false;
	public static $span_first_hour_timestamp = false; // Start time's timestamp with minutes truncated. Should be equal to or less than $span_start_time_timestamp.
	public static $span_end_time_timestamp = false;

	public static $span_total_days_in_span = false;

	public static $span_template_data = array();

	/**
	 * The street address that you want to geocode, in the format used by the
	 * national postal service of the country concerned. Additional address
	 * elements such as business names and unit, suite or floor numbers
	 * should be avoided.
	 *
	 * @link https://developers.google.com/maps/documentation/geocoding/intro#geocoding
	 *
	 * @var string
	 */
	public static $location = '';

	/**
	 * The comma-separated latitude,longitude coordinates to send to Dark Sky API.
	 *
	 * @var string
	 */
	public static $latitude_longitude = '';

	public static function shortcode_name() {
		return TKEventW_Setup::$shortcode_name;
	}

	public function handleShortcode( $atts ) {

		$output = '';

		$plugin_options = TKEventW_Functions::plugin_options();

		if ( empty( $plugin_options ) ) {
			TKEventW_Functions::invalid_shortcode_message( 'Please complete the initial setup' );

			return TKEventW_Functions::$shortcode_error_message;
		} else {
			$api_key_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'darksky_api_key' );

			$multi_day_off_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'multi_day_off' );

			$gmaps_api_key_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'google_maps_api_key' );

			$text_before = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'text_before' );

			$text_after = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'text_after' );

			$display_template_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'display_template' );

			$time_format_day_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'time_format_day', 'M j' );

			$time_format_hours_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'time_format_hours', 'ga' );

			$time_format_minutes_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'time_format_minutes', 'g:i' );

			$cutoff_past_days_option   = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'cutoff_past_days', 30 );
			$cutoff_future_days_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'cutoff_future_days', 365 );

			$units_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'darksky_units', 'auto' );

			$transients_off_option              = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'transients_off' );
			$transients_expiration_hours_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'transients_expiration_hours', 12 );

			$timezone_source_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'timezone_source', 'api' );

			$sunrise_sunset_off_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'sunrise_sunset_off' );

			//$icons_option = TkEventW__Functions::array_get_value_by_key ( $plugin_options, 'icons' );

			$plugin_credit_link_on_option   = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'plugin_credit_link_on' );
			$darksky_credit_link_off_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'darksky_credit_link_off' );

			$debug_on_option = TKEventW_Functions::array_get_value_by_key( $plugin_options, 'debug_on' );

		}

		/*
		* Required:
		* api_key
		* lat/long
		* start time
		*/
		// Attributes
		$defaults = array(
			'api_key'                 => $api_key_option,
			'multi_day_off'           => $multi_day_off_option,
			'gmaps_api_key'           => $gmaps_api_key_option,
			'post_id'                 => get_the_ID(),
			// The ID of the current post
			// if lat_long argument is used, it will override the 2 individual latitude and longitude arguments if all 3 arguments exist.
			'lat_long'                => '',
			// manually entered
			'lat_long_custom_field'   => '',
			// get custom field value
			// separate latitude
			'lat'                     => '',
			// manually entered
			'lat_custom_field'        => '',
			// get custom field value
			// separate longitude
			'long'                    => '',
			// manually entered
			'long_custom_field'       => '',
			// get custom field value
			// location/address -- to be geocoded by Google Maps
			'location'                => '',
			// manually entered
			'location_custom_field'   => '',
			// get custom field value
			// time (ISO 8601 or Unix Timestamp)
			'start_time'              => '',
			// manually entered
			'start_time_custom_field' => '',
			// get custom field value
			'end_time'                => '',
			// manually entered
			'end_time_custom_field'   => '',
			// get custom field value
			// time constraints in strtotime relative dates
			'cutoff_past'             => $cutoff_past_days_option,
			'cutoff_future'           => $cutoff_future_days_option,
			// API options -- see https://darksky.net/dev/docs/time-machine
			'exclude'                 => '',
			// comma-separated. Default/fallback is $exclude_default
			'transients_off'          => $transients_off_option,
			// "true" is the only valid value
			'transients_expiration'   => $transients_expiration_hours_option,
			// Display Customizations
			'before'                  => $text_before,
			'after'                   => $text_after,
			'time_format_day'         => $time_format_day_option,
			'time_format_hours'       => $time_format_hours_option,
			'time_format_minutes'     => $time_format_minutes_option,
			'units'                   => '',
			// default/fallback is $units_default
			'timezone'                => '',
			// allow entering specific timezone -- see https://secure.php.net/manual/timezones.php -- does NOT support Manual Offset (e.g. UTC+10), even though WordPress provides these options.
			'timezone_source'         => $timezone_source_option,
			'sunrise_sunset_off'      => $sunrise_sunset_off_option,
			// "true" is the only valid value
			'icons'                   => '',
			'plugin_credit_link_on'   => $plugin_credit_link_on_option,
			// "true" is the only valid value
			'darksky_credit_link_off' => $darksky_credit_link_off_option,
			// anything !empty()
			// HTML
			'class'                   => '',
			// custom class
			'template'                => $display_template_option,
			// Debug Mode
			'debug_on'                => $debug_on_option,
			// anything !empty()
		);
		// TODO: Max days -- set to 1 for non-multiday forcasts

		$atts = shortcode_atts( $defaults, $atts, 'tk-event-weather' );

		// extract( $atts ); // convert each array item to individual variable

		// Code

		self::$debug_enabled = (bool) $atts['debug_on']; // TODO not used at all? -- maybe var_dump( get_defined_vars() ) somewhere?


		// if false === $transients, clear existing and set new transients
		if ( ! empty( $atts['transients_off'] )
		     && 'true' == $atts['transients_off']
		) {
			self::$transients_enabled = false;
		} else {
			self::$transients_enabled = true;
		}


		self::$dark_sky_api_key = $atts['api_key'];

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

		self::$span_template_data['post_id'] = $post_id;

		// only used temporarily if separate lat and long need to be combined
		$latitude  = '';
		$longitude = '';

		// combined lat,long (manual then custom field)
		if ( ! empty ( $atts['lat_long'] ) ) {
			self::$latitude_longitude = $atts['lat_long'];
		} elseif ( ! empty( $post_id ) && ! empty( $atts['lat_long_custom_field'] ) ) {
			self::$latitude_longitude = get_post_meta( $post_id, $atts['lat_long_custom_field'], true );
		}

		self::$latitude_longitude = TKEventW_Functions::valid_lat_long( self::$latitude_longitude );

		// if no lat,long yet then build via separate lat and long
		if ( empty ( self::$latitude_longitude ) ) {
			// latitude
			if ( ! empty ( $atts['lat'] ) ) {
				$latitude = $atts['lat'];
			} elseif ( ! empty( $post_id ) && ! empty( $atts['lat_custom_field'] ) ) {
				$latitude = get_post_meta( $post_id, $atts['lat_custom_field'], true );
			}

			// longitude
			if ( ! empty ( $atts['long'] ) ) {
				$longitude = $atts['long'];
			} elseif ( ! empty( $post_id ) && ! empty( $atts['long_custom_field'] ) ) {
				$longitude = get_post_meta( $post_id, $atts['long_custom_field'], true );
			}

			// combine into comma-separated
			self::$latitude_longitude = sprintf( '%F,%F', $latitude, $longitude );
			self::$latitude_longitude = TKEventW_Functions::valid_lat_long( self::$latitude_longitude );
		}

		if ( empty( self::$latitude_longitude ) ) {

			if ( ! empty ( $atts['location'] ) ) {
				self::$location = $atts['location'];
			} elseif ( ! empty( $post_id ) && ! empty( $atts['location_custom_field'] ) ) {
				self::$location = get_post_meta( $post_id, $atts['location_custom_field'], true );
			}

			self::$location = trim( self::$location );
		}

		// Get lat,long from Google Maps API
		if ( ! empty( self::$location ) ) {

			self::$google_maps_api_key = TKEventW_Functions::sanitize_key_allow_uppercase( $atts['gmaps_api_key'] );

			// Fetch from transient or Google Maps Geocoding API
			self::$latitude_longitude = TKEventW_API_Google_Maps::get_lat_long();

			$output .= TKEventW_API_Google_Maps::get_debug_output();
		}

		if ( empty( self::$latitude_longitude ) ) {
			if ( empty( TKEventW_Functions::$shortcode_error_message ) ) { // TODO move to the method itself (to not overwrite)
				TKEventW_Functions::invalid_shortcode_message( 'Please enter valid Latitude and Longitude coordinates (or a Location that Google Maps can get coordinates for)' );
			}

			return TKEventW_Functions::$shortcode_error_message;
		}

		self::$span_template_data['latitude_longitude'] = self::$latitude_longitude;

		// Start Time
		// ISO 8601 datetime or Unix timestamp
		if ( '' != $atts['start_time'] ) {
			$start_time = $atts['start_time'];
		} elseif ( ! empty( $post_id ) && ! empty( $atts['start_time_custom_field'] ) ) {
			$start_time = get_post_meta( $post_id, $atts['lat_long_custom_field'], true );
		} else {
			$start_time = '';
		}

		if ( '' != $start_time ) {
			// check ISO 8601 first because it's stricter
			if ( true === TKEventW_Time::valid_iso_8601_date_time( $start_time, 'bool' ) ) {
				$start_time           = TKEventW_Time::valid_iso_8601_date_time( $start_time );
				$start_time_iso_8601  = $start_time;
				$start_time_timestamp = date( 'U', strtotime( $start_time ) );
			} // check timestamp
			elseif ( true === TKEventW_Time::valid_timestamp( $start_time, 'bool' ) ) {
				$start_time           = TKEventW_Time::valid_timestamp( $start_time );
				$start_time_iso_8601  = date( DateTime::ATOM, $start_time ); // DateTime::ATOM is same as 'c'
				$start_time_timestamp = $start_time;
			} // strtotime() or invalid (and therefore clear out)
			else {
				$start_time = strtotime( $start_time );

				if ( false === $start_time ) {
					$start_time           = '';
					$start_time_timestamp = '';
				} else {
					$start_time_timestamp = $start_time;
					$start_time_iso_8601  = date( DateTime::ATOM, $start_time ); // DateTime::ATOM is same as 'c'
				}
			}
		}

		// avoid error of variable not being set
		if ( ! isset( $start_time_timestamp ) ) {
			$start_time_timestamp = '';
		}

		$start_time_timestamp = TKEventW_Time::valid_timestamp( $start_time_timestamp );


		if ( empty( $start_time_timestamp ) ) {
			TKEventW_Functions::invalid_shortcode_message( 'Please enter a valid Start Time format' );

			return TKEventW_Functions::$shortcode_error_message;
		}

		self::$span_start_time_timestamp = $start_time_timestamp;
		self::$span_first_hour_timestamp = TKEventW_Time::timestamp_truncate_minutes( $start_time_timestamp );

		self::$span_template_data['span_first_hour_timestamp'] = TKEventW_Shortcode::$span_first_hour_timestamp;


		// cutoff_past
		// strtotime date relative to self::$span_first_hour_timestamp
		if ( ! empty( $atts['cutoff_past'] ) ) {
			$cutoff_past = $atts['cutoff_past'];
		} else {
			$cutoff_past = '';
		}

		if ( empty( $cutoff_past ) ) { // not set or set to zero (which means "no limit" per plugin option)
			$min_timestamp = '';
		} else {
			if ( is_int( $cutoff_past ) ) {
				// if is_int, use that number of NEGATIVE (past) days, per plugin option
				$min_timestamp = strtotime( sprintf( '-%d days', absint( $cutoff_past ) ) );
			} else {
				// else, use raw input, hopefully formatted correctly (e.g. cutoff_past="2 weeks")
				$min_timestamp = strtotime( esc_html( $cutoff_past ) ); // returns false on bad input
			}
		}

		$min_timestamp = TKEventW_Time::valid_timestamp( $min_timestamp );

		if ( ! empty( $min_timestamp ) && '' != TKEventW_Shortcode::$span_first_hour_timestamp ) {
			if ( $min_timestamp > TKEventW_Shortcode::$span_first_hour_timestamp ) {
				TKEventW_Functions::invalid_shortcode_message( 'Event Start Time needs to be more recent than the Past Cutoff Time' );

				return TKEventW_Functions::$shortcode_error_message;
			}
		}

		// max 60 years in the past, per API docs
		if ( strtotime( '-60 years' ) > TKEventW_Shortcode::$span_first_hour_timestamp ) {
			TKEventW_Functions::invalid_shortcode_message( 'Event Start Time needs to be more recent than 60 years in the past, per Dark Sky API docs,' );

			return TKEventW_Functions::$shortcode_error_message;
		}

		// End Time
		// ISO 8601 datetime or Unix timestamp
		if ( '' != $atts['end_time'] ) {
			$end_time = $atts['end_time'];
		} elseif ( ! empty( $post_id ) && ! empty( $atts['end_time_custom_field'] ) ) {
			$end_time = get_post_meta( $post_id, $atts['lat_long_custom_field'], true );
		} else {
			$end_time = '';
		}

		if ( '' != $end_time ) {
			// check ISO 8601 first because it's stricter
			if ( true === TKEventW_Time::valid_iso_8601_date_time( $end_time, 'bool' ) ) {
				$end_time           = TKEventW_Time::valid_iso_8601_date_time( $end_time );
				$end_time_iso_8601  = $end_time;
				$end_time_timestamp = TKEventW_Time::valid_timestamp( date( 'U', strtotime( $end_time ) ) ); // date() returns a string
			} // check timestamp
			elseif ( true === TKEventW_Time::valid_timestamp( $end_time, 'bool' ) ) {
				$end_time           = TKEventW_Time::valid_timestamp( $end_time );
				$end_time_iso_8601  = date( DateTime::ATOM, $end_time ); // DateTime::ATOM is same as 'c'
				$end_time_timestamp = $end_time;
			} // strtotime() or invalid (and therefore clear out)
			else {
				$end_time = strtotime( $end_time, $start_time_timestamp ); // strtotime() being relative to Start Time

				if ( false === $end_time ) {
					$end_time           = '';
					$end_time_timestamp = '';
				} else {
					$end_time_timestamp = $end_time;
					$end_time_iso_8601  = date( DateTime::ATOM, $end_time ); // DateTime::ATOM is same as 'c'
				}
			}
		}

		// avoid error of variable not being set
		if ( ! isset( $end_time_timestamp ) ) {
			$end_time_timestamp = '';
		}

		$end_time_timestamp = TKEventW_Time::valid_timestamp( $end_time_timestamp );

		if ( '' == $end_time_timestamp ) {
			$end_time_timestamp = TKEventW_Shortcode::$span_first_hour_timestamp + DAY_IN_SECONDS; // just padding it on through the next day
			$total_days_in_span = 1; // and then manually setting Total Days to 1 because of fudging the End Time (because we do not yet have the timezone)
		}

		self::$span_end_time_timestamp = $end_time_timestamp;

		// if Event Start and End times are the same
		if ( TKEventW_Shortcode::$span_first_hour_timestamp == $end_time_timestamp ) {
			// this is allowed as of version 1.4
			// TkEventW__Functions::invalid_shortcode_message( 'Please make sure Event Start Time and Event End Time are not the same' );
			// return TKEventW_Functions::$shortcode_error_message;
		}

		// if Event End time is before Start time
		if ( TKEventW_Shortcode::$span_first_hour_timestamp > $end_time_timestamp ) {
			TKEventW_Functions::invalid_shortcode_message( 'Event Start Time must be earlier than Event End Time' );

			return TKEventW_Functions::$shortcode_error_message;
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
			if ( is_int( $cutoff_future ) ) {
				// if is_int, use that number of POSITIVE (future) days, per plugin option
				$max_timestamp = strtotime( sprintf( '+%d days', absint( $cutoff_future ) ) );
			} else {
				// else, use raw input, hopefully formatted correctly (e.g. cutoff_future="2 weeks")
				$max_timestamp = strtotime( esc_html( $cutoff_future ) ); // returns false on bad input
			}
		}

		$max_timestamp = TKEventW_Time::valid_timestamp( $max_timestamp );

		if ( ! empty( $max_timestamp ) && '' != $end_time_timestamp ) {
			if ( $end_time_timestamp > $max_timestamp ) {
				TKEventW_Functions::invalid_shortcode_message( 'Event End Time needs to be more recent than Future Cutoff Time' );

				return TKEventW_Functions::$shortcode_error_message;
			}
		}

		// max 10 years future, per API docs
		if ( $end_time_timestamp > strtotime( '+10 years' ) ) {
			TKEventW_Functions::invalid_shortcode_message( 'Event End Time needs to be less than 10 years in the future, per Dark Sky API docs,' );

			return TKEventW_Functions::$shortcode_error_message;
		}


		//
		// TKEventW_Shortcode::$span_first_hour_timestamp is equal to or greater than $min_timestamp and $end_time_timestamp is less than or equal to $max_timestamp
		// or $min_timestamp and/or $max_timestamp were set to zero (i.e. no limits)
		// so continue...
		//


		// Before Text
		$before = sanitize_text_field( $atts['before'] );

		$before_filtered = apply_filters( 'tk_event_weather_before_full_html', $before ); // if you filter it, you're responsible for the entire HTML (e.g. wrapping in h4 tag)

		if ( '' != $before || '' != $before_filtered ) {
			if ( $before_filtered === $before ) {
				$before = sprintf( '<h4 class="tk-event-weather__before">%s</h4>', $before );
			} else {
				$before = $before_filtered;
			}
		}

		self::$span_template_data['before'] = $before;

		// After Text
		$after = sanitize_text_field( $atts['after'] );

		$after_filtered = apply_filters( 'tk_event_weather_after_full_html', $after ); // if you filter it, you're responsible for the entire HTML (e.g. wrapping in p tag)

		if ( '' != $after || '' != $after_filtered ) {
			if ( $after_filtered === $after ) {
				$after = sprintf( '<p class="tk-event-weather__after">%s</p>', $after );
			} else {
				$after = $after_filtered;
			}
		}

		self::$span_template_data['after'] = $after;

		// time_format_day
		$time_format_day = sanitize_text_field( $atts['time_format_day'] );

		self::$time_format_day = $time_format_day;

		// time_format_hours
		$time_format_hours = sanitize_text_field( $atts['time_format_hours'] );

		self::$time_format_hours = $time_format_hours;

		// time_format_minutes
		$time_format_minutes = sanitize_text_field( $atts['time_format_minutes'] );

		self::$time_format_minutes = $time_format_minutes;

		// units
		$units = TKEventW_Functions::remove_all_whitespace( strtolower( $atts['units'] ) );

		if ( ! array_key_exists( $units, TKEventW_Functions::darksky_option_units() ) ) {
			$units = $units_option;
		}

		self::$dark_sky_api_units = $units;

		// exclude
		$exclude = '';

		// shortcode argument's value
		$exclude_arg = TKEventW_Functions::remove_all_whitespace( strtolower( $atts['exclude'] ) );

		if ( ! empty( $exclude ) ) {
			$exclude_arg_array = explode( ',', $exclude_arg );

			if ( is_array( $exclude_arg_array ) ) {
				sort( $exclude_arg_array );
				$possible_excludes = TKEventW_Functions::darksky_option_exclude();

				foreach ( $exclude_arg_array as $key => $value ) {
					// if valid 'exclude' then keep it, else ignore it
					if ( array_key_exists( $value, $possible_excludes ) ) {
						if ( empty( $exclude ) ) { // if first to be added to $exclude
							$exclude .= $value;
						} else { // if not first one added to $exclude
							$exclude .= ',';
							$exclude .= $value;
						}
					} else {
					}
				} // foreach()
			} // is_array()
		} // if

		if ( empty( $exclude_arg ) ) {
			$exclude = 'minutely,alerts';
		}

		self::$dark_sky_api_exclude = $exclude;


		// Sunrise Sunset
		self::$span_template_data['sunrise_sunset']['on'] = false;

		if (
			empty( $atts['sunrise_sunset_off'] )
			|| 'true' != $atts['sunrise_sunset_off']
		) {
			self::$span_template_data['sunrise_sunset']['on'] = true;
		}


		// Icons
		$icons = $atts['icons'];

		if (
			empty( $icons )
			|| 'climacons' == $icons
			|| ! in_array( $icons, TKEventW_Functions::valid_icon_type() )
		) {
			$icons = 'climacons_font';
		}

		// enqueue CSS file if using Climacons Icon Font
		if ( 'climacons_font' == $icons ) {
			wp_enqueue_style( 'tkeventw-climacons' );
		}

		self::$span_template_data['icons'] = $icons;

		if ( 'true' == $atts['plugin_credit_link_on'] ) {
			self::$span_template_data['plugin_credit_link_enabled'] = true;
		}

		if ( 'true' == $atts['darksky_credit_link_off'] ) {
			self::$span_template_data['darksky_credit_link_enabled'] = false;
		}

		self::$span_template_data['class'] = $atts['class'];

		// Template
		$display_template = TKEventW_Functions::remove_all_whitespace( strtolower( $atts['template'] ) );

		if ( ! array_key_exists( $display_template, TKEventW_Template::valid_display_templates() ) ) {
			$display_template = 'hourly_horizontal';
		}

		self::$span_template_data['template']            = $display_template;
		self::$span_template_data['template_class_name'] = TKEventW_Template::template_class_name( $display_template );


		TKEventW_Time::set_timezone_and_source_from_shortcode_args( $atts['timezone'], $atts['timezone_source'] );

		if ( ! empty( TKEventW_Functions::$shortcode_error )) { //TODO remove this variable
			return TKEventW_Functions::$shortcode_error_message;
		}

		/**
		 * Run the first day through Dark Sky API.
		 *
		 * Must be done before setting Timezone because the timezone might
		 * be getting set via the API, depending on the settings.
		 */
		$first_day_class = new TKEventW_Single_Day( self::$span_start_time_timestamp, self::$span_end_time_timestamp, 1 );
		$first_day_data  = $first_day_class::get_result();

		if ( ! empty( self::$debug_enabled ) ) {
			$output .= $first_day_data['api_data_debug'];
		}

		if ( ! empty( TKEventW_Functions::$shortcode_error )) {
			return TKEventW_Functions::$shortcode_error_message;
		}

		$output .= $first_day_data['template_output'];

		if ( 'true' == $atts['multi_day_off'] ) {
			self::$span_template_data['multi_day_off'] = true;
		}


		// Only calculate Total Days in Span if not already set because it might have been forced to 1 (if no End Time was set)
		if ( empty( $total_days_in_span ) ) {
			$total_days_in_span = TKEventW_Time::count_start_end_cal_days_span( self::$span_start_time_timestamp, self::$span_end_time_timestamp, self::$timezone );
		}

		$midnight_first_day = TKEventW_Time::get_a_days_min_max_timestamp( self::$span_start_time_timestamp, self::$timezone );
		$midnight_last_day  = TKEventW_Time::get_a_days_min_max_timestamp( self::$span_end_time_timestamp, self::$timezone );

		$midnight_timestamps_except_first_day = array();

		$existing_timezone = date_default_timezone_get(); // will fallback to UTC but may also return a TZ environment variable (e.g. EST)
		date_default_timezone_set( self::$timezone );

		// because we are excluding the first day
		for ( $i = $total_days_in_span - 1; 0 !== $i; $i -- ) {
			$days_string                            = sprintf( '+%d days', $i );
			$midnight_timestamps_except_first_day[] = strtotime( $days_string, $midnight_first_day );
		}
		date_default_timezone_set( $existing_timezone );

		sort( $midnight_timestamps_except_first_day, SORT_NUMERIC );

		// Check for data inconsistencies... should not happen.
		$hopefully_midnight_last_day = array_slice( $midnight_timestamps_except_first_day, - 1 );
		if (
			empty( $hopefully_midnight_last_day[0] )
			|| $midnight_last_day !== $hopefully_midnight_last_day[0]
		) {
			// instead of returning a full error, let's just display one day; it's better than nothing.
			$total_days_in_span = 1;
		}

		$max_allowed_consecutive_days = apply_filters( 'tk_event_weather_multi_day_max_allowed_consecutive_days', 10 );

		$max_allowed_consecutive_days = absint( $max_allowed_consecutive_days );

		// reset to default if filtering caused trouble
		if ( 1 > $max_allowed_consecutive_days ) {
			$max_allowed_consecutive_days = 10;
		}

		if ( $max_allowed_consecutive_days < $total_days_in_span ) {
			$total_days_in_span = $max_allowed_consecutive_days;
		}

		$day_index = 2;
		if ( 1 < $total_days_in_span ) {
			// if multi-day is enabled
			if ( ! empty( self::$span_template_data['multi_day_off'] ) ) {
				TKEventW_Functions::invalid_shortcode_message( 'Multi-Day Forecasting is disabled and Event End Time is not on the same day as Event Start Time. Please correct the Event End Time to either be a different time on the same day or to be blank (to display weather through the end of the same day)' );
				$output .= TKEventW_Functions::$shortcode_error_message;
			} else {
				foreach ( $midnight_timestamps_except_first_day as $midnight ) {
					$day_class = new TKEventW_Single_Day( $midnight, self::$span_end_time_timestamp, $day_index );
					$day_data  = $day_class::get_result();

					if ( ! empty( self::$debug_enabled ) ) {
						$output .= $day_data['api_data_debug'];
					}

					$output .= $day_data['template_output'];

					// if last day, output credit link(s)
					if ( $total_days_in_span === $day_index ) {
						if ( 'true' == $atts['plugin_credit_link_on'] ) {
							$output .= TKEventW_Functions::plugin_credit_link();
							$output .= PHP_EOL;
						}

						if ( empty( $atts['darksky_credit_link_off'] ) ) {
							$output .= TKEventW_Functions::darksky_credit_link();
							$output .= PHP_EOL;
						}
					}

					$day_index ++;
				}
			}
		}

		// one last check just to make sure
		if ( ! empty( TKEventW_Functions::$shortcode_error_message ) ) {
			return TKEventW_Functions::$shortcode_error_message;
		}

		return $output;
	}

	// do not comment out -- needed because of extending an abstract class
	public function addScript() {
		$plugin_options = TKEventW_Functions::plugin_options();

		if ( ! self::$addedAlready ) {
			self::$addedAlready = true;

			wp_enqueue_style( sanitize_html_class( TKEventW_Setup::shortcode_name_hyphenated() ) );

			if ( empty( $plugin_options['scroll_horizontal_off'] ) ) {
				wp_enqueue_style( sanitize_html_class( TKEventW_Setup::shortcode_name_hyphenated() . '-scroll-horizontal' ) );
			}

			//wp_register_script('my-script', plugins_url('js/my-script.js', __FILE__), array('jquery'), '1.0', true);
			//wp_print_scripts('my-script');
		}
	}

}
