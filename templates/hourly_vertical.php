<?php

namespace TKEventWeather;

/**
 * Template: Hourly Vertical -- like https://github.com/cliffordp/tk-event-weather/issues/3#issuecomment-174612368
 *
 * Override this template in your own theme by creating a file at [your-child-theme]/tk-event-weather/hourly_vertical.php
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// make sure we have data to work with!
if ( empty( $context ) || ! is_object( $context ) ) {
	return '';
}

$output = '';

$index = 1;

foreach ( $context->weather_hourly as $key => $value ) {

	$display_time = Time::timestamp_to_display( $value->time, Shortcode::$timezone, Shortcode::$time_format_hours );

	// if sunrise or sunset timestamp = this hourly weather timestamp, don't display this hour's weather. Instead, only do sunrise/sunset
	// Example: Sunset at 6:00pm, don't display the 6pm hourly weather info
	// Example: Sunset at 5:59pm, do display the 6pm hourly weather info

	// doing this hour's weather
	if ( ! empty ( $display_time ) ) {
		if ( true === $context->sunrise_sunset['sunrise_to_be_inserted'] && $context->sunrise_sunset['sunrise_timestamp'] == $value->time ) {
			// unless this hour's timestamp = sunrise timestamp
		} elseif ( true === $context->sunrise_sunset['sunset_to_be_inserted'] && $context->sunrise_sunset['sunset_timestamp'] == $value->time ) {
			// unless this hour's timestamp = sunset timestamp
		} else {
			// actually do this hour's weather
			$output .= Template::template_start_of_each_item( $context->template_class_name, $index );

			$wind_bearing = '';
			if ( isset( $value->windBearing ) ) {
				$wind_bearing = $value->windBearing;
			}

			$wind_direction = Functions::wind_bearing_to_direction( $value->windBearing, false );

			$wind_html = sprintf(
				'<span class="%1$s__wind" title="%2$s %3$s %4$s">%2$s %3$s</span>',
				$context->template_class_name,
				Functions::rounded_float_value( $value->windSpeed ),
				$context->wind_speed_units,
				$wind_direction
			);

			$output .= sprintf(
				'">
				<span data-timestamp="%1$d" class="%2$s__time">%3$s</span>
				<span class="%2$s__icon %4$s" title="%5$s">%6$s</span>
				<span class="%2$s__temperature">%7$s%8$s</span>
				%9$s',
				$value->time,
				$context->template_class_name,
				$display_time,
				$value->icon,
				$value->summary,
				Functions::icon_html( $value->icon ),
				Functions::temperature_to_display( $value->temperature ),
				$context->temperature_units,
				$wind_html
			);

			$output .= PHP_EOL;

			$output .= '</div>'; // close template_start_of_each_item()

			$index ++; // increment index
		}
	}

	// now do sunrise or sunset
	if ( true === $context->sunrise_sunset['sunrise_to_be_inserted'] && $value->time == $context->sunrise_sunset['sunrise_hour_timestamp'] ) {
		$output .= Template::template_start_of_each_item( $context->template_class_name, $index );

		$output .= sprintf(
			' sunrise">
			<span data-timestamp="%1$d" class="%2$s__time">%3$s</span>
			<span class="%2$s__icon sunrise" title="%4$s">%5$s</span>
			<span>&nbsp;</span>',
			$context->sunrise_sunset['sunrise_timestamp'],
			$context->template_class_name,
			Time::timestamp_to_display( $context->sunrise_sunset['sunrise_timestamp'], Shortcode::$timezone, Shortcode::$time_format_minutes ),
			__( 'Sunrise', 'tk-event-weather' ),
			Functions::icon_html( 'sunrise' )
		);

		$output .= PHP_EOL;

		$output .= '</div>'; // close template_start_of_each_item()

		$index ++; // increment index
	} elseif ( true === $context->sunrise_sunset['sunset_to_be_inserted'] && $value->time == $context->sunrise_sunset['sunset_hour_timestamp'] ) {
		$output .= Template::template_start_of_each_item( $context->template_class_name, $index );

		$output .= sprintf(
			' sunset">
			<span data-timestamp="%1$d" class="%2$s__time">%3$s</span>
			<span class="%2$s__icon sunset" title="%4$s">%5$s</span>
			<span>&nbsp;</span>',
			$context->sunrise_sunset['sunset_timestamp'],
			$context->template_class_name,
			Time::timestamp_to_display( $context->sunrise_sunset['sunset_timestamp'], Shortcode::$timezone, Shortcode::$time_format_minutes ),
			__( 'Sunset', 'tk-event-weather' ),
			Functions::icon_html( 'sunset' )
		);

		$output .= PHP_EOL;

		$output .= '</div>'; // close template_start_of_each_item()

		$index ++; // increment index
	} else {
		// nothing
	}
} // end foreach()

echo $output;
