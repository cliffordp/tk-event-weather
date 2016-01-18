<?php

// Option 3 from http://plugin.michael-simpson.com/?page_id=39

include_once('TkEventWeather_ShortCodeScriptLoader.php');

class TkEventWeather_TkEventWeatherShortcode extends TkEventWeather_ShortCodeScriptLoader {
    
    static $addedAlready = false;
    
    public function handleShortcode($atts) {
        return 'Hello World!';
    }
 
    public function addScript() {
        if (!self::$addedAlready) {
            self::$addedAlready = true;
            //wp_register_script('my-script', plugins_url('js/my-script.js', __FILE__), array('jquery'), '1.0', true);
            //wp_print_scripts('my-script');
        }
    }
    
}
