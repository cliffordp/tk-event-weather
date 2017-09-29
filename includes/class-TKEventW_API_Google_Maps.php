<?php

class TKEventW_API_Google_Maps {
	// all variables and methods should be 'static'

	private static function geocode_request_uri() {
		$address = TKEventW_Shortcode::$location;
		if ( empty( $address ) ) {
			return '';
		}

		$api_key = TKEventW_Functions::sanitize_key_allow_uppercase( TKEventW_Shortcode::$google_maps_api_key );

		$uri_base = 'https://maps.googleapis.com/maps/api/geocode/json';

		$uri_query_args = array();

		$uri_query_args['address'] = urlencode( $address );

		// TODO if not set, make it required and throw an error
		if ( ! empty( $api_key ) ) {
			$uri_query_args['key'] = urlencode( $api_key );
		}

		/**
		 * Filter to allow adding things like Region Biasing.
		 *
		 * @link https://developers.google.com/maps/documentation/geocoding/intro#RegionCodes
		 */
		$uri_query_args = apply_filters( 'tkeventw_geocode_request_uri_query_args', $uri_query_args );

		$uri = add_query_arg( $uri_query_args, $uri_base );

		return $uri;
	}

	private static function get_geocode_response_data() {
		// Get from transient if exists and valid
		$transient_data = self::get_transient_value();

		if ( ! empty( $transient_data ) ) { // TODO is_object()???
			if ( ! is_object( $transient_data ) || is_wp_error( $transient_data ) ) {
				$transient_data = '';
				delete_transient( self::get_transient_name() );
			}
		}

		if ( ! empty( $transient_data ) ) {
			return $transient_data;
		}

		// Get from API if no transient
		$response = wp_safe_remote_get( esc_url_raw( self::geocode_request_uri() ) );

		if ( is_wp_error( $response ) ) {
			return TKEventW_Functions::invalid_shortcode_message( 'Google Maps Geocoding API request sent but resulted in a WordPress Error. Please troubleshoot' );
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return TKEventW_Functions::invalid_shortcode_message( 'Google Maps Geocoding API request sent but nothing received. Please troubleshoot' );
		}

		$data = json_decode( $body );

		if ( empty( $data ) ) {
			return TKEventW_Functions::invalid_shortcode_message( 'Google Maps Geocoding API response received but some sort of data inconsistency. Please troubleshoot' );
		}

		/**
		 * Set transient if enabled and API call resulted in usable data.
		 *
		 * Allowed to store for up to 30 calendar days.
		 *
		 * @link https://developers.google.com/maps/terms#10-license-restrictions
		 */
		if ( ! empty( TKEventW_Shortcode::$transients_enabled ) ) {
			set_transient( self::get_transient_name(), $data, 30 * DAY_IN_SECONDS );
		}

		return $data;
	}

	public static function get_debug_messages() {
		$output = '';

		if ( empty( TKEventW_Shortcode::$debug_enabled ) ) {
			return $output;
		}

		$data = self::get_geocode_response_data();

		if ( empty( $data ) ) {
			return $output;
		}

		/* Request URI
		 * Example Debug Output:
		<!--
		TK Event Weather -- Google Maps Geocoding API -- Request URI
		https://maps.googleapis.com/maps/api/geocode/json?address=The+White+House
		-->
		 */
		$output .= sprintf(
			'<!--%1$s%2$s -- Google Maps Geocoding API -- Request URI%1$s%2$s%1$s-->%1$s',
			PHP_EOL,
			TKEventW_Setup::plugin_display_name(),
			self::geocode_request_uri()
		);

		/**
		 * JSON Data
		 *
		 * api-result-examples/google_maps.txt
		 */
		$output .= sprintf( '<!--%1$s%2$s -- Google Maps Geocoding API -- JSON Data%1$s%2$s%1$s-->%1$s',
			PHP_EOL,
			TKEventW_Setup::plugin_display_name(),
			json_encode( $data, JSON_PRETTY_PRINT ) // JSON_PRETTY_PRINT option requires PHP 5.4
		);

		return $output;
	}

	private static function get_transient_name() {
		$name = sprintf(
			'%s_gmaps_%s',
			TKEventW_Setup::$transient_name_prepend,
			TKEventW_Functions::remove_all_whitespace( TKEventW_Shortcode::$location )
		);

		$name = TKEventW_Functions::sanitize_transient_name( $name );

		return $name;
	}

	private static function get_transient_value() {
		$value = TKEventW_Functions::transient_get_or_delete( self::get_transient_name(), TKEventW_Shortcode::$transients_enabled );
	}

	public static function get_lat_long() {
		if ( ! empty( TKEventW_Shortcode::$latitude_longitude ) ) {
			return TKEventW_Functions::valid_lat_long( TKEventW_Shortcode::$latitude_longitude );
		}

		$data = self::get_geocode_response_data();

		/**
		 * @link https://developers.google.com/maps/documentation/geocoding/intro#StatusCodes
		 */
		if ( 'OK' != $data->status ) {
			return TKEventW_Functions::invalid_shortcode_message( 'The Google Maps Geocoding API resulted in an error: ' . $data->status . '. See https://developers.google.com/maps/documentation/geocoding/intro#StatusCodes' );
		}


		if ( ! empty ( $data->results[0]->geometry->location->lat ) ) {
			$latitude  = $data->results[0]->geometry->location->lat;
			$longitude = $data->results[0]->geometry->location->lng;
		}

		// build comma-separated coordinates
		$latitude_longitude = sprintf( '%F,%F', $latitude, $longitude );
		$latitude_longitude = TKEventW_Functions::valid_lat_long( $latitude_longitude );

		return $latitude_longitude;
	}


}