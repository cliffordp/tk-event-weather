<?php

class TKEventW_Single_Day {
	// all variables and methods should be 'static'

	/**
	 *
	 * api-result-examples/dark_sky.txt
	 *
	 */

	// Transients
	// @link https://codex.wordpress.org/Transients_API
	// @link https://codex.wordpress.org/Easier_Expression_of_Time_Constants

	// build transient
	// Make API call if nothing from Transients
	// e.g. https://api.darksky.net/forecast/APIKEY/LATITUDE,LONGITUDE,TIME
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
	* https://api.darksky.net/forecast/_______API_KEY_______/36.281445,-75.794662,2016-02-01T16:30:00-05:00?units=auto&exclude=alerts,daily,flags,hourly,minutely
	* API call in Unix Timestamp format (same result)
	* https://api.darksky.net/forecast/_______API_KEY_______/36.281445,-75.794662,1454362200?units=auto&exclude=alerts,daily,flags,hourly,minutely
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




	*
	*
	*/


	/*
	Example var_dump($request) when bad data, like https://api.darksky.net/forecast/___API_KEY___/0.000000,0.000000,1466199900?exclude=minutely
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


	public static $result = array(
		'start_time_timestamp' => false,
		'end_time_timestamp'   => false,
		'day_number_of_span'   => false,
		'api_data'             => false,
		'api_data_debug'       => false,
		'template_output'      => false,
	);

	/**
	 * TKEventW_Single_Day constructor.
	 *
	 * @param int $start_time_timestamp
	 * @param int $end_time_timestamp
	 * @param int $day_number_of_span
	 */
	public function __construct( $start_time_timestamp, $end_time_timestamp, $day_number_of_span ) {
		self::$result['start_time_timestamp'] = TKEventW_Time::valid_timestamp( $start_time_timestamp );
		self::$result['end_time_timestamp']   = TKEventW_Time::valid_timestamp( $end_time_timestamp );

		self::$result['day_number_of_span'] = (int) $day_number_of_span;

		self::$result['api_data'] = self::build_api_data();

		self::$result['template_output'] = self::build_template_output();
	}

	public static function get_result() {
		return self::$result;
	}

	private static function build_api_data() {
		$api_class = new TKEventW_API_Dark_Sky( self::$result['start_time_timestamp'], self::$result['end_time_timestamp'] );

		self::$result['api_data_debug'] = $api_class::get_debug_output();

		return $api_class::get_response_data();
	}

	private static function build_template_output() {
		if ( true === TKEventW_Functions::$shortcode_error ) {
			return false;
		}

		$template_data = TKEventW_Shortcode::$span_template_data;

		// https://developer.wordpress.org/reference/functions/wp_list_pluck/
		$hourly_timestamps = wp_list_pluck( self::$result['api_data']->hourly->data, 'time' );

		$houly_hours_keys = array_keys( $hourly_timestamps );

		// First hour to start pulling for Hourly Data
		foreach ( $hourly_timestamps as $key => $value ) {
			if ( intval( $value ) == intval( self::$result['start_time_timestamp'] ) ) {
				$weather_hourly_start_key = $key; // so we know where to start when pulling hourly weather
				break;
			}
		}

		// Protect against odd hourly weather scenarios like location only having data from midnight to 8am and event start time is 9am
		if ( ! isset( $weather_hourly_start_key ) ) { // need to allow for zero due to numeric array
			$invalid_shortcode_message = sprintf( 'Event Start Time error. API did not return enough hourly data for %d to %d. Please troubleshoot', self::$result['start_time_timestamp'], self::$result['end_time_timestamp'] );
			TKEventW_Functions::invalid_shortcode_message( $invalid_shortcode_message );

			return TKEventW_Functions::$shortcode_error_message;
		}

		// End Time Weather
		foreach ( $hourly_timestamps as $key => $value ) {
			if ( intval( $value ) >= intval( self::$result['end_time_timestamp'] ) ) {
				$weather_hourly_end_key = $key;
				break;
			}
		}

		// if none, just get last hour of the day (e.g. if Event End Time is next day 2am, just get 11pm same day as Event Start Time (not perfect but may be better than 2nd API call)
		if ( ! isset( $weather_hourly_end_key ) ) { // need to allow for zero due to numeric array
			$weather_hourly_end_key = end( $houly_hours_keys );
		}

		if ( ! isset( $weather_hourly_end_key ) ) { // need to allow for zero due to numeric array
			TKEventW_Functions::invalid_shortcode_message( 'Event End Time is out of range. Please troubleshoot' );

			return TKEventW_Functions::$shortcode_error_message;
		}

		$template_data['weather_last_hour_timestamp'] = TKEventW_Time::get_last_hour_hour_of_forecast( self::$result['end_time_timestamp'] );


		$template_data['sunrise_sunset']['sunrise_timestamp']      = false;
		$template_data['sunrise_sunset']['sunrise_hour_timestamp'] = false;
		$template_data['sunrise_sunset']['sunset_timestamp']       = false;
		$template_data['sunrise_sunset']['sunset_hour_timestamp']  = false;
		$template_data['sunrise_sunset']['sunrise_to_be_inserted'] = false;
		$template_data['sunrise_sunset']['sunset_to_be_inserted']  = false;

		if ( ! empty( $template_data['sunrise_sunset']['on'] ) ) {
			// might not be a sunrise this day
			if ( isset( self::$result['api_data']->daily->data[0]->sunriseTime ) ) {
				$template_data['sunrise_sunset']['sunrise_timestamp']      = TKEventW_Time::valid_timestamp( self::$result['api_data']->daily->data[0]->sunriseTime );
				$template_data['sunrise_sunset']['sunrise_hour_timestamp'] = TKEventW_Time::timestamp_truncate_minutes( $template_data['sunrise_sunset']['sunrise_timestamp'] );
				if ( $template_data['sunrise_sunset']['sunrise_timestamp'] >= TKEventW_Shortcode::$span_first_hour_timestamp ) {
					$template_data['sunrise_sunset']['sunrise_to_be_inserted'] = true;
				}
			}

			// might not be a sunset this day
			if ( isset( self::$result['api_data']->daily->data[0]->sunsetTime ) ) {
				$template_data['sunrise_sunset']['sunset_timestamp']      = TKEventW_Time::valid_timestamp( self::$result['api_data']->daily->data[0]->sunsetTime );
				$template_data['sunrise_sunset']['sunset_hour_timestamp'] = TKEventW_Time::timestamp_truncate_minutes( $template_data['sunrise_sunset']['sunset_timestamp'] );
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
			foreach ( self::$result['api_data']->hourly->data as $key => $value ) {

				if ( $key > $weather_hourly_end_key ) {
					break;
				}

				if ( $index == $key ) {
					$weather_hourly[ $index ] = $value;
					$index ++;
				}
			}
		}

		//$weather_hourly = TkEventW__Functions::sort_multidim_array_by_sub_key( $weather_hourly, 'time' );

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


		$temperature_units = TKEventW_Functions::temperature_units( self::$result['api_data']->flags->units );

		$template_data['temperature_units'] = $temperature_units;

		$wind_speed_units = TKEventW_Functions::wind_speed_units( self::$result['api_data']->flags->units );

		$template_data['wind_speed_units'] = $wind_speed_units;

		// Total Days in Span won't be set at time of first day's run so don't try to include it
		$class = sprintf( 'tk-event-weather__wrapper tk-event-weather__span-%d-to-%d tk-event-weather__day-index-%d %s',
			TKEventW_Shortcode::$span_start_time_timestamp,
			TKEventW_Shortcode::$span_end_time_timestamp,
			self::$result['day_number_of_span'],
			sanitize_html_class( $template_data['class'] )
		);


		$output = '';

		if ( ! empty( TKEventW_Shortcode::$debug_enabled ) ) {
			$output .= sprintf( '<!--%1$s%2$s -- Template Data converted to JSON%1$s%3$s%1$s-->%1$s',
				PHP_EOL,
				TKEventW_Setup::plugin_display_name(),
				json_encode( $template_data, JSON_PRETTY_PRINT ) // JSON_PRETTY_PRINT option requires PHP 5.4
			);

		}

		/**
		 * Start Building Output!!!
		 * All data should be set by now!!!
		 */

		// cannot do <style> tags inside template because it will break any open div (e.g. wrapper div)
		$output .= sprintf( '<div class="%s">', $class );
		$output .= PHP_EOL;

		$output .= $template_data['before'] . PHP_EOL;

		$output .= sprintf(
			'<div class="tk-event-weather-template %s">',
			TKEventW_Shortcode::$span_template_data['template_class_name']
		);
		$output .= PHP_EOL;

		// https://github.com/GaryJones/Gamajo-Template-Loader/issues/13#issuecomment-196046201
		ob_start(); // TODO still needed?
		TKEventW_Template::load_template( $template_data['template'], $template_data );
		$output .= ob_get_clean();

		if ( ! empty( $template_data['plugin_credit_link_enabled'] ) ) {
			$output .= TKEventW_Functions::plugin_credit_link();
		}

		if ( ! empty( $template_data['darksky_credit_link_enabled'] ) ) {
			$output .= TKEventW_Functions::darksky_credit_link();
		}

		$output .= '</div>'; // .tk-event-weather-template
		$output .= PHP_EOL;

		$output .= $template_data['after'] . PHP_EOL;

		$output .= '</div>'; // .tk-event-weather--wrapper
		$output .= PHP_EOL;

		return $output;
	}


}