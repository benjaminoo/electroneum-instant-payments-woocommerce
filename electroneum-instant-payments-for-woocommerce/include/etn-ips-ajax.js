// Initiate handle for the timer
var timeOutId = 0;

/*
 * Ajax function that polls the website server (not ETN server) every 2 seconds to confirm payment
 */
function check_order_completed() {	
	
	// Send the AJAX request
	jQuery.ajax({
		url: my_ajax_obj.ajaxurl, // This variable was localized in plugin backend (contains path to Wordpress Ajax library)
		type: 'post',
		data: {
			'action': 'check_order_status', // Plugin function to call on AJAX request
			'order_id' : jQuery('#etn-ips-order-id').val() // Data about the order
		},
		success:function(response) {
			// If response = 0, order is not paid yet. Set another timeout for 2 seconds
			if (response == 0) {
				setTimeout(check_order_completed, 2000);
			// If response = 1, order is paid. Hide QR div, show payment confirmation div, and stop the timeout requests
			} else if (response == 1) {
				// Show and hide appropriate DIVs
				jQuery('#etn-ips-waiting-payment').hide();
				jQuery('#etn-ips-payment-successful').show();

				// Clear the timeout
				clearTimeout(timeOutId);

				// Set a timeout to redirect to thank-you page after 5 seconds
				setTimeout(redirect_to_shop, 5000);
			}
		},
		error: function(jqXHR, textStatus, errorThrown){
			console.log(errorThrown);
		}
	});  	
}

// Start running the function above when the page loads
jQuery(document).ready(check_order_completed());