<?php
/**
 * Template: Event Low to High (plain text)
 *
 * Override this template in your own theme by creating a file at [your-child-theme]/tk-event-weather/low_high.php
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// make sure we have data to work with!
if ( empty( $context ) || ! is_object( $context ) ) {
  return false;
}

$template_class = sanitize_html_class ( sprintf( 'tk-event-weather-template__%s', $context->template ) );


$output = sprintf( '<div class="tk-event-weather-template %s">', $template_class );

if ( $context->weather_hourly_high == $context->weather_hourly_low ) {
	$output .= sprintf( '<span class="degrees-same">%s%s</span>',
	  TkEventWeather_Functions::temperature_to_display( $context->weather_hourly_low ),
	  $context->temperature_units
  );
} else {
	$output .= sprintf( '<span class="degrees-low">%s</span>&ndash;<span class="degrees-high">%s</span>%s',
	  TkEventWeather_Functions::temperature_to_display( $context->weather_hourly_low, 0, '' ), // no degree symbol
	  TkEventWeather_Functions::temperature_to_display( $context->weather_hourly_high ),
	  $context->temperature_units
  );
}

$output .= PHP_EOL;
	
$output .= '</div>'; // .tk-event-weather-template

echo $output;
?>