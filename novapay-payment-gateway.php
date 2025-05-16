<?php
/**
 * Plugin Name: Novapay Payment Gateway
 * Description: Adds Novapay payment gateway to WooCommerce.
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

/**
 * Include the main gateway class.
 */
function novapay_init_gateway_class() {
    include_once(plugin_dir_path(__FILE__) . 'includes/class-novapay-gateway.php');
}
add_action('plugins_loaded', 'novapay_init_gateway_class');

/**
 * Add the gateway to WooCommerce.
 */
function novapay_add_to_woocommerce($methods) {
    $methods[] = 'WC_Novapay_Gateway';
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'novapay_add_to_woocommerce');
