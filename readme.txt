=== HubbleSite Daily Image Widget ===
Contributors: johncoswell
Tags: widget, picture, image
Requires at least: 2.7.1
Tested up to: 2.8
Stable tag: 0.1

The HubbleSite Daily Image Widget embeds a daily HubbleSite Gallery image on your WordPress blog.

== Description ==

This widget will let you embed and show a beautiful image from the Hubble Space Telescope every day of the year. The image changes daily, and you can choose to show all sorts of other information about the image within the widget, as well.

== Installation ==

Copy the hubblesite-daily-image-widget directory to your wp-content/plugins/ directory and activate the plugin. The widget will appear on the Appearance -> Widgets screen. Embed the widget in one of your sidebars and change the widget options to show or hide different parts of the widget.

== Frequently Asked Questions ==

= How do I style the widget myself? =

Turn off "HubbleSite Styles" in the Widget Options and then style the elements with your theme's CSS file.

= I don't use dynamic sidebars. Is there a template tag I can use to embed the widget? =

`the_hubblesite_daily_image_widget()` will do just that.

= What do I need to run the unit tests? =

[PHPUnit](http://www.phpunit.de/) and [MockPress](http://github.com/johnbintz/mockpress/).

== License ==

The HubbleSite Daily Image Widget is released under the GNU GPL version 2.0 or later.