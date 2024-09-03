<?php

namespace WC_KORAPAY;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Korapay_API class.
 *
 * Handles communication with the Kora Pay API, including transaction verification and refunds.
 */
class WC_Korapay_API {

    /**
     * The API base URL for Kora Pay.
     *
     * @var string
     */
    private static $api_url = 'ttps://api.korapay.com/merchant/api/v1/';


    /**
     * Get the arguments for the API request.
     *
     * Prepares the necessary arguments for the API request, including headers and body.
     *
     * @param array $body The request body to send.
     * @param int $timeout If we need to set a timeout.
     * @param string $method The HTTP method to use (GET, POST, etc.).
     * @return array The arguments for the API request.
     */
    private static function get_request_args( $body = array(), $timeout = 0, $method = 'POST' ) {
        // Set up the request headers.
        $headers = array(
            'Authorization' => 'Bearer ' . get_option( 'wc_korapay_api_key' ),
            'Content-Type'  => 'application/json',
        );

        // Prepare arguments for the request.
        $args = array(
            'headers' => $headers,
            'method'  => strtoupper( $method ),
        );

        // Include the body if it's a POST request.
        if ( ! empty( $body ) && 'POST' === strtoupper( $method ) ) {
            $args['body'] = wp_json_encode( $body );
        }

        // Check timeout.
        if ( 0 < (int) $timeout ) {
            $args['timeout'] = $timeout;
        }

        return $args;
    }

    /**
     * Send a request to the Kora Pay API.
     *
     * @param string $endpoint The API endpoint to call.
     * @param array $body The request body to send.
     * @param int $timeout If we need to set a timeout.
     * @param string $method The HTTP method to use (GET, POST, etc.).
     * @return array|WP_Error The processed response from the API or a WP_Error on failure.
     */
    public static function send_request( $endpoint, $body = array(), $timeout = 0, $method = 'POST' ) {
        $url = self::$api_url . ltrim( $endpoint, '/' );

        // Prepare the request arguments based on the method.
        $args = self::get_request_args( $body, $timeout, $method );

        // Send the request using appropriate WP HTTP function.
        $response = ( 'GET' === strtoupper( $method ) ) ? wp_remote_get( $url, $args ) : wp_remote_post( $url, $args );

        // Check for errors in the response.
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Decode the JSON response.
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        // Check if the API call was successful.
        if ( isset( $data['status'] ) && ( true === $data['status'] || 'success' === $data['status'] ) ) {
            // Return the data on successful API call.
            return $data;
        }

        // Return an error if the API call failed.
        return new \WP_Error( 'korapay_api_failed', __( 'API call to Kora Pay failed.', 'woo-korapay' ) );
    }

    /**
     * Verify a transaction with the Kora Pay API.
     *
     * @param string $transaction_id The ID of the transaction to verify.
     * @return array|WP_Error The response from the Kora Pay API or a WP_Error on failure.
     */
    public static function verify_transaction( $transaction_id ) {
        // Construct the API endpoint for transaction verification.
        $endpoint = 'charges/' . $transaction_id;

        // Send the API request using the send_request method.
        $response = self::send_request( $endpoint, array(), 'GET' );

        // Return the response, which is either the data array or WP_Error.
        return $response;
    }

    /**
     * Process a refund with the Kora Pay API.
     *
     * This method sends a request to the Kora Pay API to process a refund.
     *
     * @param string $transaction_id The ID of the transaction to refund.
     * @param float $amount The amount to refund.
     * @return array|WP_Error The response from the Kora Pay API or a WP_Error on failure.
     */
    public static function process_refund( $transaction_id, $amount ) {
        // Construct the API endpoint for processing refunds.
        $endpoint = '/transactions/refund';

        // Prepare the request body.
        $body = array(
            'transaction_id' => $transaction_id,
            'amount'         => $amount,
        );

        // Send the API request using the send_request method.
        $response = self::send_request( $endpoint, $body, 'POST' );

        // Return the response, which is either the data array or WP_Error.
        return $response;
    }

}
