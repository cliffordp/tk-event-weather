<?php
namespace TKEventWeather;

// e.g. https://plugins.trac.wordpress.org/browser/form-to-post/trunk/FormToPost_Plugin.php

class Plugin extends Life_Cycle {

	private static $customizer_flag = 'tk_event_weather';

	private static $customizer_section_id = 'tk_event_weather_section';

	// public so add-ons can reference it
	public static $customizer_panel_id = 'tk_event_weather_panel';


	public function get_plugin_display_name() {
		return 'TK Event Weather';
	}

	protected function get_main_plugin_file_name() {
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
	protected function install_database_tables() {
		//				global $wpdb;
		//				$tableName = $this->prefix_table_name('mytable');
		//				$wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
		//						`id` INTEGER NOT NULL");
	}

	/**
	 * See: http://plugin.michael-simpson.com/?page_id=101
	 * Drop plugin-created tables on uninstall.
	 * @return void
	 */
	protected function uninstall_database_tables() {
		//				global $wpdb;
		//				$tableName = $this->prefix_table_name('mytable');
		//				$wpdb->query("DROP TABLE IF EXISTS `$tableName`");
	}


	public function activate() {

	}

	public function deactivate() {
	}

	/**
	 * Perform actions when upgrading from version X to version Y
	 * See: http://plugin.michael-simpson.com/?page_id=35
	 * @return void
	 */
	public function upgrade() {
		$upgrade_ok    = true;
		$saved_version = $this->get_version_saved();
		if ( $this->is_version_less_than( $saved_version, '1.5' ) ) {
			// TODO: delete old options, including transients https://wordpress.stackexchange.com/a/75758/22702 -- delete leftover Plugin::$customizer_flag array keys like ^forecast_io%
		}

		// Post-upgrade, set the current version in the options
		$code_version = $this->get_version();
		if ( $upgrade_ok && $saved_version != $code_version ) {
			$this->save_installed_version();
		}
	}

	public function add_actions_and_filters() {

		// Add options administration page
		// http://plugin.michael-simpson.com/?page_id=47
		add_action( 'admin_menu', array( $this, 'add_settings_sub_menu_page' ) );

		// Example adding a script & style just for the options administration page
		// http://plugin.michael-simpson.com/?page_id=47
		//				if (strpos($_SERVER['REQUEST_URI'], $this->get_settings_slug()) !== false) {
		//						wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
		//						wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
		//				}


		// Add Actions & Filters
		// http://plugin.michael-simpson.com/?page_id=37

		add_action( 'template_redirect', array( $this, 'register_assets' ), 0 );

		// WP Admin Bar: add TK Event Weather settings link under Customizer item
		add_action( 'admin_bar_menu', array( $this, 'customizer_link_to_edit_current_url' ) );


		// Adding scripts & styles to all pages
		// Examples:
		//				wp_enqueue_script('jquery');
		//				wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
		//				wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));


		add_action( 'customize_register', array( $this, 'customizer_options' ) );

		add_filter( 'tk_event_weather_customizer_link', array( $this, 'customizer_options_link' ), 20 );

		// Register short codes
		// http://plugin.michael-simpson.com/?page_id=39

		$shortcode = new Shortcode();
		$shortcode->register( Setup::$shortcode_name );


		// Register AJAX hooks
		// http://plugin.michael-simpson.com/?page_id=41

	}

	//
	// Start of Cliff's custom functions
	//

	public static function register_assets() {
		Functions::register_css();
		Functions::register_climacons_css();
	}

	/**
	 * Add Customizer URL query parameters to a given link.
	 *
	 * @param $url                  The URL to add Customizer URL query
	 *                              parameters to.
	 * @param $deep_link_to_section Optional to deep link into a particular
	 *                              section, such as 'display', 'api_dark_sky',
	 *                              'api_google', or 'advanced'.
	 *
	 * @return string
	 */
	private static function convert_link_to_a_customizer_link( $url, $deep_link_to_section = '' ) {
		// add flag in the Customizer url so we know we're in this plugin's Customizer Section
		$url = add_query_arg( self::$customizer_flag, 'true', $url );

		// auto-open the panel
		$url = add_query_arg( 'autofocus[panel]', self::$customizer_panel_id, $url );

		if ( ! empty( $deep_link_to_section ) ) {
			// e.g. 'display' becomes self::$customizer_section_id . '_display'
			$section_id = sprintf( '%s_%s', self::$customizer_section_id, $deep_link_to_section );
			$url        = add_query_arg( 'autofocus[section]', $section_id, $url );
		}

		return $url;
	}

	/**
	 * WP Admin Bar: add TK Event Weather settings link under Customizer item
	 *
	 * Based on wp_admin_bar_customize_menu()
	 *
	 * @param $wp_adminbar
	 */
	public static function customizer_link_to_edit_current_url( $wp_adminbar ) {
		if (
			is_customize_preview()
			|| is_admin()
		) {
			return;
		}

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$url = add_query_arg( 'url', urlencode( $current_url ), wp_customize_url() );

		$url = self::convert_link_to_a_customizer_link( $url );

		$wp_adminbar->add_node( array(
			'id'     => 'tkeventweather_edit_page',
			'title'  => __( 'Open in TK Event Weather settings', 'tk-event-weather' ),
			'parent' => 'customize',
			'href'   => $url,
			'meta'   => array(
				'class' => 'hide-if-no-customize',
			),
		) );
	}

	public static function customizer_options_link() {
		$url = wp_customize_url();

		$url = self::convert_link_to_a_customizer_link( $url );

		return $url;
	}

	public function customizer_edit_shortcut_setting() {
		// Always start at Dark Sky API Key if not entered
		$darksky_api_key = Functions::array_get_value_by_key( Functions::plugin_options(), 'darksky_api_key' );

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
	 * See: https://developer.wordpress.org/themes/customize-api/
	 */
	public function customizer_options( \WP_Customize_Manager $wp_customize ) {

		// Add Edit Shortcuts
		// https://developer.wordpress.org/themes/customize-api/tools-for-improved-user-experience/#selective-refresh-fast-accurate-updates
		$wp_customize->selective_refresh->add_partial(
			self::customizer_edit_shortcut_setting(), array(
				'selector'            => '.tk-event-weather__wrapper',
				'container_inclusive' => true,
				'render_callback'     => function () {
					// purposefully not set because the setting is dynamic
					// will just refresh it all, which is what we want anyway
				},
				// 'fallback_refresh'		=> true,
			)
		);

		// Customizer Panel
		$wp_customize->add_panel(
			self::$customizer_panel_id,
			array(
				'title'       => $this->get_plugin_display_name(),
				'description' => esc_html__( 'Plugin options and settings', 'tk-event-weather' ),
			)
		);


		// Customizer Sections
		$wp_customize->add_section(
			self::$customizer_section_id . '_display',
			array(
				'title'       => __( 'Display', 'tk-event-weather' ),
				'description' => __( 'Templates, Text, and Date/Time Format settings', 'tk-event-weather' ),
				'priority'    => 10,
				'panel'       => self::$customizer_panel_id,
			)
		);

		$wp_customize->add_section(
			self::$customizer_section_id . '_api_dark_sky',
			array(
				'title'       => __( 'Dark Sky API', 'tk-event-weather' ),
				'description' => __( 'Dark Sky API settings', 'tk-event-weather' ),
				'priority'    => 30,
				// comment out if not using a custom Panel. With high priority within panel, add-ons will display below this "Core Settings" section
				'panel'       => self::$customizer_panel_id,
			)
		);

		$wp_customize->add_section(
			self::$customizer_section_id . '_api_google',
			array(
				'title'       => __( 'Google Maps API', 'tk-event-weather' ),
				'description' => __( 'Google Maps API settings', 'tk-event-weather' ),
				'priority'    => 40,
				// comment out if not using a custom Panel. With high priority within panel, add-ons will display below this "Core Settings" section
				'panel'       => self::$customizer_panel_id,
			)
		);

		$wp_customize->add_section(
			self::$customizer_section_id . '_advanced',
			array(
				'title'       => __( 'Advanced', 'tk-event-weather' ),
				'description' => __( 'Transients and Debug settings', 'tk-event-weather' ),
				'priority'    => 50,
				'panel'       => self::$customizer_panel_id,
			)
		);

		// Dark Sky API Key
		// https://developer.wordpress.org/reference/functions/sanitize_key/ -- Lowercase alphanumeric characters, dashes and underscores are allowed. -- which matches Dark Sky's API Key pattern
		// Display Template
		$wp_customize->add_setting(
			self::$customizer_flag . '[display_template]', array(
				'type'    => 'option',
				'default' => '',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_display_template_control', array(
				'label'       => esc_html__( 'Default Display Template', 'tk-event-weather' ),
				'description' => esc_html__( 'Choose your default Display Template. If left blank, default will be "Hourly Horizontal".', 'tk-event-weather' ),
				'section'     => self::$customizer_section_id . '_display',
				'settings'    => self::$customizer_flag . '[display_template]',
				'type'        => 'select',
				'choices'     => Template::valid_display_templates( 'true' ),
			)
		);

		// Disable horizontal scrolling
		$wp_customize->add_setting(
			self::$customizer_flag . '[scroll_horizontal_off]', array(
				'type'    => 'option',
				'default' => '',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_scroll_horizontal_off_control', array(
				'label'       => esc_html__( 'Disable Horizontal Scrolling', 'tk-event-weather' ),
				'description' => __( 'If checked, the horizontal scrolling stylesheet will not load and, therefore, it will wrap to multiple rows and there will not be a horizontal scroll bar.', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_display',
				'settings'    => self::$customizer_flag . '[scroll_horizontal_off]',
				'type'        => 'checkbox',
				'choices'     => array( 'true' => __( 'Disable', 'tk-event-weather' ) ),
			)
		);

		// Disable vertical to columns
		$wp_customize->add_setting(
			self::$customizer_flag . '[vertical_to_columns_off]', array(
				'type'    => 'option',
				'default' => '',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_vertical_to_columns_off_control', array(
				'label'       => esc_html__( 'Disable Vertical to Columns', 'tk-event-weather' ),
				'description' => __( 'If checked, the vertical columns stylesheet will not load and, therefore, each day displayed vertically will be below the previous day.', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_display',
				'settings'    => self::$customizer_flag . '[vertical_to_columns_off]',
				'type'        => 'checkbox',
				'choices'     => array( 'true' => __( 'Disable', 'tk-event-weather' ) ),
			)
		);

		// Disable Sunrise/Sunset
		$wp_customize->add_setting(
			self::$customizer_flag . '[sunrise_sunset_off]', array(
				'type'    => 'option',
				'default' => '',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_sunrise_sunset_off_control', array(
				'label'       => esc_html__( 'Disable Sunrise/Sunset', 'tk-event-weather' ),
				'description' => __( 'Check this box to disable including sunrise and sunset times into the hourly weather views.', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_display',
				'settings'    => self::$customizer_flag . '[sunrise_sunset_off]',
				'type'        => 'checkbox',
				'choices'     => array( 'true' => __( 'Disable', 'tk-event-weather' ) ),
			)
		);

		// Before Text
		$wp_customize->add_setting(
			self::$customizer_flag . '[text_before]', array(
				'type'              => 'option',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_text_before_control', array(
				'label'       => esc_html__( 'Text before Forecast display', 'tk-event-weather' ),
				'description' => __( 'What text should be displayed before the hourly weather? (h3 tag)<br>Example: Forecast<br>Default: none', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_display',
				'settings'    => self::$customizer_flag . '[text_before]',
				'type'        => 'text',
			)
		);

		// After Text
		$wp_customize->add_setting(
			self::$customizer_flag . '[text_after]', array(
				'type'              => 'option',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_text_after_control', array(
				'label'       => esc_html__( 'Text after Forecast display', 'tk-event-weather' ),
				'description' => __( 'What text should be displayed after the hourly weather? (p tag)<br>Example: a disclaimer about the weather not being guaranteed<br>Default: none', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_display',
				'settings'    => self::$customizer_flag . '[text_after]',
				'type'        => 'text',
			)
		);

		// Day Format (before each forecast)
		$wp_customize->add_setting(
			self::$customizer_flag . '[time_format_day]', array(
				'type'              => 'option',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_time_format_day_control', array(
				'label'       => esc_html__( 'Day Format', 'tk-event-weather' ),
				'description' => sprintf( __( "Per day date format. Default: M j (e.g. Oct 7)%sReference %s and %sthe Codex's Formatting Date and Time%s for available time formats (links open in new window).", 'tk-event-weather' ), '<br>', '<a href="https://codex.wordpress.org/Function_Reference/date_i18n" target="_blank">date_i18n()</a>', '<a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">', '</a>' ),
				'section'     => self:: $customizer_section_id . '_display',
				'settings'    => self::$customizer_flag . '[time_format_day]',
				'type'        => 'text',
			)
		);

		// Hours Time Format
		$wp_customize->add_setting(
			self::$customizer_flag . '[time_format_hours]', array(
				'type'              => 'option',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_time_format_hours_control', array(
				'label'       => esc_html__( 'Hourly Time Format', 'tk-event-weather' ),
				'description' => __( 'Per hour time format. Default: ga (e.g. 7am)', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_display',
				'settings'    => self::$customizer_flag . '[time_format_hours]',
				'type'        => 'text',
			)
		);

		// Minutes Time Format (Sunrise/Sunset Format)
		$wp_customize->add_setting(
			self::$customizer_flag . '[time_format_minutes]', array(
				'type'              => 'option',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_time_format_minutes_control', array(
				'label'       => esc_html__( 'Minutes Time Format', 'tk-event-weather' ),
				'description' => esc_html__( 'Time format used for sunrises and sunsets. Default: g:i (e.g. 7:12am)' ),
				'section'     => self:: $customizer_section_id . '_display',
				'settings'    => self::$customizer_flag . '[time_format_minutes]',
				'type'        => 'text',
			)
		);

		// Enable Plugin Credit Link
		$wp_customize->add_setting(
			self::$customizer_flag . '[plugin_credit_link_on]', array(
				'type'    => 'option',
				'default' => '',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_plugin_credit_link_on_control', array(
				'label'       => esc_html__( 'Enable display of the plugin credit link', 'tk-event-weather' ),
				'description' => __( "<strong>Check this box to turn on</strong> linking to the TK Event Weather plugin's home page. <strong>We sure appreciate it!</strong>", 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_display',
				'settings'    => self::$customizer_flag . '[plugin_credit_link_on]',
				'type'        => 'checkbox',
				'choices'     => array( 'true' => __( 'Enable', 'tk-event-weather' ) ),
			)
		);

		// Disable Dark Sky Credit Link
		$wp_customize->add_setting(
			self::$customizer_flag . '[darksky_credit_link_off]', array(
				'type'    => 'option',
				'default' => '',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_darksky_credit_link_off_control', array(
				'label'       => esc_html__( 'Disable display of the Dark Sky credit link', 'tk-event-weather' ),
				'description' => __( "Check this box to disable linking to Dark Sky.<br>You should not check this box without permission from Dark Sky, per their Terms of Use.", 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_display',
				'settings'    => self::$customizer_flag . '[darksky_credit_link_off]',
				'type'        => 'checkbox',
				'choices'     => array( 'true' => __( 'Disable', 'tk-event-weather' ) ),
			)
		);

		$wp_customize->add_setting(
			self::$customizer_flag . '[darksky_api_key]', array(
				'type'              => 'option',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_darksky_api_key_control', array(
				'label'       => esc_html__( 'Dark Sky API Key', 'tk-event-weather' ),
				'description' => __( 'Enter your <a href="https://darksky.net/dev/" target="_blank">Dark Sky API Key</a> (link opens in new window)', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_api_dark_sky',
				'settings'    => self::$customizer_flag . '[darksky_api_key]',
				'type'        => 'password',
				/**
				 * Avoid the nagging LastPass prompt upon each WP Customizer save
				 * @link https://developer.mozilla.org/en-US/docs/Web/Security/Securing_your_site/Turning_off_form_autocompletion
				 * @link https://www.chromium.org/developers/design-documents/form-styles-that-chromium-understands
				 * @link https://lastpass.com/support.php?cmd=showfaq&id=10512
				 */
				'input_attrs' => array(
					'data-lpignore' => 'true',
					'autocomplete'  => 'new-password',
				),
			)
		);

		// Multi-Day control
		$wp_customize->add_setting(
			self::$customizer_flag . '[multi_day_limit]', array(
				'type'              => 'option',
				'default'           => '',
				'sanitize_callback' => array( 'Functions', 'sanitize_absint_allow_blank' ),
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_multi_day_limit_control', array(
				'label'       => esc_html__( 'Multi-day forecast limit', 'tk-event-weather' ),
				'description' => __( "This is to protect you against too many API calls at once due to a typo between the Start Date and End Date, for example. <strong>Change this to 1 to disable multi-day forecasts</strong>, or increase it beyond 10 if you really want to. Note that each calendar day of a forecast request will cost 1 Dark Sky API credit. Example: December 2 at 7pm to December 4 at 5am would be 3 days.<br><strong>Default: 10</strong>", 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_api_dark_sky',
				'settings'    => self::$customizer_flag . '[multi_day_limit]',
				'type'        => 'text',
			)
		);

		// Multi-Day force showing past days while Today is in the span of days
		$wp_customize->add_setting(
			self::$customizer_flag . '[multi_day_ignore_start_at_today]', array(
				'type'    => 'option',
				'default' => '',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_multi_day_ignore_start_at_today_control', array(
				'label'       => esc_html__( 'Disable considering Today into multi-day forecasts', 'tk-event-weather' ),
				'description' => __( 'If a multi-day forecast starts prior to Today but ends on or after Today, the multi-day forecast will start at Today. Once the entire multi-day span is in the past, the entire span of days will display according to your "Past cutoff (in days)" setting. Example: Today is July 7 and the forecast spans July 6-10; with this box unchecked, July 7-10 will be displayed. On July 11, July 6-10 will be displayed.', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_api_dark_sky',
				'settings'    => self::$customizer_flag . '[multi_day_ignore_start_at_today]',
				'type'        => 'checkbox',
				'choices'     => array( 'true' => __( 'Disable', 'tk-event-weather' ) ),
			)
		);

		// Past cutoff days
		$wp_customize->add_setting(
			self::$customizer_flag . '[cutoff_past_days]', array(
				'type'              => 'option',
				'default'           => '',
				'sanitize_callback' => array( 'Functions', 'sanitize_absint_allow_blank' ),
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_cutoff_past_days_control', array(
				'label'       => esc_html__( 'Past cutoff (in days)', 'tk-event-weather' ),
				'description' => __( 'If datetime is this far in the past, do not output the forecast. Enter zero for "no limit".<br>Example: "30" would disable weather more than 30 days in the past.<br>Default: 30', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_api_dark_sky',
				'settings'    => self::$customizer_flag . '[cutoff_past_days]',
				'type'        => 'text',
			)
		);

		// Future cutoff days
		$wp_customize->add_setting(
			self::$customizer_flag . '[cutoff_future_days]', array(
				'type'              => 'option',
				'default'           => '',
				'sanitize_callback' => array( 'Functions', 'sanitize_absint_allow_blank' ),
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_cutoff_future_days_control', array(
				'label'       => esc_html__( 'Future cutoff (in days)', 'tk-event-weather' ),
				'description' => __( 'If datetime is this far in the future, do not output the forecast. Enter zero for "no limit".<br>Example: "365" would disable weather more than 1 year in the future.<br>Default: 365', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_api_dark_sky',
				'settings'    => self::$customizer_flag . '[cutoff_future_days]',
				'type'        => 'text',
			)
		);

		// Units
		$wp_customize->add_setting(
			self::$customizer_flag . '[darksky_units]', array(
				'type'    => 'option',
				'default' => '',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_darksky_units_control', array(
				'label'       => esc_html__( 'Units', 'tk-event-weather' ),
				'description' => __( 'Although it is recommended to leave this as "Auto", you may choose to force returning the weather data in specific units.<br>Reference: <a href="https://darksky.net/dev/docs#request-parameters" target="_blank">Dark Sky API Docs</a> (link opens in new window)', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_api_dark_sky',
				'settings'    => self::$customizer_flag . '[darksky_units]',
				'type'        => 'select',
				'choices'     => API_Dark_Sky::valid_units( 'true' ),
			)
		);

		// Language
		$wp_customize->add_setting(
			self::$customizer_flag . '[darksky_language]', array(
				'type'    => 'option',
				'default' => '',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_darksky_language_control', array(
				'label'       => esc_html__( 'Language', 'tk-event-weather' ),
				'description' => __( 'Language for the summary text(s).<br>Reference: <a href="https://darksky.net/dev/docs#request-parameters" target="_blank">Dark Sky API Docs</a> (link opens in new window)<br><strong>Default: English</strong>', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_api_dark_sky',
				'settings'    => self::$customizer_flag . '[darksky_language]',
				'type'        => 'select',
				'choices'     => API_Dark_Sky::valid_languages( 'true' ),
			)
		);

		// Timezone Sources
		$wp_customize->add_setting(
			self::$customizer_flag . '[timezone_source]', array(
				'type'    => 'option',
				'default' => '',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_timezone_source_control', array(
				'label'       => esc_html__( 'Timezone Source', 'tk-event-weather' ),
				'description' => __( "In which timezone should hourly times be displayed?<br><strong>From API</strong> means times will be displayed per location. For example, if an event on your site is in New York City, the weather times get displayed in New York City time even if your WordPress timezone is set to Honolulu, Hawaii or UTC-10.<br><strong>From Wordpress</strong> means all weather times display in your WordPress timezone. From the example above, the event in New York City would have its weather displayed in Honolulu time.<br>(If you do not see WordPress as an option here, please first set it in your General Settings.)<br>Default: From API", 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_api_dark_sky',
				'settings'    => self::$customizer_flag . '[timezone_source]',
				'type'        => 'select',
				'choices'     => Time::valid_timezone_sources( 'true' ),
			)
		);

		// Google Maps API Key
		// e.g. AIza...URyTxC0w
		$wp_customize->add_setting(
			self::$customizer_flag . '[google_maps_api_key]', array(
				'type'    => 'option',
				'default' => '',
				// 'sanitize_callback'	=> 'sanitize_key', // cannot use this because need to allow uppercase
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_google_maps_api_key_control', array(
				'label'       => esc_html__( 'Google Maps Geocoding API Key', 'tk-event-weather' ),
				'description' => __( 'Input your Standard (Free) or Premium <a href="https://developers.google.com/maps/documentation/geocoding/get-api-key" target="_blank">Google Maps Geocoding API Key</a> (link opens in new window) to use the <strong>location</strong> shortcode argument. When creating an API key: for "Where will you be calling the API from?", choose "Web server (e.g. node.js, Tomcat)".<br>Important Terms are documentend in the Tools tab of the plugin settings page.', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_api_google',
				'settings'    => self::$customizer_flag . '[google_maps_api_key]',
				'type'        => 'password',
				'input_attrs' => array(
					'data-lpignore' => 'true',
					'autocomplete'  => 'new-password',
				),
			)
		);

		// Transient expiration in hours
		$wp_customize->add_setting(
			self::$customizer_flag . '[transients_expiration_hours]', array(
				'type'              => 'option',
				'default'           => '',
				'sanitize_callback' => array( 'Functions', 'sanitize_absint_allow_blank' ),
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_transients_expiration_hours_control', array(
				'label'       => esc_html__( 'Dark Sky transient expiration (in hours)', 'tk-event-weather' ),
				'description' => __( 'If stored Dark Sky API data is older than this many hours, pull fresh weather data from the API.<br>Default: 12<br>Note: Google Maps Geocoding API transients are always set to 30 days.', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_advanced',
				'settings'    => self::$customizer_flag . '[transients_expiration_hours]',
				'type'        => 'text',
			)
		);

		// Disable transients
		$wp_customize->add_setting(
			self::$customizer_flag . '[transients_off]', array(
				'type'    => 'option',
				'default' => '',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_transients_off_control', array(
				'label'       => esc_html__( 'Disable Transients', 'tk-event-weather' ),
				'description' => __( 'The <a href="https://codex.wordpress.org/Transients_API" target="_blank">WordPress Transients API</a> (link opens in new window) is used to reduce repetitive API calls and improve performance. Check this box if you wish to disable using Transients (suggested only for testing purposes).<br>Note: Applies to both Dark Sky and Google Maps Geocoding API transients.', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_advanced',
				'settings'    => self::$customizer_flag . '[transients_off]',
				'type'        => 'checkbox',
				'choices'     => array( 'true' => __( 'Disable', 'tk-event-weather' ) ),
			)
		);

		// Enable Debug Mode
		$wp_customize->add_setting(
			self::$customizer_flag . '[debug_on]', array(
				'type'    => 'option',
				'default' => '',
			)
		);

		$wp_customize->add_control(
			self::$customizer_flag . '_debug_on_control', array(
				'label'       => esc_html__( 'Enable Debug Mode for this plugin', 'tk-event-weather' ),
				'description' => __( 'Prints extra information to the page only for Administrators.<br>Warning: Likely exposes your API key(s) to all Administrators.', 'tk-event-weather' ),
				'section'     => self:: $customizer_section_id . '_advanced',
				'settings'    => self::$customizer_flag . '[debug_on]',
				'type'        => 'checkbox',
				'choices'     => array( 'true' => __( 'Enable', 'tk-event-weather' ) ),
			)
		);


		/*
					// Icons
					$wp_customize->add_setting( self::$customizer_flag . '[icons]', array(
						'type'				=> 'option',
						'default'			=> '',
					));

					$wp_customize->add_control( self::$customizer_flag . '_icons_control', array(
						'label'				=> esc_html__( 'Icons settings', 'tk-event-weather' ),
						'description'		=> __( '', 'tk-event-weather' ),
						'section'			=> self:: $customizer_section_id . '_display',
						'settings'			=> self::$customizer_flag . '[sunrise_sunset_off]',
						'type'				=> 'select',
						'choices'			=> Functions::valid_icon_type( 'true' ),
					));
		*/


	} // end customizer_options()

}
