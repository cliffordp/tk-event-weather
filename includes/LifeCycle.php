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

include_once( 'InstallIndicator.php' );

class TKEventWeather_Life_Cycle extends TKEventWeather_Install_Indicator {

	public function install() {
		// Initialize DB Tables used by the plugin
		$this->install_database_tables();

		// Other Plugin initialization - for the plugin writer to override as needed
		$this->other_install();

		// Record the installed version
		$this->save_installed_version();

		// To avoid running install() more then once
		$this->mark_as_installed();
	}

	public function uninstall() {
		$this->other_uninstall();
		$this->uninstall_database_tables();
		$this->delete_saved_options();
		$this->mark_as_uninstalled();
	}

	/**
	 * Perform any version-upgrade activities prior to activation (e.g. database changes)
	 * @return void
	 */
	public function upgrade() {
	}

	/**
	 * See: http://plugin.michael-simpson.com/?page_id=105
	 * @return void
	 */
	public function activate() {
	}

	/**
	 * See: http://plugin.michael-simpson.com/?page_id=105
	 * @return void
	 */
	public function deactivate() {
	}

	public function add_actions_and_filters() {
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
	}

	/**
	 * See: http://plugin.michael-simpson.com/?page_id=101
	 * Drop plugin-created tables on uninstall.
	 * @return void
	 */
	protected function uninstall_database_tables() {
	}

	/**
	 * Override to add any additional actions to be done at install time
	 * See: http://plugin.michael-simpson.com/?page_id=33
	 * @return void
	 */
	protected function other_install() {
	}

	/**
	 * Override to add any additional actions to be done at uninstall time
	 * See: http://plugin.michael-simpson.com/?page_id=33
	 * @return void
	 */
	protected function other_uninstall() {
	}

	/**
	 * Puts the configuration page in the Plugins menu by default.
	 * Override to put it elsewhere or create a set of submenus
	 * Override with an empty implementation if you don't want a configuration page
	 * @return void
	 */
	public function add_settings_sub_menu_page() {
		//$this->add_settings_submenu_page_to_plugins_menu();
		$this->add_settings_submenu_page_to_settings_menu();
	}


	protected function require_extra_plugin_files() {
		require_once( ABSPATH . 'wp-includes/pluggable.php' );
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	/**
	 * @return string Slug name for the URL to the Setting page
	 * (i.e. the page for setting options)
	 *
	 * Lower case to increase compatibility with Freemius and general standards
	 */
	protected function get_settings_slug() {
		return strtolower( get_class( $this ) . 'settings' );
	}

	protected function add_settings_submenu_page_to_plugins_menu() {
		$this->require_extra_plugin_files();
		$display_name = $this->get_plugin_display_name();
		add_submenu_page(
			'plugins.php',
			$display_name,
			$display_name,
			'manage_options',
			$this->get_settings_slug(),
			array( $this, 'settings_page' )
		);
	}


	protected function add_settings_submenu_page_to_settings_menu() {
		$this->require_extra_plugin_files();
		$display_name = $this->get_plugin_display_name();
		add_options_page(
			$display_name,
			$display_name,
			'manage_options',
			$this->get_settings_slug(),
			array( $this, 'settings_page' )
		);
	}

	/**
	 * @param    $name string name of a database table
	 *
	 * @return string input prefixed with the WordPress DB table prefix
	 * plus the prefix for this plugin (lower-cased) to avoid table name collisions.
	 * The plugin prefix is lower-cases as a best practice that all DB table names are lower case to
	 * avoid issues on some platforms
	 */
	protected function prefix_table_name( $name ) {
		global $wpdb;

		return $wpdb->prefix . strtolower( $this->prefix( $name ) );
	}


	/**
	 * Convenience function for creating AJAX URLs.
	 *
	 * @param $action_name string the name of the ajax action registered in a call like
	 *                    add_action('wp_ajax_action_name', array($this, 'function_name'));
	 *                    and/or
	 *                    add_action('wp_ajax_nopriv_action_name', array($this, 'function_name'));
	 *
	 * If have an additional parameters to add to the Ajax call, e.g. an "id" parameter,
	 * you could call this function and append to the returned string like:
	 *        $url = $this->get_ajax_url('myaction&id=') . urlencode($id);
	 * or more complex:
	 *        $url = sprintf($this->get_ajax_url('myaction&id=%s&var2=%s&var3=%s'), urlencode($id), urlencode($var2), urlencode($var3));
	 *
	 * @return string URL that can be used in a web page to make an Ajax call to $this->function_name
	 */
	public function get_ajax_url( $action_name ) {
		return admin_url( 'admin-ajax.php' ) . '?action=' . $action_name;
	}

}
