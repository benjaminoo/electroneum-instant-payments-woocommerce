<?php 
require_once(__DIR__ . '/../include/electroneum_ips_library.php');

// Instantiate new instance of Electroneum IPS Gateway
$etn_ips_gateway = new Electroneum_IPS_Gateway;

// Get order_id from GET
$order_id = $_GET['order_id'];

// Print out page headers (as defined by current template)
get_header();

// Start function to check for order payment and print out appropriate instructions
$etn_ips_gateway->request_payment($order_id);

// Print out page footers (as defined by current template) 
get_footer(); 

?>