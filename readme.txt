=== Contact Form 7 Postcode Extension ===
Contributors: Mark Hewitt
Tags: Contact Form 7, Contact, Contact Form, postcode
Requires at least: 4.0
Tested up to: 4.2.4
Stable tag: 1.0

This plugin provides 1 new tag types for the Contact Form 7 Plugin. It allows for the dynamic population of a UK address being creating
a single text field that is connected to the http://www.postcodesoftware.net API for fetching addresses via the postal code.

== Description ==

= WHAT DOES IT DO? =

Enables the population of an address by looking up the postal code on http://www.postcodesoftware.net

= HOW TO USE IT =

Simply add a postcode short-code to your form. Ensure you have installed it correctly and it will automatically allow you to
lookup UK address based on their postcode.

= INCLUDED SHORTCODES =

[postcode]

== Installation ==

This section describes how to install the plugin and get it working.

1. Download and install the Contact Form 7 Plugin located at http://wordpress.org/extend/plugins/contact-form-7/
2. Upload the plugin folder to the '/wp-content/plugins/' directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Get the latest code for this extension from http://www.github.com/mrhewitt/contact-form-7-postcode-extension
5. Upload the plugin folder to the '/wp-content/plugins/' directory
6. Activate the plugin through the 'Plugins' menu in WordPress
7. You will now have a [postcode] shortag button on your contact form editor

Note the account/password to for accessing the API is still encoded in the PHP directly for simplicity and security.
On first installation of the software create a .PHP file called account.php with the following template:

<?php 
$POSTCODE_ACCOUNT = '[accountname]';
$POSTCODE_PASSWORD = '[password]';
?>

If this file does not exist it will use the default test account and be limited to using only LS postcodes.


== Frequently Asked Questions ==

None.  Yet.

