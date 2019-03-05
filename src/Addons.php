<?php

namespace TKEventWeather;

class Addons {

	/**
	 * An array for each published integration add-on.
	 *
	 * @var multi-dimensional array
	 * @var     array
	 * @var string plugin_dir The plugin's directory name.
	 * @var string plugin_file The plugin's main file name.
	 * @var string addon_dir The add-on's directory name.
	 * @var string      addon_file The add-on's main file name.
	 */
	private static $plugin_addons = array(
		array(
			'plugin_dir'  => 'all-in-one-event-calendar',
			'plugin_file' => 'all-in-one-event-calendar.php',
			'addon_dir'   => 'tk-event-weather-ai1ec-premium',
			'addon_file'  => 'tk-event-weather-ai1ec.php',
		),
		array(
			'plugin_dir'  => 'events-manager',
			'plugin_file' => 'events-manager.php',
			'addon_dir'   => 'tk-event-weather-events-manager-premium',
			'addon_file'  => 'tk-event-weather-events-manager.php',
		),
		array(
			'plugin_dir'  => 'google-calendar-events',
			'plugin_file' => 'google-calendar-events.php',
			'addon_dir'   => 'tk-event-weather-simple-calendar-premium',
			'addon_file'  => 'tk-event-weather-simple-calendar.php',
		),
		array(
			'plugin_dir'  => 'the-events-calendar',
			'plugin_file' => 'the-events-calendar.php',
			'addon_dir'   => 'tk-event-weather-the-events-calendar-premium',
			'addon_file'  => 'tk-event-weather-the-events-calendar.php',
		),
	);

	/**
	 * The HTML to send to the admin notice action.
	 *
	 * @var string
	 */
	private static $admin_notice_output = '';

	private static function detect_plugins_to_integrate_with() {
		// If get_plugins() isn't available, require it
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( \ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$all_installed_plugins = get_plugins();

		foreach ( self::$plugin_addons as $data ) {
			$full_plugin = sprintf( '%s/%s', $data['plugin_dir'], $data['plugin_file'] );

			$full_addon = sprintf( '%s/%s', $data['addon_dir'], $data['addon_file'] );

			if (
				array_key_exists( $full_plugin, $all_installed_plugins )
				&& ! array_key_exists( $full_addon, $all_installed_plugins )
			) {
				self::admin_notice_about_addon( $data['addon_dir'] );
			}
		}
	}

	/**
	 * Build the wp-admin notice to display and append it to the existing
	 * self::$admin_notice_output.
	 *
	 * @param $addon_dir
	 */
	private static function admin_notice_about_addon( $addon_dir ) {
		if (
			defined( 'TK_EVENT_WEATHER_DISABLE_UPSELLS' )
			&& true === (bool) TK_EVENT_WEATHER_DISABLE_UPSELLS
		) {
			return;
		}

		$plugin_name = self::get_plugin_name_from_addon_dir( $addon_dir );

		$message = sprintf(
			__( 'Want %1$s%3$s%2$s to work seamlessly with your %1$s%4$s%2$s plugin?', 'tk-event-weather' ),
			'<strong>',
			'</strong>',
			Setup::plugin_display_name(),
			$plugin_name
		);

		$addon_link_slug = str_replace( '-premium', '', $addon_dir );
		$link_to_addon   = tk_event_weather_freemius()->addon_url( $addon_link_slug );

		$link_text = esc_html__( 'Click here for pricing', 'tk-event-weather' );

		self::$admin_notice_output .= sprintf(
			'<div class="notice notice-info is-dismissible">
				<p>%s</p>
				<p><a href="%s">%s</a></p>
			</div>',
			$message,
			$link_to_addon,
			$link_text
		);
	}

	/**
	 * Get the plugin display name from its associated add-on.
	 *
	 * @param $addon_dir
	 *
	 * @return string
	 */
	private static function get_plugin_name_from_addon_dir( $addon_dir ) {
		// If get_plugins() isn't available, require it
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( \ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$plugin_name = '';

		foreach ( self::$plugin_addons as $data ) {
			if ( ! empty( $plugin_name ) ) {
				break;
			}

			if ( $addon_dir == $data['addon_dir'] ) {
				$plugin_data = get_plugins( '/' . $data['plugin_dir'] );

				$plugin_file_name = $data['plugin_file'];

				if ( ! empty( $plugin_data[ $plugin_file_name ]['Name'] ) ) {
					$plugin_name = $plugin_data[ $plugin_file_name ]['Name'];
				}
			}
		}

		return $plugin_name;
	}

	/**
	 * Output self::$admin_notice_output if on the Plugins List page or the
	 * TK Event Weather settings page.
	 *
	 * @see Life_Cycle::get_settings_slug
	 * @see Plugin::add_actions_and_filters
	 */
	public static function output_admin_notices() {
		global $pagenow;

		$screen = get_current_screen();

		$life_cycle = new Life_Cycle();

		// Will not display on the Add-ons wp-admin page because it adds "-addons" to the $screen->id. The same is true for other screens like Account, Contact Us, etc.
		$tkeventw_settings_screen = 'settings_page_' . $life_cycle->get_settings_slug();

		if (
			is_admin()
			&& (
				'plugins.php' === $pagenow
				|| $tkeventw_settings_screen === $screen->id
			)
			&& current_user_can( required_capability() )
		) {
			self::detect_plugins_to_integrate_with();
			echo self::$admin_notice_output;
		}
	}
}