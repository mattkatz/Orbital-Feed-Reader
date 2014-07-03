=== Orbital Feed Reader ===
Contributors: mttktz
Donate link: https://www.gittip.com/mattkatz/
Tags: feed reader, google reader, rss, atom, 
Requires at least: 3.0.1
Tested up to: 3.9
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Read where you write. Orbital integrates a feed reader into WordPress. Blog directly from your feeds!

== Description ==

Feed readers turn the internet into a better place, but most of them are either hosted by someone else or they are tied to the desktop. With Orbital you can own your feeds, right in your WordPress admin panel.

Even better, because it is integrated into WordPress, you can reblog the great stuff you find and share it faster. Post better content, more often, by reducing the friction between where you read and where you write.

== Installation ==

Just as simple as you'd expect. 

1. Upload the Orbital Feed Reader directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

Orbital will add a few sample feeds and instructional entries to get you started. Click the `+` sign to add your favorites or import an OPML file.


== Frequently Asked Questions ==

= What if it doesn't work? =

Please let me know asap! The [Orbital issue tracker is over on github](http://github.com/mattkatz/Orbital-Feed-Reader/issues).

== Screenshots ==

1. Quick screencast of hitting the ⬇ key, finding an article to blog, adding an image and publishing.


== Changelog ==

= 0.1.7.2 - missing feedlist =
* bugfix: Strict php systems were blowing up feadlist get. Made the call more failsafe.

= 0.1.7.1 - headline fix = 
* bugfix: doubled headlines!

= 0.1.7 - Multi-User = 
* bugfix: feed unread count is multiplied by tag count
* Multi-user support
* MU - a fresh install should install feeds for each user
* MU - handle deleting a user
* MU - support adding a new user

= 0.1.6.1 - Tagfix = 
* bugfix: bad unread count on some feeds
* bugfix: unread count on feeds was multiplied by tag count for all feeds

= 0.1.6 - Tagging =
* Tag/Categorize feeds
* bugfix: Shouldn't insert duplicate user entries
* bugfix: Up shouldn't unmark read
* bugfix: problem with inifinite scroll
* bugfix: Show Read items doesn't untoggle
* bugfix: fix parseint on chrome
* bugfix: fix save to only use user_feed_id
* bugfix: Publish Date still not being properly stored
* bugfix: unsubscribe button isn't on newline

= 0.1.5 - Sort Order -  = 
* bugfix: The subscription window says RSS url, not URL or RSS URL
* bugfix: Make the feed edit add refresh bigger! People must click!
* bugfix: remove this y-indicator idea inspired by newsblur.
* bugfix: fix layout issue
* show feed title when autodiscovering feeds
* Dynamically change sort order
* Make sort order a setting
* make the entries display according to my chosen sort order

= 0.1.4 = 
* fixed some issues with running the plugin on PHP 5.2
* Pull created and updated date directly from the feed

= 0.1.3 = 
read/unread issue

= 0.1.2 = 
* Fixed some JS files that were missing

= 0.1.1 = 
* Renamed the menu to Orbital with unread count
* Added a pointer to the Orbital Menu item on first start.
* Add a link to settings from the plugins page
* Add a setting to auto quote text if nothing is highlighted.

= 0.1 =
First release. 
You should be able to 

* Add and remove feeds 
* Subscribe to new feeds with OPML 
* Edit the descriptions feeds.
* Mark items as read or unread.


