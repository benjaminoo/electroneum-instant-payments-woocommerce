=== Electroneum Instant Payments for WooCommerce ===
Contributors: electroneum101
Donate link: http://electroneum101.com/
Tags: electroneum, etn, ips, instant payments, crypto, cryptocurrency, woocommerce, shop, store, cart, e-commerce, payment, payments 
Requires at least: 4.7
Tested up to: 4.9.8
Stable tag: 1.1.2
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept Electroneum Instant Payments on your WooCommerce store.

== Description ==

This is an Electroneum Instant Payments gateway for WooCommerce. It allows you to safely and securely start accepting Electroneum as payment on your WooCommerce-powered store.

= Features =

* Displays an Electroneum QR code to the customer on checkout, which can be scanned with the Electroneum app or clicked to begin the payment process (works seamlessly on mobile and desktop).
* Checks for payment in the background and automatically displays instant payment confirmation to the customer, immediately after payment has been made. No need for the customer to click buttons or refresh pages.
* Automatically redirects back to the "Thank You" page once payment has been received.
* The cart is only cleared when payment is received. Customers can therefore come back at a later stage and continue where they left of.
* Supports multiple payment checking methods, including direct polls and webhooks. 
* Fast and easy setup. Simply install, activate, and provide your unique API key, API secret, and vendor outlet on the plugin *Settings* page.

= Get Involved =

Developers are welcome. Please contribute to the source code on the [GitHub repository](https://github.com/benjaminoo/electroneum-instant-payments-woocommerce).

== Installation ==

1. Install the plugin through the WordPress plugins screen by going to *Plugins* -> *Add New*, and searching for *Electroneum Instant Payments for Woocoomerce*. Alternatively, upload the [plugin files](https://github.com/benjaminoo/electroneum-instant-payments-woocommerce/releases) to the  `/wp-content/plugins/plugin-name` directory.
1. Activate the plugin by navigating to *Plugins* -> *Installed Plugins* and clicking on *Activate* below the Electroneum Instant Payments for Woocommerce plugin name.
1. Visit Electroneum's user [vendor page](https://my.electroneum.com/user/vendor) (requires login) and follow the prompts to enter your details and create a vendor outlet.
1. For the webhook address, enter `https://yoursite.com/wc-api/electroneum_ips_gateway/` replacing *yoursite.com* with your site's main URL. This must be in place for payment status to be checked automatically in the backgroud. Note that you must have HTTPS to be enabled for this to work.
1. On your Wordpress dashboard, click on the *Electroneum IPS* menu item in the left navigation bar. This will take you to the plugin's settings page in Woocommerce.
1. Enter your API key, API secret, and vendor outlet ID in the fields provided and save your settings.

== Frequently Asked Questions ==

= Where do I sign up as an Electroneum Vendor? =

Navigate to the Electroneum [user vendor page](https://my.electroneum.com/user/vendor) and follow the prompts to enter your details and create a vendor outlet.

= Where do I get my API credentials? =

After signing up as a vendor on Electroneum's website, your API key and API secret will be displayed on the [user vendor page](https://my.electroneum.com/user/vendor). Your vendor outlet ID will be displayed on the [outlets page](https://my.electroneum.com/user/vendor/brands).

= What is an API Webhook? =

The API Webhook is a web address that you enter on your Electroneum vendor page, that points to a background page on your website. Electroneum sends payment information to this page every time a customer has made a payment. This enables automatic checking for payment confirmation in the background and streamlines the front-end user experience.

= How do I set my API Webhook? =

Navigate to the Electroneum [user vendor page](https://my.electroneum.com/user/vendor) and insert the following webhook in the appropriate field:

`https://yoursite.com/wc-api/electroneum_ips_gateway/`

...replacing *yoursite.com* with your website's main URL. Note that this URL must start with https://

== Screenshots ==

1. The Electroneum QR code as displayed to the customer on checkout.
2. The success message after payment has been confirmed.
3. Plugin settings page, where you can enter your Electroneum vendor details.

== Changelog ==

= 1.1.2 =
* Fix - Fix path to Javascript libraries

= 1.1.0 =
* Feature - Automatic checking for payment confirmation in the background
* Tweak - Improved order flow for better user experience and cart-retention

== Upgrade Notice ==

= 1.1.2 =
Fixes path to required Javascript libraries.

= 1.1.0 =
Initial release on Wordpress.