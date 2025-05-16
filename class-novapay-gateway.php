<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Novapay Payment Gateway Class
 */
class WC_Novapay_Gateway extends WC_Payment_Gateway {

    /**
     * Class constructor
     */
    public function __construct() {

        $this->id = 'novapay'; // payment gateway plugin ID
        $this->icon = apply_filters('woocommerce_novapay_icon', ''); // URL of the icon that will be displayed on checkout page
        $this->has_fields = false; // Indicates whether the payment gateway has its own payment fields
        $this->method_title = 'Novapay';  // Title of the payment method shown on the admin page. Can be read- and writable since 2.5.0
        $this->method_description = 'Allows payments via Novapay.'; // Description of the payment method shown on the admin page

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->api_key = $this->get_option('api_key');
        $this->callback_url = $this->get_option('callback_url');

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_wc_novapay_gateway', array($this, 'novapay_callback_handler')); // Callback handler
        add_action('woocommerce_thankyou', array($this, 'thankyou_page')); // Thank you page message

    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title'   => 'Enable/Disable',
                'type'    => 'checkbox',
                'label'   => 'Enable Novapay Payment',
                'default' => 'yes'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Novapay',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Pay with Novapay',
            ),
            'api_key' => array(
                'title'       => 'API Key',
                'type'        => 'text',
                'description' => 'Enter your Novapay API Key.',
                'default'     => '',
            ),
            'callback_url' => array(
                'title'       => 'Callback URL',
                'type'        => 'text',
                'description' => 'Enter the URL where Novapay will redirect the user after payment.',
                'default'     => '',
            ),
        );
    }

    /**
     * Process the payment and redirect to Novapay
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id) {

        global $woocommerce;

        $order = wc_get_order($order_id);

        $api_url = 'https://novapay.solutions/api/v1/cryptoTransaction/CreateCryptoOrder'; // API endpoint [cite: 11, 12]
        $api_key = $this->api_key;
        $amount = $order->get_total();
        $merchant_trx_id = $order_id . '_' . time(); // Unique transaction ID

        $data = array(
            'merchantTrxId' => $merchant_trx_id, // [cite: 13, 14]
            'fiatAmount' => $amount,  // [cite: 13, 14, 15]
        );

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key, // [cite: 12]
            ),
            'body' => json_encode($data),
            'method' => 'POST',
            'timeout' => 45,
        );

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            wc_add_notice('Payment error: ' . $response->get_error_message(), 'error');
            return array(
                'result' => 'fail',
            );
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['code'])) {
            $order->update_status('pending', __('Pending payment via Novapay.', 'woocommerce'));
            $payment_url = 'https://novapay.solutions/gateway/code/' . $result['code']; // Redirect URL [cite: 19]

            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $payment_url,
            );
        } else {
            wc_add_notice('Payment error: Invalid response from Novapay.', 'error');
            return array(
                'result' => 'fail',
            );
        }
    }

    /**
     * Handle Novapay callback
     */
    public function novapay_callback_handler() {
        @ob_clean();
        header('HTTP/1.1 200 OK');
        die('Novapay Callback Successful');
        //TODO: Implement callback handling to update order status if needed
    }

    /**
     * Thank you page
     */
    public function thankyou_page($order_id) {
        // Display a thank you message (optional)
        echo '<p>' . __('Thank you for your order.', 'woocommerce') . '</p>';
    }
}
