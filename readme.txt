=== WP-PostViews Plus ===
Contributors: fantasyworld
Donate link: https://www.paypal.me/RicherYang
Tags: views, hits, counter, postviews, bot, user
Requires at least: 5.0
Requires PHP: 5.6.20
Tested up to: 5.4.1
Stable tag: 2.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

Enables You To Display How Many Times A Post Had Been Viewed By User Or Bot.

== Description ==

It can set that if count the registered member views OR views in index page.
To differentiate between USER and BOT is by HTTP_agent, and it can set at admin

== Installation ==

You can either install it automatically from the WordPress admin, or do it manually:

1. Upload 'wp-postviews-plus' directory to the '/wp-content/plugins/' directory.
2. Activate the plugin 'WP-PostViews Plus' through the 'Plugins' menu in WordPress.
3. Place the show views function in your templates. [function reference](https://richer.tw/wp-postviews-plus/function_reference "function reference")

= Usage =

You need edit you theme to show the post views.
Add `<?php if(function_exists('the_views')) { the_views(); } ?>` to show the post views in your page.

== Screenshots ==

1. Using page
2. setting page
3. widget setting page

== Frequently Asked Questions ==

Please visit the [plugin page](https://richer.tw/wp-postviews-plus " ") with any questions.

== Upgrade Notice ==

== Changelog ==

Please move to [plugin change log](https://richer.tw/wp-postviews-plus/history " ")
