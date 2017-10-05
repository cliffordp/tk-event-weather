<?php

class TKEventW_Time {
	// all variables and methods should be 'static'

	/**
	 * Numeric array of WordPress' own UTC offset options.
	 *
	 * Examples: 'UTC+0', 'UTC-5', 'UTC+8.75'
	 *
	 * @see wp_timezone_choice()
	 *
	 * @return array
	 */
	public static function wp_manual_utc_offsets_array() {
		$result = array();

		// Manual UTC offsets, code borrowed from https://developer.wordpress.org/reference/functions/wp_timezone_choice/
		$offset_range = array(
			- 12,
			- 11.5,
			- 11,
			- 10.5,
			- 10,
			- 9.5,
			- 9,
			- 8.5,
			- 8,
			- 7.5,
			- 7,
			- 6.5,
			- 6,
			- 5.5,
			- 5,
			- 4.5,
			- 4,
			- 3.5,
			- 3,
			- 2.5,
			- 2,
			- 1.5,
			- 1,
			- 0.5,
			0,
			0.5,
			1,
			1.5,
			2,
			2.5,
			3,
			3.5,
			4,
			4.5,
			5,
			5.5,
			5.75,
			6,
			6.5,
			7,
			7.5,
			8,
			8.5,
			8.75,
			9,
			9.5,
			10,
			10.5,
			11,
			11.5,
			12,
			12.75,
			13,
			13.75,
			14
		);

		foreach ( $offset_range as $offset ) {
			if ( 0 <= $offset ) {
				$offset_value = '+' . $offset;
			} else {
				$offset_value = (string) $offset;
			}

			$offset_value = 'UTC' . $offset_value;

			$result[] = esc_attr( $offset_value );
		}

		return $result;
	}

	/**
	 * Timezone Sources options
	 *
	 * @param string $prepend_empty
	 *
	 * @return array
	 */
	public static function valid_timezone_sources( $prepend_empty = 'false' ) {

		$result = array(
			'api'       => __( 'From API (i.e. Location-specific)', 'tk-event-weather' ),
			'wordpress' => __( 'From WordPress General Settings', 'tk-event-weather' ),
		);

		// do not give WordPress option if option is not set
		$wp_timezone = get_option( 'timezone_string' ); // could return NULL
		if ( empty( $wp_timezone ) ) {
			unset( $result['wordpress'] );
		}

		if ( 'true' == $prepend_empty ) {
			$result = self::array_prepend_empty( $result );
		}

		return $result;
	}

	/**
	 * TODO: Unused function
	 * Valid strtotime() relative time
	 *
	 * @param string $prepend_empty
	 *
	 * @link http://php.net/manual/en/function.strtotime.php
	 * @link http://php.net/manual/en/datetime.formats.relative.php -- plural "s" is optional for all except "weeks" so just used plural for all
	 *
	 * @return array|bool
	 */
	public static function valid_strtotime_units( $prepend_empty = 'false' ) {
		$result = array(
			'hours'  => __( 'Hours', 'tk-event-weather' ),
			'days'   => __( 'Days (Default)', 'tk-event-weather' ),
			'weeks'  => __( 'Weeks', 'tk-event-weather' ),
			'months' => __( 'Months', 'tk-event-weather' ),
			'years'  => __( 'Years', 'tk-event-weather' ),
		);

		if ( 'true' == $prepend_empty ) {
			$result = self::array_prepend_empty( $result );
		}

		return $result;
	}


	/**
	 * if valid timestamp, returns integer timestamp
	 * or returns boolean if $return_format = 'bool' and is valid timestamp
	 * else returns empty string
	 */
	public static function valid_timestamp( $input, $return_format = '' ) {
		$result = TKEventW_Functions::remove_all_whitespace( $input ); // converts to string

		if ( is_numeric( $result ) ) {
			$result = intval( $result ); // convert to integer
		}

		if ( ! empty( $return_format ) && 'bool' != $return_format ) {
			$return_format = '';
		}

		// is valid timestamp
		if ( is_int( $result ) && date( 'U', $result ) == intval( $result ) ) {
			if ( '' == $return_format ) {
				return intval( $result );
			} else {
				return true;
			}
		} // is NOT valid
		else {
			if ( '' == $return_format ) {
				return '';
			} else {
				return false;
			}
		}
	}

	/**
	 * @param        $input
	 * @param string $return_format
	 *
	 * @return bool|mixed|string
	 */
	public static function valid_iso_8601_date_time( $input, $return_format = '' ) {
		$result = TKEventW_Functions::remove_all_whitespace( $input );

		if ( ! empty( $return_format ) && 'bool' != $return_format ) {
			$return_format = '';
		}

		// default to Today's date (from WordPress' GMT Offset) if begins with capital "T" (skipping the date)
		if ( 0 === strpos( $result, 'T' ) ) {
			$today  = current_time( 'Y-m-d' ); // e.g. "2017-03-11" for March 11, 2017
			$result = $today . $result;
		}

		// is valid ISO 8601 time (i.e. we do not want valid ISO 8601 Duration, Time Interval, etc.)
		// API requires [YYYY]-[MM]-[DD]T[HH]:[MM]:[SS] -- https://en.wikipedia.org/wiki/ISO_8601#Combined_date_and_time_representations
		// with an optional timezone formatted as Z for UTC time or {+,-}[HH]:[MM] (with or without separating colon) for an offset in hours or minutes
		// For the latter format, if no timezone is present, local time (at the provided latitude and longitude) is assumed.
		/*
				@link https://regex101.com/r/mL0xZ4/1
				Should match ISO 8601 datetime for Dark Sky API:
				[YYYY]-[MM]-[DD]T[HH]:[MM]:[SS]
				with an optional timezone formatted as Z for UTC time or {+,-}[HH]:[MM] (with or without separating colon) for an offset
	
				Does Match:
				2008-09-15T15:53:00
				2007-03-01T13:00:00Z
				2015-10-05T21:46:54-1500
				2015-10-05T21:46:54+07:00
	
				Does Not Match:
				2015-10-05T21:46:54-02
				0
				2008
				2008-09
				2008-09-15
				2008-09-15 11:12:13
				2008-09-15 11:12
				1988-05-26T23:00:00.000Z
		*/
		if ( 1 === preg_match( '/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(Z|[\+-]\d{2}:?\d{2})?$/', $result ) ) {
			if ( '' == $return_format ) {
				return $result;
			} else {
				return true;
			}
		} // is NOT valid
		else {
			if ( '' == $return_format ) {
				return '';
			} else {
				return false;
			}
		}
	}

	// e.g. 4:45pm -> 4:00pm
	public static function timestamp_truncate_minutes( $timestamp = '' ) {
		// timestamp
		if ( false === self::valid_timestamp( $timestamp, 'bool' ) ) {
			return false;
		} else {
			// modulus
			// 1 hour = 3600 seconds
			// e.g. 2:30 --> 30 minutes = 1800 seconds --> $timestamp = $timestamp - 1800;
			$timestamp -= $timestamp % 3600;

			return self::valid_timestamp( $timestamp );
		}
	}

	/**
	 * @param string $timestamp
	 * @param string $timezone
	 * @param string $date_format
	 *
	 * @return string
	 */
	public static function timestamp_to_display( $timestamp = '', $timezone = '', $date_format = '' ) {
		// timestamp
		$timestamp = self::valid_timestamp( $timestamp );

		if ( '' === $timestamp ) {
			return '';
		}

		// We will change timezone just for this conversion. Then we'll set it back.
		$existing_timezone = date_default_timezone_get(); // will fallback to UTC but may also return a TZ environment variable (e.g. EST)

		// Dark Sky API may return an escaped timezone string
		$timezone = stripslashes( $timezone );

		if ( ! in_array( $timezone, timezone_identifiers_list() ) ) {
			$timezone = get_option( 'timezone_string' ); // could return NULL
		}

		if ( empty( $timezone ) ) {
			$timezone = $existing_timezone;
		}

		date_default_timezone_set( $timezone );


		if ( empty ( $date_format ) ) {
			return '';
		}

		// $date = date ( $date_format, $timestamp );
		$date = date_i18n( $date_format, $timestamp );
		// possibly relevant issues with date_i18n(): https://core.trac.wordpress.org/ticket/38771, https://core.trac.wordpress.org/ticket/39595#comment:5

		// set back to what date_default_timezone_get() was
		date_default_timezone_set( $existing_timezone );

		// return 
		return $date;
	}

	/**
	 * Given a timestamp, find the minimum or maximum timestamp of that same day.
	 *
	 * If minimum, it will be midnight of that day. Daylight Savings Time (DST)
	 * is not a factor because it does not change over at midnight; it changes
	 * over at 2 AM.
	 * If maximum, it will be 11:59:59 PM of that day.
	 *
	 * @param        $timestamp
	 * @param string $timezone
	 * @param bool   $max Default is FALSE, which means return midnight.
	 *
	 * @return bool|int
	 */
	public static function get_a_days_min_max_timestamp( $timestamp, $timezone = '', $max = false ) {
		// We will change timezone just for this conversion. Then we'll set it back.
		$existing_timezone = date_default_timezone_get(); // will fallback to UTC but may also return a TZ environment variable (e.g. EST)

		// Dark Sky API may return an escaped timezone string
		$timezone = stripslashes( $timezone );

		if ( ! in_array( $timezone, timezone_identifiers_list() ) ) {
			$timezone = get_option( 'timezone_string' ); // could return NULL
		}

		if ( empty( $timezone ) ) {
			$timezone = $existing_timezone;
		}

		date_default_timezone_set( $timezone );

		$datetime = new DateTime();

		$datetime->setTimestamp( $timestamp );

		$day = $datetime->format( 'Y-m-d' );

		if ( true === $max ) {
			// 11:59:59 PM of this day
			$day .= ' 23:59:59';
		} else {
			// Midnight of this day
			$day .= ' 00:00:00';
		}

		$result = strtotime( $day );

		// set back to what date_default_timezone_get() was
		date_default_timezone_set( $existing_timezone );

		return $result;
	}

	public static function count_start_end_cal_days_span( $start_time_timestamp, $end_time_timestamp, $timezone = '' ) {
		// We will change timezone just for this conversion. Then we'll set it back.
		$existing_timezone = date_default_timezone_get(); // will fallback to UTC but may also return a TZ environment variable (e.g. EST)

		// Dark Sky API may return an escaped timezone string
		$timezone = stripslashes( $timezone );

		if ( ! in_array( $timezone, timezone_identifiers_list() ) ) {
			$timezone = get_option( 'timezone_string' ); // could return NULL
		}

		if ( empty( $timezone ) ) {
			$timezone = $existing_timezone;
		}

		date_default_timezone_set( $timezone );

		$start = new DateTime();
		$end   = new DateTime();

		$start->setTimestamp( $start_time_timestamp );
		$end->setTimestamp( $end_time_timestamp );

		/**
		 * @link https://secure.php.net/manual/en/datetime.diff.php
		 * @link https://secure.php.net/manual/en/dateinterval.format.php
		 */
		$diff = (int) $start->diff( $end, true )->format( '%a' );

		/**
		 * If Start and End are on the same calendar day, difference will be zero.
		 * We are trying to get total calendar days covered by 2 dates, not just
		 * the technical difference.
		 */
		$diff = $diff + 1;

		// set back to what date_default_timezone_get() was
		date_default_timezone_set( $existing_timezone );

		return $diff;
	}

	public static function get_last_hour_hour_of_forecast( $end_time_timestamp ) {
		/**
		 * Helps with setting 'sunset_to_be_inserted'
		 *
		 * if event ends at 7:52pm, set to 8pm
		 * if event ends at 7:00:00pm (not 7:00:01pm or later), set to 7pm // TODO verify
		 *
		 */
		$top_of_hour      = self::timestamp_truncate_minutes( $end_time_timestamp ); // e.g. 7pm instead of 7:52pm
		$top_of_next_hour = HOUR_IN_SECONDS + $top_of_hour; // e.g. 8pm

		if ( $end_time_timestamp == $top_of_hour ) { // e.g. event ends at 7:00:00
			$result = $top_of_hour;
		} else {
			$result = $top_of_next_hour;
		}

		return $result;
	}

}