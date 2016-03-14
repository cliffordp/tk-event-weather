<?php
/**
 * Template: Event Low to High (plain text)
 *
 * Override this template in your own theme by creating a file at [your-child-theme]/tk-event-weather/event_low_high.php
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// make sure we have data to work with!
if ( empty( $context ) || ! is_array( $context ) ) {
  //return false;
}

//var_dump($context);

$output = sprintf( '<div class="tk-event-weather--%s">', $context->template );

$output .= __( 'Event temperature range:', 'tk-event-weather' );

$output .= ' ';

if ( $context->weather_hourly_high == $context->weather_hourly_low ) {
	$output .= sprintf( '<span class="tk-event-weather-degrees-same">%s</span><span class="tk-event-weather-degrees">&deg;%s</span>',
	  TkEventWeather_Functions::rounded_float_value( $context->weather_hourly_low ),
	  $context->temperature_units
  );
} else {
	$output .= sprintf( '<span class="tk-event-weather-degrees-low">%s</span><span class="tk-event-weather-temperature-separator">%s</span><span class="tk-event-weather-degrees-high">%s</span><span class="tk-event-weather-temperature-degrees">&deg;%s</span>',
	  $context->weather_hourly_low,
	  TkEventWeather_Functions::temperature_separator_html(),
	  TkEventWeather_Functions::rounded_float_value( $context->weather_hourly_high ),
	  $context->temperature_units
  );
}

$output .= '</span>'; // .tk-event-weather.tk-event-weather-temperature


$output .= '</div>';

echo $output;
?>