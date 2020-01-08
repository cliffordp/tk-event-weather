<?php

namespace TKEventWeather;

class Single_Day {
	// all variables and methods should be 'static'

	public static $start_time_timestamp = false;

	public static $end_time_timestamp = false;

	public static $day_number_of_span = false;

	public static $api_data = false;

	public static $api_data_debug = false;

	public static $template_output = false;


	/**
	 * Single_Day constructor.
	 *
	 * @param int $start_time_timestamp
	 * @param int $end_time_timestamp
	 * @param int $day_number_of_span
	 */
	public function __construct( $start_time_timestamp, $end_time_timestamp, $day_number_of_span ) {
		self::$start_time_timestamp = Time::valid_timestamp( $start_time_timestamp );
		self::$end_time_timestamp   = Time::valid_timestamp( $end_time_timestamp );

		self::$day_number_of_span = (int) $day_number_of_span;

		self::$api_data = self::build_api_data();

		self::$template_output = self::build_template_output();
	}

	private static function build_api_data() {
		$api_class = new API_Dark_Sky( self::$start_time_timestamp, self::$end_time_timestamp );

		self::$api_data_debug = $api_class::get_debug_output();

		return $api_class::get_response_data();
	}

	/**
	 * Get the template output HTML.
	 *
	 * @since 1.6.2 Set empty values for missing properties to avoid undefined property errors in template files.
	 *
	 * @return bool|string
	 */
	private static function build_template_output() {
		if ( ! empty( Functions::$shortcode_error_message ) ) {
			return false;
		}

		$template_data = Shortcode::$span_template_data;

		/**
		 * Pass along useful information from __construct(), but do NOT include
		 * self::$api_data, self::$api_data_debug, or self::$template_output
		 * because then we will cause duplicate output (debug info output
		 * elsewhere) and/or circular references (JSON encoding its own output).
		 */
		$template_data['start_time_timestamp'] = self::$start_time_timestamp;
		$template_data['end_time_timestamp']   = self::$end_time_timestamp;
		$template_data['day_number_of_span']   = self::$day_number_of_span;
		$template_data['api_data']             = self::$api_data;


		// https://developer.wordpress.org/reference/functions/wp_list_pluck/
		$hourly_timestamps = wp_list_pluck( self::$api_data->hourly->data, 'time' );

		$houly_hours_keys = array_keys( $hourly_timestamps );

		// First hour to start pulling for Hourly Data
		foreach ( $hourly_timestamps as $key => $value ) {
			if ( intval( $value ) === intval( Time::timestamp_truncate_minutes( self::$start_time_timestamp ) ) ) {
				$weather_hourly_start_key = $key; // so we know where to start when pulling hourly weather
				break;
			}
		}

		// Protect against odd hourly weather scenarios like location only having data from midnight to 8am and event start time is 9am
		if ( ! isset( $weather_hourly_start_key ) ) { // need to allow for zero due to numeric array
			$invalid_shortcode_message = sprintf( 'Event Start Time error. API did not return enough hourly data for %d to %d. Please troubleshoot', self::$start_time_timestamp, self::$end_time_timestamp );
			Functions::invalid_shortcode_message( $invalid_shortcode_message );

			return Functions::$shortcode_error_message;
		}

		// End Time Weather
		foreach ( $hourly_timestamps as $key => $value ) {
			if ( intval( $value ) >= intval( self::$end_time_timestamp ) ) {
				$weather_hourly_end_key = $key;
				break;
			}
		}

		// if none, just get last hour of the day (e.g. if Event End Time is next day 2am, just get 11pm same day as Event Start Time (not perfect but may be better than 2nd API call)
		if ( ! isset( $weather_hourly_end_key ) ) { // need to allow for zero due to numeric array
			$weather_hourly_end_key = end( $houly_hours_keys );
		}

		if ( ! isset( $weather_hourly_end_key ) ) { // need to allow for zero due to numeric array
			Functions::invalid_shortcode_message( 'Event End Time is out of range. Please troubleshoot' );

			return Functions::$shortcode_error_message;
		}

		$template_data['weather_last_hour_timestamp'] = Time::get_last_hour_of_forecast( self::$end_time_timestamp );


		$template_data['sunrise_sunset']['sunrise_timestamp']      = false;
		$template_data['sunrise_sunset']['sunrise_hour_timestamp'] = false;
		$template_data['sunrise_sunset']['sunset_timestamp']       = false;
		$template_data['sunrise_sunset']['sunset_hour_timestamp']  = false;
		$template_data['sunrise_sunset']['sunrise_to_be_inserted'] = false;
		$template_data['sunrise_sunset']['sunset_to_be_inserted']  = false;

		if ( ! empty( $template_data['sunrise_sunset']['on'] ) ) {
			// might not be a sunrise this day
			if ( isset( self::$api_data->daily->data[0]->sunriseTime ) ) {
				$template_data['sunrise_sunset']['sunrise_timestamp']      = Time::valid_timestamp( self::$api_data->daily->data[0]->sunriseTime );
				$template_data['sunrise_sunset']['sunrise_hour_timestamp'] = Time::timestamp_truncate_minutes( $template_data['sunrise_sunset']['sunrise_timestamp'] );
				if ( $template_data['sunrise_sunset']['sunrise_timestamp'] >= Shortcode::$span_first_hour_timestamp ) {
					$template_data['sunrise_sunset']['sunrise_to_be_inserted'] = true;
				}
			}

			// might not be a sunset this day
			if ( isset( self::$api_data->daily->data[0]->sunsetTime ) ) {
				$template_data['sunrise_sunset']['sunset_timestamp']      = Time::valid_timestamp( self::$api_data->daily->data[0]->sunsetTime );
				$template_data['sunrise_sunset']['sunset_hour_timestamp'] = Time::timestamp_truncate_minutes( $template_data['sunrise_sunset']['sunset_timestamp'] );
				if ( $template_data['weather_last_hour_timestamp'] >= $template_data['sunrise_sunset']['sunset_timestamp'] ) {
					$template_data['sunrise_sunset']['sunset_to_be_inserted'] = true;
				}
			}
		}


		// Hourly Weather
		// any internal pointers to reset first?

		$weather_hourly = array();

		$index = $weather_hourly_start_key;

		if ( is_integer( $index ) ) {
			foreach ( self::$api_data->hourly->data as $key => $value ) {

				if ( $key > $weather_hourly_end_key ) {
					break;
				}

				if ( $index == $key ) {
					$weather_hourly[ $index ] = $value;
					$index ++;
				}
			}
		}

		//$weather_hourly = Functions::sort_multidim_array_by_sub_key( $weather_hourly, 'time' );

		// Avoid undefined property errors in template files
		// TODO: could loop through all possible properties instead of just these most likely offenders
		foreach ( $weather_hourly as &$item ) {
			if ( ! isset( $item->icon ) ) {
				$item->icon = '';
			}
			if ( ! isset( $item->summary ) ) {
				$item->summary = '';
			}
			if ( ! isset( $item->temperature ) ) {
				$item->temperature = null;
			}
			if ( ! isset( $item->windBearing ) ) {
				$item->windBearing = null;
			}
			if ( ! isset( $item->windSpeed ) ) {
				$item->windSpeed = null;
			}
		}

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


		$temperature_units = Functions::temperature_units( self::$api_data->flags->units );

		$template_data['temperature_units'] = $temperature_units;

		$wind_speed_units = Functions::wind_speed_units( self::$api_data->flags->units );

		$template_data['wind_speed_units'] = $wind_speed_units;

		/**
		 * Start Building Output!!!
		 * All data should be set by now!!!
		 */

		// https://github.com/GaryJones/Gamajo-Template-Loader/issues/13#issuecomment-196046201
		// it is because each template does echo() at the end
		ob_start();

		if ( ! empty( Shortcode::$debug_enabled ) ) {
			printf(
				'<!--%1$s%2$s -- Template Data converted to JSON%1$s%3$s%1$s-->%1$s',
				PHP_EOL,
				Setup::plugin_display_name(),
				json_encode( $template_data, JSON_PRETTY_PRINT ) // JSON_PRETTY_PRINT option requires PHP 5.4
			);
		}

		Template::load_template( 'single_day_before', $template_data );
		Template::load_template( $template_data['template'], $template_data );
		Template::load_template( 'single_day_after', $template_data );

		return (string) ob_get_clean();
	}


}