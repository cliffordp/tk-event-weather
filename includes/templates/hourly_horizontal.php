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
  return '';
}

$output = '';

$index = 1;

foreach ( $context->weather_hourly as $key => $value ) {
  
  $display_time = TkEventWeather_Functions::timestamp_to_display ( $value->time, $context->utc_offset_hours );
	
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
    	$output .= TkEventWeather_Functions::template_start_of_each_item( $context->template_class_name, $index );
    	
    	$output .= sprintf( '">
        <span data-timestamp="%1$d" class="%2$s__time">%3$s</span>
        <span class="%2$s__icon %4$s" title="%5$s">%6$s</span>
        <span class="%2$s__temperature">%7$s%8$s</span>',
        $value->time,
        $context->template_class_name,
    	  $display_time,
    	  $value->icon,
    	  $value->summary,
    	  TkEventWeather_Functions::icon_html( $value->icon ),
    	  TkEventWeather_Functions::temperature_to_display( $value->temperature ),
    	  $context->temperature_units
      );
      
      $output .= PHP_EOL;
      
      $output .= '</div>'; // close template_start_of_each_item()
      
      $index++; // increment index
  	}
	}
	
  // now do sunrise or sunset
	if ( true === $context->sunrise_sunset['sunrise_to_be_inserted'] && $value->time == $context->sunrise_sunset['sunrise_hour_timestamp'] ) {
  	$output .= TkEventWeather_Functions::template_start_of_each_item( $context->template_class_name, $index );
  	
  	$output .= sprintf( ' sunrise">
      <span data-timestamp="%1$d" class="%2$s__time">%3$s</span>
      <span class="%2$s__icon sunrise">%4$s</span>
      <span>&nbsp;</span>',
      $context->sunrise_sunset['sunrise_timestamp'],
      $context->template_class_name,
  	  TkEventWeather_Functions::timestamp_to_display ( $context->sunrise_sunset['sunrise_timestamp'], $context->utc_offset_hours, __( 'g:i' ) ),
  	  TkEventWeather_Functions::icon_html( 'sunrise' )
    );
    
    $output .= PHP_EOL;
    
    $output .= '</div>'; // close template_start_of_each_item()
    
    $index++; // increment index
	} elseif ( true === $context->sunrise_sunset['sunset_to_be_inserted'] && $value->time == $context->sunrise_sunset['sunset_hour_timestamp'] ) {
  	$output .= TkEventWeather_Functions::template_start_of_each_item( $context->template_class_name, $index );
  	
  	$output .= sprintf( ' sunset">
      <span data-timestamp="%1$d" class="%2$s__time">%3$s</span>
      <span class="%2$s__icon sunset">%4$s</span>
      <span>&nbsp;</span>',
      $context->sunrise_sunset['sunset_timestamp'],
      $context->template_class_name,
  	  TkEventWeather_Functions::timestamp_to_display ( $context->sunrise_sunset['sunset_timestamp'], $context->utc_offset_hours, __( 'g:i' ) ),
  	  TkEventWeather_Functions::icon_html( 'sunset' )
    );
    
    $output .= PHP_EOL;
    
    $output .= '</div>'; // close template_start_of_each_item()
    
    $index++; // increment index
  } else {
  	// nothing
	}  
  
} // end foreach()

echo $output;
?>