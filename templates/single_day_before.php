<?php

namespace TKEventWeather;

/**
 * Template: Before Single Day (plain text)
 *
 * Override this template in your own theme by creating a file at [your-child-theme]/tk-event-weather/single_day_before.php
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

if ( true === Time::timestamp_is_during_today( $context->start_time_timestamp ) ) {
	$day_type = 'today';
} elseif ( \time() < $context->start_time_timestamp ) {
	$day_type = 'future';
} elseif ( \time() > $context->start_time_timestamp ) {
	$day_type = 'past';
} else {
	// unexpected but here to protect against undefined variable
	$day_type = '';
}

// Total Days in Span won't be set at time of first day's run so don't try to include it
$class = sprintf( '%1$s__wrap_single_day %2$s %1$s__span-%3$d-to-%4$d %1$s__day-index-%5$d %1$s__day-type-%6$s %7$s',
	TK_EVENT_WEATHER_HYPHENS,
	Shortcode::$span_template_data['template_class_name'],
	Shortcode::$span_start_time_timestamp,
	Shortcode::$span_end_time_timestamp,
	$context->day_number_of_span,
	sanitize_html_class( $day_type ),
	sanitize_html_class( $context->class )
);

$output .= sprintf( '<div class="%s">', esc_attr( $class ) );
$output .= PHP_EOL;

$output .= apply_filters( TK_EVENT_WEATHER_UNDERSCORES . '_before_each_day', '', $context );
$output .= PHP_EOL;

$output .= '<div class="' . TK_EVENT_WEATHER_HYPHENS . '-template">';
$output .= PHP_EOL;

$output .= '<h4 class="' . TK_EVENT_WEATHER_HYPHENS . '-day-name"';
if ( ! empty( $context->api_data->daily->data[0]->summary ) ) { // Note "daily" instead of "hourly"
	$output .= sprintf( ' title="%s"', esc_attr( $context->api_data->daily->data[0]->summary ) );
}


$day_name = Time::timestamp_to_display( $context->start_time_timestamp, Shortcode::$timezone, Shortcode::$time_format_day );

/**
 * Filter to disable changing a day's name from something like "Oct 1" to
 * "Today".
 *
 * @param $today_text
 * @param $day_name
 */
$change_day_name_to_today = apply_filters( TK_EVENT_WEATHER_UNDERSCORES . '_change_day_name_if_is_today', true, $day_name, $context );

if (
	! empty( $change_day_name_to_today )
	&& true === Time::timestamp_is_during_today( $context->start_time_timestamp )
) {
	$day_name = _x( 'Today', 'day name if forecast is for today', 'tk-event-weather' );
}

$output .= sprintf( '>%s</h4>', esc_attr( $day_name ) );
$output .= PHP_EOL;

// Same as "title" attribute for Day Name but displayed below it so it is more noticeable
if ( ! empty( $context->api_data->daily->data[0]->summary ) ) { // Note "daily" instead of "hourly"
	$output .= sprintf( '<div class="%s-day-summary">%s</div>', TK_EVENT_WEATHER_HYPHENS, esc_html( $context->api_data->daily->data[0]->summary ) );
	$output .= PHP_EOL;
}

$output .= '<div class="' . TK_EVENT_WEATHER_HYPHENS . '-single-day-weather">';
$output .= PHP_EOL;

echo $output;
