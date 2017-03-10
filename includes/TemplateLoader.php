<?php
/**
 *
 * @package        TkEventWeather
 * @author        TourKick (Clifford Paulick)
 * @link        https://github.com/GaryJones/Gamajo-Template-Loader#installation
 * @copyright    2016 TourKick (Clifford Paulick)
 * @license        GPL-2.0+
 */

if ( ! class_exists( 'Gamajo_Template_Loader' ) ) {
	require 'vendor/gamajo-template-loader/class-gamajo-template-loader.php';
}

// uses the 'template-data' branch of Gamajo Template Loader -- see https://github.com/GaryJones/Gamajo-Template-Loader/tree/template-data

/**
 * Template loader for TK Event Weather.
 *
 * Only need to specify class properties here.
 *
 * @package TkEventWeather
 * @author    TourKick (Clifford Paulick)
 */
class TkEventWeather__TemplateLoader extends Gamajo_Template_Loader {

	/**
	 * Prefix for filter names.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $filter_prefix = 'tk_event_weather';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $theme_template_directory = 'tk-event-weather';

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $plugin_directory = TK_EVENT_WEATHER_PLUGIN_ROOT_DIR; // cannot use a function so need to use a constant, which gets defined in the root plugin file

	/**
	 * Directory name where templates are found in this plugin.
	 *
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 *
	 * @since 1.1.0
	 *
	 * @type string
	 */
	protected $plugin_template_directory = 'includes/templates'; // or includes/templates, etc.

}
