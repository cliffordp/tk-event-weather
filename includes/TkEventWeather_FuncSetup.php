<?php

class TkEventWeather_FuncSetup {
  // all variables and methods should be 'static'
  
  public static $transient_name_prepend = 'tkeventw';
  
  // https://wordpress.org/about/requirements/
  
  public static $min_allowed_version_php = '5.4';
  
  public static $min_allowed_version_mysql = '5.0';
  
  public static $min_allowed_version_wordpress = '4.3.0';
  
  public static $support_email_address = 'tko+support-tk-event-weather@tourkick.com';
  
  /**
    *
    * Plugin Directories
    *
    */
  
  public static function plugin_dir_path_root() {
    return TK_EVENT_WEATHER_PLUGIN_ROOT_DIR; // from root plugin file
  }
  
  // https://developer.wordpress.org/reference/functions/plugin_dir_path/ does include trailing slash
  public static function plugin_dir_path_includes() {
    return self::plugin_dir_path_root() . 'includes/'; // e.g. /Users/cmp/Documents/git/GitHub/tk-event-weather/includes/
  }
  
  public static function plugin_dir_path_vendor() {
    return self::plugin_dir_path_includes() . 'vendor/'; // e.g. /Users/cmp/Documents/git/GitHub/tk-event-weather/includes/vendor/
  }
  
  public static function plugin_dir_path_views() {
    return self::plugin_dir_path_includes() . 'views/'; // e.g. /Users/cmp/Documents/git/GitHub/tk-event-weather/includes/views/
  }
  
  /**
    *
    * Plugin URLs
    *
    */
  
  // https://developer.wordpress.org/reference/functions/plugin_dir_url/ does respect HTTPS and does include trailing slash
  public static function plugin_dir_url_includes() {
    return plugin_dir_url( __FILE__ ); // e.g. http://example.com/wp-content/plugins/tk-event-weather/includes/
  }
  
  public static function plugin_dir_url_root() {
    // remove "includes/" from end of string (requires escaping the last backslash)
    return preg_replace( '/(includes\/$)/', '', self::plugin_dir_url_includes() ); // e.g. http://example.com/wp-content/plugins/tk-event-weather/
  }
  
  public static function plugin_dir_url_vendor() {
    return self::plugin_dir_url_includes() . 'vendor/';
  }
    
  // no need for URL to 'views' directory
 
}