<?php
/*
	"WordPress Plugin Template" Copyright (C) 2016 Michael Simpson	(email : michael.d.simpson@gmail.com)

	This file is part of WordPress Plugin Template for WordPress.

	WordPress Plugin Template is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	WordPress Plugin Template is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Contact Form to Database Extension.
	If not, see http://www.gnu.org/licenses/gpl-3.0.html
*/

include_once( 'ShortCodeLoader.php' );

/**
 * Adapted from this excellent article:
 * http://scribu.net/wordpress/optimal-script-loading.html
 *
 * The idea is you have a shortcode that needs a script loaded, but you only
 * want to load it if the shortcode is actually called.
 */
abstract class TKEventWeather_Shortcode_Script_Loader extends TKEventWeather_Shortcode_Loader {

	var $do_add_script;

	public function register( $shortcode_name ) {
		$this->register_shortcode_to_function( $shortcode_name, 'handle_shortcode_wrapper' );

		// It will be too late to enqueue the script in the header,
		// but can add them to the footer
		add_action( 'wp_footer', array( $this, 'add_script_wrapper' ) );
	}

	public function handle_shortcode_wrapper( $atts ) {
		// Flag that we need to add the script
		$this->do_add_script = true;

		return $this->handle_shortcode( $atts );
	}


	public function add_script_wrapper() {
		// Only add the script if the shortcode was actually called
		if ( $this->do_add_script ) {
			$this->add_script();
		}
	}

	/**
	 * @abstract override this function with calls to insert scripts needed by your shortcode in the footer
	 * Example:
	 *    wp_register_script('my-script', plugins_url('js/my-script.js', __FILE__), array('jquery'), '1.0', true);
	 *    wp_print_scripts('my-script');
	 * @return void
	 */
	public abstract function add_script();

}
