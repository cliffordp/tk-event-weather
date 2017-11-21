<?php

namespace TKEventWeather;

/**
 * Template: After Single Day (plain text)
 *
 * Override this template in your own theme by creating a file at [your-child-theme]/tk-event-weather/single_day_after.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// make sure we have data to work with!
if (
	empty( $context )
	|| ! is_object( $context )
) {
	return false;
}

$output = '';

$output .= '</div>'; // .tk-event-weather-single-day-weather
$output .= PHP_EOL;

$output .= '</div>'; // .tk-event-weather-template
$output .= PHP_EOL;

$output .= '</div>'; // $template_data['single_day_before_class']
$output .= PHP_EOL;

$output .= apply_filters( TK_EVENT_WEATHER_UNDERSCORES . '_after_each_day', '', $context );
$output .= PHP_EOL;

echo $output;
