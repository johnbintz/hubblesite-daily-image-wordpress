=== HubbleSite Daily Image Widget ===
Contributors: stsci-opo, johncoswell
Tags: widget, picture, image
Requires at least: 2.7.1
Tested up to: 2.8.4
Stable tag: 0.1.1

The HubbleSite Daily Image Widget embeds a daily HubbleSite Gallery image on your WordPress blog.

== Description ==

Use this widget to embed a new, stunning Hubble Space Telescope image each day of the year. The image changes daily. You can choose to include information about the image within the widget as well.

== Installation ==

Copy the `hubblesite-daily-image` directory to your `wp-content/plugins/` directory and activate the plugin. The widget will appear on the Appearance -> Widgets screen. Embed the widget in one of your sidebars and change the widget options to show or hide different parts of the widget.

== Frequently Asked Questions ==

= I don't use dynamic sidebars. Is there a template tag I can use to embed the widget? =

`the_hubblesite_daily_image_widget()`

will do just that.

= What do I need to run the unit tests? =

[PHPUnit](http://www.phpunit.de/) and [MockPress](http://github.com/johnbintz/mockpress/).

== Changelog ==

= 0.1.1 =
* Use WordPress's HTTP download routines instead of our own.

= 0.1 =
* Initial release

== License ==

The HubbleSite Daily Image Widget is released under the GNU GPL version 2.0 or later.
