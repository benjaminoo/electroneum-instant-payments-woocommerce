<?php
require_once('vendor/Vendor.php');
require_once('vendor/Exception/VendorException.php');
	
class Electroneum_IPS_Gateway extends WC_Payment_Gateway
{	

	private $vendor;
	
	/*
	 * Electroneum IPS Payment Gateway Constructor
	 */
	function __construct()
	{	
		// ID of this class
		$this->id = "electroneum_ips_gateway"; 
		
		// Icon to be used on checkout page when displaying Elecroneum IPS as a payment option
		$this->icon = plugins_url('electroneum-ips-gateway/assets/electroneum-24.png');
		
		// We have no fields to be completed on the checkout page
		$this->has_fields = FALSE;
		
		// Title to be used on checkout page when displaying Elecroneum IPS as a payment option
		$this->method_title = __("Electroneum Instant Payments Gateway", 'electroneum_ips_gateway');
		
		// Description to be used on checkout page when displaying Elecroneum IPS as a payment option
		$this->method_description = __("Allows customers to checkout using Electroneum's Instant Payment System.", 'electroneum_ips_gateway');
				
		// Initialize setting form fields
		$this->init_form_fields();
		$this->init_settings();
				
		// Register methods for validating configuration fields on settings page (such as API key, API secret, vendor outlet ID)
		add_action('admin_notices', array($this, 'validate_fields'));

		// Iterate through all settings and store them as variables in this class
		foreach ($this->settings as $setting_key => $value) {
			$this->$setting_key = $value;
        }
		
		// Register method for handling calls from ETN's servers to our webhook
		add_action('woocommerce_api_electroneum_ips_gateway', array($this, 'callback_handler'));

		// Register methods when an admin is currently logged in
		if (is_admin()) {
			// Save Settings
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			//add_filter('woocommerce_currencies', 'add_my_currency');
			//add_filter('woocommerce_currency_symbol', 'add_my_currency_symbol', 10, 2);
			//add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 2);
		}		
	}
	
	/*
	 * Initializes settings for Electroneum IPS on the Woocomerce settings page
	 */
	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'electroneum_ips_gateway'),
				'type' => 'checkbox',
				'label' => __('Enable the Electroneum Instant Payments Gateway', 'electroneum_ips_gateway'),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __( 'Title', 'electroneum_ips_gateway' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'electroneum_ips_gateway' ),
				'default' => __('Electroneum Instant Payments', 'electroneum_ips_gateway'),
			),
			'description' => array(
				'title' => __('Description', 'electroneum_ips_gateway'),
				'type' => 'textarea',
				'description' => __( 'This controls the description of the Electroneum Instant Payments Gateway that the users sees on checkout.', 'electroneum_ips_gateway' ),
				'default' => __('Pay securely using Electroneum Instant Payment System (BETA). <a href="https://electroneum101.com/what-is-electroneum/" target="_blank">More info</a>', 'electroneum_ips_gateway'),
			),
			'api_key' => array(
                'title' => __('Vendor API Key', 'electroneum_ips_gateway'),
                'type' => 'text',
                'description' => __('Your unique vendor API key as seen on the <a href="https://my.electroneum.com/user/vendor">Electroneum vendor page</a> (requires login).', 'electroneum_ips_gateway'),
                'default' => '',
            ),
			'api_secret' => array(
                'title' => __('Vendor Secret Key', 'electroneum_ips_gateway'),
                'type' => 'text',
                'description' => __('Your unique API secret as seen on the <a href="https://my.electroneum.com/user/vendor">Electroneum vendor page</a> (requires login).', 'electroneum_ips_gateway'),
                'default' => '',
			),
			'vendor_outlet' => array(
                'title' => __('Vendor Outlet Key', 'electroneum_ips_gateway'),
                'type' => 'text',
                'description' => __('Your unique vendor outlet ID as seen on the <a href="https://my.electroneum.com/user/vendor">Electroneum vendor page</a> (requires login).', 'electroneum_ips_gateway'),
                'default' => '',
            )
		);
	}

	/*
	 * Process payment
	 */
	function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );

		// Mark as on-hold (we're awaiting the payment)
		$order->update_status('pending', __( 'Awaiting Electroneum Instant Payment.', 'woocommerce' ));
		
		// Put together URL link with ID of our custom payment page, as well as an array of GET variables to send along
		$redirect = add_query_arg(array('order_id' => $order->get_id(), 'key' => $order->get_order_key()), get_permalink(get_option('payment_post_id')));

		// Return redirect URL to order-received page
		return array(
			'result' => 'success',
			'redirect' => $redirect
		);
	}
	
	/*
	 * Runs on the custom payments page. Shows a payment QR code to customer and checks for payment through AJAX, or alternatively when refreshed (if no JS is available)
	 */
	public function request_payment($order_id)
	{
		// Check if the website owner has completed all necessary configuration, and exit with an error message to the user if not
		if (!$this->validate_fields()) {
			$this->show_error_on_checkout();
			return;
		}
		
		// Instantiate Vendor from Electroneum library 
		$vendor = new \Electroneum\Vendor\Vendor($this->api_key, $this->api_secret);
		
		// Get the order ID from Woocommerce
		$order = wc_get_order($order_id);
		
		// Get the return URL for the "Payment completed" button
		$return_url = $this->get_return_url($order);
		
		// Check if a payment_id was already generated for this order
		if (empty(get_post_meta($order_id, 'etn_ips_payment_id', true))) {
			
			// If no payment_id is found, generate a payment_id from the Vendor class
			$payment_id = $vendor->generatePaymentId();

			// Store the payment_id as order meta information in the database
			$order->update_meta_data( "etn_ips_payment_id", $payment_id);
			$order->save();
		} else {
			//Get the payment_id from the database
			$payment_id = get_post_meta($order_id, 'etn_ips_payment_id', true);
		}		
		
		// Check to make sure the order has not been marked as completed or processing yet (for example by the webhook)
		$order_data = $order->get_data();
		if($order_data['status'] == 'completed' || $order_data['status'] == 'processing')
		{
			// Put together content
			$content = $this->display_payment_success_message($return_url);
			// JS script to redirect to thank-you page after 5 seconds
			$content .= "<script>jQuery(document).ready(setTimeout(redirect_to_shop,5000));</script>";
			
			// Wrap content in a styled div before printing
			echo $this->content_wrap($content);
			
			// Stop execution of this function
			return;
		}
		
		// Poll payment status on ETN's servers
		try {
			// Generate the payload.
			$payload = [
				'payment_id' => $payment_id,
				'vendor_address' => 'etn-it-' . $this->vendor_outlet
			];
			
			// Check for confirmation.
			$result = $vendor->checkPaymentPoll(json_encode($payload));
			
		} catch (\Electroneum\Vendor\Exception\VendorException $error) {
			echo $error;
		}		
	
		// If status == 1, payment was received
		if ($result['status'] == 1) {
			
			global $woocommerce;
			
			// Update order status and mark as complete
			//if($this->virtual_digital_products_in_cart($order_id)){
				//$order->update_status('completed', __('Payment was successful.', 'electroneum_ips_gateway'));
			//} else {
				//$order->update_status('processing', __('Payment was successful.', 'electroneum_ips_gateway'));
			//}
			$order->payment_complete();
			
			// Reduce stock levels
			if ($this->woocommerce_version_check("3.0")) {
				$order->wc_reduce_stock_levels();
			} else {
				$order->reduce_order_stock();
			}

			// Empty the cart
			$woocommerce->cart->empty_cart();
			
			// Put together content
			$content = $this->display_payment_success_message($return_url);
			// JS script to redirect to thank-you page after 5 seconds
			$content .= "<script>jQuery(document).ready(setTimeout(redirect_to_shop,5000));</script>";
			
			// Wrap content in a styled div before printing
			echo $this->content_wrap($content);
			
		} else {
			
			//Check if we have stored an ETN amount for this order in the DB yet
			if (empty(get_post_meta($order_id, 'etn_ips_amount', true))) {
				
				// Get amount (in specified currency) and currency from Woocommerce order
				$amount = floatval(preg_replace('#[^\d.]#', '', $order->get_total()));
				$currency = $order->get_currency();
			
				// Get the ETN amount for the selected currency, converted in real-time by Vendor class
				$etn_amount = $vendor->currencyToEtn($amount, $currency);
				
				// Store the etn_amount as order meta information so it's not changed on refresh
				$order->update_meta_data( "etn_ips_amount", $etn_amount);
				$order->save();
				
			} else {
				// Get the amount from the order meta information
				$etn_amount = get_post_meta($order_id, 'etn_ips_amount', true);
			}
				
			// Enqueue AJAX javascript on the frontend.
			wp_enqueue_script(
				'etn-ips-ajax-script', // Registration handler
				get_site_url() . "/wp-content/plugins/electroneum-instant-payments-for-woocommerce/include/etn-ips-ajax.js", // Location of javascript file to enqueue
				array('jquery')
			);
			// The wp_localize_script allows us to output the path of the Wordpress Ajax library to Javascript for our script to use
			wp_localize_script(
				'etn-ips-ajax-script', // The registration handler of our script enqueued above
				'my_ajax_obj', // Name of Javascript object that will be created
				array('ajaxurl' => admin_url('admin-ajax.php')) // Array of data contained in the object (in this case the URL to ajax file)
			);
						
			// Get the QR code in order to show a QR box to the customer
			$qr_code = $vendor->getQrCode($etn_amount, $this->vendor_outlet, $payment_id);
			
			// Get the plugin directory file to use when including Javascript libraries
			$site_url = get_site_url();
			
			// Print out instructions and QR code box
			$widget_src = $site_url . "/wp-content/plugins/electroneum-instant-payments-for-woocommerce/include/etn.vendor-widget-0.1.0.min.js";
			wp_enqueue_script("etn-ips-widget", $widget_src);
			
			// Put together the output, containing both a payment div with QR code, and a success div which is hidden until payment is confirmed through AJAX
			$content = "
			<div id='etn-ips-waiting-payment'>
				<h3>We are awaiting your payment.</h3>
				Your purchase requires a payment of <b>$etn_amount ETN</b>.
				<br>Scan the QR code in your Electroneum app or click it to start the process.
				<br><br>
				<div data-etn-vendor='$qr_code' data-etn-lang='en'></div>
				<br><br>
				<a style='padding:10px; border:solid 1px black; border-radius:4px; background-color:#00b9eb; cursor:pointer;' onclick='location.reload();'>I've Made the Payment</a>
				<input type='hidden' id='etn-ips-order-id' value='$order_id'>
				<br><br>
			</div>
			<div id='etn-ips-payment-successful' style='display:none;'>" 
			. $this->display_payment_success_message($return_url) 
			. "</div>";
			
			// Wrap the output in a styled div before printing
			echo $this->content_wrap($content);
		}	
	}
	
	/*
	 * Prints a message when payment was successful
	 */
	function display_payment_success_message($return_url) {
		return "
			<center>
				<h3>Electroneum payment successful!</h3>
				<img src='" . plugins_url('electroneum-ips-gateway/assets/checked.png') . "'>
				<br><br>
				Your Electroneum payment has been received and your order is now complete.
				<br><br>
				Redirecting you back to the <a href='" . $return_url . "'>shop</a>...
				<br><br>
				<script>function redirect_to_shop() { window.location.href = '" . $return_url . "'; }</script>
			</center>";
	}
	
	/*
	 * Wraps payment request and success message in a div to create styling consistency
	 */
	function content_wrap($content) {
		$str = "<center style='padding:10px;'>
				<div style='border:solid 1px black; border-radius: 4px; padding:10px; max-width: 500px; background-color: white;'>";
		$str .= $content;
		$str .= "</div></center>";
		$str .= "<br><br>";
		return $str;
	}
	
	/*
	 * Webhook to listen for incoming payments
	 *
	 * Webhook callback address must be formatted as https://yoursite.com/wc-api/electroneum_ips_gateway/
	 */
	public function callback_handler()
	{	
		$vendor = new \Electroneum\Vendor\Vendor($this->api_key, $this->api_secret);
		
		try {
			// Get the payload and signature from an incoming webhook
			$payload = @file_get_contents('php://input');
			$signature = @$_SERVER['HTTP_ETN_SIGNATURE'];
			
			// OVERRIDE: Use the generated test webhook data from https://my.electroneum.com/user/vendor.
			//$payload = "";
			//$signature = "";
			
			// Verify the signature.
			if ($vendor->verifySignature($payload, $signature)) {
				// Signature passed.
				http_response_code(200);
				
				// Log and process the transaction.
				$payload = json_decode($payload);
				
				// Get the payment ID from the payload
				$payment_id = $payload->{'payment_id'};
				
				// Find the order in the database with the associated payment_id, and market as "on-hold"
				$the_query = new WP_Query(array('post_type'=>'shop_order','post_status'=>'wc-pending','meta_value'=>$payment_id,'fields' => 'ids','posts_per_page '=> 1));

				// Loop through the returned array to get the order's ID
				foreach($the_query->posts as $id) {
					$order_id = $id;
				}
				
				// Get the order from Woocommerce and mark it as complete
				$order = wc_get_order($order_id);
				$order->payment_complete();
				
			} else {
				// Signature failed.
				http_response_code(401);
			}
		} catch (\Electroneum\Vendor\Exception\VendorException $error) {
			echo $error;	
		}	
	}
	
	/*
	 * Validate data in setting fields and display errors if necessary 
	 */	
	public function validate_fields()
	{
		if ($this->check_api_key() && $this->check_api_secret() && $this->check_vendor_address()) {
			return true;
		} else {
			return false;
		}
	}
	
	/*
	 * Displays an error (used when website owner has not completed his unique api key, secret, and vendor outlet on Electroneum IPS Settings page) 
	 */	
	function show_error_on_checkout() {
		echo '<div class="woocommerce-notice woocommerce-info">The Electroneum Instant Payments plugin on this website is yet not configured correctly and therefore cannot accept payments. Please alert the website owner if possible.</div>';
	} 


	/*
	 * Check that the supplied API key is valid 
	 */	
	public function check_api_key()
	{	
		// Must be 32 chars long, start with "key_live_", and the rest alpha-numeric
		if (strlen($this->api_key) == 32 && substr($this->api_key, 0,9) == 'key_live_' && ctype_alnum(substr($this->api_key,10))) {
			return true;
		} else {
			if(is_admin()) {
				echo "<div class='notice notice-error is-dismissible'><p>Your Electroneum API key is invalid. Must be 32 characters long and start with <b>key_live_</b>. Find it <a href='https://my.electroneum.com/user/vendor' _target='blank'>here</a> (requires login).</p></div>";
			}
			return false;
		}
	}
	
	/*
	 * Check that the supplied secret key is valid 
	 */	
	public function check_api_secret()
	{	
		// Must be 64 chars long, start with "sec_live_", and the rest alpha-numeric
		if (strlen($this->api_secret) == 64 && substr($this->api_secret, 0,9) == 'sec_live_' && ctype_alnum(substr($this->api_secret,10))) {
			return true;
		} else {
			if(is_admin()) {
				echo "<div class='notice notice-error is-dismissible'><p>Your Electroneum API secret is invalid. Must be 64 characters long and start with <b>sec_live_</b>. Find it <a href='https://my.electroneum.com/user/vendor' _target='blank'>here</a> (requires login).</p></div>";
			}
			return false;
		}
	}
	
	/*
	 * Check that the supplied vendor address is valid 
	 */	
	public function check_vendor_address()
	{	
		// Must be alpha-numeric and 13 chars long
		if (strlen($this->vendor_outlet) == 13 && ctype_alnum($this->vendor_outlet)) {
			return true;
		} else {
			if(is_admin()) {
				echo "<div class='notice notice-error is-dismissible'><p>Your Electroneum vendor ID is invalid. Must be 13 characters long and alpha-numeric. Find it <a href='https://my.electroneum.com/user/vendor' _target='blank'>here</a> (requires login).</p></div>";
			}
			return false;
		}
	}
	
	/*
	 * Check if site's Woocommerce version is newer (higher) than specified version 
	 */	
	public function woocommerce_version_check( $version = '2.1' ) {
		// Is Woocommerce installed?
		if ( function_exists( 'is_woocommerce_active' ) && is_woocommerce_active() ) {
			global $woocommerce;
			if( version_compare( $woocommerce->version, $version, ">=" ) ) {
				return true;
			}
		}
	return false;
	}
}
?>