<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Novapay API Class
 */
class Novapay_API {

    private $api_key;

    /**
     * Constructor
     *
     * @param string $api_key Novapay API Key
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Create a new order/transaction
     *
     * @param array $data Order data
     * @return array|WP_Error
     */
    public function create_order($data) {
        $api_url = 'https://novapay.solutions/api/v1/cryptoTransaction/CreateCryptoOrder'; // [cite: 11, 12] API endpoint

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key, // [cite: 12]
            ),
            'body' => json_encode($data),
            'method' => 'POST', // [cite: 12]
            'timeout' => 45,
        );

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        return $result;
    }

    /**
     * Get order status
     *
     * @param string $code Order code
     * @return array|WP_Error
     */
    public function get_order_status($code) {
        $api_url = 'https://novapay.solutions/api/v1/cryptoTransaction/CryptoOrderStatus'; // [cite: 20, 21] API endpoint

        $data = array(
            'code' => $code, // [cite: 23, 24]
        );

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key, // [cite: 22]
            ),
            'body' => json_encode($data),
            'method' => 'POST', // [cite: 21]
            'timeout' => 45,
        );

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        return $result;
    }
}
