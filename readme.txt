=== Orbital Feed Reader ===
Contributors: mttktz
Donate link: https://www.gittip.com/mattkatz/
Tags: feed reader, google reader, rss, atom, 
Requires at least: 3.9.1
Tested up to: 4.3
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


= 0.2.1 - Polyglot =
* Supports translations
* bugfix: WP_cron no longer updating feeds
* bugfix: Installations with a non-edit user may crash
* bugfix: Uninstall doesn't clean up.

= 0.2 Wordpress integration =
* Display Blogroll as a widget
* centralize all $http in service
* new keyboard shortcut feed switcher
* Security: Add NONCE and Permission valdation to ajax calls
* selectedFeedController should know read/unread pref
* Move Mark Feed Read to feeds class
* Extract Feeds and Entries to their own backend
* Use the ajaxurl global variable so we don't have to localize the admin_ajax
* bugfix: Add feed Title discovery to simplepie 
* bugfix: Toggling settings returns false
* bugfix: fix the display of dates

= 0.1.9.1 - FOCUS = 
* bugfix: 4.0 introduces a strange box-shadow on focused elements.

= 0.1.9 - Born Free =
* Better import of large OPML files
* Import feed tags in OPML files
* Allow users to edit feeds from OPML before importing
* Show opml export link to the user. 
* Public OPML export for not logged in users
* Export tags in opml
* Mark feeds private
* Check export for private feeds!
* Make the feedlist collapsible with tags
* Add unread count to All feed
* Better note that we are at end of entries
* bugfix: Upgrading causes orphaned feeds
* bugfix: fix read/unread toggling
* bugfix: Private status for feeds not showing in feed editor

= 0.1.8.1 - remove error message =
* bugfix: called a WP function too early for some cases

= 0.1.8 - Beauty Treatment =
* Flatten and improve the style
* Move feed list to the left
* Move the action bar into the wp admin_bar
* Make sure admin bar items collapse properly
* Actually looks half decent
* Usable on android browsers ( might be iphone too)
* Clean up logging

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


