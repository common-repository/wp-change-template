=== WP Change Template ===
Contributors: t1gr0u
Tags: change, template, theme, date, day, month, year, time, hour, minute, plugin, admin, preview
Requires at least: 2.7.0
Tested up to: 2.8.4
Stable tag: 0.3

Change theme on a specific date, ex.: have a christmas theme for december and revert back to normal after.

== Description ==

The plugin allows to change theme (template) on desired dates.
If you want a Christmas theme automatically on your blog just for 5 days (or more), the plugin will do it for you.

Just enter a start date, an end date , select times and the theme which you want to use, that's it.

You can have as many combinations as you wish.

A theme for each seasons, halloween theme, christmas theme... or even a day and night theme...

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `wp-change-template` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to 'Settings' on the admin panel and select 'WP Change Template'

== Frequently Asked Questions ==

= How to set up? =

Go to admin panel, select 'Settings' then 'WP Change Template'.
Enter a title, a start date, an end date and a theme which will be used.
That's it.

= Every day change? =

Go to admin panel, select 'Settings' then 'WP Change Template'.
Enter a title, select "every" for start date, select "every" end date , select the times and a theme which will be used.
That's it.

= Where is the 'WP Change Template' admin? =

Go to your admin panel, select 'Settings' then 'WP Change Template'.

= What happens if a theme has been deleted and there is still a rule ? =

The plugin will either use another active rule or display the default template which you have choosen in the admin.

= Dates and times? =

They all depend on the "timezone" which you have setup in your general settings.

== Screenshots ==

1. Admin panel

== Changelog ==

= 0.3 =
* Fixed a little problem with 'date_default_timezone_set' which doesn t exists in PHP4

= 0.2 =
* Added activation (activate or deactivate a rule)
* Display status (Show status of each action)
* Display theme and rule (display default theme, and used rule theme)
* Display preview (preview the theme of each rule)
* Added time slots (choose what time the rule will act)
* Added timezone (The rule will act on the timezone you have selected in Settings -> General)

= 0.1 =
* First release.

== Admin Panel ==

The admin panel will allow to move re-order your theme changes.
Just remember that, the top is the lowest and the bottom is the highest level.
So, if you have a theme change ('theme1') set for the 23/07 - 01/08 first and theme change ('theme2') for the 24/07 - 26/07, and today's date is 24/07,
'theme2' will be displayed unless you move down 'theme1'."

The rules, which you have created, will stay year on year.