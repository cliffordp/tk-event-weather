<?php

namespace TKEventWeather;

/**
 *
 * @package        TkEventWeather
 * @author         TourKick (Clifford Paulick)
 * @link           https://github.com/GaryJones/Gamajo-Template-Loader#installation
 * @copyright      2016 TourKick (Clifford Paulick)
 * @license        GPL-3.0+
 */

if ( ! class_exists( '\Gamajo_Template_Loader' ) ) {
	require Setup::plugin_dir_path_vendor( 'gamajo/template-loader/class-gamajo-template-loader.php' );
}

/**
 * Template loader for TK Event Weather.
 *
 * Only need to specify class properties here.
 *
 * @package   TkEventW
 * @author    TourKick (Clifford Paulick)
 */
class Template_Loader extends \Gamajo_Template_Loader {

	/**
	 * Prefix for filter names.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $filter_prefix = \TK_EVENT_WEATHER_UNDERSCORES;

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $theme_template_directory = \TK_EVENT_WEATHER_HYPHENS;

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	protected $plugin_directory = \TK_EVENT_WEATHER_PLUGIN_ROOT_DIR; // cannot use a function so need to use a constant, which gets defined in the root plugin file
}
