<?php

namespace TKEventWeather;

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

class Options_Manager {

	public function get_option_metadata() {
		//  http://plugin.michael-simpson.com/?page_id=31
		return array(
			//'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
			//'DropOnUninstall' => array(__('Drop this plugin\'s Database table on uninstall', 'TEXT_DOMAIN'), 'false', 'true')
		);
	}

	/**
	 * Remove the prefix from the input $name.
	 * Idempotent: If no prefix found, just returns what was input.
	 *
	 * @param    $name string
	 *
	 * @return string $optionName without the prefix.
	 */
	public function &un_prefix( $name ) {
		$option_name_prefix = $this->get_option_name_prefix();
		if ( strpos( $name, $option_name_prefix ) === 0 ) {
			return substr( $name, strlen( $option_name_prefix ) );
		}

		return $name;
	}

	/**
	 * Generates the prefix for the various database option names.
	 *
	 * Lowercase, ending with an underscore.
	 * Example prefix: tk_event_weather_
	 * Becomes this database option: tk_event_weather_installed
	 *
	 * @return string
	 */
	public function get_option_name_prefix() {
		return TK_EVENT_WEATHER_UNDERSCORES . '_';
	}

	/**
	 * A wrapper function delegating to WP delete_option() but it prefixes the input $optionName
	 * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
	 *
	 * @param    $optionName string defined in settings.php and set as keys of $this->optionMetaData
	 *
	 * @return bool from delegated call to delete_option()
	 */
	public function delete_option( $optionName ) {
		$prefixed_option_name = $this->prefix( $optionName ); // how it is stored in DB

		return delete_option( $prefixed_option_name );
	}

	/**
	 * Get the prefixed version input $name suitable for storing in WP options
	 * Idempotent: if $optionName is already prefixed, it is not prefixed again, it is returned without change
	 *
	 * @param    $name string option name to prefix. Defined in settings.php and set as keys of $this->optionMetaData
	 *
	 * @return string
	 */
	public function prefix( $name ) {
		$option_name_prefix = $this->get_option_name_prefix();
		if ( strpos( $name, $option_name_prefix ) === 0 ) { // 0 but not false
			return $name; // already prefixed
		}

		return $option_name_prefix . $name;
	}

	/**
	 * A wrapper function delegating to WP add_option() but it prefixes the input $optionName
	 * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
	 *
	 * @param    $optionName string defined in settings.php and set as keys of $this->optionMetaData
	 * @param    $value      mixed the new value
	 *
	 * @return null from delegated call to delete_option()
	 */
	public function update_option( $optionName, $value ) {
		$prefixed_option_name = $this->prefix( $optionName ); // how it is stored in DB

		return update_option( $prefixed_option_name, $value );
	}

	/**
	 * @param    $option_name string name of a Role option (see comments in getRoleOption())
	 *
	 * @return bool indicates if the user has adequate permissions
	 */
	public function can_user_do_role_option( $option_name ) {
		$role_allowed = $this->get_role_option( $option_name );
		if ( 'Anyone' == $role_allowed ) {
			return true;
		}

		return $this->is_user_role_equal_or_better_than( $role_allowed );
	}

	/**
	 * A Role Option is an option defined in get_option_metadata() as a choice of WP standard roles, e.g.
	 * 'CanDoOperationX' => array('Can do Operation X', 'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber')
	 * The idea is use an option to indicate what role level a user must minimally have in order to do some operation.
	 * So if a Role Option 'CanDoOperationX' is set to 'Editor' then users which role 'Editor' or above should be
	 * able to do Operation X.
	 * Also see: canUserDoRoleOption()
	 *
	 * @param    $optionName
	 *
	 * @return string role name
	 */
	public function get_role_option( $optionName ) {
		$role_allowed = $this->get_option( $optionName );
		if ( ! $role_allowed || $role_allowed == '' ) {
			$role_allowed = 'Administrator';
		}

		return $role_allowed;
	}

	/**
	 * A wrapper function delegating to WP get_option() but it prefixes the input $optionName
	 * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
	 *
	 * @param $optionName string defined in settings.php and set as keys of $this->optionMetaData
	 * @param $default    string default value to return if the option is not set
	 *
	 * @return string the value from delegated call to get_option(), or optional default value
	 * if option is not set.
	 */
	public function get_option( $optionName, $default = null ) {
		$prefixed_option_name = $this->prefix( $optionName ); // how it is stored in DB

		$ret_val = get_option( $prefixed_option_name );

		if ( ! $ret_val && $default ) {
			$ret_val = $default;
		}

		return $ret_val;
	}

	/**
	 * @param $role_name string a standard WP role name like 'Administrator'
	 *
	 * @return bool
	 */
	public function is_user_role_equal_or_better_than( $role_name ) {
		if ( 'Anyone' == $role_name ) {
			return true;
		}
		$capability = $this->role_to_capability( $role_name );

		return current_user_can( $capability );
	}

	/**
	 * Given a WP role name, return a WP capability which only that role and roles above it have
	 * @link https://codex.wordpress.org/Roles_and_Capabilities#User_Level_to_Role_Conversion
	 *
	 * @param    $role_name
	 *
	 * @return string a WP capability or '' if unknown input role
	 */
	protected function role_to_capability( $role_name ) {
		switch ( $role_name ) {
			case 'Network Administrator':
				return 'manage_network'; // multisite only
			case 'Administrator':
				return 'manage_options';
			case 'Customizer': // applies to Administrators by default
				return 'customize'; // since WP 4.0
			case 'Editor':
				return 'publish_pages';
			case 'Author':
				return 'publish_posts';
			case 'Contributor':
				return 'edit_posts';
			case 'Subscriber':
				return 'read';
			case 'Anyone':
				return 'read';
		}

		return '';
	}

	/**
	 * Creates HTML for the Administration page to set options for this plugin.
	 * Override this method to create a customized page.
	 * @return void
	 */
	public function settings_page() {
		$capability = required_capability();
		if ( ! current_user_can( $capability ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'tk-event-weather' ) );
		}

		// HTML for the page
		?>


		<div class="wrap">
			<h1><?php echo $this->get_plugin_display_name();
				echo ' ';
				_e( 'Settings', 'tk-event-weather' ); ?></h1>

			<?php
			// Greeting Box
			?>
			<div style="width: 80%; padding: 20px; margin: 20px; background-color: #fff; font-size: 120%;">
				<?php
				$tourkick_logo = Setup::plugin_dir_url_images() . 'tourkick-logo-square-300.png';
				printf( '<a href="http://tourkick.com/" target="_blank"><img style="float: left; margin: 5px 40px 10px 10px;" width="100" height="100" src="%s"></a>', $tourkick_logo );
				?>
				<?php
				$addons_url = tk_event_weather_freemius()->addon_url( '' );
				if ( ! empty( $addons_url ) ) {
					?>
					<p style="font-size: 120%;">
						<?php
						printf(
							esc_html__( 'Check out the %sTK Event Weather add-on plugins%s to automatically integrate with popular WordPress calendars!', 'tk-event-weather' ),
							'<a href="' . tk_event_weather_freemius()->addon_url( '' ) . '">',
							'</a>'
						);
						?>
					</p>
					<?php
				}
				?>
				<br>
				<p>
					<a href="http://b.tourkick.com/tkeventw-rate-5-stars"
					   target="_blank"><?php esc_html_e( 'Share your 5-Star Review on WordPress.org', 'tk-event-weather' ); ?></a>
				</p>
				<p>
					<a href="http://b.tourkick.com/github-tk-event-weather" target="_blank">Contribute via GitHub</a>
				</p>
				<p><?php esc_html_e( 'Find me online', 'tk-event-weather' ); ?>:
					<a
						href="http://b.tourkick.com/twitter-follow-tourkick" target="_blank">Twitter</a> |
					<a
						href="http://b.tourkick.com/facebook-tourkick" target="_blank">Facebook</a> |
					<a
						href="http://b.tourkick.com/cliffpaulick-w-org-profile-plugins" target="_blank">WordPress
						Profile</a> |
					<a href="http://b.tourkick.com/tourkick-com" target="_blank">Website</a>
				</p>
				<hr>
				<p style="font-style: italic;"><?php echo terms_agreement_text(); ?></p>
			</div>


			<?php
			$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'options';
			$active_tab = wp_kses_post( esc_attr( $active_tab ) );
			?>
			<h2 class="nav-tab-wrapper">
				<a href="<?php printf( '?page=%s&tab=options', $this->get_settings_slug() ); ?>"
				   class="nav-tab <?php echo $active_tab == 'options' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Options', 'tk-event-weather' ); ?></a>
				<a href="<?php printf( '?page=%s&tab=tools', $this->get_settings_slug() ); ?>"
				   class="nav-tab <?php echo $active_tab == 'tools' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Tools', 'tk-event-weather' ); ?></a>
				<a href="<?php printf( '?page=%s&tab=help', $this->get_settings_slug() ); ?>"
				   class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Help', 'tk-event-weather' ); ?></a>
			</h2>
			<?php
			if ( $active_tab == 'options' ) {
				?>
				<p><?php _e( "This plugin uses the WordPress Customizer to set its options.", 'tk-event-weather' ); ?></p>
				<p><?php _e( "Click the button below to be taken directly to this plugin's section within the WordPress Customizer.", 'tk-event-weather' ); ?></p>
				<p>
					<a href="<?php echo apply_filters( TK_EVENT_WEATHER_UNDERSCORES . '_customizer_link', get_admin_url( get_current_blog_id(), 'customize.php' ) ); ?>"
					   class="button-primary">
						<?php _e( 'Edit Plugin Settings in WP Customizer', 'tk-event-weather' ) ?>
					</a>
				</p>
				<br><br>
				<?php
				/**
				 * Commented out until method exists to return a string for direct URL to re-prompt anonymous users with opt-in -- https://github.com/Freemius/wordpress-sdk/issues/42
				 * because connect_again() doesn't return a string to be used like this (as-is will re-prompt anonymous users every time they visit the plugin's settings page)
				 * and because Edit Freemius Settings button (then click Delete All Accounts button) is only for my development/testing, not for users
				 *
				 * if ( ! empty ( tk_event_weather_freemius()->is_anonymous() ) ) {
				 * printf ( '<p><a href="%s" class="button-secondary">%s</a></p>', tk_event_weather_freemius()->connect_again(), __( 'Connect to Freemius!', 'tk-event-weather' ) );
				 * } else {
				 * // maybe https://developer.wordpress.org/reference/functions/network_admin_url/ ?
				 * printf ( '<p><a href="%s" class="button-secondary">%s</a></p>', admin_url( 'admin.php?page=freemius' ), __( 'Edit Freemius Settings', 'tk-event-weather' ) );
				 * } // freemius is_anonymous()
				 */
			} // options tab
			elseif ( $active_tab == 'tools' ) {
			?>
			<!-- Hide because of overlapping Google Map iframe -->
			<style type="text/css">#wpfooter {
					display: none;
				}</style>
			<h2><?php _e( 'Shortcode Examples', 'tk-event-weather' ); ?></h2>
			<p><?php printf( __( "For your reference, over a dozen examples are available at %sthis plugin's WordPress.org FAQs%s. (link opens in new window)", 'tk-event-weather' ), '<a href="https://wordpress.org/plugins-wp/tk-event-weather/#faq" target="_blank">', '</a>' ); ?>
			</p>
		</div>
	<br>
		<h2><?php _e( 'Google Maps', 'tk-event-weather' ); ?></h2>
		<p style="font-style: italic;"><?php printf(
				__( 'By using Google Maps, including the %slocation%s shortcode argument, you are agreeing to be bound by %s (link opens in new window).', 'tk-event-weather' ),
				'<strong>',
				'</strong>',
				'<a href="https://developers.google.com/maps/terms" target="_blank">Google\'s Terms of Service</a>'
			);
			?></p>
		<p><?php _e( 'Powered by Google.', 'tk-event-weather' ); ?></p>
		<h2><?php _e( 'Geocoding&mdash;Find Latitude and Longitude Coordinates', 'tk-event-weather' ); ?></h2>
		<p><?php printf(
				__( 'To help you find the Latitude and Longitude of a location to use in your shortcode, you may do a Google Maps lookup here without %sAPI usage limitations%s (link opens in new window). Type an address or place name, get the coordinates, manually paste them into wherever you are using the shortcode', 'tk-event-weather' ),
				'<a href="https://developers.google.com/maps/faq#usage-limits" target="_blank">',
				'</a>'
			); ?>:</p>
		<iframe style="text-align: center; margin-left: 10%; margin-right: 10%; width: 80%; min-width: 300px;"
				name="Google Maps API Geocoder Tool" src="http://b.tourkick.com/google-maps-geocoder"
				height="575" width="800">
			<p>Your browser does not support iframes. Please visit the <a
					href="http://b.tourkick.com/google-maps-geocoder" target="_blank">Google Maps API
					Geocoder Tool</a> directly.</p>
		</iframe>
	<br><br>
		<?php
	} // docs tab
	elseif ( $active_tab == 'help' ) {
		// modified from https://github.com/woothemes/woocommerce/blob/master/includes/admin/views/html-admin-page-status-report.php
		// reference (outdated screenshots): https://docs.woothemes.com/document/understanding-the-woocommerce-system-status-report/
		?>
		<style>
			table.tkeventweather_status_table {
				margin-bottom: 1em
			}

			table.tkeventweather_status_table tr:nth-child(2n) td,
			table.tkeventweather_status_table tr:nth-child(2n) th {
				background: #fcfcfc
			}

			table.tkeventweather_status_table th {
				font-weight: 700;
				padding: 9px
			}

			table.tkeventweather_status_table td:first-child {
				width: 33%
			}

			table.tkeventweather_status_table td mark {
				background: 0 0
			}

			table.tkeventweather_status_table td mark.yes {
				color: #7ad03a
			}

			table.tkeventweather_status_table td mark.no {
				color: #999
			}

			table.tkeventweather_status_table td mark.error {
				color: #a00
			}

			#debug-report {
				display: none;
				margin: 10px 0;
				padding: 0;
				position: relative
			}

			#debug-report textarea {
				font-family: monospace;
				width: 100%;
				margin: 0;
				height: 300px;
				padding: 20px;
				-moz-border-radius: 0;
				-webkit-border-radius: 0;
				border-radius: 0;
				resize: none;
				font-size: 12px;
				line-height: 20px;
				outline: 0
			}
		</style>
		<div class="updated inline">
			<p><?php _e( 'Please copy and paste this information in your ticket when contacting support:', 'tk-event-weather' ); ?> </p>
			<p class="submit"><a href="#"
								 class="button-primary debug-report"><?php _e( 'Get System Report', 'tk-event-weather' ); ?></a>
			</p>
			<div id="debug-report">
				<textarea readonly="readonly"></textarea>
				<h3 id="copy-for-support">&#x21b3;
					<?php
					// http://htmlarrows.com/arrows/down-arrow-with-tip-right/
					_e( 'Copy and send to Support', 'tk-event-weather' );
					// http://htmlarrows.com/arrows/down-arrow-with-corner-left/
					?>
					&#x21b5;
				</h3>
				<hr>
				<p><?php _e( 'And/Or you might want to send your personal computer specifications:', 'tk-event-weather' ); ?></p>
				<p><a target="_blank"
					  href="<?php printf( 'http://supportdetails.com/?sender_name=%s&sender=%s&recipient=%s', urlencode( get_home_url() ), urlencode( get_bloginfo( 'admin_email' ) ), urlencode( Setup::$support_email_address ) ); ?>"
					  class="button-secondary support-details"><?php _e( 'Get Personal Computer Details', 'tk-event-weather' ); ?></a>
				</p>
				<p>
					<em><?php _e( 'Then click the "Send Details" button from that site!', 'tk-event-weather' ); ?></em>
				</p>
			</div>
		</div>

		<table class="tkeventweather_status_table widefat" cellspacing="0" id="status">
			<thead>
			<tr>
				<th colspan="3" data-export-label="WordPress Environment">
					<h2><?php _e( 'WordPress Environment', 'tk-event-weather' ); ?></h2>
				</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td data-export-label="Home URL"><?php _e( 'Home URL', 'tk-event-weather' ); ?>:</td>
				<td><?php form_option( 'home' ); ?></td>
			</tr>
			<tr>
				<td data-export-label="Site URL"><?php _e( 'Site URL', 'tk-event-weather' ); ?>:</td>
				<td><?php form_option( 'siteurl' ); ?></td>
			</tr>

			<tr>
				<td data-export-label="WP Version"><?php _e( 'WP Version', 'tk-event-weather' ); ?>:</td>
				<td><?php
					$wordpress_version = get_bloginfo( 'version' );
					if ( version_compare( $wordpress_version, Setup::$min_allowed_version_wordpress, '<' ) ) {
						echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf(
								__( '%s - This plugin requires a minimum WordPress version of %s. See: %s', 'tk-event-weather' ),
								esc_html( $wordpress_version ),
								Setup::$min_allowed_version_wordpress,
								'<a href="https://codex.wordpress.org/WordPress_Versions" target="_blank">' . __( 'WordPress Version History', 'tk-event-weather' ) . '</a>'
							) . '</mark>';
					} else {
						echo '<mark class="yes">' . esc_html( $wordpress_version ) . '</mark>';
					}
					?></td>
			</tr>
			<tr>
				<td data-export-label="WP Multisite"><?php _e( 'WP Multisite Enabled', 'tk-event-weather' ); ?>
					:
				</td>
				<td><?php if ( is_multisite() ) {
						echo '<span class="dashicons dashicons-yes"></span>';
					} else {
						echo '&ndash;';
					} ?></td>
			</tr>
			<tr>
				<td data-export-label="WP Memory Limit"><?php _e( 'WP Memory Limit', 'tk-event-weather' ); ?>:
				</td>
				<td><?php
					$memory = Functions::php_size_string_to_integer( \WP_MEMORY_LIMIT );
					if ( function_exists( 'memory_get_usage' ) ) {
						$system_memory = @ini_get( 'memory_limit' );
						$system_memory = Functions::php_size_string_to_integer( $system_memory );
						$memory        = max( $memory, $system_memory );
					}
					if ( $memory < 67108864 ) { // '64M' in bytes
						echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( '%s - We recommend setting memory to at least 64MB. See: %s', 'tk-event-weather' ), size_format( $memory ), '<a href="http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP" target="_blank">' . __( 'Increasing memory allocated to PHP', 'tk-event-weather' ) . '</a>' ) . '</mark>';
					} else {
						echo '<mark class="yes">' . size_format( $memory ) . '</mark>';
					}
					?></td>
			</tr>
			<tr>
				<td data-export-label="WP Debug Mode"><?php _e( 'WP Debug Mode', 'tk-event-weather' ); ?>:</td>
				<td>
					<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
						<mark class="yes">
							<span class="dashicons dashicons-yes"></span></mark>
					<?php else : ?>
						<mark class="no">&ndash;</mark>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td data-export-label="WP Cron"><?php _e( 'WP Cron', 'tk-event-weather' ); ?>:</td>
				<td>
					<?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?>
						<mark class="no">&ndash;</mark>
					<?php else : ?>
						<mark class="yes">
							<span class="dashicons dashicons-yes"></span></mark>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td data-export-label="Permalinks"><?php _e( 'Permalink Structure', 'tk-event-weather' ); ?>:
				</td>
				<td><?php echo esc_html( get_option( 'permalink_structure' ) ); ?></td>
			</tr>
			<tr>
				<td data-export-label="WP Language"><?php _e( 'WP Language', 'tk-event-weather' ); ?>:</td>
				<td><?php echo get_locale(); ?></td>
			</tr>
			<tr>
				<td data-export-label="WP Timezone"><?php _e( 'WP Timezone', 'tk-event-weather' ); ?>:</td>
				<td><?php echo get_option( 'timezone_string' ); ?></td>
			</tr>
			<tr>
				<td data-export-label="WP GMT Offset"><?php _e( 'WP GMT Offset', 'tk-event-weather' ); ?>:</td>
				<td><?php echo get_option( 'gmt_offset' ); ?></td>
			</tr>
			<tr>
				<td data-export-label="WP Date Format"><?php _e( 'WP Date Format', 'tk-event-weather' ); ?>:
				</td>
				<td><?php echo get_option( 'date_format' ); ?></td>
			</tr>
			<tr>
				<td data-export-label="WP Time Format"><?php _e( 'WP Time Format', 'tk-event-weather' ); ?>:
				</td>
				<td><?php echo get_option( 'time_format' ); ?></td>
			</tr>
			</tbody>
		</table>
		<table class="tkeventweather_status_table widefat" cellspacing="0">
			<thead>
			<tr>
				<th colspan="3" data-export-label="Server Environment">
					<h2><?php _e( 'Server Environment', 'tk-event-weather' ); ?></h2>
				</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td data-export-label="Server Info"><?php _e( 'Server Info', 'tk-event-weather' ); ?>:</td>
				<td><?php echo esc_html( $_SERVER['SERVER_SOFTWARE'] ); ?></td>
			</tr>
			<tr>
				<td data-export-label="PHP Version"><?php _e( 'PHP Version', 'tk-event-weather' ); ?>:</td>
				<td><?php
					// Check if phpversion function exists.
					if ( function_exists( 'phpversion' ) ) {
						$php_version = phpversion();
						if ( version_compare( $php_version, min_php_version(), '<' ) ) {
							echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf(
									__( '%s - This plugin requires a minimum PHP version of %s. See: %s', 'tk-event-weather' ),
									esc_html( $php_version ),
									min_php_version(),
									'<a href="http://docs.woothemes.com/document/how-to-update-your-php-version/" target="_blank">' . __( 'How to update your PHP version', 'tk-event-weather' ) . '</a>'
								) . '</mark>';
						} else {
							echo '<mark class="yes">' . esc_html( $php_version ) . '</mark>';
						}
					} else {
						_e( "Couldn't determine PHP version because phpversion() doesn't exist.", 'tk-event-weather' );
					}
					?></td>
			</tr>
			<?php if ( function_exists( 'ini_get' ) ) : ?>
				<tr>
					<td data-export-label="PHP Post Max Size"><?php _e( 'PHP Post Max Size', 'tk-event-weather' ); ?>
						:
					</td>
					<td><?php
						$post_max_size = ini_get( 'post_max_size' );
						$post_max_size = Functions::php_size_string_to_integer( $post_max_size );
						echo size_format( $post_max_size );
						?></td>
				</tr>
				<tr>
					<td data-export-label="PHP Time Limit"><?php _e( 'PHP Timeout Limit', 'tk-event-weather' ); ?>
						:
					</td>
					<td><?php echo ini_get( 'max_execution_time' ); ?></td>
				</tr>
				<tr>
					<td data-export-label="PHP Max Input Vars"><?php _e( 'PHP Max Input Variables', 'tk-event-weather' ); ?>
						:
					</td>
					<td><?php echo ini_get( 'max_input_vars' ); ?></td>
				</tr>
				<tr>
					<td data-export-label="SUHOSIN Installed"><?php _e( 'SUHOSIN Installed', 'tk-event-weather' ); ?>
						:
					</td>
					<td><?php echo extension_loaded( 'suhosin' ) ? '<span class="dashicons dashicons-yes"></span>' : '&ndash;'; ?></td>
				</tr>
			<?php endif; ?>
			<tr>
				<td data-export-label="MySQL Version"><?php _e( 'MySQL Version', 'tk-event-weather' ); ?>:</td>
				<td>
					<?php
					/** @global wpdb $wpdb */
					global $wpdb;
					$mysql_version = $wpdb->db_version();
					if ( version_compare( $mysql_version, Setup::$min_allowed_version_mysql, '<' ) ) {
						echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf(
								__( '%s - This plugin requires a minimum MySQL version of %s. See: %s', 'tk-event-weather' ),
								esc_html( $mysql_version ),
								Setup::$min_allowed_version_mysql,
								'<a href="https://wordpress.org/about/requirements/" target="_blank">' . __( 'WordPress Requirements', 'tk-event-weather' ) . '</a>'
							) . '</mark>';
					} else {
						echo '<mark class="yes">' . esc_html( $mysql_version ) . '</mark>';
					}
					?>
				</td>
			</tr>
			<tr>
				<td data-export-label="Max Upload Size"><?php _e( 'Max Upload Size', 'tk-event-weather' ); ?>:
				</td>
				<td><?php echo size_format( wp_max_upload_size() ); ?></td>
			</tr>
			<tr>
				<td data-export-label="Default Timezone is UTC"><?php _e( 'Default Server Timezone is UTC', 'tk-event-weather' ); ?>
					:
				</td>
				<td><?php
					$default_timezone = date_default_timezone_get();
					if ( 'UTC' !== $default_timezone ) {
						echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( 'Default timezone is %s - it should be UTC', 'tk-event-weather' ), $default_timezone ) . '</mark>';
					} else {
						echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
					} ?>
				</td>
			</tr>
			<?php
			$posting = array();
			// fsockopen/cURL.
			$posting['fsockopen_curl']['name'] = 'fsockopen/cURL';
			if ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ) {
				$posting['fsockopen_curl']['success'] = true;
			} else {
				$posting['fsockopen_curl']['success'] = false;
				$posting['fsockopen_curl']['note']    = __( 'Your server does not have fsockopen or cURL enabled - PayPal IPN and other scripts which communicate with other servers will not work. Contact your hosting provider.', 'tk-event-weather' );
			}
			// SOAP.
			$posting['soap_client']['name'] = 'SoapClient';
			if ( class_exists( 'SoapClient' ) ) {
				$posting['soap_client']['success'] = true;
			} else {
				$posting['soap_client']['success'] = false;
				$posting['soap_client']['note']    = sprintf( __( 'Your server does not have the %s class enabled - some gateway plugins which use SOAP may not work as expected.', 'tk-event-weather' ), '<a href="http://php.net/manual/en/class.soapclient.php">SoapClient</a>' );
			}
			// DOMDocument.
			$posting['dom_document']['name'] = 'DOMDocument';
			if ( class_exists( 'DOMDocument' ) ) {
				$posting['dom_document']['success'] = true;
			} else {
				$posting['dom_document']['success'] = false;
				$posting['dom_document']['note']    = sprintf( __( 'Your server does not have the %s class enabled - HTML/Multipart emails, and also some extensions, will not work without DOMDocument.', 'tk-event-weather' ), '<a href="http://php.net/manual/en/class.domdocument.php">DOMDocument</a>' );
			}
			// GZIP.
			$posting['gzip']['name'] = 'GZip';
			if ( is_callable( 'gzopen' ) ) {
				$posting['gzip']['success'] = true;
			} else {
				$posting['gzip']['success'] = false;
				$posting['gzip']['note']    = sprintf( __( 'Your server does not support the %s function - this is required to use the GeoIP database from MaxMind. The API fallback will be used instead for geolocation.', 'tk-event-weather' ), '<a href="http://php.net/manual/en/zlib.installation.php">gzopen</a>' );
			}
			// Multibyte String.
			$posting['mbstring']['name'] = 'Multibyte String';
			if ( extension_loaded( 'mbstring' ) ) {
				$posting['mbstring']['success'] = true;
			} else {
				$posting['mbstring']['success'] = false;
				$posting['mbstring']['note']    = sprintf( __( 'Your server does not support the %s functions - this is required for better character encoding. Some fallbacks will be used instead for it.', 'tk-event-weather' ), '<a href="http://php.net/manual/en/mbstring.installation.php">mbstring</a>' );
			}
			// WP Remote Get Check.
			$posting['wp_remote_get']['name'] = __( 'Remote Get', 'tk-event-weather' );
			$response                         = wp_safe_remote_get( 'http://www.woothemes.com/wc-api/product-key-api?request=ping&network=' . ( is_multisite() ? '1' : '0' ) );
			if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
				$posting['wp_remote_get']['success'] = true;
			} else {
				$posting['wp_remote_get']['note'] = __( 'wp_remote_get() failed. This plugin won\'t work with your server. Contact your hosting provider.', 'tk-event-weather' );
				if ( is_wp_error( $response ) ) {
					$posting['wp_remote_get']['note'] .= ' ' . sprintf( __( 'Error: %s', 'tk-event-weather' ), Functions::tk_clean_var( $response->get_error_message() ) );
				} else {
					$posting['wp_remote_get']['note'] .= ' ' . sprintf( __( 'Status code: %s', 'tk-event-weather' ), Functions::tk_clean_var( $response['response']['code'] ) );
				}
				$posting['wp_remote_get']['success'] = false;
			}
			// $posting = apply_filters( 'woocommerce_debug_posting', $posting );
			foreach ( $posting as $post ) {
				$mark = ! empty( $post['success'] ) ? 'yes' : 'error';
				?>
				<tr>
					<td data-export-label="<?php echo esc_html( $post['name'] ); ?>"><?php echo esc_html( $post['name'] ); ?>
						:
					</td>
					<td>
						<mark class="<?php echo $mark; ?>">
							<?php echo ! empty( $post['success'] ) ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no-alt"></span>'; ?><?php echo ! empty( $post['note'] ) ? wp_kses_data( $post['note'] ) : ''; ?>
						</mark>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<table class="tkeventweather_status_table widefat" cellspacing="0">
			<thead>
			<tr>
				<th colspan="3" data-export-label="TK Event Weather Plugin Options">
					<h2><?php _e( 'TK Event Weather Plugin Options', 'tk-event-weather' ); ?></h2>
				</th>
			</tr>
			</thead>
			<tbody>
			<?php
			$plugin_options = Functions::plugin_options();

			if ( ! empty( $plugin_options ) && is_array( $plugin_options ) ) {
				foreach ( $plugin_options as $key => $option ) {
					?>
					<tr>
						<td><?php printf( '<strong>Core:</strong> %s', esc_html( $key ) ); ?></td>
						<td><?php echo esc_html( $option ); ?></td>
					</tr>
					<?php
				}
			}

			// TODO test with multiple addons
			/**
			 * Filter to allow add-ons' plugin options to be on the debug report.
			 *
			 * @param array Array should always be added to, not replaced, to
			 *              allow for multiple add-ons inserting data.
			 */
			$addon_plugin_options = apply_filters( TK_EVENT_WEATHER_UNDERSCORES . '_add_on_plugin_options_array', array() );

			if (
				! empty( $addon_plugin_options )
				&& is_array( $addon_plugin_options )
			) {
				$prepend = 'Addon'; // initial set but can change whenever there is a matching key... until there's the next matching key (to support multiple add-ons)
				foreach ( $addon_plugin_options as $key => $option ) {
					if ( 'add_on_name' == $key ) {
						$prepend = $option;
						continue;
					}
					?>
					<tr>
						<td><?php printf( '<strong>%s:</strong> %s', esc_html( $prepend ), esc_html( $key ) ); ?></td>
						<td><?php echo esc_html( $option ); ?></td>
					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>
		<table class="tkeventweather_status_table widefat" cellspacing="0">
			<thead>
			<tr>
				<th colspan="3"
					data-export-label="Active Plugins (<?php echo count( (array) get_option( 'active_plugins' ) ); ?>)">
					<h2><?php _e( 'Active Plugins', 'tk-event-weather' ); ?>
						(<?php echo count( (array) get_option( 'active_plugins' ) ); ?>)</h2>
				</th>
			</tr>
			</thead>
			<tbody>
			<?php
			$active_plugins = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
				$active_plugins            = array_merge( $active_plugins, $network_activated_plugins );
			}
			foreach ( $active_plugins as $plugin ) {
				$plugin_data    = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
				$dirname        = dirname( $plugin );
				$version_string = '';
				$network_string = '';
				if ( ! empty( $plugin_data['Name'] ) ) {
					// Link the plugin name to the plugin url if available.
					$plugin_name = esc_html( $plugin_data['Name'] );
					if ( ! empty( $plugin_data['PluginURI'] ) ) {
						$plugin_name = '<a href="' . esc_url( $plugin_data['PluginURI'] ) . '" title="' . esc_attr__( 'Visit plugin homepage', 'tk-event-weather' ) . '" target="_blank">' . $plugin_name . '</a>';
					}
					?>
					<tr>
						<td><?php echo $plugin_name; ?></td>
						<td><?php echo sprintf( _x( 'by %s', 'by author', 'tk-event-weather' ), $plugin_data['Author'] ) . ' &ndash; ' . esc_html( $plugin_data['Version'] ) . $version_string . $network_string; ?></td>
					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>
		<table class="tkeventweather_status_table widefat" cellspacing="0">
			<thead>
			<tr>
				<th colspan="3" data-export-label="Theme">
					<h2><?php _e( 'Theme', 'tk-event-weather' ); ?></h2>
				</th>
			</tr>
			</thead>
			<?php
			include_once( ABSPATH . 'wp-admin/includes/theme-install.php' );
			$active_theme  = wp_get_theme();
			$theme_version = $active_theme->Version;
			?>
			<tbody>
			<tr>
				<td data-export-label="Name"><?php _e( 'Current Active Theme', 'tk-event-weather' ); ?>:</td>
				<td><?php echo esc_html( $active_theme->Name ); ?></td>
			</tr>
			<tr>
				<td data-export-label="Version"><?php _e( 'Current Active Theme Version', 'tk-event-weather' ); ?>
					:
				</td>
				<td><?php
					echo is_child_theme() ? __( 'N/A - Child Theme in use', 'tk-event-weather' ) : esc_html( $theme_version );
					?></td>
			</tr>
			<tr>
				<td data-export-label="Author URL"><?php _e( 'Current Active Theme Author URL', 'tk-event-weather' ); ?>
					:
				</td>
				<td><?php echo $active_theme->{'Author URI'}; ?></td>
			</tr>
			<tr>
				<td data-export-label="Child Theme"><?php _e( 'Current Active Theme is a Child Theme', 'tk-event-weather' ); ?>
					:
				</td>
				<td><?php
					echo is_child_theme() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<span class="dashicons dashicons-no-alt"></span>';
					?></td>
			</tr>
			<?php
			if ( is_child_theme() ) :
				$parent_theme = wp_get_theme( $active_theme->Template );
				?>
				<tr>
					<td data-export-label="Parent Theme Name"><?php _e( 'Parent Theme Name', 'tk-event-weather' ); ?>
						:
					</td>
					<td><?php echo esc_html( $parent_theme->Name ); ?></td>
				</tr>
				<tr>
					<td data-export-label="Parent Theme Version"><?php _e( 'Parent Theme Version', 'tk-event-weather' ); ?>
						:
					</td>
					<td><?php
						echo esc_html( $parent_theme->Version );
						?></td>
				</tr>
				<tr>
					<td data-export-label="Parent Theme Author URL"><?php _e( 'Parent Theme Author URL', 'tk-event-weather' ); ?>
						:
					</td>
					<td><?php echo $parent_theme->{'Author URI'}; ?></td>
				</tr>
			<?php endif ?>
			</tbody>
		</table>

		<script type="text/javascript">
			jQuery( 'a.debug-report' ).click( function () {
				var report = '';
				jQuery( '.tkeventweather_status_table thead, .tkeventweather_status_table tbody' ).each( function () {
					if ( jQuery( this ).is( 'thead' ) ) {
						var label = jQuery( this ).find( 'th:eq(0)' ).data( 'export-label' ) || jQuery( this ).text();
						report = report + '\n### ' + jQuery.trim( label ) + ' ###\n\n';
					} else {
						jQuery( 'tr', jQuery( this ) ).each( function () {
							var label = jQuery( this ).find( 'td:eq(0)' ).data( 'export-label' ) || jQuery( this ).find( 'td:eq(0)' ).text();
							var the_name = jQuery.trim( label ).replace( /(<([^>]+)>)/ig, '' ); // Remove HTML.
							// Find value
							var $value_html = jQuery( this ).find( 'td:eq(1)' ).clone(); // 2nd <td>
							$value_html.find( '.private' ).remove();
							$value_html.find( '.dashicons-yes' ).replaceWith( '&#10004;' );
							$value_html.find( '.dashicons-no-alt, .dashicons-warning' ).replaceWith( '&#10060;' );
							// Format value
							var the_value = jQuery.trim( $value_html.text() );
							var value_array = the_value.split( ', ' );
							if ( value_array.length > 1 ) {
								// If value have a list of plugins ','.
								// Split to add new line.
								var temp_line = '';
								jQuery.each( value_array, function ( key, line ) {
									temp_line = temp_line + line + '\n';
								} );
								the_value = temp_line;
							}
							report = report + '' + the_name + ': ' + the_value + '\n';
						} );
					}
				} );
				try {
					jQuery( '#debug-report' ).slideDown().find( 'textarea' ).val( '`' + report + '`' ).focus().select();
					jQuery( this ).fadeOut();
					return false;
				} catch ( e ) {
					/* jshint devel: true */
					console.log( e );
				}
				return false;
			} );
		</script>
		<?php
	} else {
		// nothing
	}
		?>

		</div>
		<?php

	}

	/**
	 * @return string display name of the plugin to show as a name/title in HTML.
	 * Just returns the class name. Override this method to return something more readable
	 */
	public function get_plugin_display_name() {
		return esc_html__( 'TK Event Weather', 'tk-event-weather' );
	}

	/**
	 * Cleanup: remove each of this core plugin's options from the database, as
	 * well as those of add-ons (since they should be using
	 * 'tk_event_weather...' option names).
	 *
	 * @link https://coderwall.com/p/yrqrkw/delete-all-existing-wordpress-transients-in-mysql-database
	 */
	protected function delete_saved_options() {
		$customizer_options = get_option( TK_EVENT_WEATHER_UNDERSCORES );

		if ( ! empty( $customizer_options['uninstall_delete_all_data'] ) ) {
			// delete customizer options for core plugin and add-on plugins
			$all_options = wp_load_alloptions();

			// Delete options that start with 'tk_event_weather'
			foreach ( $all_options as $option => $value ) {
				if ( 0 === strpos( $option, TK_EVENT_WEATHER_UNDERSCORES ) ) {
					delete_option( $option );
				}
			}

			// delete all other options is handled via mark_as_uninstalled()

			// delete all our transients
			global $wpdb;

			$table_name        = "{$wpdb->prefix}options";
			$general_transient = '%\_transient\_%';
			$our_transient     = '%\_' . Setup::$transient_name_prepend . '_%';

			$sql = $wpdb->prepare( "DELETE FROM `$table_name` WHERE `option_name` LIKE ( %s ) AND `option_name` LIKE ( %s )", $general_transient, $our_transient );

			$transients_deleted = $wpdb->query( $sql ); // Number of rows affected/selected or false on error
		}
	}

}