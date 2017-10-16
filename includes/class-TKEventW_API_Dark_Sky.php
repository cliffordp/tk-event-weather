<?php

class TKEventW_API_Dark_Sky {
	// all variables and methods should be 'static'

	public static $start_time_timestamp = false;
	public static $end_time_timestamp = false;

	public function __construct( $start_time_timestamp, $end_time_timestamp ) {
		self::$start_time_timestamp = $start_time_timestamp;
		self::$end_time_timestamp   = $end_time_timestamp;
	}

	/**
	 * @link https://darksky.net/dev/docs#time-machine-request
	 *
	 * @return string
	 */
	private static function request_uri() {
		$api_key = urlencode( sanitize_key( TKEventW_Shortcode::$dark_sky_api_key ) );

		if ( empty( $api_key ) ) {
			TKEventW_Functions::invalid_shortcode_message( 'Please enter your Dark Sky API Key' );

			return '';
		}

		$uri_base = sprintf(
			'https://api.darksky.net/forecast/%s/%s,%s',
			$api_key,
			TKEventW_Shortcode::$latitude_longitude,
			self::$start_time_timestamp
		);

		$uri_query_args = array();

		$units = self::get_request_uri_units();

		if ( ! empty( $units ) ) {
			$uri_query_args['units'] = urlencode( $units );
		}

		$exclude = TKEventW_Shortcode::$dark_sky_api_exclude;

		if ( ! empty( $exclude ) ) {
			$uri_query_args['exclude'] = urlencode( $exclude );
		}

		/**
		 * Filter to allow overriding Units and/or Exclude; or to add Language.
		 *
		 * @link https://darksky.net/dev/docs#time-machine-request-parameters
		 */
		$uri_query_args = apply_filters( 'tk_event_weather_dark_sky_request_uri_query_args', $uri_query_args );

		$uri = add_query_arg( $uri_query_args, $uri_base );

		return $uri;
	}

	private static function get_request_uri_units() {
		return TKEventW_Shortcode::$dark_sky_api_units;
	}

	/**
	 * @param $transient
	 *
	 * @return bool
	 */
	private static function valid_transient( $transient ) {
		if ( ! empty( $transient ) ) {
			if (
				// WordPress error
				is_wp_error( $transient )

				// expected result of json_decode() of API response
				|| ! is_object( $transient )

				/**
				 * if API doesn't technically error out but does return an API error message
				 * examples:
				 * {"code":400,"error":"An invalid time was specified."}
				 * {"code":400,"error":"An invalid units parameter was provided."}
				 * {"code":400,"error":"The given location (or time) is invalid."}
				 */
				|| ! empty( $transient->error )
			) {
				$transient = '';
				// will be deleted if it exists, regardless of expiration date
				delete_transient( self::get_transient_name() );
			}
		}

		if ( ! empty( $transient ) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function get_response_data() {
		// Get from transient if exists and valid
		$transient_data = self::get_transient_value();

		if ( true === self::valid_transient( $transient_data ) ) {
			TKEventW_Shortcode::$dark_sky_api_transient_used = 'TRUE';

			return $transient_data;
		}

		// Get from API
		$response = wp_safe_remote_get( esc_url_raw( self::request_uri() ) );

		if ( is_wp_error( $response ) ) {
			return TKEventW_Functions::invalid_shortcode_message( 'Dark Sky API request sent but resulted in a WordPress Error. Please troubleshoot' );
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return TKEventW_Functions::invalid_shortcode_message( 'Dark Sky API request sent but nothing received. Please troubleshoot' );
		}

		$data = json_decode( $body );

		if ( empty( $data ) ) {
			return TKEventW_Functions::invalid_shortcode_message( 'Dark Sky API response received but some sort of data inconsistency. Please troubleshoot' );
		}

		if ( ! empty( $data->error ) ) {
			return TKEventW_Functions::invalid_shortcode_message( 'Dark Sky API responded with an error: ' . $data->error . ' - Please troubleshoot' );
		}

		if ( empty( $data->hourly->data ) ) {
			return TKEventW_Functions::invalid_shortcode_message( 'Dark Sky API responded but without hourly data. Please troubleshoot' );
		}

		// Set transient if enabled and API call resulted in usable data.
		if (
			! empty( TKEventW_Shortcode::$transients_enabled )
			&& ! empty( TKEventW_Shortcode::$transients_expiration_hours )
		) {
			set_transient( self::get_transient_name(), $data, TKEventW_Shortcode::$transients_expiration_hours * HOUR_IN_SECONDS ); // e.g. 12 hours
		}

		TKEventW_Time::set_timezone_from_api( $data->timezone ); // TODO: minor optimization: only run the first day of a multi-day request

		return $data;
	}

	public static function get_debug_output() {
		$output = '';

		if ( empty( TKEventW_Shortcode::$debug_enabled ) ) {
			return $output;
		}

		$data = self::get_response_data();

		if ( empty( $data ) ) {
			return $output;
		}

		/* Example Debug Output:
		<!--
		TK Event Weather -- Dark Sky API -- Request URI
		https://api.darksky.net/forecast/___API_KEY___/38.897676,-77.036530,1464604200?units=auto&exclude=minutely,alerts
		-->
		*/
		$output .= sprintf(
			'<!--%1$s%2$s -- Dark Sky API -- Request URI%1$s%3$s%1$s-->%1$s',
			PHP_EOL,
			TKEventW_Setup::plugin_display_name(),
			self::request_uri()
		);

		/**
		 * JSON Data
		 *
		 * api-result-examples/google_maps.txt
		 */
		$output .= sprintf( '<!--%1$s%2$s -- Dark Sky API -- Obtained from Transient: %3 -- JSON Data%1$s%4$s%1$s-->%1$s',
			PHP_EOL,
			TKEventW_Setup::plugin_display_name(),
			TKEventW_Shortcode::$dark_sky_api_transient_used,
			json_encode( $data, JSON_PRETTY_PRINT ) // JSON_PRETTY_PRINT option requires PHP 5.4
		);

		return $output;
	}

	/**
	 * Prepare Exclude for transient purposes.
	 *
	 * First letter of each valid exclusion, not separated by any character.
	 *
	 * @return string
	 */
	private static function get_exclude_for_transient() {
		$exclude = TKEventW_Shortcode::$dark_sky_api_exclude;

		$exclude_array = explode( ',', $exclude );

		sort( $exclude_array );

		$exclude_for_transient = '';

		foreach ( $exclude_array as $key => $value ) {
			$exclude_for_transient .= substr( $value, 0, 1 );
		}

		return $exclude_for_transient;
	}

	private static function get_start_time_top_of_hour_timestamp( $timestamp ) {
		return TKEventW_Time::timestamp_truncate_minutes( $timestamp );
	}

	private static function get_transient_name() {
		$name = sprintf(
			'%s_%s_%s_%s_%s_%s_%d',
			TKEventW_Setup::$transient_name_prepend,
			'darksky',
			TKEventW_Shortcode::$dark_sky_api_units,
			self::get_exclude_for_transient(),
			// latitude (before comma)
			substr( strstr( TKEventW_Shortcode::$latitude_longitude, ',', true ), 0, 6 ), // requires PHP 5.3.0+
			// first 6 (assuming period is in first 5, getting first 6 will result in 5 valid characters for transient name
			// longitude (after comma)
			substr( strstr( TKEventW_Shortcode::$latitude_longitude, ',', false ), 0, 6 ), // does not require PHP 5.3.0+
			substr( self::get_start_time_top_of_hour_timestamp( self::$start_time_timestamp ), - 5, 5 ) // last 5 of Start Time timestamp with minutes truncated
		//substr( $end_time_timestamp, -5, 5 ) // last 5 of End Time timestamp
		// noticed in testing sometimes leading zero(s) get truncated, possibly due to sanitize_key()... but, as long as it is consistent we are ok.
		);

		$name = TKEventW_Functions::sanitize_transient_name( $name );

		return $name;
	}

	private static function get_transient_value() {
		return TKEventW_Functions::transient_get_or_delete( self::get_transient_name(), TKEventW_Shortcode::$transients_enabled );
	}

}