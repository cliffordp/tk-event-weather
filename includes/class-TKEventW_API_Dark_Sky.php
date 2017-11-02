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

		$language = self::get_request_uri_language();

		if ( ! empty( $language ) ) {
			$uri_query_args['lang'] = urlencode( $language );
		}

		$exclude = TKEventW_Shortcode::$dark_sky_api_exclude;

		if ( ! empty( $exclude ) ) {
			$uri_query_args['exclude'] = urlencode( $exclude );
		}

		/**
		 * Filter to allow overriding Units, Language, and/or Exclude.
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

	private static function get_request_uri_language() {
		return TKEventW_Shortcode::$dark_sky_api_language;
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

	/**
	 * Set timezone as soon as possible because so many other things rely on it.
	 *
	 * We usually want the timezone set via the API, but we need the timezone
	 * set ASAP because of running the API data through the display template
	 * or using functions requiring the timezone.
	 *
	 * @param $use_this_timezone
	 *
	 * @return bool
	 */
	public static function set_timezone_if_needed( $use_this_timezone ) {
		if (
			! empty( TKEventW_Shortcode::$timezone )
			|| ! in_array( $use_this_timezone, timezone_identifiers_list() )
		) {
			return false;
		}

		// set it from API if we're allowed to
		TKEventW_Time::set_timezone_from_api( $use_this_timezone );
	}

	public static function get_response_data() {
		// Get from transient if exists and valid
		$transient_data = self::get_transient_value();

		if ( true === self::valid_transient( $transient_data ) ) {
			TKEventW_Shortcode::$dark_sky_api_transient_used = 'TRUE';

			if ( ! empty( $transient_data->timezone ) ) {
				self::set_timezone_if_needed( $transient_data->timezone );
			}

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
		if ( ! empty( TKEventW_Shortcode::$transients_enabled ) ) {
			$transient_expiration_hours = absint( TKEventW_Shortcode::$transients_expiration_hours );

			if ( empty( $transient_expiration_hours ) ) {
				$transient_expiration_hours = 12;
			}

			set_transient( self::get_transient_name(), $data, $transient_expiration_hours * HOUR_IN_SECONDS ); // e.g. 12 hours
		}

		if ( ! empty( $data->timezone ) ) {
			self::set_timezone_if_needed( $data->timezone );
		}

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

		/**
		 * Example Debug Output:
		 *
		 * api-result-examples/dark_sky.txt
		 * <!--
		 * TK Event Weather -- Dark Sky API -- Request URI
		 * https://api.darksky.net/forecast/___API_KEY___/38.897676,-77.036530,1464604200?units=auto&exclude=minutely,alerts
		 * -->
		 */
		$output .= sprintf( '<!--%1$s%2$s -- Dark Sky API -- Obtained from Transient: %3$s -- Request URI:%1$s%4$s%1$s -- JSON Data:%1$s%5$s%1$s-->%1$s',
			PHP_EOL,
			TKEventW_Setup::plugin_display_name(),
			TKEventW_Shortcode::$dark_sky_api_transient_used,
			self::request_uri(),
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
			'%s_%s_%s_%s_%s_%s_%s_%d',
			TKEventW_Setup::$transient_name_prepend,
			'darksky',
			TKEventW_Shortcode::$dark_sky_api_units,
			TKEventW_Shortcode::$dark_sky_api_language,
			self::get_exclude_for_transient(),
			// latitude (before comma)
			substr( strstr( TKEventW_Shortcode::$latitude_longitude, ',', true ), 0, 6 ), // Requires PHP 5.3.0+
			// first 6 (assuming period is in first 5, getting first 6 will result in 5 valid characters for transient name
			// longitude (after comma)
			substr( strstr( TKEventW_Shortcode::$latitude_longitude, ',', false ), 0, 7 ), // End at 7 because 0 is the comma (which gets removed downstream). Does not require PHP 5.3.0+
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

	/**
	 * Dark Sky Unit. May select only one per API call.
	 *
	 * @link https://darksky.net/dev/docs#request-parameters
	 *
	 * @param string $prepend_empty
	 *
	 * @return array
	 */
	public static function valid_units( $prepend_empty = 'false' ) {
		$result = array(
			'auto' => __( 'Auto (Default)', 'tk-event-weather' ),
			'ca'   => __( 'Canada', 'tk-event-weather' ),
			'si'   => __( 'SI (International System of Units)', 'tk-event-weather' ),
			'uk2'  => __( 'UK', 'tk-event-weather' ),
			'us'   => __( 'USA', 'tk-event-weather' ),
		);

		if ( 'true' == $prepend_empty ) {
			$result = TKEventW_Functions::array_prepend_empty( $result );
		}

		return $result;
	}

	/**
	 * Dark Sky Language. May select only one per API call.
	 *
	 * @link https://darksky.net/dev/docs#request-parameters
	 *
	 * @param string $prepend_empty
	 *
	 * @return array
	 */
	public static function valid_languages( $prepend_empty = 'false' ) {
		$result = array(
			'ar'          => __( 'Arabic', 'tk-event-weather' ),
			'az'          => __( 'Azerbaijani', 'tk-event-weather' ),
			'be'          => __( 'Belarusian', 'tk-event-weather' ),
			'bg'          => __( 'Bulgarian', 'tk-event-weather' ),
			'bs'          => __( 'Bosnian', 'tk-event-weather' ),
			'ca'          => __( 'Catalan', 'tk-event-weather' ),
			'cs'          => __( 'Czech', 'tk-event-weather' ),
			'de'          => __( 'German', 'tk-event-weather' ),
			'el'          => __( 'Greek', 'tk-event-weather' ),
			'en'          => __( 'English', 'tk-event-weather' ),
			'es'          => __( 'Spanish', 'tk-event-weather' ),
			'et'          => __( 'Estonian', 'tk-event-weather' ),
			'fr'          => __( 'French', 'tk-event-weather' ),
			'hr'          => __( 'Croatian', 'tk-event-weather' ),
			'hu'          => __( 'Hungarian', 'tk-event-weather' ),
			'id'          => __( 'Indonesian', 'tk-event-weather' ),
			'it'          => __( 'Italian', 'tk-event-weather' ),
			'is'          => __( 'Icelandic', 'tk-event-weather' ),
			'kw'          => __( 'Cornish', 'tk-event-weather' ),
			'nb'          => __( 'Norwegian Bokmål', 'tk-event-weather' ),
			'nl'          => __( 'Dutch', 'tk-event-weather' ),
			'pl'          => __( 'Polish', 'tk-event-weather' ),
			'pt'          => __( 'Portuguese', 'tk-event-weather' ),
			'ru'          => __( 'Russian', 'tk-event-weather' ),
			'sk'          => __( 'Slovak', 'tk-event-weather' ),
			'sl'          => __( 'Slovenian', 'tk-event-weather' ),
			'sr'          => __( 'Serbian', 'tk-event-weather' ),
			'sv'          => __( 'Swedish', 'tk-event-weather' ),
			'tet'         => __( 'Tetum', 'tk-event-weather' ),
			'tr'          => __( 'Turkish', 'tk-event-weather' ),
			'uk'          => __( 'Ukrainian', 'tk-event-weather' ),
			'x-pig-latin' => __( 'Pig Latin (Igpay Atinlay)', 'tk-event-weather' ),
			'zh'          => __( 'simplified Chinese', 'tk-event-weather' ),
			'zh-tw'       => __( 'traditional Chinese', 'tk-event-weather' ),
		);

		natcasesort( $result ); // sorts by values, maintains keys, sort natural, case insensitive

		if ( 'true' == $prepend_empty ) {
			$result = TKEventW_Functions::array_prepend_empty( $result );
		}

		return $result;
	}

	/**
	 * Dark Sky Excludes. May select one or multiple (but not all since that
	 * would be valid but return no data).
	 *
	 * @link https://darksky.net/dev/docs#request-parameters
	 *
	 * @param string $prepend_empty
	 *
	 * @return array
	 */
	public static function valid_excludes( $prepend_empty = 'false' ) {
		$result = array(
			'currently' => __( 'Currently', 'tk-event-weather' ),
			'minutely'  => __( 'Minutely', 'tk-event-weather' ),
			'hourly'    => __( 'Hourly', 'tk-event-weather' ),
			'daily'     => __( 'Daily', 'tk-event-weather' ),
			'alerts'    => __( 'Alerts', 'tk-event-weather' ),
			'flags'     => __( 'Flags', 'tk-event-weather' ),
		);

		if ( 'true' == $prepend_empty ) {
			$result = TKEventW_Functions::array_prepend_empty( $result );
		}

		return $result;
	}

}