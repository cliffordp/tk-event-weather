<?php

// e.g. https://plugins.trac.wordpress.org/browser/form-to-post/trunk/FormToPost_Plugin.php

include_once('TkEventWeather_LifeCycle.php');

class TkEventWeather_Plugin extends TkEventWeather_LifeCycle {
    
    private static $customizer_flag = 'tk_event_weather';
    
    private static $customizer_section_id = 'tk_event_weather_section';
    
    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            //'Forecast_io_API_Key' => array(__('Enter your <a href="https://developer.forecast.io/" target="_blank">Forecast.io API Key</a> (link opens in new window) (required)', 'tk-weather-for-tec')),
            //'Forecast_Time_of_Event' => array(__('Use start time or end time?', 'tk-weather-for-tec'), '', 'Start', 'End'),
            /*
              'CanDoSomething' => array(__('Which user role can do something', 'tk-weather-for-tec'),
                                        '', 'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber', 'Anyone'),
            */
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

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
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
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
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Example adding a script & style just for the options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
        //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        //            wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        }


        // Add Actions & Filters
        // http://plugin.michael-simpson.com/?page_id=37


        // Adding scripts & styles to all pages
        // Examples:
        //        wp_enqueue_script('jquery');
        //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));


        add_action( 'customize_register', array( $this, 'customizer_options' ) );
        
        add_filter( 'tk_event_weather_customizer_link', array( $this, 'customizer_options_link' ), 20 );
        
        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39


        // Register AJAX hooks
        // http://plugin.michael-simpson.com/?page_id=41

    }

    //
    // Start of Cliff's custom functions
    //
    
  	// Reference: https://github.com/cliffordp/mdl-shortcodes/blob/master/inc/class-mdl-shortcodes.php
  	public static function customizer_options_link(){
  		$url = 'customize.php';
  		  		
  		// get the page to return to (hit X on the Customizer)
  		//$url = add_query_arg( 'return', urlencode( admin_url( 'themes.php' ) ), $url );
  		
  		// add flag in the Customizer url so we know we're in this plugin's Customizer Section
  		$url = add_query_arg( self::$customizer_flag, 'true', $url );
  		
  		// auto-open the MDL Shortcodes editor
  		$url = add_query_arg( 'autofocus[section]', self::$customizer_section_id, $url );
  		
  		return $url;
  	}
    
    /**
     * Add plugin options to Customizer
     * See: https://developer.wordpress.org/themes/advanced-topics/customizer-api/
     */
    public function customizer_options( $wp_customize ) {
        
/*
    		// Customizer Panel
    		$wp_customize->add_panel(
    			'tk_event_weather_panel',
    			array(
    				'title'			=> $this->getPluginDisplayName(),
    				'description'	=> esc_html__('Plugin options and settings', 'tk-weather-for-tec'),
    				//'priority'		=> 10,
    			)
    		);
*/
    		
    		// Customizer Section
    		$wp_customize->add_section( self::$customizer_section_id,
    			array(
    				'title'       => $this->getPluginDisplayName(),
    				'description' => esc_html__( 'Plugin options and settings', 'tk-weather-for-tec' ),
    				//'priority'		=> 12,
    				//'panel'			=> 'tk_event_weather_panel',
    			)
    		);
    			
    			// Forecast.io API Key
    			// https://developer.wordpress.org/reference/functions/sanitize_key/ -- Lowercase alphanumeric characters, dashes and underscores are allowed. -- which matches Forecast.io's API Key pattern
    			$wp_customize->add_setting( 'tk_event_weather[forecast_io_api_key]', array(
    				'type'        => 'option',
    				'capability'  => 'edit_theme_options',
    				'default'     => '',
    				'sanitize_callback' => 'sanitize_key',
    			));
    			
    			$wp_customize->add_control( 'tk_event_weather_forecast_io_api_key_control', array(
      			'label'     => esc_html__( 'Forecast.io API Key', 'tk-weather-for-tec' ),
    				'description' => __( 'Enter your <a href="https://developer.forecast.io/" target="_blank">Forecast.io API Key</a> (link opens in new window)', 'tk-weather-for-tec' ),
    				'section'   => self::$customizer_section_id,
    				'settings'  => 'tk_event_weather[forecast_io_api_key]',
    				'type'		  => 'password',
    			));
    			
    }

}
