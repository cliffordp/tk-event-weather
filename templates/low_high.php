<?php

namespace TKEventWeather;

/**
 * Template: Event Low to High (plain text)
 *
 * Override this template in your own theme by creating a file at [your-child-theme]/tk-event-weather/low_high.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// make sure we have data to work with!
if ( empty( $context ) || ! is_object( $context ) ) {
	return false;
}

$output = '';

if ( $context->weather_hourly_high == $context->weather_hourly_low ) {
	$output .= sprintf(
		'<span class="degrees-same">%s%s</span>',
		Functions::temperature_to_display( $context->weather_hourly_low ),
		$context->temperature_units
	);
} else {
	$output .= sprintf(
		'<span class="temperature-low">%s</span>
		<span class="temperature-separator">&ndash;</span>
		<span class="temperature-high">%s</span><span class="temperature-units">%s</span>',
		Functions::temperature_to_display( $context->weather_hourly_low, 0, '' ), // no degree symbol
		Functions::temperature_to_display( $context->weather_hourly_high ),
		$context->temperature_units
	);
}

echo $output;
