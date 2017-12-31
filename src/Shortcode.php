<?php

namespace TKEventWeather;

// Option 3 from http://plugin.michael-simpson.com/?page_id=39

class Shortcode extends Shortcode_Script_Loader {

	/**
	 * Allows us to pass a custom context (set via shortcode argument) to all
	 * action and filter hooks.
	 *
	 * All calendar-specific/integration add-ons that run this shortcode should
	 * set this so the calendar-/integration-specific settings may choose to
	 * only affect shortcodes in those contexts, not site-wide while the add-on
	 * is active.
	 *
	 * @var string
	 */
	public static $custom_context = '';

	public static $dark_sky_api_key = '';
	public static $dark_sky_api_units = '';
	public static $dark_sky_api_language = '';
	public static $dark_sky_api_exclude = '';
	public static $dark_sky_api_uri_query_args = array();
	public static $dark_sky_api_transient_used = 'FALSE';
	public static $google_maps_api_key = '';
	public static $google_maps_api_transient_used = 'FALSE';
	public static $debug_enabled = false;
	public static $transients_enabled = true;
	public static $transients_expiration_hours = 0;
	public static $timezone_source = '';

	// ONLY set via Time::set_timezone_and_source_from_shortcode_args()
	public static $timezone = ''; // can be blank if timezone is set manually via shortcode argument
	public static $time_format_day = ''; // cannot be blank, possibly set via Time::set_timezone_from_api()
	public static $time_format_hours = '';
	public static $time_format_minutes = '';
	public static $span_start_time_timestamp = false;

	// Variables named with "span" are for the entire timespan, such as multiday.
	public static $span_first_hour_timestamp = false;
	public static $span_end_time_timestamp = false; // Start time's timestamp with minutes truncated. Should be equal to or less than $span_start_time_timestamp.
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

	public function handle_shortcode( $atts ) {
		// If multiple shortcodes being rendered, don't let one ruin it for the ones that come after it.
		Functions::$shortcode_error_message = '';

		$plugin_options = Functions::plugin_options();

		if ( empty( $plugin_options ) ) {
			Functions::invalid_shortcode_message( 'Please complete the initial setup', Plugin::customizer_link_to_edit_current_url(), 'Get Started!' );

			return Functions::$shortcode_error_message;
		} else {
			$api_key_option = Functions::array_get_value_by_key( $plugin_options, 'darksky_api_key' );

			$multi_day_limit_option = Functions::array_get_value_by_key( $plugin_options, 'multi_day_limit' );

			$multi_day_ignore_start_at_today_option = Functions::array_get_value_by_key( $plugin_options, 'multi_day_ignore_start_at_today' );

			$gmaps_api_key_option = Functions::array_get_value_by_key( $plugin_options, 'google_maps_api_key' );

			$text_before = Functions::array_get_value_by_key( $plugin_options, 'text_before' );

			$text_after = Functions::array_get_value_by_key( $plugin_options, 'text_after' );

			$display_template_option = Functions::array_get_value_by_key( $plugin_options, 'display_template' );

			$time_format_day_option = Functions::array_get_value_by_key( $plugin_options, 'time_format_day', 'M j' );

			$time_format_hours_option = Functions::array_get_value_by_key( $plugin_options, 'time_format_hours', 'ga' );

			$time_format_minutes_option = Functions::array_get_value_by_key( $plugin_options, 'time_format_minutes', 'g:i' );

			$cutoff_past_days_option   = Functions::array_get_value_by_key( $plugin_options, 'cutoff_past_days', 30 );
			$cutoff_future_days_option = Functions::array_get_value_by_key( $plugin_options, 'cutoff_future_days', 365 );

			$units_option = Functions::array_get_value_by_key( $plugin_options, 'darksky_units', 'auto' );

			$language_option = Functions::array_get_value_by_key( $plugin_options, 'darksky_language', '' );

			$transients_off_option              = Functions::array_get_value_by_key( $plugin_options, 'transients_off' );
			$transients_expiration_hours_option = Functions::array_get_value_by_key( $plugin_options, 'transients_expiration_hours', 12 );

			$timezone_source_option = Functions::array_get_value_by_key( $plugin_options, 'timezone_source', 'api' );

			$sunrise_sunset_off_option = Functions::array_get_value_by_key( $plugin_options, 'sunrise_sunset_off' );

			//$icons_option = Functions::array_get_value_by_key ( $plugin_options, 'icons' );

			$plugin_credit_link_on_option   = Functions::array_get_value_by_key( $plugin_options, 'plugin_credit_link_on' );
			$darksky_credit_link_off_option = Functions::array_get_value_by_key( $plugin_options, 'darksky_credit_link_off' );

			$debug_on_option = Functions::array_get_value_by_key( $plugin_options, 'debug_on' );

		}

		/*
		* Required:
		* api_key
		* lat/long
		* start time
		*/
		// Attributes
		$defaults = array(
			'custom_context'                  => '',
			'api_key'                         => $api_key_option,
			'multi_day_limit'                 => $multi_day_limit_option,
			'multi_day_ignore_start_at_today' => $multi_day_ignore_start_at_today_option,
			'gmaps_api_key'                   => $gmaps_api_key_option,
			'post_id'                         => get_the_ID(), // The ID of the current post
			// if lat_long argument is used, it will override the 2 individual latitude and longitude arguments if all 3 arguments exist.
			'lat_long'                        => '', // manually entered
			'lat_long_custom_field'           => '', // get custom field value
			// separate latitude
			'lat'                             => '', // manually entered
			'lat_custom_field'                => '', // get custom field value
			// separate longitude
			'long'                            => '', // manually entered
			'long_custom_field'               => '', // get custom field value
			// location/address -- to be geocoded by Google Maps
			'location'                        => '', // manually entered
			'location_custom_field'           => '', // get custom field value
			// time (ISO 8601 or Unix Timestamp)
			'start_time'                      => '', // manually entered
			'start_time_custom_field'         => '', // get custom field value
			'end_time'                        => '', // manually entered
			'end_time_custom_field'           => '', // get custom field value
			// time constraints in strtotime relative dates
			'cutoff_past'                     => $cutoff_past_days_option,
			'cutoff_future'                   => $cutoff_future_days_option,
			// API options -- see https://darksky.net/dev/docs/time-machine
			'exclude'                         => '', // comma-separated. Default/fallback is $exclude_default
			'transients_off'                  => $transients_off_option, // "true" is the only valid value
			'transients_expiration'           => $transients_expiration_hours_option,
			// Display Customizations
			'before'                          => $text_before,
			'after'                           => $text_after,
			'time_format_day'                 => $time_format_day_option,
			'time_format_hours'               => $time_format_hours_option,
			'time_format_minutes'             => $time_format_minutes_option, // default/fallback is $units_default
			'units'                           => '',
			'language'                        => '',
			'timezone'                        => '', // allow entering specific timezone -- see https://secure.php.net/manual/timezones.php -- does NOT support Manual Offset (e.g. UTC+10), even though WordPress provides these options.
			'timezone_source'                 => $timezone_source_option,
			'sunrise_sunset_off'              => $sunrise_sunset_off_option, // "true" is the only valid value
			'icons'                           => '',
			'plugin_credit_link_on'           => $plugin_credit_link_on_option, // "true" is the only valid value
			'darksky_credit_link_off'         => $darksky_credit_link_off_option, // anything !empty()
			// HTML
			'class'                           => '', // custom class
			'template'                        => $display_template_option,
			// Debug Mode
			'debug_on'                        => $debug_on_option, // anything !empty()
		);

		$atts = shortcode_atts( $defaults, $atts, 'tk-event-weather' );

		// Code

		// Custom Context (e.g. for add-ons)
		self::$custom_context = esc_attr( trim( $atts['custom_context'] ) );

		self::$span_template_data['custom_context'] = trim( $atts['custom_context'] ); // also passed to $context in the templates so they don't need an additional parameter in the hook

		// Initialize output
		$wrapper_class = sprintf( '%s__wrapper', \TK_EVENT_WEATHER_HYPHENS );
		if ( ! empty( self::$custom_context ) ) {
			$wrapper_class .= sprintf( ' context-%s', self::$custom_context );
		}

		$output = sprintf( '<div class="%s">', $wrapper_class );
		$output .= PHP_EOL;

		// Text Before Shortcode Output (not per day -- there's a filter for that)
		$before = sanitize_text_field( $atts['before'] );

		$before = apply_filters( \TK_EVENT_WEATHER_UNDERSCORES . '_text_before', $before, self::$custom_context );

		if ( ! empty( $before ) ) {
			$before = sprintf( '<h4 class="%s__before">%s</h4>', \TK_EVENT_WEATHER_HYPHENS, $before );
		}

		$output .= $before;
		$output .= PHP_EOL;

		// Template
		$display_template = Functions::remove_all_whitespace( strtolower( $atts['template'] ) );

		if ( ! array_key_exists( $display_template, Template::valid_display_templates() ) ) {
			$display_template = 'hourly_horizontal';
		}

		self::$span_template_data['template']            = $display_template;
		self::$span_template_data['template_class_name'] = Template::template_class_name( $display_template );

		$output .= sprintf( '<div class="%s__wrap_weather %s">', \TK_EVENT_WEATHER_HYPHENS, $display_template );
		$output .= PHP_EOL;

		// Enable Debug only if user is logged in and can use Customizer (an Admin).
		$capability = required_capability();
		if (
			current_user_can( $capability )
			&& true === (bool) $atts['debug_on']
		) {
			self::$debug_enabled = true;
		}


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

		self::$latitude_longitude = Functions::valid_lat_long( self::$latitude_longitude );

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
			self::$latitude_longitude = Functions::valid_lat_long( self::$latitude_longitude );
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

			self::$google_maps_api_key = Functions::sanitize_key_allow_uppercase( $atts['gmaps_api_key'] );

			// Fetch from transient or Google Maps Geocoding API
			self::$latitude_longitude = API_Google_Maps::get_lat_long();

			$output .= API_Google_Maps::get_debug_output();
		}

		if ( empty( self::$latitude_longitude ) ) {
			Functions::invalid_shortcode_message( 'Please enter valid Latitude and Longitude coordinates (or a Location that Google Maps can get coordinates for)' );

			return Functions::$shortcode_error_message;
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
			if ( true === Time::valid_iso_8601_date_time( $start_time, 'bool' ) ) {
				$start_time           = Time::valid_iso_8601_date_time( $start_time );
				$start_time_iso_8601  = $start_time;
				$start_time_timestamp = date( 'U', strtotime( $start_time ) );
			} // check timestamp
			elseif ( true === Time::valid_timestamp( $start_time, 'bool' ) ) {
				$start_time           = Time::valid_timestamp( $start_time );
				$start_time_iso_8601  = date( \DateTime::ATOM, $start_time ); // \DateTime::ATOM is same as 'c'
				$start_time_timestamp = $start_time;
			} // strtotime() or invalid (and therefore clear out)
			else {
				$start_time = strtotime( $start_time );

				if ( false === $start_time ) {
					$start_time           = '';
					$start_time_timestamp = '';
				} else {
					$start_time_timestamp = $start_time;
					$start_time_iso_8601  = date( \DateTime::ATOM, $start_time ); // \DateTime::ATOM is same as 'c'
				}
			}
		}

		// avoid error of variable not being set
		if ( ! isset( $start_time_timestamp ) ) {
			$start_time_timestamp = '';
		}

		$start_time_timestamp = Time::valid_timestamp( $start_time_timestamp );


		if ( empty( $start_time_timestamp ) ) {
			Functions::invalid_shortcode_message( 'Please enter a valid Start Time format' );

			return Functions::$shortcode_error_message;
		}

		self::$span_start_time_timestamp = $start_time_timestamp;
		self::$span_first_hour_timestamp = Time::timestamp_truncate_minutes( $start_time_timestamp );

		self::$span_template_data['span_first_hour_timestamp'] = Shortcode::$span_first_hour_timestamp;


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

		$min_timestamp = Time::valid_timestamp( $min_timestamp );

		if ( ! empty( $min_timestamp ) && '' != Shortcode::$span_first_hour_timestamp ) {
			if ( $min_timestamp > Shortcode::$span_first_hour_timestamp ) {
				Functions::invalid_shortcode_message( 'Event Start Time needs to be more recent than the Past Cutoff Time' );

				return Functions::$shortcode_error_message;
			}
		}

		// max 60 years in the past, per API docs
		if ( strtotime( '-60 years' ) > Shortcode::$span_first_hour_timestamp ) {
			Functions::invalid_shortcode_message( 'Event Start Time needs to be more recent than 60 years in the past, per Dark Sky API docs,' );

			return Functions::$shortcode_error_message;
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
			if ( true === Time::valid_iso_8601_date_time( $end_time, 'bool' ) ) {
				$end_time           = Time::valid_iso_8601_date_time( $end_time );
				$end_time_iso_8601  = $end_time;
				$end_time_timestamp = Time::valid_timestamp( date( 'U', strtotime( $end_time ) ) ); // date() returns a string
			} // check timestamp
			elseif ( true === Time::valid_timestamp( $end_time, 'bool' ) ) {
				$end_time           = Time::valid_timestamp( $end_time );
				$end_time_iso_8601  = date( \DateTime::ATOM, $end_time ); // \DateTime::ATOM is same as 'c'
				$end_time_timestamp = $end_time;
			} // strtotime() or invalid (and therefore clear out)
			else {
				$end_time = strtotime( $end_time, $start_time_timestamp ); // strtotime() being relative to Start Time

				if ( false === $end_time ) {
					$end_time           = '';
					$end_time_timestamp = '';
				} else {
					$end_time_timestamp = $end_time;
					$end_time_iso_8601  = date( \DateTime::ATOM, $end_time ); // \DateTime::ATOM is same as 'c'
				}
			}
		}

		// avoid error of variable not being set
		if ( ! isset( $end_time_timestamp ) ) {
			$end_time_timestamp = '';
		}

		$end_time_timestamp = Time::valid_timestamp( $end_time_timestamp );

		if ( '' == $end_time_timestamp ) {
			$end_time_timestamp = Shortcode::$span_first_hour_timestamp + DAY_IN_SECONDS; // just padding it on through the next day
			$total_days_in_span = 1; // and then manually setting Total Days to 1 because of fudging the End Time (because we do not yet have the timezone)
		}

		self::$span_end_time_timestamp = $end_time_timestamp;

		// if Event Start and End times are the same
		//if ( Shortcode::$span_first_hour_timestamp == $end_time_timestamp ) {
		// this is allowed as of version 1.4
		//}

		// if Event End time is before Start time
		if ( Shortcode::$span_first_hour_timestamp > $end_time_timestamp ) {
			Functions::invalid_shortcode_message( 'Event Start Time must be earlier than Event End Time' );

			return Functions::$shortcode_error_message;
		}


		//
		// cutoff_future
		// strtotime date relative to $end_time_timestamp
		if ( ! empty( $atts['cutoff_future'] ) ) {
			$cutoff_future = $atts['cutoff_future'];
		} else {
			$cutoff_future = '';
		}

		// TODO: date_default_timezone_set() for any strtotime() -- likely not an issue here because of so far in the future -- should move to a method anyway and search for all other strtotime() usage
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

		$max_timestamp = Time::valid_timestamp( $max_timestamp );

		if ( ! empty( $max_timestamp ) && '' != $end_time_timestamp ) {
			if ( $end_time_timestamp > $max_timestamp ) {
				Functions::invalid_shortcode_message( 'Event End Time needs to be more recent than Future Cutoff Time' );

				return Functions::$shortcode_error_message;
			}
		}

		// max 10 years future, per API docs
		if ( $end_time_timestamp > strtotime( '+10 years' ) ) {
			Functions::invalid_shortcode_message( 'Event End Time needs to be less than 10 years in the future, per Dark Sky API docs,' );

			return Functions::$shortcode_error_message;
		}


		/**
		 * Shortcode::$span_first_hour_timestamp is equal to or greater than $min_timestamp and
		 * $end_time_timestamp is less than or equal to $max_timestamp
		 * or $min_timestamp and/or $max_timestamp were set to zero (i.e. no limits)
		 * so continue...
		 */


		// time_format_day
		$time_format_day = sanitize_text_field( $atts['time_format_day'] );

		self::$time_format_day = $time_format_day;

		// time_format_hours
		$time_format_hours = sanitize_text_field( $atts['time_format_hours'] );

		self::$time_format_hours = $time_format_hours;

		// time_format_minutes
		$time_format_minutes = sanitize_text_field( $atts['time_format_minutes'] );

		self::$time_format_minutes = $time_format_minutes;

		// Units
		$units = Functions::remove_all_whitespace( strtolower( $atts['units'] ) );

		if ( ! array_key_exists( $units, API_Dark_Sky::valid_units() ) ) {
			$units = $units_option;
		}

		self::$dark_sky_api_units = $units;

		// Language
		$language = Functions::remove_all_whitespace( strtolower( $atts['language'] ) );

		if ( ! array_key_exists( $language, API_Dark_Sky::valid_languages() ) ) {
			$language = $language_option;
		}

		self::$dark_sky_api_language = $language;

		// exclude
		$exclude = '';

		// shortcode argument's value
		$exclude_arg = Functions::remove_all_whitespace( strtolower( $atts['exclude'] ) );

		if ( ! empty( $exclude ) ) {
			$exclude_arg_array = explode( ',', $exclude_arg );

			if ( is_array( $exclude_arg_array ) ) {
				sort( $exclude_arg_array );
				$possible_excludes = API_Dark_Sky::valid_excludes();

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
			|| ! in_array( $icons, Functions::valid_icon_type() )
		) {
			$icons = 'climacons_font';
		}

		// As of v1.5.4, we always enqueue the Climacons Icon Font CSS file so nothing to do here.
		// Maybe could add do_action() for if 'climacons_font' !== $icons

		self::$span_template_data['icons'] = $icons;

		if ( 'true' == $atts['plugin_credit_link_on'] ) {
			self::$span_template_data['plugin_credit_link_enabled'] = true;
		} else {
			self::$span_template_data['plugin_credit_link_enabled'] = false;
		}

		if ( 'true' == $atts['darksky_credit_link_off'] ) {
			self::$span_template_data['darksky_credit_link_enabled'] = false;
		} else {
			self::$span_template_data['darksky_credit_link_enabled'] = true;
		}

		self::$span_template_data['class'] = $atts['class'];


		Time::set_timezone_and_source_from_shortcode_args( $atts['timezone'], $atts['timezone_source'] );

		if ( ! empty( Functions::$shortcode_error_message ) ) {
			return Functions::$shortcode_error_message;
		}

		// Multi-Day Start at Today
		if ( 'true' == $atts['multi_day_ignore_start_at_today'] ) {
			self::$span_template_data['multi_day_start_at_today'] = false;
		} else {
			self::$span_template_data['multi_day_start_at_today'] = true;
		}

		/**
		 * Run the first day through Dark Sky API.
		 *
		 * Must be done before setting Timezone because the timezone might
		 * be getting set via the API, depending on the settings.
		 */
		$first_day_class = new Single_Day( self::$span_start_time_timestamp, self::$span_end_time_timestamp, 1 );

		// Set the Timezone ASAP (after first day's API call), since it's needed to determine everything else
		if (
			empty( self::$timezone )
			&& ! empty( Single_Day::$api_data->timezone )
		) {
			Time::set_timezone_from_api( Single_Day::$api_data->timezone );
		}

		if ( empty( self::$timezone ) ) {
			Functions::invalid_shortcode_message( 'Timezone was unable to be determined from the API and is required to continue. You might want to enable Debug Mode and investigate if there was a problem with this specific API response or some other error' );

			return Functions::$shortcode_error_message;
		}

		$midnight_first_day = Time::get_a_days_min_max_timestamp( self::$span_start_time_timestamp, self::$timezone );
		$midnight_last_day  = Time::get_a_days_min_max_timestamp( self::$span_end_time_timestamp, self::$timezone );

		// Multi-Day Limit
		$multi_day_limit = absint( $atts['multi_day_limit'] );

		if ( empty( $multi_day_limit ) ) {
			$multi_day_limit = 10;
		}

		self::$span_template_data['multi_day_limit'] = $multi_day_limit;

		if ( ! empty( self::$debug_enabled ) ) {
			$output .= $first_day_class::$api_data_debug . PHP_EOL;
		}

		if ( ! empty( Functions::$shortcode_error_message ) ) {
			return Functions::$shortcode_error_message;
		}

		// Only calculate Total Days in Span if not already set because it might have been forced to 1 (if no End Time was set)
		if ( empty( $total_days_in_span ) ) {
			$total_days_in_span = Time::count_start_end_cal_days_span( self::$span_start_time_timestamp, self::$span_end_time_timestamp, self::$timezone );
		}

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

		// if entire span is in the past, set back to FALSE
		$today_midnight = Time::get_a_days_min_max_timestamp( time(), self::$timezone );

		if (
			true === self::$span_template_data['multi_day_start_at_today']
			&& $midnight_last_day < $today_midnight
		) {
			self::$span_template_data['multi_day_start_at_today'] = false;
		}

		// output first day if multi-day is off or if the span does not include Today
		if (
			1 == $total_days_in_span
			|| false === self::$span_template_data['multi_day_start_at_today']
			|| $today_midnight <= $midnight_first_day
		) {
			$output .= $first_day_class::$template_output;
		}

		// If total days are greater than multi-day limit, reduce total days. Prior to v1.5.4, this used to throw a shortcode error. This way is more user-friendly, especially for dynamic sources like calendar events.
		if ( $multi_day_limit < $total_days_in_span ) {
			$total_days_in_span = $multi_day_limit;

			if ( 1 < $total_days_in_span ) { // because first day was removed and may have been the only day
				$midnights = array_slice( $midnights, 0, $total_days_in_span - 1 );
			}
		}

		$day_index = 2;
		if ( 1 < $total_days_in_span ) {
			foreach ( $midnight_timestamps_except_first_day as $midnight ) {
				if (
					true === self::$span_template_data['multi_day_start_at_today']
					&& $today_midnight > $midnight
				) {
					continue;
				}

				$day_class = new Single_Day( $midnight, self::$span_end_time_timestamp, $day_index );

				if ( ! empty( self::$debug_enabled ) ) {
					$output .= $day_class::$api_data_debug;
				}

				$output .= $day_class::$template_output;

				$day_index ++;
			}
		}

		// one last check just to make sure
		if ( ! empty( Functions::$shortcode_error_message ) ) {
			return Functions::$shortcode_error_message;
		}

		$output .= '</div>'; // .tk-event-weather__wrap_weather
		$output .= PHP_EOL;

		// These are outside .tk-event-weather__wrap_weather to avoid getting caught up in flexbox (if applicable, like with vertical columns) and to ensure it outputs only once at the end instead of within each day.
		$output .= Functions::get_credit_links(); // empty if should not display either
		$output .= PHP_EOL;

		// Text After Shortcode Output (not per day -- there's a filter for that)
		$after = sanitize_text_field( $atts['after'] );

		$after_filtered = apply_filters( \TK_EVENT_WEATHER_UNDERSCORES . '_text_after', $after, self::$custom_context );

		if ( ! empty( $after ) ) {
			$after = sprintf( '<p class="%s__after">%s</p>', \TK_EVENT_WEATHER_HYPHENS, $after );
		}

		$output .= $after;
		$output .= PHP_EOL;

		$output .= '</div>'; // .tk-event-weather__wrapper
		$output .= PHP_EOL;

		return $output;
	}

	// This method must be declared because of extending an abstract class.
	public function add_script() {
	}

}