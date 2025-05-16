<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Handle Novapay callback
 */
add_action('woocommerce_api_wc_novapay_gateway', 'novapay_process_callback');

function novapay_process_callback() {
    global $woocommerce;

    if (isset($_GET['code'])) {
        $code = sanitize_text_field($_GET['code']);
        $order_id = intval(explode('_', $code)[0]); // Extract order ID from the code
        $order = wc_get_order($order_id);

        if ($order) {
            $api_key = get_post_meta($order_id, '_novapay_api_key', true);
            $novapay_api = new Novapay_API($api_key);
            $response = $novapay_api->get_order_status($code);

            if (!is_wp_error($response) && isset($response['status'])) {
                $status = intval($response['status']);

                switch ($status) {
                    case 0: // submitted
                        $order->update_status('pending', __('Novapay: Transaction submitted.', 'woocommerce'));
                        break;
                    case 1: // canceled
                        $order->update_status('cancelled', __('Novapay: Transaction canceled.', 'woocommerce'));
                        break;
                    case 2: // rejected
                        $order->update_status('failed', __('Novapay: Transaction rejected.', 'woocommerce'));
                        break;
                    case 3: // pending
                        $order->update_status('pending', __('Novapay: Transaction pending.', 'woocommerce'));
                        break;
                    case 4: // completed
                        $order->payment_complete();
                        $order->add_order_note(__('Novapay: Payment completed.', 'woocommerce'));
                        break;
                    case 5: // refunded
                        $order->update_status('refunded', __('Novapay: Transaction refunded.', 'woocommerce'));
                        break;
                    default:
                        $order->update_status('failed', __('Novapay: Unknown transaction status.', 'woocommerce'));
                        break;
                }

                // Redirect to the order received page
                wp_safe_redirect($order->get_checkout_order_received_url());
                exit;

            } else {
                // Log the error and redirect to the checkout page with an error message
                error_log('Novapay Callback Error: ' . print_r($response, true));
                wc_add_notice(__('Payment error: Could not retrieve transaction status from Novapay.', 'woocommerce'), 'error');
                wp_safe_redirect(wc_get_checkout_url());
                exit;
            }

        } else {
            // Log the error and redirect to the homepage
            error_log('Novapay Callback Error: Order not found.');
            wc_add_notice(__('Payment error: Order not found.', 'woocommerce'), 'error');
            wp_safe_redirect(home_url());
            exit;
        }

    } else {
        // Log the error and redirect to the homepage
        error_log('Novapay Callback Error: No code provided.');
        wc_add_notice(__('Payment error: No transaction code provided.', 'woocommerce'), 'error');
        wp_safe_redirect(home_url());
        exit;
    }
}
