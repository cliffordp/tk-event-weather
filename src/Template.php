<?php

namespace TKEventWeather;

class Template {
	/**
	 * @param       $slug
	 * @param array $data
	 * @param null  $name
	 * @param bool  $load
	 */
	public static function load_template( $slug, $data = array(), $name = null, $load = true ) {
		$template_loader = self::new_template_loader();
		$template_loader->set_template_data( $data, 'context' ); // passed-through data becomes accessible as $context->piece_of_data within template
		$template_loader->get_template_part( $slug, $name, $load );
	}

	/**
	 * Views / Templates
	 *
	 * @link https://pippinsplugins.com/template-file-loaders-plugins/
	 */

	public static function new_template_loader() {
		return new Template_Loader();
	}

	/**
	 * @param string $template_name
	 *
	 * @return string
	 */
	public static function template_class_name( $template_name = '' ) {
		$result = '';

		if ( array_key_exists( $template_name, self::valid_display_templates() ) ) {
			$result = sanitize_html_class( sprintf( 'template-%s', $template_name ) );
		}

		return $result;
	}

	/**
	 * @param string $prepend_empty
	 *
	 * @return array|bool
	 */
	public static function valid_display_templates( $prepend_empty = 'false' ) {
		$result = array(
			'hourly_horizontal' => __( 'Hourly (Horizontal)', 'tk-event-weather' ),
			'hourly_vertical'   => __( 'Hourly (Vertical)', 'tk-event-weather' ),
			'low_high'          => __( 'Low-High Temperature', 'tk-event-weather' ),
		);

		$custom_display_templates = apply_filters( TK_EVENT_WEATHER_UNDERSCORES . '_custom_display_templates', array(), Shortcode::$custom_context );

		if ( ! empty( $custom_display_templates ) ) {
			$result = array_merge( $result, $custom_display_templates );
		}

		if ( 'true' == $prepend_empty ) {
			$result = Functions::array_prepend_empty( $result );
		}

		return $result;
	}

	// does NOT close the opening DIV tag
	// could add optional argument to customize element (div, span, etc)

	public static function template_start_of_each_item( $template_class_name = '', $index = '' ) {
		$result = '<div class="';

		$template_class_name = sanitize_html_class( $template_class_name );

		if ( ! empty( $template_class_name ) && is_integer( $index ) ) {
			$result = sprintf( '<div class="%1$s__index-%2$d %1$s__item', $template_class_name, $index );
		} elseif ( ! empty( $template_class_name ) && ! is_integer( $index ) ) {
			$result = sprintf( '<div class="%1$s %1$s__item ', $template_class_name );
		} else {
			// nothing to do
		}

		return $result;
	}


}
