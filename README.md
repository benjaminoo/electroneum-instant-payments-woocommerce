# Electroneum Instant Payments for WooCommerce

This is an Electroneum Instant Payments gateway for WooCommerce. It allows you to safely and securely accept Electroneum as payment on any WooCommerce-powered store.

# Features

* Displays an Electroneum QR code to the customer on checkout, which can be scanned with the Electroneum app or clicked to begin the payment process (works seamlessly on mobile and desktop).
* Checks for payment in the background and automatically displays instant payment confirmation to the customer, immediately after payment has been made. No need for the customer to click buttons or refresh pages.
* Automatically redirects back to the "Thank You" page once payment has been received.
* The cart is only cleared when payment is received. Customers can therefore come back at a later stage and continue where they left of.
* Supports multiple payment checking methods, including direct polls and webhooks. 
* Fast and easy setup. Simply install, activate, and provide your unique API key, API secret, and vendor outlet on the plugin *Settings* page.

# Installation

Installation is very easy and can be done through the Wordpress plugin manager on any Wordpress website.

1. In your Wordpress dashboard, navigate to *Plugins* -> *Add New* and searching for *electroneum woocoomerce*, and install the plugin by Electroneum101 called "Electroneum Instant Payments for WooCommerce". Alternatively, you can [download](https://wordpress.org/plugins/electroneum-instant-payments-for-woocommerce/) the plugin from the Wordpress directory and upload it manually.
1. Activate the plugin by navigating to *Plugins* -> *Installed Plugins* and clicking on *Activate* below the Electroneum Instant Payments for Woocommerce plugin name.
1. Visit Electroneum's user [vendor page](https://my.electroneum.com/user/vendor) (requires login) and follow the prompts to enter your details and create a vendor outlet.
1. For the webhook address, enter `https://yoursite.com/wc-api/electroneum_ips_gateway/` replacing *yoursite.com* with your site's main URL. This must be in place for payment status to be checked automatically in the backgroud. Note that you must have HTTPS to be enabled for this to work.
1. On your Wordpress dashboard, click on the *Electroneum IPS* menu item in the left navigation bar. This will take you to the plugin's settings page in Woocommerce.
1. Enter your API key, API secret, and vendor outlet ID in the fields provided and save your settings.

That's it. Your plugin is now configured and should be ready for action.

# Development

## Features in Development

* The plugin currently does not check that the store uses one of Electroneum's supported currencies (as visible on the Value tab of the Electroneum app). I'm currently developing a check for this.

## Known Issues

The following known issue does not harm the functioning of the plugin in any way:

* There is an issue when entering the API keys and API secret, the a notice is displayed after saving - even though the items entered are perfectly valid. This notice can be dismissed by reloading the page. My guess is that the notice is generated before the settings are saved. If you know how to solve this issue, please let me know. 
