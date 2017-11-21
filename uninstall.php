<?php

namespace TKEventWeather;

/**
 * @link https://developer.wordpress.org/plugins/the-basics/uninstall-methods/
 * @link https://developer.wordpress.org/reference/functions/register_uninstall_hook/
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Need to load main plugin file to set the constants and load init.php
require_once( TK_EVENT_WEATHER_HYPHENS . '.php' );

$life_cyle = new Life_Cycle;
$life_cyle->uninstall();