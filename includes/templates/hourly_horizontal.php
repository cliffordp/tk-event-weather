<?php
/**
 * Template: Hourly Horizontal -- like https://github.com/cliffordp/tk-event-weather/issues/3#issuecomment-178440095
 *
 * Override this template in your own theme by creating a file at [your-child-theme]/tk-event-weather/hourly_horizontal.php
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

$index = 1;

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
	
	$output .= PHP_EOL;
	
	$output .= sprintf( '<div id="%1$s__index-%2$d" class="%1$s__item ', $template_class, $index );
	
	if ( true === $doing_sunrise ) {
  	$output .= sprintf( 'sunrise">
      <span class="%1$s__time">%2$s</span>
      <span class="%1$s__icon sunrise">%3$s</span>
      <span>&nbsp;</span>',
      $template_class,
  	  TkEventWeather_Functions::timestamp_to_display ( $context->sunrise_sunset['sunrise_timestamp'], __( 'g:i' ) ),
  	  TkEventWeather_Functions::icon_html( 'sunrise' )
    );
  } elseif ( true === $doing_sunset ) {
  	$output .= sprintf( 'sunset">
      <span class="%1$s__time">%2$s</span>
      <span class="%1$s__icon sunset">%3$s</span>
      <span>&nbsp;</span>',
      $template_class,
  	  TkEventWeather_Functions::timestamp_to_display ( $context->sunrise_sunset['sunset_timestamp'], __( 'g:i' ) ),
  	  TkEventWeather_Functions::icon_html( 'sunset' )
    );
  } else {
    // if sunrise or sunset timestamp = this hourly weather timestamp, don't display this hour, otherwise do display this hour
    // Example: Sunset at 6:00pm, don't display the 6pm hourly weather info
    // Example: Sunset at 5:59pm, do display the 6pm hourly weather info
    if ( ( $doing_sunrise && $context->sunrise_sunset['sunrise_timestamp'] == $value->time ) || ( $doing_sunset && $context->sunrise_sunset['sunset_timestamp'] == $value->time ) ) {
      // nothing
    } else {
    	$output .= sprintf( '">
        <span class="%1$s__time">%2$s</span>
        <span class="%1$s__icon %3$s">%4$s</span>
        <span class="%1$s__temperature">%5$s%6$s</span>',
        $template_class,
    	  $display_time,
    	  $value->icon,
    	  TkEventWeather_Functions::icon_html( $value->icon ),
    	  TkEventWeather_Functions::temperature_to_display( $value->temperature ),
    	  $context->temperature_units
      );
    }
  }
  
	$output .= PHP_EOL;
	
  $output .= '</div>'; // __item
  
  
  // Resets
  $doing_sunrise = false;
  $doing_sunset = false;
  $display_time = '';
  
  $index++;

} // end foreach()

$output .= PHP_EOL;
	
$output .= '</div>'; // .tk-event-weather-template

echo $output;
?>