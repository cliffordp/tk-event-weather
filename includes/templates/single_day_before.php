<?php
/**
 * Template: Before Single Day (plain text)
 *
 * Override this template in your own theme by creating a file at [your-child-theme]/tk-event-weather/single_day_before.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// make sure we have data to work with!
if ( empty( $context ) || ! is_object( $context ) ) {
	return false;
}

$output = '';

// Total Days in Span won't be set at time of first day's run so don't try to include it
$class = sprintf( 'tk-event-weather__wrap_single_day %s tk-event-weather__span-%d-to-%d tk-event-weather__day-index-%d %s',
	TKEventW_Shortcode::$span_template_data['template_class_name'],
	TKEventW_Shortcode::$span_start_time_timestamp,
	TKEventW_Shortcode::$span_end_time_timestamp,
	$context->day_number_of_span,
	sanitize_html_class( $context->class )
);

$output .= sprintf( '<div class="%s">', esc_attr( $class ) );
$output .= PHP_EOL;

$output .= $context->before;
$output .= PHP_EOL;

$output .= '<div class="tk-event-weather-template">';
$output .= PHP_EOL;

$output .= '<h4 class="tk-event-weather-day-name"';
if ( ! empty( $context->api_data->daily->data[0]->summary ) ) { // Note "daily" instead of "hourly"
	$output .= sprintf( ' title="%s"', esc_attr( $context->api_data->daily->data[0]->summary ) );
}
$output .= sprintf( '>%s</h4>', esc_attr( date_i18n( TKEventW_Shortcode::$time_format_day, $context->start_time_timestamp ) ) );
$output .= PHP_EOL;

// Same as "title" attribute for Day Name but displayed below it so it is more noticeable
if ( ! empty( $context->api_data->daily->data[0]->summary ) ) { // Note "daily" instead of "hourly"
	$output .= sprintf( '<div class="tk-event-weather-day-summary">%s</div>', esc_html( $context->api_data->daily->data[0]->summary ) );
	$output .= PHP_EOL;
}

$output .= '<div class="tk-event-weather-single-day-weather">';
$output .= PHP_EOL;

echo $output;
