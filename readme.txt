=== AVH Amazon ===
Contributors: petervanderdoes
Donate link: http://blog.avirtualhome.com/wordpress-plugins/
Tags: amazon, wishlist, widget, wedding registry, baby registry, shortcode, post, page
Requires at least: 2.5
Tested up to: 2.8
Stable tag: 3.0.5

The AVH Amazon plugin gives you the ability to show items from your Amazon wishlist by using widgets or shortcode in posts and pages.

== Description ==

The AVH Amazon plugin gives you the ability to add multiple widgets which will display one or more random item(s) from your Amazon wishlist, baby registry and/or wedding registry.
It also has the ability to show an item with its link, in posts and pages by use of shortcode. 

In the plugin reference is made to Wishlist only but you can use your Baby Registry ID or Wedding Registry ID as well.

= Features =

* General
	* Works with amazon.com, and locales amazon.ca, amazon.de and amazon.co.uk.

* Wishlist
	* Add Associated ID.
	* Choice of thumbnail size, Small/Medium/Large.
	* Option to use unlimited widgets.
	* Multiple items from the same Wish List can be displayed in the widget.
	* A configurable footer can be displayed on the bottom of the widget linking to the list on Amazon.

* Shortcode
	* Create the shortcode with the help of a metabox
	* In the metabox you can select an item or select to randomize the items from your wishlist or search for an item by ASIN. 
	* The shortcode creates text, picture or text & picture links.
	* If a text link or text & picture link is used, the default text is the item description from Amazon but the text of the link can be changed.
	* The value all for the ASIN option will show all items from your wishlist. In combination with a text & picture link type you can create a wishlist page.

* Tools
	* Look up your wishlist ID.

== Installation ==

The avh-amazon plugin can be installed in 3 easy steps:

1. Unzip the "avh-amazon" archive and put the directory "avh-amazon" into your "plugins" folder (wp-content/plugins).
1. Activate the plugin.

== Frequently Asked Questions ==

= Can I use this plugin if I don't have a widget enabled theme? =
Yes you can, you can use the following code to display the wishlist:
 $avhwidget=& new AVHAmazonWidget();$avhwidget->widgetWishlist(array(),1 , FALSE); 

= What about support? =
I created a support site at http://forums.avirtualhome.com where you can ask questions or request features.

= Is the plug in available in my language? =
Maybe, maybe not, I don't really know what your language is, but you can find out in the directory languages. If it's not available feel free to translate it, it's only a few lines of text :). Send your translation to me and I will add it and give you credit for it.

= Where is the Baby/Wedding Registry widget? =
There is no separate widget for the registries. To show the registry items use the Wishlist widget and use your Baby Registry ID or Wedding Registry ID.

= How do I find my Baby Registry and/or Wedding Registry ID? =
When you create either registry Amazon sends you an email with the direct link to access your registry. The ID is the last part of the URL.
Example:
http://www.amazon.com/gp/registry/1234567890ABC
The ID is 1234567890ABC

= What is an ASIN? =
ASIN stands for Amazon Standard Identification Number. Every product has its own ASIN--a unique code they use to identify it. For books, the ASIN is the same as the 10-digit ISBN number.
You will find an item's ASIN on the product detail page.

= Amazon Policy Change per May 11, 2009 =
Amazon has decided that calls to Amazon have to signed using a secret key you receive as a developer. Because this key can be used for other purposes as this plugin it is necessary for everybody who uses this plugin to sign up as a developer and receive their secret key.
You can sign up at the following pages and signing up is free:

Canada https://associates.amazon.ca/gp/flex/advertising/api/sign-in.html
Germany https://partnernet.amazon.de/gp/flex/advertising/api/sign-in.html
United Kingdom https://affiliate-program.amazon.co.uk/gp/flex/advertising/api/sign-in.html
United States https://affiliate-program.amazon.com/gp/flex/advertising/api/sign-in.html

After the registration is complete go to this page:
https://aws-portal.amazon.com/gp/aws/developer/account/index.html?ie=UTF8&action=access-key

And select Access Identifiers You will the ability to see your secret key, if you don't see one generate one. Copy your key into the options page of the plugin and you are all set.

If you don't get a secret key all calls from this plugin to Amazon will fail per August 15, 2009.
Until you enter your secret key, you will see a reminder to do this once day in the Admin section WordPress, and all the time when you go to the settings page of this plugin.

== Screenshots ==

None

== Changelog ==
= Version 3.0.5 -
* Bugfix: When using the secret key, the call to Amazon would fail.

= Version 3.0.4 =
* Bugfix: When multiple pages of a list were retrieved and no AWS Key was supplied an error would occur.
* Bugfix: Certain options weren't saved in the widget.
* Bugfix: Shortcodes didn't work. Call to wrong class.

= Version 3.0.3 =
* If the plugin was installed prior to upgrading to WordPress 2.8, the widgets would disappear from the sidebars.

= Version 3.0.2 =
* Reported in conjunction with my AVH Extended Categories widget. The self class does not exists in getInstance.

= Version 3.0.1 =
* Conflict with declaration of sha256

= Version 3.0 =
* It uses the new Widget class introduced in WordPress 2.8.
* Optimizations for WordPress 2.8.
* Amazon policy change. Calls to Amazon need to be signed per August 15 of 2009. In order to sign calls you will need an Amazon Web Services account. See the FAQ for more details.
* Picture is shown in metabox for ASIN search.
* Use of WordPress defined variables, fixes problems when wp-content directory is moved.

= Version 2.4 =
* Speed improvements.
* Increased security.
* Reduced memory footprint.
* RFC: Ability to select picture size in the short code.
* Bugfix: Shortcode URL's for items not in a awishlist were wrong.
* Several other small bugs are fixed.

= Version 2.3.4 =
* Bugfix: Footer option in widget didn't show.

= Version 2.3.3 =
* Bugfix: In the shortcode the default associate ID wasn't set to the right one when using a a non US locale.

= Version 2.3.2 =
* Bugfix: The shortcode didn't retrieve the assiocated ID set in the admin page.

= Version 2.3.1 =
* Bugfix: Certain character were not displayed correctly (Characters with Umlauts for example)

= Version 2.3 =
* RFC: When calling the widget directly the array parameter can hold the widget options.
* Updated WDSL
* With WordPress 2.7, when deleting the plugin it will clean up the database, removing the entries related the plugin.
* Source code improvements.

= Version 2.2.4 =
* Bugfix: The footer in the widget wasn't linking correctly.

= Version 2.2.3 =
* Bugfix: Metabox wasn't displayed properly in WordPress 2.7

= Version 2.2.2 =
* Bugfix: Support for non-widget code was broken.
* Bugfix: When running PHP 5 some warnings "Call-time pass-by-reference has been deprecated"

= Version 2.2.1 =
* Bugfix: Widget didn't get the default values.

= Version 2.2 =
* Display a "no image available" picture when no picture is available.
* Ability to set default settings for the shortcode.
* Improve storage of the options.
* Clear the cache folder at each upgrade.
* Show searching indicator when searching for WishList or Asin in the Shortcode Metabox.
* Bugfix: Fixed memory problem. Problem was not using avhamazon class in the shortcode.
* Bugfix: Metabox was displayed incorrectly on the Page page.
* RFC: Added option pic+text for the linktype parameter in the shortcode.
* RFC: Added option all for the asin parameter in the shortcode. This will show all items from a wishlist.

= Version 2.1 =
* Changed the amount of widgets you can use from 9 to unlimited.

= Version 2.0.1 =
* Bugfix: Problem with widget header.

= Version 2.0 =
* Compatibility changed to WordPress 2.5 and higher.
* RFC: Added shortcode implementation.
* Number of items to be displayed in the widget can now be changed per widget.

= Version 1.5 =
* Bugfix: Old WSDL was used to look up the Wish List
* RFC: Added Amazon.co.uk compatibility. 

= Version 1.4 =
* Bugfix: Only 10 Items from a Wish List were loaded to setup the widget. All items are loaded now.
* Bugfix: When the list contains one item the widget wouldn't process it correctly (No image and wrong link).
* RFC: Added Amazon.de compatibility.
* RFC: Multiple items from the same Wish List can be displayed in the widget.
* RFC: A configurable footer can be displayed on the bottom of the widget linking to the list on Amazon.
* Updated WSDL Location.
* Code improvements.

= Version 1.3 =
* RFC: Added Amazon.ca compatibility.

= Version 1.2 =
* Bug Fix: Creation of the link for the Wish List.

= Version 1.1 =
* Link from widget makes Amazon recognize it as a link from a Wish List. And the shipping address from the Wish List can be used.
* Several bug fixes of files not found for WordPress 2.3. 
* Code clean up.

= Version 1.0.1 =
* Bug Fix: Don't display purchased items from the lists.
* Bug Fix: HTML problem when using WordPress 2.3

= Version 1.0 =
* Added compatibility with WordPress 2.5
* Added option to use multiple widgets.

= Version 0.4.1 =
* Bug Fix: Didn't display picture anymore 

 Version 0.4
* Added an "tools" option under Admin -> Manage. You can look up your Wishlist ID by entering the email you use at Amazon. There is no check if the email is valid, if it isn't you won't see your wishlist(s)

== Glossary ==
	* RFC - Request For Change. Changes requested by users.