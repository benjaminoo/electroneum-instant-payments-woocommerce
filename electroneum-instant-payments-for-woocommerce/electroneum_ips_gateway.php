<?php
/*
Plugin Name: Electroneum Instant Payments for WooCommerce
Plugin URI: https://electroneum101.com/woocommerce-plugin/
Description: Ads Electroneum Instant Payments as a payment method for Woocommerce. Provides customers with instant visual confirmation upon payment.
Version: 1.1.4
Author: Electroneum101
Author URI: http://electroneum101.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: electroneum_ips_gateway
*/

// Exit if file is accessed directly rather than through Wordpress
if (!defined('ABSPATH')) {
    exit;
}

// Register function that initializes the Electroneum IPS plugin
add_action('plugins_loaded', 'electroneum_ips_gateway_init', 0);

// Plugin constructor. Checks if Woocomerce is installed and loads the payment gateway
function electroneum_ips_gateway_init()
{
	global $woocommerce;
	
    // Check if Woocommerce is installed
	if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;
    if (!class_exists('WC_Payment_Gateway')) return;
	
	// Check if the order currency is supported. If not, do not register ETN as a payment method, and show a notice on the settings page
	if (!in_array(get_woocommerce_currency(), ["AUD","BRL","BTC","CAD","CDF","CHF","CLP","CNY","CZK","DKK","EUR","GBP","HKD","HUF","IDR","ILS","INR","JPY","KRW","MXN","MYR","NOK","NZD","PHP","PKR","PLN","RUB","SEK","SGD","THB","TRY","TWD","USD","ZAR"])) {
		// Show a notice and exit
		add_action('admin_notices', 'show_error_currency_not_supported');
		return;
	}
		
	// Include our payment gateway
	require_once('include/electroneum_ips_library.php');

    // Register our gateway class with Woocommerce
    add_filter('woocommerce_payment_gateways', 'electroneum_ips_gateway');
    function electroneum_ips_gateway($methods)
    {
        $methods[] = 'Electroneum_IPS_Gateway';
        return $methods;
    }
}

// Add function that run on AJAX polls - to check if order is completed yet
add_action( 'wp_ajax_check_order_status', 'my_check_order_status'); // Logged in users
add_action( 'wp_ajax_nopriv_check_order_status', 'my_check_order_status'); // Logged out users

// Register custom post type for Electroneum IPS - enables custom payment page
add_action( 'init', 'create_electroneum_ips_post_type');

// Create page using custom post type - enables custom payment page
add_action( 'init', 'create_electroneum_ips_payment_page');

// Load custom template for our payment page with custom post type
add_filter( 'template_include', 'electroneum_ips_payment_page_template');

// Add a menu item that links to the Electroneum Instant Payments Gateway options tab in Woocommerce
add_action('admin_menu', 'electroneum_ips_create_menu');

// Add additional links under the plugin name on the plugins page
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'electroneum_ips_plugin_action_links' );

/*
 * Loads settings menu item on left sidebar for Electroneum IPS Gateway
 */
function electroneum_ips_create_menu()
{
    add_menu_page(
        __('Electroneum Instant Payments Gateway', 'textdomain'),	// Page title
        'Electroneum IPS',											// Menu title
        'manage_options',											// Capability
        'admin.php?page=wc-settings&tab=checkout&section=electroneum_ips_gateway', // Menu slug
        '',															// Function
        plugins_url('electroneum-instant-payments-for-woocommerce/assets/electroneum.png'),			// Icon URL  
        56 															// Position on menu
    );
}

/*
 * Adds additional links under the plugin name on the plugins page
 */
function electroneum_ips_plugin_action_links( $links ) {
	$links = array_merge( array(
		'<a href="admin.php?page=wc-settings&tab=checkout&section=electroneum_ips_gateway">' . __( 'Settings', 'electroneum_ips_gateway' ) . '</a>'
	), $links );
	return $links;
}

/*
 * AJAX call to check order status
 */
function my_check_order_status() {
	
	// Load instance of Electroneum IPS Gateway
	$gateway = new Electroneum_IPS_Gateway;
	
	// Get the order ID from the AJAX POST call
	$order_id = $_POST['order_id'];

	// Get the order from Woocommerce and get data
	$order = wc_get_order($order_id);
	$order_data = $order->get_data();

	// If order is "completed" or "processing", we can give confirmation that payment has gone through
	if($order_data['status'] == 'completed' || $order_data['status'] == 'processing')
	{
		echo 1; // Payment completed
	} elseif ($order_data['status'] == 'pending') {
		echo 0; // Payment not completed
	}
	
	// Always end AJAX-printing scripts with die();
	die();
}

/*
 * Create a post using our custom post type - this helps us display a custom payment page
 */
function create_electroneum_ips_payment_page() {
	
	global $wpdb;
	
	// Get the ID of our custom payments page from settings
	$payment_post_id = get_option('payment_post_id');
	
	// Create a custom GUID (URL) for our custom for our payments page
	$guid = home_url('/electroneum-ips/electroneum');
	
	// Check to see if the post ID returned has post_type = "electroneum-ips" and guid equal to one generated above
	if ($payment_post_id && get_post_type($payment_post_id) == "electroneum_ips" && get_the_guid($payment_post_id) == $guid) {
		// Post already created, so return
		return;
	} else {
		// Put together data to create the custom post
		$page_data = array(
			'post_status' => 'publish',
			'post_type' => 'electroneum_ips',
			'post_title' => 'Electroneum',
			'post_content' => 'Electroneum Instant Payments',
			'comment_status' => 'closed',
			'guid' => $guid,
		);
		
		// Create the post
		$payment_post_id = wp_insert_post($page_data);
		
		// Update our settings with the ID of the newly created post
		$ppp = update_option('payment_post_id', $payment_post_id);
	}	
}

/*
 * Create a custom post type - this helps us display a custom payment page
 */
function create_electroneum_ips_post_type() {
	// Register our new post type, "electroneum-ips"
	register_post_type('electroneum_ips',
		array(
			'labels' => array(
				'name' => __('Electroneum Instant Payment'),
				'singular_name' => __('Electroneum Instant Payment')
			),
			'public' => true,
			'has_archive' => false,
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'show_in_admin_bar' => false,
			'show_in_rest' => false,
			'hierarchical' => false,
			'supports' => array('title'),
		)
	);
	flush_rewrite_rules();
}

/*
 * Returns our custom template file if post type is "electroneum-ips", else return the usual one
 */
function electroneum_ips_payment_page_template($page_template) {
	// Check if the post type if "electroneum-ips"
	if (get_post_type() && get_post_type() === 'electroneum_ips') {
		// Return our custom template file
		return dirname(__FILE__) . '/templates/electroneum_ips_payment.php';
	}	
	// Return usual template file
	return $page_template;
}

function show_error_currency_not_supported()
{	
	if(is_admin() && $_GET['page'] == "wc-settings" && $_GET['section'] == "electroneum_ips_gateway") {
		$wc_settings_url = home_url('/wp-admin/admin.php?page=wc-settings');
		echo "<div class='notice notice-error is-dismissible'><p>Your store's currency is currently not supported by Electroneum Instant Payments. Please change it to a supported currency under <b>Currency Options</b> on the WooCommerce <a href='$wc_settings_url'>general settings page</a>. You can view a list of supported currencies on <a href='https://community.electroneum.com/t/using-the-etn-instant-payment-api/121' target='_blank'>this page</a>.</div>";
	}
}
?>