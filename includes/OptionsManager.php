<?php
/*
    "WordPress Plugin Template" Copyright (C) 2016 Michael Simpson  (email : michael.d.simpson@gmail.com)

    This file is part of WordPress Plugin Template for WordPress.

    WordPress Plugin Template is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    WordPress Plugin Template is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Contact Form to Database Extension.
    If not, see http://www.gnu.org/licenses/gpl-3.0.html
*/

require_once( 'Functions.php' );

class TkEventWeather__OptionsManager {

    public function getOptionNamePrefix() {
        return get_class($this) . '_';
    }

    /**
     * Cleanup: remove all options from the DB
     * @return void
     */
    protected function deleteSavedOptions() {
/*
        $optionMetaData = $this->getOptionMetaData();
        if (is_array($optionMetaData)) {
            foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
                $prefixedOptionName = $this->prefix($aOptionKey); // how it is stored in DB
                delete_option($prefixedOptionName);
            }
        }
*/
    }

    /**
     * @return string display name of the plugin to show as a name/title in HTML.
     * Just returns the class name. Override this method to return something more readable
     */
    public function getPluginDisplayName() {
        return get_class($this);
    }

    /**
     * Get the prefixed version input $name suitable for storing in WP options
     * Idempotent: if $optionName is already prefixed, it is not prefixed again, it is returned without change
     * @param  $name string option name to prefix. Defined in settings.php and set as keys of $this->optionMetaData
     * @return string
     */
    public function prefix($name) {
        $optionNamePrefix = $this->getOptionNamePrefix();
        if (strpos($name, $optionNamePrefix) === 0) { // 0 but not false
            return $name; // already prefixed
        }
        return $optionNamePrefix . $name;
    }

    /**
     * Remove the prefix from the input $name.
     * Idempotent: If no prefix found, just returns what was input.
     * @param  $name string
     * @return string $optionName without the prefix.
     */
    public function &unPrefix($name) {
        $optionNamePrefix = $this->getOptionNamePrefix();
        if (strpos($name, $optionNamePrefix) === 0) {
            return substr($name, strlen($optionNamePrefix));
        }
        return $name;
    }

    /**
     * A wrapper function delegating to WP get_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     * @param $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @param $default string default value to return if the option is not set
     * @return string the value from delegated call to get_option(), or optional default value
     * if option is not set.
     */
    public function getOption($optionName, $default = null) {
        $prefixedOptionName = $this->prefix($optionName); // how it is stored in DB
        $retVal = get_option($prefixedOptionName);
        if (!$retVal && $default) {
            $retVal = $default;
        }
        return $retVal;
    }

    /**
     * A wrapper function delegating to WP delete_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     * @param  $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @return bool from delegated call to delete_option()
     */
    public function deleteOption($optionName) {
        $prefixedOptionName = $this->prefix($optionName); // how it is stored in DB
        return delete_option($prefixedOptionName);
    }

    /**
     * A wrapper function delegating to WP add_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     * @param  $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @param  $value mixed the new value
     * @return null from delegated call to delete_option()
     */
    public function updateOption($optionName, $value) {
        $prefixedOptionName = $this->prefix($optionName); // how it is stored in DB
        return update_option($prefixedOptionName, $value);
    }

    /**
     * A Role Option is an option defined in getOptionMetaData() as a choice of WP standard roles, e.g.
     * 'CanDoOperationX' => array('Can do Operation X', 'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber')
     * The idea is use an option to indicate what role level a user must minimally have in order to do some operation.
     * So if a Role Option 'CanDoOperationX' is set to 'Editor' then users which role 'Editor' or above should be
     * able to do Operation X.
     * Also see: canUserDoRoleOption()
     * @param  $optionName
     * @return string role name
     */
    public function getRoleOption($optionName) {
        $roleAllowed = $this->getOption($optionName);
        if (!$roleAllowed || $roleAllowed == '') {
            $roleAllowed = 'Administrator';
        }
        return $roleAllowed;
    }

    /**
     * Given a WP role name, return a WP capability which only that role and roles above it have
     * http://codex.wordpress.org/Roles_and_Capabilities
     * @param  $roleName
     * @return string a WP capability or '' if unknown input role
     */
    protected function roleToCapability($roleName) {
        switch ($roleName) {
            case 'Super Admin':
                return 'manage_options';
            case 'Administrator':
                return 'manage_options';
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
     * @param $roleName string a standard WP role name like 'Administrator'
     * @return bool
     */
    public function isUserRoleEqualOrBetterThan($roleName) {
        if ('Anyone' == $roleName) {
            return true;
        }
        $capability = $this->roleToCapability($roleName);
        return current_user_can($capability);
    }

    /**
     * @param  $optionName string name of a Role option (see comments in getRoleOption())
     * @return bool indicates if the user has adequate permissions
     */
    public function canUserDoRoleOption($optionName) {
        $roleAllowed = $this->getRoleOption($optionName);
        if ('Anyone' == $roleAllowed) {
            return true;
        }
        return $this->isUserRoleEqualOrBetterThan($roleAllowed);
    }

    /**
     * see: http://codex.wordpress.org/Creating_Options_Pages
     * @return void
     */
    public function createSettingsMenu() {
        $pluginName = $this->getPluginDisplayName();
        //create new top-level menu
        add_menu_page($pluginName . ' Plugin Settings',
                      $pluginName,
                      'administrator',
                      get_class($this),
                      array($this, 'settingsPage')
        /*,plugins_url('/images/icon.png', __FILE__)*/); // if you call 'plugins_url; be sure to "require_once" it
    }
    
    
    /**
     * Creates HTML for the Administration page to set options for this plugin.
     * Override this method to create a customized page.
     * @return void
     */
    public function settingsPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'tk-event-weather'));
        }
        
        // HTML for the page
        $settingsGroup = get_class($this) . '-settings-group';
        
        ?>
        
                
        <div class="wrap">
          <h1><?php echo $this->getPluginDisplayName(); echo ' '; _e( 'Settings', 'tk-event-weather' ); ?></h1>
            
        <?php
          // Greeting Box        
        ?>
        <div style="width: 80%; padding: 20px; margin: 20px; background-color: #fff; font-size: 120%;">
        <?php 
          $tourkick_logo = TkEventWeather__FuncSetup::plugin_dir_url_images() . 'tourkick-logo-square-300.png';
          printf( '<a href="http://tourkick.com/" target="_blank"><img style="float: left; margin: 5px 40px 10px 10px;" width="100" height="100" src="%s"></a>', $tourkick_logo );
        ?>
	          <?php
		        $addons_url = tk_event_weather_freemius()->addon_url( '' );
		        if ( ! empty( $addons_url ) ) {
			  ?>
			  <p style="font-size: 120%;">
			  <?php
			        printf( esc_html__( 'Check out the %sTK Event Weather add-on plugins%s to automatically integrate with popular WordPress calendars!', 'tk-event-weather' ),
			          '<a href="' . tk_event_weather_freemius()->addon_url( '' ) . '">',
			          '</a>'
					);
		      ?>
	          <ul style="list-style: disc; list-style-position: inside;">
		          <li>TK Event Weather for The Events Calendar by Modern Tribe</li>
	          </ul>
          </p>
		  <?php
		  	}
		  ?>
          <br>
          <p>
	          <a href="http://b.tourkick.com/tkeventw-rate-5-stars" target="_blank"><?php esc_html_e( 'Share your 5-Star Review on WordPress.org', 'tk-event-weather' ); ?></a>
	      </p>
          <p>
		  	  <a href="http://b.tourkick.com/github-tk-event-weather" target="_blank">Contribute via GitHub</a>	      </p>
          <p><?php esc_html_e( 'Find me online', 'tk-event-weather' ); ?>: <a href="http://b.tourkick.com/twitter-follow-tourkick" target="_blank">Twitter</a> | <a href="http://b.tourkick.com/facebook-tourkick" target="_blank">Facebook</a> | <a href="http://b.tourkick.com/cliffpaulick-w-org-profile-plugins" target="_blank">WordPress Profile</a> | <a href="http://b.tourkick.com/tourkick-com" target="_blank">Website</a></p>
        </div>
        
        
        
            
            <?php
              $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'options';
            ?>
            <h2 class="nav-tab-wrapper">
              <a href="<?php printf( '?page=%s&tab=options', $this->getSettingsSlug() ); ?>" class="nav-tab <?php echo $active_tab == 'options' ? 'nav-tab-active' : ''; ?>">Options</a>
              <a href="<?php printf( '?page=%s&tab=help', $this->getSettingsSlug() ); ?>" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>">Help</a>
            </h2>
            <?php
              if( $active_tab == 'options' ) {
            ?>
            <p><?php _e( "This plugin uses the WordPress Customizer to set its options.", 'tk-event-weather' ); ?></p>
            <p><?php _e( "Click the button below to be taken directly to this plugin's section within the WordPress Customizer.", 'tk-event-weather' ); ?></p>
            <p>
                <a href="<?php echo apply_filters( 'tk_event_weather_customizer_link', get_admin_url( get_current_blog_id(), 'customize.php' ) ); ?>" class="button-primary">
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
              if ( ! empty ( tk_event_weather_freemius()->is_anonymous() ) ) {
                printf ( '<p><a href="%s" class="button-secondary">%s</a></p>', tk_event_weather_freemius()->connect_again(), __( 'Connect to Freemius!', 'tk-event-weather' ) );
              } else {
                // maybe https://developer.wordpress.org/reference/functions/network_admin_url/ ?
                printf ( '<p><a href="%s" class="button-secondary">%s</a></p>', admin_url( 'admin.php?page=freemius' ), __( 'Edit Freemius Settings', 'tk-event-weather' ) );
                } // freemius is_anonymous()
              */
              } // options tab
              elseif( $active_tab == 'help' ) {
                // modified from https://github.com/woothemes/woocommerce/blob/master/includes/admin/views/html-admin-page-status-report.php
                // reference (outdated screenshots): https://docs.woothemes.com/document/understanding-the-woocommerce-system-status-report/
            ?>
            <style>
              table.tkeventw_status_table {
                  margin-bottom: 1em
              }
              
              table.tkeventw_status_table tr:nth-child(2n) td,
              table.tkeventw_status_table tr:nth-child(2n) th {
                  background: #fcfcfc
              }
              
              table.tkeventw_status_table th {
                  font-weight: 700;
                  padding: 9px
              }
              
              table.tkeventw_status_table td:first-child {
                  width: 33%
              }
              
              table.tkeventw_status_table td mark {
                  background: 0 0
              }
              
              table.tkeventw_status_table td mark.yes {
                  color: #7ad03a
              }
              
              table.tkeventw_status_table td mark.no {
                  color: #999
              }
              
              table.tkeventw_status_table td mark.error {
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
            	<p class="submit"><a href="#" class="button-primary debug-report"><?php _e( 'Get System Report', 'tk-event-weather' ); ?></a></p>
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
                <p><a target="_blank" href="<?php printf( 'http://supportdetails.com/?sender_name=%s&sender=%s&recipient=%s', urlencode( get_home_url() ), urlencode( get_bloginfo( 'admin_email' ) ), urlencode( TkEventWeather__FuncSetup::$support_email_address ) ); ?>" class="button-secondary support-details"><?php _e( 'Get Personal Computer Details', 'tk-event-weather' ); ?></a></p>
                <p><em><?php _e( 'Then click the "Send Details" button from that site!', 'tk-event-weather' ); ?></em></p>
            	</div>
            </div>
            
            <table class="tkeventw_status_table widefat" cellspacing="0" id="status">
            	<thead>
            		<tr>
            			<th colspan="3" data-export-label="WordPress Environment"><h2><?php _e( 'WordPress Environment', 'tk-event-weather' ); ?></h2></th>
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
            				if ( version_compare( $wordpress_version, TkEventWeather__FuncSetup::$min_allowed_version_wordpress, '<' ) ) {
            					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( '%s - This plugin requires a minimum WordPress version of %s. See: %s', 'tk-event-weather' ),
            					  esc_html( $wordpress_version ),
            					  TkEventWeather__FuncSetup::$min_allowed_version_wordpress,
            					  '<a href="https://codex.wordpress.org/WordPress_Versions" target="_blank">' . __( 'WordPress Version History', 'tk-event-weather' ) . '</a>' ) . '</mark>';
            				} else {
            					echo '<mark class="yes">' . esc_html( $wordpress_version ) . '</mark>';
            				}
            		?></td>
            		</tr>
            		<tr>
            			<td data-export-label="WP Multisite"><?php _e( 'WP Multisite Enabled', 'tk-event-weather' ); ?>:</td>
            			<td><?php if ( is_multisite() ) echo '<span class="dashicons dashicons-yes"></span>'; else echo '&ndash;'; ?></td>
            		</tr>
            		<tr>
            			<td data-export-label="WP Memory Limit"><?php _e( 'WP Memory Limit', 'tk-event-weather' ); ?>:</td>
            			<td><?php
            				$memory = WP_MEMORY_LIMIT;
            				if ( function_exists( 'memory_get_usage' ) ) {
            					$system_memory = @ini_get( 'memory_limit' );
            					$memory        = max( $memory, $system_memory );
            				}
            				if ( $memory < 67108864 ) {
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
            					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
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
            					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
            				<?php endif; ?>
            			</td>
            		</tr>
            		<tr>
            			<td data-export-label="Language"><?php _e( 'WordPress Language', 'tk-event-weather' ); ?>:</td>
            			<td><?php echo get_locale(); ?></td>
            		</tr>
            		<tr>
            			<td data-export-label="Permalinks"><?php _e( 'Permalink Structure', 'tk-event-weather' ); ?>:</td>
            			<td><?php echo esc_html( get_option( 'permalink_structure' ) ); ?></td>
            		</tr>
            	</tbody>
            </table>
            <table class="tkeventw_status_table widefat" cellspacing="0">
            	<thead>
            		<tr>
            			<th colspan="3" data-export-label="Server Environment"><h2><?php _e( 'Server Environment', 'tk-event-weather' ); ?></h2></th>
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
            					if ( version_compare( $php_version, TkEventWeather__FuncSetup::$min_allowed_version_php, '<' ) ) {
            						echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( '%s - This plugin requires a minimum PHP version of %s. See: %s', 'tk-event-weather' ),
            						  esc_html( $php_version ),
            						  TkEventWeather__FuncSetup::$min_allowed_version_php,
            						  '<a href="http://docs.woothemes.com/document/how-to-update-your-php-version/" target="_blank">' . __( 'How to update your PHP version', 'tk-event-weather' ) . '</a>' ) . '</mark>';
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
            				<td data-export-label="PHP Post Max Size"><?php _e( 'PHP Post Max Size', 'tk-event-weather' ); ?>:</td>
            				<td><?php echo size_format( ini_get( 'post_max_size' ) ); ?></td>
            			</tr>
            			<tr>
            				<td data-export-label="PHP Time Limit"><?php _e( 'PHP Timeout Limit', 'tk-event-weather' ); ?>:</td>
            				<td><?php echo ini_get( 'max_execution_time' ); ?></td>
            			</tr>
            			<tr>
            				<td data-export-label="PHP Max Input Vars"><?php _e( 'PHP Max Input Variables', 'tk-event-weather' ); ?>:</td>
            				<td><?php echo ini_get( 'max_input_vars' ); ?></td>
            			</tr>
            			<tr>
            				<td data-export-label="SUHOSIN Installed"><?php _e( 'SUHOSIN Installed', 'tk-event-weather' ); ?>:</td>
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
            				if ( version_compare( $mysql_version, TkEventWeather__FuncSetup::$min_allowed_version_mysql, '<' ) ) {
            					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( '%s - This plugin requires a minimum MySQL version of %s. See: %s', 'tk-event-weather' ),
            					  esc_html( $mysql_version ),
            					  TkEventWeather__FuncSetup::$min_allowed_version_mysql,
            					  '<a href="https://wordpress.org/about/requirements/" target="_blank">' . __( 'WordPress Requirements', 'tk-event-weather' ) . '</a>' ) . '</mark>';
            				} else {
            					echo '<mark class="yes">' . esc_html( $mysql_version ) . '</mark>';
            				}
            				?>
            			</td>
            		</tr>
            		<tr>
            			<td data-export-label="Max Upload Size"><?php _e( 'Max Upload Size', 'tk-event-weather' ); ?>:</td>
            			<td><?php echo size_format( wp_max_upload_size() ); ?></td>
            		</tr>
            		<tr>
            			<td data-export-label="Default Timezone is UTC"><?php _e( 'Default Server Timezone is UTC', 'tk-event-weather' ); ?>:</td>
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
            			$posting['wp_remote_get']['name'] = __( 'Remote Get', 'tk-event-weather');
            			$response = wp_safe_remote_get( 'http://www.woothemes.com/wc-api/product-key-api?request=ping&network=' . ( is_multisite() ? '1' : '0' ) );
            			if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
            				$posting['wp_remote_get']['success'] = true;
            			} else {
            				$posting['wp_remote_get']['note']    = __( 'wp_remote_get() failed. This plugin won\'t work with your server. Contact your hosting provider.', 'tk-event-weather' );
            				if ( is_wp_error( $response ) ) {
            					$posting['wp_remote_get']['note'] .= ' ' . sprintf( __( 'Error: %s', 'tk-event-weather' ), TkEventWeather__Functions::tk_clean_var( $response->get_error_message() ) );
            				} else {
            					$posting['wp_remote_get']['note'] .= ' ' . sprintf( __( 'Status code: %s', 'tk-event-weather' ), TkEventWeather__Functions::tk_clean_var( $response['response']['code'] ) );
            				}
            				$posting['wp_remote_get']['success'] = false;
            			}
            			// $posting = apply_filters( 'woocommerce_debug_posting', $posting );
            			foreach ( $posting as $post ) {
            				$mark = ! empty( $post['success'] ) ? 'yes' : 'error';
            				?>
            				<tr>
            					<td data-export-label="<?php echo esc_html( $post['name'] ); ?>"><?php echo esc_html( $post['name'] ); ?>:</td>
            					<td>
            						<mark class="<?php echo $mark; ?>">
            							<?php echo ! empty( $post['success'] ) ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no-alt"></span>'; ?> <?php echo ! empty( $post['note'] ) ? wp_kses_data( $post['note'] ) : ''; ?>
            						</mark>
            					</td>
            				</tr>
            				<?php
            			}
            		?>
            	</tbody>
            </table>
            <table class="tkeventw_status_table widefat" cellspacing="0">
            	<thead>
            		<tr>
            			<th colspan="3" data-export-label="TK Event Weather Plugin Options"><h2><?php _e( 'TK Event Weather Plugin Options', 'tk-event-weather' ); ?></h2></th>
            		</tr>
            	</thead>
            	<tbody>
            		<?php
            		$plugin_options = TkEventWeather__Functions::plugin_options();
                
                if ( ! empty( $plugin_options ) && is_array( $plugin_options ) ) {
              		foreach ( $plugin_options as $key=>$option ) {
              				?>
              				<tr>
              					<td><?php echo esc_html( 'Core - ' . $key ); ?></td>
              					<td><?php echo esc_html( $option ); ?></td>
              				</tr>
              				<?php
              		}
                }
            		
            		// allow add-ons to output their settings too
            		$addon_plugin_options = apply_filters( 'tk_event_weather_add_on_plugin_options_array', array() );

                if ( ! empty( $addon_plugin_options ) && is_array( $addon_plugin_options ) ) {
              		foreach ( $addon_plugin_options as $key=>$option ) {
              				?>
              				<tr>
              					<td><?php echo esc_html( 'Addon - ' . $key ); ?></td>
              					<td><?php echo esc_html( $option ); ?></td>
              				</tr>
              				<?php
              		}
            		}
            		?>
            	</tbody>
            </table>
            <table class="tkeventw_status_table widefat" cellspacing="0">
            	<thead>
            		<tr>
            			<th colspan="3" data-export-label="Active Plugins (<?php echo count( (array) get_option( 'active_plugins' ) ); ?>)"><h2><?php _e( 'Active Plugins', 'tk-event-weather' ); ?> (<?php echo count( (array) get_option( 'active_plugins' ) ); ?>)</h2></th>
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
            					$plugin_name = '<a href="' . esc_url( $plugin_data['PluginURI'] ) . '" title="' . esc_attr__( 'Visit plugin homepage' , 'tk-event-weather' ) . '" target="_blank">' . $plugin_name . '</a>';
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
            <table class="tkeventw_status_table widefat" cellspacing="0">
            	<thead>
            		<tr>
            			<th colspan="3" data-export-label="Theme"><h2><?php _e( 'Theme', 'tk-event-weather' ); ?></h2></th>
            		</tr>
            	</thead>
            		<?php
            		include_once( ABSPATH . 'wp-admin/includes/theme-install.php' );
            		$active_theme         = wp_get_theme();
            		$theme_version        = $active_theme->Version;
            		?>
            	<tbody>
            		<tr>
            			<td data-export-label="Name"><?php _e( 'Current Active Theme', 'tk-event-weather' ); ?>:</td>
            			<td><?php echo esc_html( $active_theme->Name ); ?></td>
            		</tr>
            		<tr>
            			<td data-export-label="Version"><?php _e( 'Current Active Theme Version', 'tk-event-weather' ); ?>:</td>
            			<td><?php
            				echo is_child_theme() ? __( 'N/A - Child Theme in use', 'tk-event-weather' ) : esc_html( $theme_version );
            			?></td>
            		</tr>
            		<tr>
            			<td data-export-label="Author URL"><?php _e( 'Current Active Theme Author URL', 'tk-event-weather' ); ?>:</td>
            			<td><?php echo $active_theme->{'Author URI'}; ?></td>
            		</tr>
            		<tr>
            			<td data-export-label="Child Theme"><?php _e( 'Current Active Theme is a Child Theme', 'tk-event-weather' ); ?>:</td>
            			<td><?php
            				echo is_child_theme() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<span class="dashicons dashicons-no-alt"></span>';
            			?></td>
            		</tr>
            		<?php
            		if( is_child_theme() ) :
            			$parent_theme         = wp_get_theme( $active_theme->Template );
            		?>
            		<tr>
            			<td data-export-label="Parent Theme Name"><?php _e( 'Parent Theme Name', 'tk-event-weather' ); ?>:</td>
            			<td><?php echo esc_html( $parent_theme->Name ); ?></td>
            		</tr>
            		<tr>
            			<td data-export-label="Parent Theme Version"><?php _e( 'Parent Theme Version', 'tk-event-weather' ); ?>:</td>
            			<td><?php
            				echo esc_html( $parent_theme->Version );
            			?></td>
            		</tr>
            		<tr>
            			<td data-export-label="Parent Theme Author URL"><?php _e( 'Parent Theme Author URL', 'tk-event-weather' ); ?>:</td>
            			<td><?php echo $parent_theme->{'Author URI'}; ?></td>
            		</tr>
            		<?php endif ?>
            	</tbody>
            </table>
            
            <script type="text/javascript">
            	jQuery( 'a.debug-report' ).click( function() {
            		var report = '';
            		jQuery( '.tkeventw_status_table thead, .tkeventw_status_table tbody' ).each( function() {
            			if ( jQuery( this ).is( 'thead' ) ) {
            				var label = jQuery( this ).find( 'th:eq(0)' ).data( 'export-label' ) || jQuery( this ).text();
            				report = report + '\n### ' + jQuery.trim( label ) + ' ###\n\n';
            			} else {
            				jQuery( 'tr', jQuery( this ) ).each( function() {
            					var label       = jQuery( this ).find( 'td:eq(0)' ).data( 'export-label' ) || jQuery( this ).find( 'td:eq(0)' ).text();
            					var the_name    = jQuery.trim( label ).replace( /(<([^>]+)>)/ig, '' ); // Remove HTML.
            					// Find value
            					var $value_html = jQuery( this ).find( 'td:eq(1)' ).clone(); // 2nd <td>
            					$value_html.find( '.private' ).remove();
            					$value_html.find( '.dashicons-yes' ).replaceWith( '&#10004;' );
            					$value_html.find( '.dashicons-no-alt, .dashicons-warning' ).replaceWith( '&#10060;' );
            					// Format value
            					var the_value   = jQuery.trim( $value_html.text() );
            					var value_array = the_value.split( ', ' );
            					if ( value_array.length > 1 ) {
            						// If value have a list of plugins ','.
            						// Split to add new line.
            						var temp_line ='';
            						jQuery.each( value_array, function( key, line ) {
            							temp_line = temp_line + line + '\n';
            						});
            						the_value = temp_line;
            					}
            					report = report + '' + the_name + ': ' + the_value + '\n';
            				});
            			}
            		});
            		try {
            			jQuery( '#debug-report' ).slideDown().find( 'textarea' ).val( '`' + report + '`' ).focus().select();
            			jQuery( this ).fadeOut();
            			return false;
            		} catch ( e ) {
            			/* jshint devel: true */
            			console.log( e );
            		}
            		return false;
            	});
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
     * Helper-function outputs the correct form element (input tag, select tag) for the given item
     * @param  $aOptionKey string name of the option (un-prefixed)
     * @param  $aOptionMeta mixed meta-data for $aOptionKey (either a string display-name or an array(display-name, option1, option2, ...)
     * @param  $savedOptionValue string current value for $aOptionKey
     * @return void
     */
    protected function createFormControl($aOptionKey, $aOptionMeta, $savedOptionValue) {
        $all_password_field_key_endings = array(
          // enter lower-case here to match any case
          '_api_key',
          '_apikey',
          '_password',
          '_secret',
        );
        
        $is_password_type_field = false;
        
        foreach( $all_password_field_key_endings as $password_ending ) {
          $aOptionKey_strlen = strlen( $aOptionKey );
          $password_ending_strlen = strlen( $password_ending );
          if(
            1 <= $password_ending_strlen
            && 1 <= $aOptionKey_strlen
            && $password_ending_strlen <= $aOptionKey_strlen
            && 0 === substr_compare( $aOptionKey, $password_ending, $aOptionKey_strlen - $password_ending_strlen, $password_ending_strlen, TRUE ) // added 1+ checks, above, so this function does not throw errors in PHP versions lower than 5.6.0 -- Reference: http://stackoverflow.com/a/619725/893907
          ) {
            $is_password_type_field = true;
          }
        }
        
        if (is_array($aOptionMeta) && count($aOptionMeta) >= 2) { // Drop-down list
            $choices = array_slice($aOptionMeta, 1);
            ?>
            <p><select name="<?php echo $aOptionKey ?>" id="<?php echo $aOptionKey ?>">
            <?php
                            foreach ($choices as $aChoice) {
                $selected = ($aChoice == $savedOptionValue) ? 'selected' : '';
                ?>
                    <option value="<?php echo $aChoice ?>" <?php echo $selected ?>><?php echo $this->getOptionValueI18nString($aChoice) ?></option>
                <?php
            }
            ?>
            </select></p>
            <?php

        }
        elseif( true === $is_password_type_field ) { // Password/API Key Type field
            ?>
            <p><input type="password" autocomplete="off" name="<?php echo $aOptionKey ?>" id="<?php echo $aOptionKey ?>"
                      value="<?php echo esc_attr($savedOptionValue) ?>" size="50"/></p>
            <?php

        }
        else { // Simple input field
            ?>
            <p><input type="text" name="<?php echo $aOptionKey ?>" id="<?php echo $aOptionKey ?>"
                      value="<?php echo esc_attr($savedOptionValue) ?>" size="50"/></p>
            <?php

        }
    }

    /**
     * Override this method and follow its format.
     * The purpose of this method is to provide i18n display strings for the values of options.
     * For example, you may create a options with values 'true' or 'false'.
     * In the options page, this will show as a drop down list with these choices.
     * But when the the language is not English, you would like to display different strings
     * for 'true' and 'false' while still keeping the value of that option that is actually saved in
     * the DB as 'true' or 'false'.
     * To do this, follow the convention of defining option values in getOptionMetaData() as canonical names
     * (what you want them to literally be, like 'true') and then add each one to the switch statement in this
     * function, returning the "__()" i18n name of that string.
     * @param  $optionValue string
     * @return string __($optionValue) if it is listed in this method, otherwise just returns $optionValue
     */
    protected function getOptionValueI18nString($optionValue) {
        switch ($optionValue) {
            case 'true':
                return __('true', 'tk-event-weather');
            case 'false':
                return __('false', 'tk-event-weather');

            case 'Administrator':
                return __('Administrator', 'tk-event-weather');
            case 'Editor':
                return __('Editor', 'tk-event-weather');
            case 'Author':
                return __('Author', 'tk-event-weather');
            case 'Contributor':
                return __('Contributor', 'tk-event-weather');
            case 'Subscriber':
                return __('Subscriber', 'tk-event-weather');
            case 'Anyone':
                return __('Anyone', 'tk-event-weather');
        }
        return $optionValue;
    }

    /**
     * Query MySQL DB for its version
     * @return string|false
     */
    protected function getMySqlVersion() {
        global $wpdb;
        $rows = $wpdb->get_results('select version() as mysqlversion');
        if (!empty($rows)) {
             return $rows[0]->mysqlversion;
        }
        return false;
    }

    /**
     * If you want to generate an email address like "no-reply@your-site.com" then
     * you can use this to get the domain name part.
     * E.g.  'no-reply@' . $this->getEmailDomain();
     * This code was stolen from the wp_mail function, where it generates a default
     * from "wordpress@your-site.com"
     * @return string domain name
     */
    public function getEmailDomain() {
        // Get the site domain and get rid of www.
        $sitename = strtolower($_SERVER['SERVER_NAME']);
        if (substr($sitename, 0, 4) == 'www.') {
            $sitename = substr($sitename, 4);
        }
        return $sitename;
    }
}

