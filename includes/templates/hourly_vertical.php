<?php
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
if ( empty( $context ) || ! is_array( $context ) ) {
  //return false;
}

//var_dump($context);

$output = sprintf( '<div class="tk-event-weather--%s">', $context->template );

foreach ( $context->weather_hourly as $key=>$value ) {
	$doing_sunrise = false;
	$doing_sunset = false;
	
	if ( true === $context->sunrise_sunset['sunrise_to_be_inserted'] && $value->time > $context->sunrise_sunset['sunrise_timestamp'] ) {
  	$doing_sunrise = true;
  	$context->sunrise_sunset['sunrise_to_be_inserted'] = false; // because we are going to do it right now and not another time
	} elseif ( true === $context->sunrise_sunset['sunset_to_be_inserted'] && $value->time > $context->sunrise_sunset['sunset_timestamp'] ) {
  	$doing_sunset = true;
  	$context->sunrise_sunset['sunset_to_be_inserted'] = false; // because we are going to do it right now and not another time
  } else {
  	// nothing
	}
	
  $display_time = TkEventWeather_Functions::timestamp_to_display ( $value->time );
	
	if ( empty ( $display_time ) ) {
  	continue;
	}
	
	if ( true === $doing_sunrise ) {
  	$output .= sprintf( '<div id="tk-event-weather-sunrise-timestamp-%s" class="tk-event-weather-hourly-sunrise">
        <span class="tk-event-weather-hourly-time">%s</span>
        <span class="tk-event-weather-hourly-icon--%s">%s</span>
      </div>',
  	  $context->sunrise_sunset['sunrise_timestamp'],
  	  TkEventWeather_Functions::timestamp_to_display ( $context->sunrise_sunset['sunrise_timestamp'] ),
  	  'sunrise',
  	  TkEventWeather_Functions::icon_html( 'sunrise' )
    );
  } elseif ( true === $doing_sunset ) {
  	$output .= sprintf( '<div id="tk-event-weather-sunset-timestamp-%s" class="tk-event-weather-hourly-sunset">
        <span class="tk-event-weather-hourly-time">%s</span>
        <span class="tk-event-weather-hourly-icon--%s">%s</span>
      </div>',
  	  $context->sunrise_sunset['sunset_timestamp'],
  	  TkEventWeather_Functions::timestamp_to_display ( $context->sunrise_sunset['sunset_timestamp'] ),
  	  'sunset',
  	  TkEventWeather_Functions::icon_html( 'sunset' )
    );
  } else {
    // nothing
  }
  
  // if sunrise or sunset timestamp = this hourly weather timestamp, don't display this hour, otherwise do display this hour
  // Example: Sunset at 6:00pm, don't display the 6pm hourly weather info
  // Example: Sunset at 5:59pm, do display the 6pm hourly weather info
  if ( ( $doing_sunrise && $context->sunrise_sunset['sunrise_timestamp'] == $value->time ) || ( $doing_sunset && $context->sunrise_sunset['sunset_timestamp'] == $value->time ) ) {
    // nothing
  } else {
  	$output .= sprintf( '<div id="tk-event-weather-hourly-timestamp-%s" class="tk-event-weather-hourly-hour">
        <span class="tk-event-weather-hourly-time">%s</span>
        <span class="tk-event-weather-hourly-icon--%s">%s</span>
        <span class="tk-event-weather-hourly-temperature">%s</span>
        <span class="tk-event-weather-hourly-wind-speed">%s</span>
        <span class="tk-event-weather-hourly-wind-bearing">%s</span>
      </div>',
  	  $value->time,
  	  $display_time,
  	  $value->icon,
  	  TkEventWeather_Functions::icon_html( $value->icon ),
  	  TkEventWeather_Functions::rounded_float_value( $value->temperature ),
  	  TkEventWeather_Functions::rounded_float_value( $value->windSpeed ),
  	  $value->windBearing
    );
  }
  
  // Resets
  $doing_sunrise = false;
  $doing_sunset = false;
  $display_time = '';

} // end foreach()

$output .= '</div>';

echo $output;
?>