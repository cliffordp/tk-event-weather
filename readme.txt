=== TK Event Weather ===
Contributors: cliffpaulick
Tags: API, calendars, celsius, classes, concerts, Dark Sky, events, fahrenheit, forecast, Forecast.io, local, meetings, shortcode, tickets, weather
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Requires at least: 4.3.0
Tested up to: 4.7
Stable tag: 1.2.5

Display beautiful, accurate, and free hourly weather forecasts between a start and end time. Perfect for event calendars.

== Description ==

Display beautiful, accurate, and free hourly weather forecasts between a start and end time on the same day. Perfect for event calendars. Includes sunrise, sunset, temperature, wind speed and direction, and is very customizable.

= Highlights =

* Registers the `[tk_event_weather]` shortcode for use anywhere on your site (not just for your site calendar's event-specific information) so its usage is very flexible as long as there is a valid Dark Sky API key, latitude, longitude, and time (timestamp or ISO 8601 format)
* Uses the Dark Sky API to provide you with an accurate, reliable (typically 99.9% uptime), and free (or low cost if over the daily free API calls limit) weather forecast
* Uses WordPress' Transients API to minimize Dark Sky API and Google Maps Geocoding API calls (i.e. does not call the APIs on every page load) to increase page load time and save you money (or keep you from hitting the free limits)
* Returns temperature (fahrenheit or celsius) in units local to the given latitude and longitude (with optional override to force display in one or the other)
* Internationalized / translatable (translations not provided)
* No WP_DEBUG messages
* Optimized loading of assets and Dark Sky API and Google Maps Geocoding API calls
* Actions and Filters available for developers and advanced customizations
* Responsive plugin developer

= Paid Add-Ons =

Currently Available:

* [TK Event Weather for The Events Calendar by Modern Tribe](http://tourkick.com/shop/wordpress/tk-event-weather-the-events-calendar/) - 400,000+ active installs

I am considering making add-ons for the following event calendars:

* [Events Manager by Marcus Sykes](https://wordpress.org/plugins/events-manager/) - 100,000+ active installs
* [All-in-One Event Calendar by Timely](https://wordpress.org/plugins/all-in-one-event-calendar/) - 100,000+ active installs
* [Simple Calendar by Moonstone Media](https://wordpress.org/plugins/google-calendar-events/) - 70,000+ active installs
* [Spider Event Calendar by WebDorado](https://wordpress.org/plugins/spider-event-calendar/) - 30,000+ active installs
* [Calendar by Kieran O'Shea](https://wordpress.org/plugins/calendar/) - 30,000+ active installs
* [My Calendar by Joe Dolson](https://wordpress.org/plugins/my-calendar/) - 30,000+ active installs
* [Event Organiser by Stephen Harris](https://wordpress.org/plugins/event-organiser/) - 30,000+ active installs
* [EventOn by ashanjay](https://codecanyon.net/item/eventon-wordpress-event-calendar-plugin/1211017?ref=cliffpaulick) - 21,000+ sales
* [Event Calendar WD by WebDorado](https://wordpress.org/plugins/event-calendar-wd/) - 10,000+ active installs
* [Calendarize it! by RightHere](http://codecanyon.net/item/calendarize-it-for-wordpress/2568439?ref=cliffpaulick) - 7,000+ sales
* [Appointment+ by WPMU DEV](https://premium.wpmudev.org/project/appointments-plus/) - 97,000+ downloads
* [Events+ by WPMU DEV](https://premium.wpmudev.org/project/events-plus/) - 77,000+ downloads
* [Event Espresso](https://eventespresso.com/third-party-addons/) - 2,000+ active installs of EE4 Decaf
* [WooCommerce Bookings](https://woocommerce.com/products/woocommerce-bookings/) - thousands of active installs (exact number unknown)

Additional add-on ideas:

* Geolocate user to display weather for user's current location (i.e. no specific latitude or longitude shortcode arguments)
* Styling (custom colors, possibly custom icons)
* Advanced templates/views

I'll consider making whichever add-ons I receive the most requests for (and are technically feasible) so please share your request via the plugin settings' built-in Feature Request form!

= Notes =
* You'll need to register for a free [Dark Sky API key](https://darksky.net/dev/)
* As of October 2016, Dark Sky allows up to 1,000 free API calls per day. The cost for additional API calls is $0.0001 per API call (or $1 per 10,000 requests).
* You'll need to enter your billing information at Dark Sky if you want to ensure your API access isn't cut off after 1,000 API calls per day.
* If you're out of API calls for the day and you haven't entered billing information, the plugin will "fail gracefully" (does not display errors to non-Administrators).
* You can check the [Dark Sky API's Status Updates](http://status.darksky.net/) to read its news.
* The [Dark Sky Terms of Use](https://darksky.net/dev/docs/terms) states, "You agree that any application or service which incorporates data obtained from the Service shall prominently display the message "Powered by Dark Sky" in a legible manner near the data or any information derived from any data from the Service. This message must, if possible, open a link to https://darksky.net/poweredby/ when clicked or touched."
* This plugin's output will automatically add a compliant link; however, there is a setting to disable outputting this link. This is because your site may not be a "public or user-facing application" (although it probably is) or because you've emailed to the address in their Terms of Use to request (and have been approved for) their extremely-affordable *white-label* account.
* This plugin and its author are not affiliated with or endorsed by The Dark Sky Company, LLC.
* This plugin utilizes [Freemius](https://freemius.com/wordpress/). All data collected via Freemius will be available to both Freemius and this plugin's author to be used in responsible ways. By opting-in to Freemius, you'll help us learn how we can make this plugin better and possibly communicate with you regarding the plugin's development.
* This plugin may contain affiliate links.

= Support Me =
* [Leave a great review](https://wordpress.org/support/view/plugin-reviews/tk-event-weather?rate=5#postform)
* Buy one of my available paid add-ons or tell me about one you'd be interested in purchasing!
* [View my other plugins](http://tourkick.com/plugins/)
* [Hire Me for Customizations](http://tourkick.com/)
* [Contribute code via GitHub](https://github.com/cliffordp/tk-event-weather)
* **[Tweet this plugin](https://twitter.com/home?status=I%20love%20the%20free%20TK%20Event%20Weather%20%23WordPress%20plugin%20by%20%40TourKick.%20http%3A//tourkick.com/plugins/tk-event-weather/%20-%20weather%20for%20any%20event!)**

== Installation ==

After automatically or manually installing to wp-content/plugins/:

1. Activate this plugin
2. Navigate to this plugin's Settings page (wp-admin > Settings > TK Event Weather)
3. Click the "Edit Plugin Settings in WP Customizer" button to enter your Dark Sky API key and setup any other available settings.
4. Then use the shortcode however you wish throughout your site (will require manually entering latitude and longitude) or install one of our add-ons to make it easy to integrate with your event calendar.

== Frequently Asked Questions ==


= What shortcodes are available? =

`[tk_event_weather]`

= What are some shortcode examples? =

1) Weather for the White House on February 1, 2016, from 4:30pm&ndash;9:45pm Eastern Time:

A) with single Latitude/Longitude shortcode argument and ISO 8601 datetime format

`[tk_event_weather lat_long="38.897676,-77.03653" start_time="2016-02-01T16:30:00-05:00" end_time="2016-02-01T21:45:00-05:00"]`

B) or separate shortcode arguments for Latitude and Longitude

`[tk_event_weather lat="38.897676" long="-77.03653" start_time="2016-02-01T16:30:00-05:00" end_time="2016-02-01T21:45:00-05:00"]`

C) or with Unix timestamps

`[tk_event_weather lat_long="38.897676,-77.03653" start_time="1454362200" end_time="1454381100"]`

D) Just like Example A but with Location shortcode argument (a Place name) -- available since version 1.2.0

`[tk_event_weather location="The White House" start_time="1454362200" end_time="1454381100"]`

E) Just like Example D but with Location shortcode argument (a full address) -- available since version 1.2.0

`[tk_event_weather location="1600 Pennsylvania Ave NW, Washington, DC 20500, USA" start_time="1454362200" end_time="1454381100"]`

F) Displaying more than one day in a sequence (multiple API calls but appear all together), [like this screenshot](https://cl.ly/1X430L0g0z2c)
`[tk_event_weather lat_long='28.5549259,-81.3342398' start_time='2016-08-27T22:00:00-04:00' end_time='2016-08-27T23:59:00-04:00' class='tkeventw-myclass']

[tk_event_weather lat_long='28.5549259,-81.3342398' start_time='2016-08-28T00:00:00-04:00' end_time='2016-08-28T04:30:00-04:00' class='tkeventw-myclass' darksky_credit_link_off='true']`

And then add some custom CSS, like this:
`.tkeventw-myclass { display: inline-block; vertical-align: top; }`

= Will this plugin work with my theme? =

This plugin will work with any properly-coded WordPress theme. Free styling / customization help to integrate with your theme is not available from the plugin author.

= What system specs are required? =

This plugin requires WordPress version 4.3.0 or later. It is always recommended to use the latest version of WordPress for compatibility, performance, and security reasons.

This plugin may not work properly with PHP versions earlier than 5.4. You should meet or exceed the [WordPress recommended software specs](https://wordpress.org/about/requirements/) for best performance and security.

Any of this plugin's add-ons for specific event calendars would require the latest version of each add-on plugin and each event calendar plugin.

= How accurate are the forecasts? =

Basically, accuracy is a high priority.

Here are quotes from the [Dark Sky API docs](https://darksky.net/dev/docs/sources):

* "The Dark Sky API is backed by a wide range of data sources, which are aggregated together to provide the most accurate forecast possible for a given location."
* "Most of our sources focus on the USA and UK, and these areas are best supported by our API. We have plans to greatly improve our international forecasts in the near future."

== Screenshots ==
1. Plugin settings screen with convenient link to plugin options in the WordPress Customizer

2. Plugin options screenshot 1 of 3

3. Plugin options screenshot 2 of 3

4. Plugin options screenshot 3 of 3

5. Example output from the "min-max / low-high" template (excluding heading text)

6. Example output from the "Hourly Vertical" template (excluding heading text)

7. Example output from the "Hourly Horizontal" template (excluding heading text)

8. Example output to Administrators when an invalid shortcode argument is used. Points out which argument was invalid.

9. A view of the Freemius links to your Freemius Account, the plugin Contact Us form, the WordPress.org Support Forum, and easy access to paid add-ons.

== Changelog ==
*Changelog DIFFs for all versions are available at <a href="http://plugins.trac.wordpress.org/browser/tk-event-weather/trunk" target="_blank">WordPress SVN</a>.*

= Version 1.2.5 =
* January 3, 2017
* Update Freemius SDK from v1.2.1 to v1.2.1.5

= Version 1.2.4 =
* October 30, 2016
* *Breaking Change:* Updated references and code for The Forecast.io API getting renamed to The Dark Sky API as of September 20, 2016. *You will need to re-enter your API key and other settings specific to The Dark Sky API.*
* Update Freemius SDK from v1.2.0 to v1.2.1

= Version 1.2.3.3 =
* August 24, 2016
* Fix `class` shortcode parameter (existed before but was not implemented into the output)
* Update Freemius SDK from v1.1.9 to v1.2.0

= Version 1.2.3.2 =
* July 22, 2016
* Update Freemius SDK from v1.1.8.1 to v1.1.9

= Version 1.2.3.1 =
* July 22, 2016
* Version bump

= Version 1.2.3 =
* July 22, 2016
* Added extra data validation check for when shortcode's Event End Time is earlier than the Event Start Time.

= Version 1.2.2 =
* May 9, 2016
* Added Title text to sunrise and sunset icons (text displays on hover, like other icons' Title text) in both Horizontal and Vertical templates.
* Add-on updates can now be detected and automatically updated (updated to Freemius v1.1.8.1).
* Plugin settings that allow numerical entry (e.g. Past Cutoff Days) can now be "blanked out" back to their defaults. Fixes issue of blank out resulting in zero value (unlimited days) due to absint() evaluating to zero for blank text input.
* Added links to TourKick's (my) and Freemius' (vendor) Terms (includes Privacy Policies) and "By using this plugin, you agree to these Terms" sort of text at the Freemius opt-in page and the plugin settings greet box area.

= Version 1.2.1 =
* April 27, 2016
* Remove usage of boolval() because it's only available in PHP 5.5.0+ and this plugin currently only requires PHP 5.0.

= Version 1.2.0 =
* April 26, 2016
* New "location" shortcode argument to enable use of Google Maps Geocoding API to retrieve latitude and longitude automatically (subject to API usage limitations or may require a Google Maps Geocoding API key).
* New "Tools" tab in plugin's settings page to use Google Maps lookup without being subject to API usage limitations (enter an address, get the coordinates, manually paste them into wherever you're using the shortcode).
* Tools tab includes shortcode examples (just for convenience) and Google Maps attribution and Terms.
* Always load shortcode's CSS file for Administrators so shortcode error messages get styled.
* New version of Freemius now allows Add-on Trials without needing to enter a credit card (only for users who have Allowed tracking and have confirmed their email address).

= Version 1.1 =
* April 11, 2016
* Fix for Freemius welcome message

= Version 1.0 =
* April 11, 2016
* Initial version
