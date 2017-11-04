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
	TKEventWeather_Shortcode::$span_template_data['template_class_name'],
	TKEventWeather_Shortcode::$span_start_time_timestamp,
	TKEventWeather_Shortcode::$span_end_time_timestamp,
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

$day_name = date_i18n( TKEventWeather_Shortcode::$time_format_day, $context->start_time_timestamp );

/**
 * Filter to disable changing a day's name from something like "Oct 1" to
 * "Today".
 *
 * @param $today_text
 * @param $day_name
 */
$change_day_name_to_today = apply_filters( 'tk_event_weather_change_day_name_if_is_today', true, $day_name );

if (
	! empty( $change_day_name_to_today )
	&& true === TKEventWeather_Time::timestamp_is_during_today( $context->start_time_timestamp )
) {
	$day_name = _x( 'Today', 'day name if forecast is for today', 'tk-event-weather' );
}

$output .= sprintf( '>%s</h4>', esc_attr( $day_name ) );
$output .= PHP_EOL;

// Same as "title" attribute for Day Name but displayed below it so it is more noticeable
if ( ! empty( $context->api_data->daily->data[0]->summary ) ) { // Note "daily" instead of "hourly"
	$output .= sprintf( '<div class="tk-event-weather-day-summary">%s</div>', esc_html( $context->api_data->daily->data[0]->summary ) );
	$output .= PHP_EOL;
}

$output .= '<div class="tk-event-weather-single-day-weather">';
$output .= PHP_EOL;

echo $output;
