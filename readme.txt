=== TK Event Weather ===
Contributors: cliffpaulick, freemius
Tags: calendar, events, forecast, shortcode, weather
License: GPL version 3 or any later version
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Requires at least: 4.5
Tested up to: 4.9.1
Requires PHP: 5.4
Stable tag: 1.5.4

Display beautiful, accurate, and free hourly weather forecasts between a start and end time. Perfect for event calendars.

== Description ==

Display beautiful, accurate, and free hourly weather forecasts between a start and end time on the same day. Perfect for event calendars. Includes sunrise, sunset, temperature, wind speed and direction, and is very customizable.

https://www.youtube.com/watch?v=KXyWZrfgL4k

https://www.youtube.com/watch?v=fbGS_LtX-es

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

* [The Events Calendar by Modern Tribe](https://wordpress.org/plugins/the-events-calendar/) - 500,000+ active installs - [Purchase](http://tourkick.com/shop/wordpress/tk-event-weather-the-events-calendar/)
* [Events Manager by Marcus Sykes](https://wordpress.org/plugins/events-manager/) - 100,000+ active installs - [Purchase](http://tourkick.com/shop/wordpress/tk-event-weather-events-manager/)
* [Simple Calendar by Moonstone Media](https://wordpress.org/plugins/google-calendar-events/) - 80,000+ active installs - [Purchase](https://tourkick.com/shop/wordpress/tk-event-weather-events-manager/)

I am considering making add-ons for the following event calendars:

* [All-in-One Event Calendar by Timely](https://wordpress.org/plugins/all-in-one-event-calendar/) - 100,000+ active installs
* [Event Organiser by Stephen Harris](https://wordpress.org/plugins/event-organiser/) - 40,000+ active installs
* [Calendar by WD / Spider Event Calendar](https://wordpress.org/plugins/spider-event-calendar/) - 30,000+ active installs
* [Event Calendar WD](https://wordpress.org/plugins/event-calendar-wd/) - 30,000+ active installs
* [My Calendar by Joe Dolson](https://wordpress.org/plugins/my-calendar/) - 30,000+ active installs
* [EventOn by ashanjay](https://codecanyon.net/item/eventon-wordpress-event-calendar-plugin/1211017?ref=cliffpaulick) - 30,000+ sales
* [Bookly Booking Plugin by Ladela](https://codecanyon.net/item/bookly-booking-plugin-responsive-appointment-booking-and-scheduling/7226091?ref=cliffpaulick) - 17,000+ sales
* [Pinpoint Booking System by DOTonPAPER](https://wordpress.org/plugins/booking-system/) - 10,000+ active installs and/or [its PRO upgrade](https://pinpoint.world/shop/product/1/pinpoint-booking-system-wordpress-plugin-pro-version) [(previously on CodeCanyon)](https://codecanyon.net/item/pinpoint-booking-system-pro-book-everything-with-wordpress/2675936?ref=cliffpaulick) - 12,000+ sales
* [WP Simple Booking Calendar by simplebookingcalendar](https://wordpress.org/plugins/wp-simple-booking-calendar/) - 10,000+ active installs and/or its [Premium version](https://www.wpsimplebookingcalendar.com/)
* [Calendarize it! by RightHere](http://codecanyon.net/item/calendarize-it-for-wordpress/2568439?ref=cliffpaulick) - 9,000+ sales
* [Appointment+ by WPMU DEV](https://premium.wpmudev.org/project/appointments-plus/) - 125,000+ downloads
* [Events+ by WPMU DEV](https://premium.wpmudev.org/project/events-plus/) - 95,000+ downloads
* [Timetable Responsive Schedule For WordPress by QuanticaLabs](https://codecanyon.net/item/timetable-responsive-schedule-for-wordpress/7010836?ref=cliffpaulick) - 6,000+ sales
* [Wordpress Pro Event Calendar by DPereyra](https://codecanyon.net/item/wordpress-pro-event-calendar/2485867?ref=cliffpaulick) - 5,500+ sales
* [Event Booking Pro by MoeHaydar](https://codecanyon.net/item/event-booking-pro-wp-plugin-paypal-or-offline/5543552?ref=cliffpaulick) - 5,000+ active installs
* [Chronosly Events Calendar](https://wordpress.org/plugins/chronosly-events-calendar/) 4,000+ active installs and/or [its Advanced (Pro) version](https://codecanyon.net/item/chronosly-event-calendar-wordpress-plugin/8493796?ref=cliffpaulick) - 800+ sales
* [Booked - Appointment Booking for WordPress by BoxyStudio](https://codecanyon.net/item/booked-appointments-appointment-booking-for-wordpress/9466968?ref=cliffpaulick) - 5,500+ active installs
* [Events Calendar Registration & Booking by elbisnero](https://codecanyon.net/item/events-calendar-registration-booking/7647762?ref=cliffpaulick) - 4,000+ sales
* [Event Espresso](https://wordpress.org/plugins/event-espresso-decaf/) - 3,000+ active installs of EE4 Decaf and/or its [paid version](https://eventespresso.com/pricing/)
* [Team Booking by VonStroheim](https://codecanyon.net/item/team-booking-wordpress-booking-system/9211794?ref=cliffpaulick) - 3,500+ sales
* [HBook - Hotel booking system by HotelWP](https://codecanyon.net/item/hbook-hotel-booking-system-wordpress-plugin/10622779?ref=cliffpaulick) - 3,000+ sales
* [Modern Events Calendar by WEBNUS](https://codecanyon.net/item/modern-events-calendar-responsive-event-scheduler-booking-for-wordpress/17731780?ref=cliffpaulick) - 3,000+ sales
* [FooEvents for WooCommerce](https://codecanyon.net/item/fooevents-for-woocommerce/11753111?ref=cliffpaulick) - 2,500+ sales
* [WP Booking Calendar by Wachipi](https://codecanyon.net/item/wp-booking-calendar/4639530?ref=cliffpaulick) - 1,900+ sales
* [WooCommerce Bookings](https://woocommerce.com/products/woocommerce-bookings/) - thousands of active installs (exact number unknown)
* [WooCommerce Appointments by BizzThemes](https://bizzthemes.com/plugins/woocommerce-appointments/)
* [Booking & Appointment Plugin for WooCommerce by Tyche Softwares](https://www.tychesoftwares.com/store/premium-plugins/woocommerce-booking-plugin/)
* [BirchPress Scheduler](https://www.birchpress.com/)

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
* The [Dark Sky Terms of Use](https://darksky.net/dev/docs/terms) states, "You agree that any application or service which incorporates data obtained from the Service shall prominently display the message "Powered by Dark Sky" in a legible manner near the data or any information derived from any data from the Service. This message must, if possible, open a link to [https://darksky.net/poweredby/](https://darksky.net/poweredby/) when clicked or touched."
* This plugin's output will automatically add a compliant link; however, there is a setting to disable outputting this link. This is because your site may not be a "public or user-facing application" (although it probably is) or because you've emailed to the address in their Terms of Use to request (and have been approved for) their extremely-affordable *white-label* account.
* This plugin and its author are not affiliated with or endorsed by The Dark Sky Company, LLC or any of the other plugins mentioned (including ones for which an add-on is available).
* This plugin utilizes [Freemius](https://freemius.com/wordpress/). All data collected via Freemius will be available to both Freemius and this plugin's author to be used in responsible ways. By opting-in to Freemius, you'll help us learn how we can make this plugin better and possibly communicate with you regarding the plugin's development.
* This plugin may contain affiliate links.

= Support Me =
* [Leave a great review](https://wordpress.org/support/view/plugin-reviews/tk-event-weather?rate=5#postform)
* Buy one of my available paid add-ons or tell me about one you'd be interested in purchasing!
* [View my other plugins](http://tourkick.com/plugins/)
* [Hire Me for Custom Integrations and other Customizations](http://tourkick.com/)
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

`.tkeventw-myclass {
	display: inline-block; vertical-align: top;
}`

G) To display from 4:30pm through the remainder of the day (do not set the end_time)

`[tk_event_weather lat_long="38.897676,-77.03653" start_time="2016-02-01T16:30:00-05:00"]`

H) To display only 4:00pm's weather, set end_time to the same

`[tk_event_weather lat_long="38.897676,-77.03653" start_time="2016-02-01T16:00:00-05:00" end_time="2016-02-01T16:00:00-05:00" sunrise_sunset_off="true"]`

I) To display Today's weather from 6am - 7pm, begin the start_time and end_time shortcode arguments at the "T" part of the ISO 8601 format. Note that you will likely need to edit the shortcode twice per year to accurately reflect the location's Daylight Savings Time (DST) UTC offset.

`[tk_event_weather location="The White House" start_time=T06:00:00-0400 end_time=T19:00:00-04:00 before="Today's Forecast"]`

J) To display the weather **from right now through the next 3 hours**. NOTE: end_time is relative to start_time, not to "now" (unless start_time is set to "now").

`[tk_event_weather location="The White House" start_time="now" end_time="+3 hours"]`

Example: At 12:06pm, this shortcode will display 12pm, 1pm, 2pm, 3pm, and 4pm (5 hours, possibly more if sunrise or sunset) -- because the shortcode always "bookends" the hours -- so it rounds 12:06pm *down* to 12:00 and rounds 3:06pm *up* to 4:00pm. So if you'd like only 12pm, 1pm, and 2pm to be displayed, you could add `class="max-3"` to the shortcode and then also add this CSS:

`.tk-event-weather__wrapper.max-3 .template-hourly_horizontal__item:nth-of-type(1n+4) {
    display: none;
}`

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

= Acknowledgements =

Many thanks to the following:
* [Climacons webfont by Christian Naths](https://github.com/christiannaths/Climacons-Font)
* [Gamajo Template Loader by Gary Jones](https://github.com/GaryJones/Gamajo-Template-Loader)
* [WordPress Plugin Template by Michael Simpson](http://plugin.michael-simpson.com/)

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

= Version 1.5.4 =
* December, 2017
* Shortcode's asset(s) now load on every page (instead of just when shortcode is present) to support Ajax-based themes and page transitions.
* Added a "Settings" quick action link on the wp-admin Plugins List page.
* Updated Freemius SDK from v1.2.2.9 to v1.2.3

= Version 1.5.3 =
* November 28, 2017
* Fixed display of Hourly Vertical with Columns when Before Text is present.
* Cleaned up the Before and After text logic and corrected these filter names: `tk_event_weather_before_full_html` to `tk_event_weather_text_before` and `tk_event_weather_after_full_html` to `tk_event_weather_text_after`.

= Version 1.5.2 =
* November 27, 2017
* Better loading logic for Freemius, which is especially important if using any add-ons.

= Version 1.5.1 =
* November 21, 2017
* Version bump for issues with .org upload of version 1.5.0
* Tweaks for uninstalling via Freemius' best practices.

= Version 1.5.0 =
* November 21, 2017
* Over 100 development hours since the last release! Enjoy the fruits of my labor and, if you're using this alongside a calendar plugin, please consider purchasing (or requesting) its paid add-on to help me and make your life easier! :)

Enhancements:

* Added support for forecasting across multiple days. To avoid accidental excess usage of API credits (each day's weather costs 1 API credit), the multi-day limit per shortcode is limited to 10 by default, customizable from 1 to as many as you want. Any shortcode that tries to span more days than the limit will result in an error with helpful tips to adjust the shortcode.
* Multi-day forecasts will trim days from the beginning only if Today is in the span of days. This can be disabled in the plugin settings.
* Added "vertical columns" mode option for displaying multiple days adjascent using CSS Flexbox.
* Displays the name of each day before each day's output. The date format can be set in the plugin settings. Each day's name also has a title attribute (displayed on hover) summarizing the entire day's weather.
* Added Dark Sky's "language" setting to display the summary text(s) in one of the languages supported by Dark Sky.
* Added a convenient link in the WP Admin Bar to edit the current URL in the Customizer, jumping right to TK Event Weather's settings panel. Removed the `tk_event_weather_customizer_link_to_core_section` filter, as it is irrelevant now.
* Added `tk_event_weather_gmaps_geocode_request_uri_query_args` filter to allow adding things like <a href="https://developers.google.com/maps/documentation/geocoding/intro#RegionCodes">Region Biasing</a>.
* Each day's Before and After content is generated via the existing template engine, allowing you to override them if desired.
* Whenever viewing today's weather forecast, the day name will be "Today" (a translatable string) instead of something like "Dec 7". This may be disabled either via template override or via a filter hook... but we think it's nifty!
* Each day now has a class of "tk-event-weather__day-type-future" (or instead ending with "today" or "past") to make it easy to style past, today, and future days to your liking.
* Added an option to delete all this plugin's data when this plugin is uninstalled via the wp-admin Plugins settings page.

Tweaks:

* Plugin now verifies a sufficient version of WordPress core is running. As of this release, 4.5 is the minimum required version.
* Improved the Customizer UI by breaking out all the plugin options from a single section to multiple sections.
* All capability checks consolidated into one, which defaults to 'customize' but is customizable via the new `tk_event_weather_required_capability` filter.
* Removed `tk_event_weather_darksky_units_default` and `tk_event_weather_darksky_exclude_default` filters. Added `tk_event_weather_dark_sky_request_uri_query_args` filter.
* Combined plugin and Dark Sky credit links into a single line and altered CSS as needed.
* Updated Freemius SDK from v1.2.1.7.1 to v1.2.2.9

Bug fixes:

* Detect when a Manual UTC Offset (like "UTC+10") is used instead of an IANA timezone name supported by the weather API and PHP (like "Australia/Brisbane"). If a manual UTC offset is used, the shortcode will now result in an error. Previously, it would fallback to use the API's detected local timezone. This change was made to reduce confusion and the possibility of inconsistencies in some edge cases.
* Hourly Horizontal's scrolling CSS no longer affects the Hourly Vertical and Low-High displays.

= Version 1.4.6 =
* May 26, 2017
* Fix - Remove potential for start time timestamp variable not being defined in cases where the start_time shortcode argument value was of an unexpected (and invalid) variety.
* Update Freemius SDK from v1.2.1.6 to v1.2.1.7.1

= Version 1.4.5 =
* May 25, 2017
* Enhancement: Support for PHP's <a href="https://secure.php.net/manual/en/function.strtotime.php">strtotime()</a>; allows you to set *start_time="now"* and/or *end_time="+3 hours"*. NOTE: end_time is relative to start_time, not to "now" (unless start_time is set to "now").
* Changed minimum required WordPress version to 4.6 <a href="https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain">for translation purposes</a>.

= Version 1.4.4 =
* April 3, 2017
* Enhancement: Hourly Horizontal forecast now displays on a single row and scrolls horizontally if necessary. There is also an option to disable this horizontal scrolling and display in multi-row like it used to be.
* Fix logic for checking if start_time is empty.

= Version 1.4.3 =
* March 24, 2017
* Improved logic for loading plugin assets
* Update Gamajo Template Loader from 1.2.0 to 1.3.0

= Version 1.4.2 =
* March 24, 2017
* Fix: Add missing Climacons SVG file
* Rename action hook from `get_template_part_tk_event_weather_hourly_horizontal` to `tk_event_weather_get_template_part_hourly_horizontal` (similar renaming for other template names)
* Change plugin's Tools tab shortcode usage examples to instead link to this plugin's WordPress.org FAQs to avoid having to upkeep them in more than one place.

= Version 1.4.1 =
* March 11, 2017
* Enhancement: If start_time or end_time begins with capital "T" (leaving out the YYYY-MM-DD part of the ISO 8601 format), today's date from WordPress' current_time( 'Y-m-d' ) will be used
* Update Freemius SDK from v1.2.1.5 to v1.2.1.6

= Version 1.4 =
* March 10, 2017
* Released <a href="http://tourkick.com/shop/wordpress/tk-event-weather-simple-calendar/" target="_blank">TK Event Weather for Simple Calendar</a>
* Enhancement: End Time can now be blank/unused and will default to displaying the remaining hours of the day.
* Enhancement: End Time can now equal Start time to display a single hour's weather. Note that if you enter 5:30am for both Start and End time, the output will be both 5am and 6am (and possibly a 3rd time in the middle with the sunrise or sunset). If you really only want a single hour displayed, you should only enter top of the hour (e.g. 5am or 6am) and also use `sunrise_sunset_off=true`. Example screenshot: https://cl.ly/2N2X1p1C1O1r
* Enhancement: New "before" (h4) and "after" (p) shortcode arguments to output certain text only if the shortcode has output. Useful for a heading like "Forecast" or some text at the end of the shortcode's output, like a disclaimer about the weather not being guaranteed.

= Version 1.3.1 =
* February 10, 2017
* Fix for PHP versions below 5.5.0 to remove expressions from empty() and now only variables are used.
* Minor tweaks around date_i18n()

= Version 1.3 =
* January 19, 2017
* Paid add-ons: lower single-site prices and bulk purchasing is now available (discounted pricing when buying for multiple sites at once).
* Tested with WordPress 4.7.1
* Breaking change: Reworked time-based functions due to the Dark Sky API response deprecating 'offset'. Uses 'timezone' response now to retrieve the timezone of the specified location, which makes this plugin display more accurately.
* Enhancement: Added Time Format options (time_format_hours and time_format_minutes shortcode arguments). Reference <a href="https://codex.wordpress.org/Function_Reference/date_i18n">date_i18n()</a> and <a href="https://codex.wordpress.org/Formatting_Date_and_Time">the Codex's Formatting Date and Time</a> for available time formats. This also changed the Hourly and Vertical templates, if you have customized those.
* Changed transient name
* Fixed error if weather API does not return a sunrise and/or sunset time (e.g. sun does not rise or set this day).
* Added WP Timezone, Date Format, and Time Format to the Help tab's system information report.

= Version 1.2.6 =
* January 4, 2017
* Added support for <a href="https://make.wordpress.org/core/2016/11/10/visible-edit-shortcuts-in-the-customizer-preview/" target="_blank">Visible Edit Shortcuts in the WP Customizer</a>
* Released <a href="http://tourkick.com/shop/wordpress/tk-event-weather-events-manager/" target="_blank">TK Event Weather for Events Manager</a>

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