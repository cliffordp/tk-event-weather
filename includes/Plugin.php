<?php

// e.g. https://plugins.trac.wordpress.org/browser/form-to-post/trunk/FormToPost_Plugin.php

include_once( 'LifeCycle.php' );
require_once( 'Functions.php' );

class TkEventWeather__Plugin extends TkEventWeather__LifeCycle {

	private static $customizer_flag = 'tk_event_weather';

	private static $customizer_section_id = 'tk_event_weather_section';

	// public so add-ons can reference it
	public static $customizer_panel_id = 'tk_event_weather_panel';



	public function getPluginDisplayName() {
		return 'TK Event Weather';
	}

	protected function getMainPluginFileName() {
		return 'tk-event-weather.php';
	}

	/**
	* See: http://plugin.michael-simpson.com/?page_id=101
	* Called by install() to create any database tables if needed.
	* Best Practice:
	* (1) Prefix all table names with $wpdb->prefix
	* (2) make table names lower case only
	* @return void
	*/
	protected function installDatabaseTables() {
		//				global $wpdb;
		//				$tableName = $this->prefixTableName('mytable');
		//				$wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
		//						`id` INTEGER NOT NULL");
	}

	/**
	* See: http://plugin.michael-simpson.com/?page_id=101
	* Drop plugin-created tables on uninstall.
	* @return void
	*/
	protected function unInstallDatabaseTables() {
		//				global $wpdb;
		//				$tableName = $this->prefixTableName('mytable');
		//				$wpdb->query("DROP TABLE IF EXISTS `$tableName`");
	}


	/**
	* Perform actions when upgrading from version X to version Y
	* See: http://plugin.michael-simpson.com/?page_id=35
	* @return void
	*/
	public function upgrade() {
	}

	public function addActionsAndFilters() {

		// Add options administration page
		// http://plugin.michael-simpson.com/?page_id=47
		add_action('admin_menu', array($this, 'addSettingsSubMenuPage'));

		// Example adding a script & style just for the options administration page
		// http://plugin.michael-simpson.com/?page_id=47
		//				if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
		//						wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
		//						wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
		//				}


		// Add Actions & Filters
		// http://plugin.michael-simpson.com/?page_id=37


		// Adding scripts & styles to all pages
		// Examples:
		//				wp_enqueue_script('jquery');
		//				wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
		//				wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));


		add_action( 'customize_register', array( $this, 'customizer_options' ) );

		add_filter( 'tk_event_weather_customizer_link', array( $this, 'customizer_options_link' ), 20 );

		// Register short codes
		// http://plugin.michael-simpson.com/?page_id=39

		include_once( 'TkEventWeatherShortcode.php' );
		$sc = new TkEventWeather__TkEventWeatherShortcode();
		$sc->register( TkEventWeather__FuncSetup::$shortcode_name );


		// Register AJAX hooks
		// http://plugin.michael-simpson.com/?page_id=41

	}

	//
	// Start of Cliff's custom functions
	//

	public static function customizer_options_link(){
		$url = 'customize.php';

		// get the page to return to (hit X on the Customizer)
		//$url = add_query_arg( 'return', esc_url( admin_url( 'themes.php' ) ), $url );

		// add flag in the Customizer url so we know we're in this plugin's Customizer Section
		$url = add_query_arg( self::$customizer_flag, 'true', $url );

		// auto-open the panel
		$url = add_query_arg( 'autofocus[panel]', self::$customizer_panel_id, $url );

		$link_to_core_section = apply_filters( 'tk_event_weather_customizer_link_to_core_section', true );

		if ( true === $link_to_core_section ) {
			// auto-open the Core Settings section within the panel
			$url = add_query_arg( 'autofocus[section]', self::$customizer_section_id, $url );
		}

		return $url;
	}

	public static function customizer_edit_shortcut_setting(){
		// Always start at Dark Sky API Key if not entered
		$darksky_api_key = TkEventWeather__Functions::array_get_value_by_key ( TkEventWeather__Functions::plugin_options(), 'darksky_api_key' );

		if ( empty( $darksky_api_key ) ) {
			$setting = self::$customizer_flag . '[darksky_api_key]';

			// purposefully not filterable
			return $setting;
		}

		// default to Display Template
		$setting = self::$customizer_flag . '[display_template]';

		return apply_filters( 'tk_event_weather_customizer_edit_shortcut_setting', $setting );
	}

	/**
	* Add plugin options to Customizer
	* See: https://developer.wordpress.org/themes/advanced-topics/customizer-api/
	*/
	public function customizer_options( WP_Customize_Manager $wp_customize ) {

		// Add Edit Shortcuts
		// https://developer.wordpress.org/themes/advanced-topics/customizer-api/#selective-refresh-fast-accurate-updates
		$wp_customize->selective_refresh->add_partial( self::customizer_edit_shortcut_setting(), array(
			'selector'					=> '.tk-event-weather__wrapper',
			'container_inclusive'		=> true,
			'render_callback'			=> function() {
				// purposefully not set because the setting is dynamic
				// will just refresh it all, which is what we want anyway
			},
			// 'fallback_refresh'		=> true,
		) );

		// Customizer Panel
		$wp_customize->add_panel(
			self::$customizer_panel_id,
			array(
				'title'				=> $this->getPluginDisplayName(),
				'description'		=> esc_html__( 'Plugin options and settings', 'tk-event-weather' ),
				//'priority'		=> 10,
			)
		);


		// Customizer Section
		$wp_customize->add_section( self::$customizer_section_id,
			array(
				'title'				=> __( 'Core Settings', 'tk-event-weather' ),
				'description'		=> __( "Set this plugin's default <a href='https://codex.wordpress.org/Shortcode_API' target='_blank'>shortcode</a> arguments (link opens in new window). Since the plugin operates as a shortcode, you may override the defaults you set here on a per-shortcode basis.", 'tk-event-weather' ),
				'priority'			=> 1, // comment out if not using a custom Panel. With high priority within panel, add-ons will display below this "Core Settings" section
				'panel'				=> self::$customizer_panel_id,
			)
		);

			// Dark Sky API Key
			// https://developer.wordpress.org/reference/functions/sanitize_key/ -- Lowercase alphanumeric characters, dashes and underscores are allowed. -- which matches Dark Sky's API Key pattern
			$wp_customize->add_setting( self::$customizer_flag . '[darksky_api_key]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
				'sanitize_callback' => 'sanitize_key',
			));

			$wp_customize->add_control( self::$customizer_flag . '_darksky_api_key_control', array(
				'label'				=> esc_html__( 'Dark Sky API Key', 'tk-event-weather' ),
				'description'		=> __( 'Enter your <a href="https://darksky.net/dev/" target="_blank">Dark Sky API Key</a> (link opens in new window)', 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[darksky_api_key]',
				'type'				=> 'password',
			));

			// Google Maps API Key
			// e.g. AIza...URyTxC0w
			$wp_customize->add_setting( self::$customizer_flag . '[google_maps_api_key]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
				// 'sanitize_callback'	=> 'sanitize_key', // cannot use this because need to allow uppercase
			));

			$wp_customize->add_control( self::$customizer_flag . '_google_maps_api_key_control', array(
				'label'				=> esc_html__( 'Google Maps Geocoding API Key', 'tk-event-weather' ),
				'description'		=> __( 'Input your Standard (Free) or Premium <a href="https://developers.google.com/maps/documentation/geocoding/get-api-key" target="_blank">Google Maps Geocoding API Key</a> (link opens in new window) to use the <strong>location</strong> shortcode argument. When creating an API key: for "Where will you be calling the API from?", choose "Web server (e.g. node.js, Tomcat)".<br>Important Terms are documentend in the Tools tab of the plugin settings page.', 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[google_maps_api_key]',
				'type'				=> 'password',
			));

			// Display Template
			$wp_customize->add_setting( self::$customizer_flag . '[display_template]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
			));

			$wp_customize->add_control( self::$customizer_flag . '_display_template_control', array(
				'label'				=> esc_html__( 'Default Display Template', 'tk-event-weather' ),
				'description'		=> esc_html__( 'Choose your default Display Template. If left blank, default will be "Hourly Horizontal".', 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[display_template]',
				'type'				=> 'select',
				'choices'			=> TkEventWeather__Functions::valid_display_templates( 'true' ),
			));

			// Hours Time Format
			$wp_customize->add_setting( self::$customizer_flag . '[time_format_hours]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
				'sanitize_callback'	=> 'sanitize_text_field',
			));

			$wp_customize->add_control( self::$customizer_flag . '_time_format_hours_control', array(
				'label'				=> esc_html__( 'Hourly Time Format', 'tk-event-weather' ),
				'description'		=> sprintf( __( "Per hour time format. Default: ga (e.g. 7am)%sReference %s and %sthe Codex's Formatting Date and Time%s for available time formats (links open in new window).", 'tk-event-weather' ), '<br>', '<a href="https://codex.wordpress.org/Function_Reference/date_i18n" target="_blank">date_i18n()</a>', '<a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">', '</a>' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[time_format_hours]',
				'type'				=> 'text',
			));

			// Minutes Time Format (Sunrise/Sunset Format)
			$wp_customize->add_setting( self::$customizer_flag . '[time_format_minutes]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
				'sanitize_callback'	=> 'sanitize_text_field',
			));

			$wp_customize->add_control( self::$customizer_flag . '_time_format_minutes_control', array(
				'label'				=> esc_html__( 'Minutes Time Format', 'tk-event-weather' ),
				'description'		=> esc_html__( 'Time format used for sunrises and sunsets. Default: g:i (e.g. 7:12am)' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[time_format_minutes]',
				'type'				=> 'text',
			));

			// Past cutoff days
			$wp_customize->add_setting( self::$customizer_flag . '[cutoff_past_days]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
				'sanitize_callback'	=> array( 'TkEventWeather__Functions', 'sanitize_absint_allow_blank' ),
			));

			$wp_customize->add_control( self::$customizer_flag . '_cutoff_past_days_control', array(
				'label'				=> esc_html__( 'Past cutoff (in days)', 'tk-event-weather' ),
				'description'		=> __( 'If datetime is this far in the past, do not output the forecast. Enter zero for "no limit".<br>Example: "30" would disable weather more than 30 days in the past.<br>Default: 30', 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[cutoff_past_days]',
				'type'				=> 'text',
			));

			// Future cutoff days
			$wp_customize->add_setting( self::$customizer_flag . '[cutoff_future_days]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
				'sanitize_callback'	=> array( 'TkEventWeather__Functions', 'sanitize_absint_allow_blank' ),
			));

			$wp_customize->add_control( self::$customizer_flag . '_cutoff_future_days_control', array(
				'label'				=> esc_html__( 'Future cutoff (in days)', 'tk-event-weather' ),
				'description'		=> __( 'If datetime is this far in the future, do not output the forecast. Enter zero for "no limit".<br>Example: "365" would disable weather more than 1 year in the future.<br>Default: 365', 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[cutoff_future_days]',
				'type'				=> 'text',
			));

			// Units
			$wp_customize->add_setting( self::$customizer_flag . '[darksky_units]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
			));

			$wp_customize->add_control( self::$customizer_flag . '_darksky_units_control', array(
				'label'				=> esc_html__( 'Units', 'tk-event-weather' ),
				'description'		=> __( 'Although it is recommended to leave this as "Auto", you may choose to force returning the weather data in specific units.<br>Reference: <a href="https://darksky.net/dev/docs/time-machine" target="_blank">Dark Sky API Docs > Options</a> (link opens in new window)', 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[darksky_units]',
				'type'				=> 'select',
				'choices'			=> TkEventWeather__Functions::darksky_option_units( 'true' ),
			));

			// Time Zone Sources
			$wp_customize->add_setting( self::$customizer_flag . '[timezone_source]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
			));

			$wp_customize->add_control( self::$customizer_flag . '_timezone_source_control', array(
				'label'				=> esc_html__( 'Time Zone Source', 'tk-event-weather' ),
				'description'		=> __( "In which time zone should hourly times be displayed?<br><strong>From API</strong> means times will be displayed per location. For example, if an event on your site is in New York City, the weather times get displayed in New York City time even if your WordPress time zone is set to Honolulu, Hawaii or UTC-10.<br><strong>From Wordpress</strong> means all weather times display in your WordPress time zone. From the example above, the event in New York City would have its weather displayed in Honolulu time.<br>(If you do not see WordPress as an option here, please first set it in your General Settings.)<br>Default: From API", 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[timezone_source]',
				'type'				=> 'select',
				'choices'			=> TkEventWeather__Functions::valid_timezone_sources( 'true' ),
			));

			// Transient expiration in hours
			$wp_customize->add_setting( self::$customizer_flag . '[transients_expiration_hours]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
				'sanitize_callback'	=> array( 'TkEventWeather__Functions', 'sanitize_absint_allow_blank' ),
			));

			$wp_customize->add_control( self::$customizer_flag . '_transients_expiration_hours_control', array(
				'label'				=> esc_html__( 'Dark Sky transient expiration (in hours)', 'tk-event-weather' ),
				'description'		=> __( 'If stored Dark Sky API data is older than this many hours, pull fresh weather data from the API.<br>Default: 12<br>Note: Google Maps Geocoding API transients are always set to 30 days.', 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[transients_expiration_hours]',
				'type'				=> 'text',
			));

			// Disable transients
			$wp_customize->add_setting( self::$customizer_flag . '[transients_off]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
			));

			$wp_customize->add_control( self::$customizer_flag . '_transients_off_control', array(
				'label'				=> esc_html__( 'Disable Transients', 'tk-event-weather' ),
				'description'		=> __( 'The <a href="https://codex.wordpress.org/Transients_API" target="_blank">WordPress Transients API</a> (link opens in new window) is used to reduce repetitive API calls and improve performance. Check this box if you wish to disable using Transients (suggested only for testing purposes).<br>Note: Applies to both Dark Sky and Google Maps Geocoding API transients.', 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[transients_off]',
				'type'				=> 'checkbox',
				'choices'			=> array( 'true' => __( 'Disable', 'tk-event-weather' ) ),
			));

			// Disable Sunrise/Sunset
			$wp_customize->add_setting( self::$customizer_flag . '[sunrise_sunset_off]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
			));

			$wp_customize->add_control( self::$customizer_flag . '_sunrise_sunset_off_control', array(
				'label'				=> esc_html__( 'Disable Sunrise/Sunset', 'tk-event-weather' ),
				'description'		=> __( 'Check this box to disable including sunrise and sunset times into the hourly weather views.', 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[sunrise_sunset_off]',
				'type'				=> 'checkbox',
				'choices'			=> array( 'true' => __( 'Disable', 'tk-event-weather' ) ),
			));

			// Disable Plugin Credit Link
			$wp_customize->add_setting( self::$customizer_flag . '[plugin_credit_link_on]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
			));

			$wp_customize->add_control( self::$customizer_flag . '_plugin_credit_link_on_control', array(
				'label'				=> esc_html__( 'Enable display of the plugin credit link', 'tk-event-weather' ),
				'description'		=> __( "<strong>Check this box to turn on</strong> linking to the TK Event Weather plugin's home page. <strong>We sure appreciate it!</strong>", 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[plugin_credit_link_on]',
				'type'				=> 'checkbox',
				'choices'			=> array( 'true' => __( 'Enable', 'tk-event-weather' ) ),
			));

			// Disable Dark Sky Credit Link
			$wp_customize->add_setting( self::$customizer_flag . '[darksky_credit_link_off]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
			));

			$wp_customize->add_control( self::$customizer_flag . '_darksky_credit_link_off_control', array(
				'label'				=> esc_html__( 'Disable display of the Dark Sky credit link', 'tk-event-weather' ),
				'description'		=> __( "Check this box to disable linking to Dark Sky<br>You should not check this box without permission from Dark Sky, per their Terms of Use.", 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[darksky_credit_link_off]',
				'type'				=> 'checkbox',
				'choices'			=> array( 'true' => __( 'Disable', 'tk-event-weather' ) ),
			));


			// Enable Debug Mode
			$wp_customize->add_setting( self::$customizer_flag . '[debug_on]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
			));

			$wp_customize->add_control( self::$customizer_flag . '_debug_on_control', array(
				'label'				=> esc_html__( 'Enable Debug Mode for this plugin', 'tk-event-weather' ),
				'description'		=> __( 'Prints extra information to the page only for Administrators.<br>Warning: Likely exposes your API key(s) to all Administrators.', 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[debug_on]',
				'type'				=> 'checkbox',
				'choices'			=> array( 'true' => __( 'Enable', 'tk-event-weather' ) ),
			));


/*
			// Icons
			$wp_customize->add_setting( self::$customizer_flag . '[icons]', array(
				'type'				=> 'option',
				'capability'		=> 'edit_theme_options',
				'default'			=> '',
			));

			$wp_customize->add_control( self::$customizer_flag . '_icons_control', array(
				'label'				=> esc_html__( 'Icons settings', 'tk-event-weather' ),
				'description'		=> __( '', 'tk-event-weather' ),
				'section'			=> self::$customizer_section_id,
				'settings'			=> self::$customizer_flag . '[sunrise_sunset_off]',
				'type'				=> 'select',
				'choices'			=> TkEventWeather__Functions::valid_icon_type( 'true' ),
			));
*/


	} // end customizer_options()

}
