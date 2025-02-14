<?php

namespace WC_KORAPAY;

defined( 'ABSPATH' ) || exit;

/**
 * Kora Pay Settings class helper.
 *
 * @class    WC_Korapay_Settings
 * @version  1.0.0
 * @package  WC_Korapay
 * @category Payment
 */
class WC_Korapay_Settings {
    
	/**
	 * Is test mode active?
	 *
	 * @var bool
	 */
	protected $settings_form_fields;

    /**
     * Retrieve the API key based on the environment and key type.
     *
     * @param string $key_type Either 'public' or 'secret'.
     * @return string The corresponding API key.
    */
    public static function get_active_key( $key_type ) {
        $settings = get_option( 'woocommerce_korapay_settings' );

        // Check if test mode is enabled.
        $test_mode = ( isset( $settings['testmode'] ) && 'yes' === $settings['testmode'] );
        
        // Determine the appropriate key to return based on the key type and environment.
        if ( 'public' === $key_type ) {
            return $test_mode ? $settings['test_public_key'] : $settings['live_public_key'];
        } elseif ( 'secret' === $key_type ) {
            return $test_mode ? $settings['test_secret_key'] : $settings['live_secret_key'];
        }

        // Return an empty string if the key type is not recognized.
        return '';
    }
    /**
     * Settings Form Field.
     */
    public static function get_settings_form_fields() {

        $settings_form_fields = array(
            'enabled'                          => array(
                'title'       => __( 'Enable/Disable', 'korapay-payments-gateway' ),
                'label'       => __( 'Enable Kora', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'description' => __( 'Enable Kora as a payment option on your website\'s checkout page.', 'korapay-payments-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'title'                            => array(
                'title'       => __( 'Title', 'korapay-payments-gateway' ),
                'type'        => 'text',
                'description' => __( 'This controls the payment method title which the user sees during checkout.', 'korapay-payments-gateway' ),
                'default'     => apply_filters( 'wc_korapay_default_gateway_title', __( 'Kora - Secure Bank, Card & USSD Payments', 'korapay-payments-gateway' ) ),
                'desc_tip'    => true,
                'custom_attributes' => array(
                    'readonly' => true,
                )
            ),
            'description'                      => array(
                'title'       => __( 'Description', 'korapay-payments-gateway' ),
                'type'        => 'textarea',
                'description' => __( 'This controls the payment method description which the user sees during checkout.', 'korapay-payments-gateway' ),
                'default'     => apply_filters( 'wc_korapay_default_gateway_description', __( 'Pay seamlessly using your bank, card, or USSD', 'korapay-payments-gateway' ) ),
                'desc_tip'    => true,
            ),
            'webhook_endpoint'                 => array(
                'title'             => __( 'Custom Webhook URL Endpoint', 'korapay-payments-gateway' ),
                'type'              => 'text',
                'description'       => sprintf( __( 'Enter your custom webhook URL endpoint here, your webhook URL will be:<code id="wc-korapay-wh-url">%s<span>{YOUR URL}</span></code>', 'korapay-payments-gateway' ), rtrim( WC()->api_request_url(  WC_KORAPAY_WEBHOOK_PREFIX ), '/' ) ),
                'default'           => '',
                'desc_tip'          => false,
                'custom_attributes' => array(
                    'title'    => __( 'Enter a valid webhook URL endpoint. Only lowercase letters, numbers, hyphens, and underscores are allowed. Max 15 characters.', 'korapay-payments-gateway' ),
                    'maxlength' => '15',
                ),
            ),
            'testmode'                         => array(
                'title'       => __( 'Test mode', 'korapay-payments-gateway' ),
                'label'       => __( 'Enable Test Mode', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'description' => __( 'Test mode enables you to test payments before going live. <br />Once the LIVE MODE is enabled on your Kora account uncheck this.', 'korapay-payments-gateway' ),
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            'payment_page_type'                => array(
                'title'       => __( 'Payment Option', 'korapay-payments-gateway' ),
                'type'        => 'select',
                'description' => __( 'Popup shows the Kora payment popup on the page while Redirect will redirect the customer to Kora to make payment.', 'korapay-payments-gateway' ),
                'default'     => '',
                'desc_tip'    => false,
                'options'     => array(
                    ''          => __( 'Select One', 'korapay-payments-gateway' ),
                    'inline'    => __( 'Popup', 'korapay-payments-gateway' ),
                    'redirect'  => __( 'Redirect', 'korapay-payments-gateway' ),
                ),
            ),
            'test_secret_key'                  => array(
                'title'       => __( 'Test Secret Key', 'korapay-payments-gateway' ),
                'type'        => 'password',
                'description' => __( 'Enter your Test Secret Key here', 'korapay-payments-gateway' ),
                'default'     => '',
            ),
            'test_public_key'                  => array(
                'title'       => __( 'Test Public Key', 'korapay-payments-gateway' ),
                'type'        => 'text',
                'description' => __( 'Enter your Test Public Key here.', 'korapay-payments-gateway' ),
                'default'     => '',
            ),
            'live_secret_key'                  => array(
                'title'       => __( 'Live Secret Key', 'korapay-payments-gateway' ),
                'type'        => 'password',
                'description' => __( 'Enter your Live Secret Key here.', 'korapay-payments-gateway' ),
                'default'     => '',
            ),
            'live_public_key'                  => array(
                'title'       => __( 'Live Public Key', 'korapay-payments-gateway' ),
                'type'        => 'text',
                'description' => __( 'Enter your Live Public Key here.', 'korapay-payments-gateway' ),
                'default'     => '',
            ),
            'customer_bears_cost'              => array(
                'title'       => __( 'Make Customers bear cost', 'korapay-payments-gateway' ),
                'label'       => __( 'Make Customers bear transction charge cost', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-customer-bear-cost',
                'description' => __( 'If enabled, the customer will bear the cost of the transaction charge', 'korapay-payments-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'autocomplete_order'               => array(
                'title'       => __( 'Autocomplete Order After Payment', 'korapay-payments-gateway' ),
                'label'       => __( 'Autocomplete Order', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-autocomplete-order',
                'description' => __( 'If enabled, the order will be marked as complete after successful payment', 'korapay-payments-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'remove_cancel_order_button'       => array(
                'title'       => __( 'Remove Cancel Order & Restore Cart Button', 'korapay-payments-gateway' ),
                'label'       => __( 'Remove the cancel order & restore cart button on the pay for order page', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
            ),/*
            'saved_cards'                      => array(
                'title'       => __( 'Saved Cards', 'korapay-payments-gateway' ),
                'label'       => __( 'Enable Payment via Saved Cards', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'description' => __( 'If enabled, users will be able to pay with a saved card during checkout. Card details are saved on Korapay servers, not on your store.<br>Note that you need to have a valid SSL certificate installed.', 'korapay-payments-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'custom_metadata'                  => array(
                'title'       => __( 'Custom Metadata', 'korapay-payments-gateway' ),
                'label'       => __( 'Enable Custom Metadata', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-metadata',
                'description' => __( 'If enabled, you will be able to send more information about the order to Korapay.', 'korapay-payments-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_order_id'                    => array(
                'title'       => __( 'Order ID', 'korapay-payments-gateway' ),
                'label'       => __( 'Send Order ID', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-order-id',
                'description' => __( 'If checked, the Order ID will be sent to Korapay', 'korapay-payments-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_name'                        => array(
                'title'       => __( 'Customer Name', 'korapay-payments-gateway' ),
                'label'       => __( 'Send Customer Name', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-name',
                'description' => __( 'If checked, the customer full name will be sent to Korapay', 'korapay-payments-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_email'                       => array(
                'title'       => __( 'Customer Email', 'korapay-payments-gateway' ),
                'label'       => __( 'Send Customer Email', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-email',
                'description' => __( 'If checked, the customer email address will be sent to Korapay', 'korapay-payments-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_phone'                       => array(
                'title'       => __( 'Customer Phone', 'korapay-payments-gateway' ),
                'label'       => __( 'Send Customer Phone', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-phone',
                'description' => __( 'If checked, the customer phone will be sent to Korapay', 'korapay-payments-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_billing_address'             => array(
                'title'       => __( 'Order Billing Address', 'korapay-payments-gateway' ),
                'label'       => __( 'Send Order Billing Address', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-billing-address',
                'description' => __( 'If checked, the order billing address will be sent to Korapay', 'korapay-payments-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_shipping_address'            => array(
                'title'       => __( 'Order Shipping Address', 'korapay-payments-gateway' ),
                'label'       => __( 'Send Order Shipping Address', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-shipping-address',
                'description' => __( 'If checked, the order shipping address will be sent to Korapay', 'korapay-payments-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_products'                    => array(
                'title'       => __( 'Product(s) Purchased', 'korapay-payments-gateway' ),
                'label'       => __( 'Send Product(s) Purchased', 'korapay-payments-gateway' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-products',
                'description' => __( 'If checked, the product(s) purchased will be sent to Korapay', 'korapay-payments-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),*/
        );
        
        return apply_filters( 'wc_korapay_settings', $settings_form_fields );
    }
}
