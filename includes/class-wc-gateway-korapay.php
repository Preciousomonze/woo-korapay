<?php

namespace WC_KORAPAY;

defined( 'ABSPATH' ) || exit;

/**
 * Kora Pay Payment Gateway class.
 *
 * @class    WC_Gateway_Korapay
 * @extends  WC_Payment_Gateway
 * @version  1.0.0
 * @package  WC_Korapay
 * @category Payment
 */
class WC_Gateway_Korapay extends \WC_Payment_Gateway {
    
	/**
	 * Is test mode active?
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Korapay test public key.
	 *
	 * @var string
	 */
	public $test_public_key;

	/**
	 * Korapay test secret key.
	 *
	 * @var string
	 */
	public $test_secret_key;

	/**
	 * Korapay live public key.
	 *
	 * @var string
	 */
	public $live_public_key;

	/**
	 * Korapay live secret key.
	 *
	 * @var string
	 */
	public $live_secret_key;

	/**
	 * Should orders be marked as complete after payment?
	 * 
	 * @var bool
	 */
	public $autocomplete_order;

	/**
	 * Korapay payment page type.
	 *
	 * @var string
	 */
	public $payment_page_type;

	/**
	 * Should we save customer cards?
	 * 
	 * @TODO: Nope.
	 *
	 * @var bool
	 */
	public $saved_cards;

	/**
	 * Should the cancel & remove order button be removed on the pay for order page.
	 *
	 * @var bool
	 */
	public $remove_cancel_order_button;

	/**
	 * Who bears Korapay charges?
	 *
	 * @var string
	 */
	public $charges_account;

	/**
	 * A flat fee to charge the sub account for each transaction.
	 *
	 * @var string
	 */
	public $transaction_charges;

	/**
	 * Active public key
	 *
	 * @var string
	 */
	public $active_public_key;

	/**
	 * Active secret key
	 *
	 * @var string
	 */
	public $active_secret_key;

	/**
	 * Gateway disabled message
	 *
	 * @var string
	 */
	public $msg;

	/**
	 * Constructor.
	 */
    public function __construct() {
        $this->id                 = 'korapay';
        $this->icon               = ''; // URL to the icon that will be displayed on checkout. TODO
        $this->method_title       = __( 'Kora', 'korapay-payments-gateway' );
        $this->method_description = sprintf( __( 'Accept online payments from local and international customers using Mastercard, Visa, Verve Cards and Bank Accounts. <a href="%1$s" target="_blank">Sign up</a> for a Kora account, and <a href="%2$s" target="_blank">get your API keys</a>.', 'korapay-payments-gateway' ), 'https://korahq.com', 'https://merchant.korapay.com/dashboard/settings/api-integrations' );
        
        $this->payment_page_type = $this->get_option( 'payment_page_type' );

        $this->supported_features();

        // Get our settings field.
        $this->form_fields = WC_Korapay_Settings::get_settings_form_fields();

        // Settings loader.
        $this->init_settings();
        $this->load_settings_data();
            
		// Hooks law :).
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_content' ) );

		// Payment listener.
		add_action( 'woocommerce_api_wc_gateway_korapay', array( $this, 'handle_transaction_verification' ) );

		// Webhook listener/API hook.
		if ( ! empty( $this->custom_webhook_endpoint ) ) {
			add_action( 'woocommerce_api_' . WC_KORAPAY_WEBHOOK_PREFIX . $this->custom_webhook_endpoint , array( $this, 'process_webhook' ) );
		}

        // Our scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
       add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
    }

    /**
     * Load Settings data.
     */
    public function load_settings_data() {
        $this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->enabled            = $this->get_option( 'enabled' );
		$this->testmode           = $this->get_option( 'testmode' ) === 'yes' ? true : false;
		$this->autocomplete_order = $this->get_option( 'autocomplete_order' ) === 'yes' ? true : false;

		$this->test_public_key = $this->get_option( 'test_public_key' );
		$this->test_secret_key = $this->get_option( 'test_secret_key' );

		$this->live_public_key = $this->get_option( 'live_public_key' );
		$this->live_secret_key = $this->get_option( 'live_secret_key' );

		$this->custom_webhook_endpoint = $this->get_option( 'webhook_endpoint' );
		$this->merchant_bears_cost      = $this->get_option( 'customer_bears_cost' ) === 'yes' ? false : true;

		// $this->saved_cards = $this->get_option( 'saved_cards' ) === 'yes' ? true : false;

        // Custom metadata.
		$this->custom_metadata = $this->get_option( 'custom_metadata' ) === 'yes' ? true : false;
		/*
		$this->meta_order_id         = $this->get_option( 'meta_order_id' ) === 'yes' ? true : false;
		$this->meta_name             = $this->get_option( 'meta_name' ) === 'yes' ? true : false;
		$this->meta_email            = $this->get_option( 'meta_email' ) === 'yes' ? true : false;
		$this->meta_phone            = $this->get_option( 'meta_phone' ) === 'yes' ? true : false;
		$this->meta_billing_address  = $this->get_option( 'meta_billing_address' ) === 'yes' ? true : false;
		$this->meta_shipping_address = $this->get_option( 'meta_shipping_address' ) === 'yes' ? true : false;
		$this->meta_products         = $this->get_option( 'meta_products' ) === 'yes' ? true : false;
		*/
        // Let's set the api keys we will be using.
		$this->active_public_key = $this->testmode ? $this->test_public_key : $this->live_public_key;
		$this->active_secret_key = $this->testmode ? $this->test_secret_key : $this->live_secret_key;

		// Check if the gateway can be used.
		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = false;
		}
    }

    /**
     * Support features.
     */
    public function supported_features() {
        $this->supports = array(
			'products',
			// 'refunds', // TODO: Waiting on Team Kora.
			// 'tokenization',
		);
    }

    
	/**
	 * Check if Merchant details is filled.
	 */
	public function admin_notices() {
		if ( 'no' === $this->enabled ) {
			return;
		}

		// Check required fields.
		if ( ! ( $this->active_public_key && $this->active_secret_key ) ) {
			echo '<div class="error"><p>' . sprintf( __( 'Please enter your Kora merchant details <a href="%s">here</a> to be able to use the Kora Gateway For WooCommerce plugin.', 'korapay-payments-gateway' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=korapay' ) ) . '</p></div>';
			return;
		}
	}

	/**
	 * Display Korapay payment icon.
	 */
	public function get_icon() {
        $icon = '<img src="' . $this->get_logo_url() . '" alt="' . apply_filters( 'wc_korapay_icon_alt_txt', __( 'Kora Payment Options', 'korapay-payments-gateway' ) ) . '" width="100px">';
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}


    /**
	 * Get payment icon URL.
     * 
     * Inspiration from Tubiz :).
	 */
	public function get_logo_url() {
		$base_location = wc_get_base_location();
		$url           = \WC_HTTPS::force_https_url( plugins_url( 'assets/images/kora-' . strtolower( $base_location['country'] ) . '.png', WC_KORAPAY_PLUGIN_FILE ) );
		return apply_filters( 'wc_gateway_korapay_icon_url', $url, $this->id );
	}

    /**
	 * Admin Panel Options.
	 */
	public function admin_options() {
		$kora_webhook_url = 'https://merchant.korapay.com/dashboard/settings/api-integrations';
		$user_webhook_url = ( ! empty( $this->custom_webhook_endpoint ) ? WC()->api_request_url(  WC_KORAPAY_WEBHOOK_PREFIX . $this->custom_webhook_endpoint ) : '' );
		?>
		<h2><?php _e( 'Kora', 'korapay-payments-gateway' ); ?>
		<?php
		if ( function_exists( 'wc_back_link' ) ) {
			wc_back_link( __( 'Return to payments', 'korapay-payments-gateway' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
		}
		?>
		</h2>
		<h4>
			<strong>
				<?php
					if ( ! empty( $user_webhook_url ) ) {
            			printf(
                			__( 'Optional: To avoid stories that touch in situations where bad network makes it impossible to verify transactions, set your webhook URL in your Korapay settings <a href="%1$s" target="_blank" rel="noopener noreferrer">here</a> to the URL below:<br><span style="color: red"><pre><code>%2$s</code></pre></span>', 'korapay-payments-gateway' ),
                			$kora_webhook_url,
                			$user_webhook_url
            			);
        			} else {
            			printf(
                			__( 'Optional: To avoid stories that touch in situations where bad network makes it impossible to verify transactions, please set a custom webhook URL by filling the <code>Custom webhook URL</code> field below, then add this URL to your Korapay settings <a href="%1$s" target="_blank" rel="noopener noreferrer">here</a>.', 'korapay-payments-gateway' ),
                			$kora_webhook_url
            			);
        			}
					?>
				</strong>
		</h4>
		<?php
		if ( $this->is_valid_for_use() ) {
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		} else {
			?>
			<div class="inline error"><p><strong><?php _e( 'Kora Payment Gateway Disabled', 'korapay-payments-gateway' ); ?></strong>: <?php echo $this->msg; ?></p></div>
			<?php
		}
	}


	
	/**
	 * Outputs scripts used for payment.
	 */
	public function payment_scripts() {

		if ( isset( $_GET['pay_for_order'] ) || ! is_checkout_pay_page() || $this->enabled === 'no' ) {
			return;
		}

		$order_key = urldecode( $_GET['key'] );
		$order_id  = absint( get_query_var( 'order-pay' ) );
		$order     = wc_get_order( $order_id );

		if ( $this->id !== $order->get_payment_method() ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' );
		$folder = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'src' : 'build' );

		wp_enqueue_script( 'wc_korapay_core', 'https://korablobstorage.blob.core.windows.net/modal-bucket/korapay-collections.min.js', array( 'jquery' ), WC_KORAPAY_VERSION, false );
		wp_enqueue_script( 'wc_korapay', WC_KORAPAY_PLUGIN_URL . 'assets/js/' . $folder . '/frontend/' . $suffix . '.js', array( 'jquery', 'wc_korapay_core' ), WC_KORAPAY_VERSION, false );

		if ( is_checkout_pay_page() && get_query_var( 'order-pay' ) ) {

			$email         = $order->get_billing_email();
			$amount        = $order->get_total() * 100;
			$txnref        = 'kp_' . $order_id . '_' . time();
			$the_order_id  = $order->get_id();
			$the_order_key = $order->get_order_key();
			$currency      = $order->get_currency();

			if ( $the_order_id == $order_id && $the_order_key == $order_key ) {
				$korapay_params['email']    = $email;
				$korapay_params['amount']   = absint( $amount );
				$korapay_params['txnref']   = $txnref;
				$korapay_params['currency'] = $currency;
			}

			$korapay_params = array(
				'key' => $this->public_key,
			);
	
			$order->update_meta_data( '_korapay_txn_ref', $txnref );
			$order->save();
		}

		wp_localize_script( 'wc_korapay', 'wc_korapay_params', $korapay_params );
	}


	/**
	 * Load admin scripts.
	 */
	public function admin_scripts() {

		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$admin_params = array(
			'plugin_url' => WC_KORAPAY_PLUGIN_URL,
		);

		wp_enqueue_script( 'wc_korapay_admin', WC_KORAPAY_PLUGIN_URL . 'assets/js/build/admin/meta' . $suffix . '.js', array( 'jquery' ), WC_KORAPAY_VERSION, true );
		wp_localize_script( 'wc_korapay_admin', 'wc_korapay_admin_params', $admin_params );
	}


    /**
	 * Displays the payment side.
	 *
	 * @param $order_id
	 */
	public function receipt_content( $order_id ) {
		$order = wc_get_order( $order_id );
		echo '<div id="wc-korapay-form">';
		echo '<p>' . __( 'Thank you for your order, please click the button below to pay with Kora.', 'korapay-payments-gateway' ) . '</p>';
		echo '<div id="wc_korapay_form"><form id="order_review" method="post" action="' . WC()->api_request_url( 'wc_gateway_korapay' ) . '"></form><button class="button" id="wc-korapay-payment-btn">' . apply_filters( 'wc_korapay_payment_btn_txt', __( 'Pay Now', 'korapay-payments-gateway' ), $order_id ) . '</button>';

		if ( ! $this->remove_cancel_order_button ) {
			echo '  <a class="button cancel" id="wc-korapay-cancel-payment-btn" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . apply_filters( 'wc_korapay_cancel_payment_btn_txt', __( 'Cancel order &amp; restore your cart', 'korapay-payments-gateway' ), $order_id ) . '</a></div>';
		}

		echo '</div>';
	}


    /**
	 * Process the payment.
	 *
	 * @param int $order_id
	 *
	 * @return array|void
	 */
	public function process_payment( $order_id ) {
		if ( 'redirect' === $this->payment_page_type ) { // It will always be redirect, for now.
			return $this->process_redirect_payment( $order_id );
		}
	}

	/**
	 * Process a redirect payment.
	 *
	 * @param int $order_id
	 * @return array|void
	 */
	public function process_redirect_payment( $order_id ) {
		$order        = wc_get_order( $order_id );
		$amount       = $order->get_total();
		$txn_ref      = 'kp_' . $order_id . '_' . time();
		$webhook_url  = ( ! empty( $this->custom_webhook_endpoint ) ? WC()->api_request_url( WC_KORAPAY_WEBHOOK_PREFIX . $this->custom_webhook_endpoint ) : '' );
		$redirect_url = WC()->api_request_url( 'wc_gateway_korapay' );

		// TODO: Set a setting field to allow non-technical users change this(not necessary).
		
		/**
		 * Filters allowed payment channels.
		 * 
		 * More info here: https://developers.korapay.com/docs/checkout-redirect
		 * Option mobile_money is not available in NGN.
		 * 
		 * @param int $order_id
		 * @return array
		 */		
		$_channels = apply_filters( 'wc_korapay_allowed_payment_channels', array(), $order_id );
		
		/**
		 * Filters default payment channel
		 * 
		 * More info here: https://developers.korapay.com/docs/checkout-redirect
		 * 
		 * @param int $order_id
		 * @return string
		 */		
		$_default_channel = apply_filters( 'wc_korapay_default_payment_channel', '', $order_id );

		$korapay_params = array(
            'amount'              => absint( $amount ),
            'currency'            => $order->get_currency(),
            'reference'           => $txn_ref,
            'redirect_url'        => $redirect_url,
            'narration'           => sprintf( apply_filters( 'wc_korapay_order_narration_text', __( 'Payment for Order #%s', 'korapay-payments-gateway' ) ), $order->get_order_number() ),
            'merchant_bears_cost' => $this->merchant_bears_cost,
			'customer'            => array(
                'email' => $order->get_billing_email(),
                'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            ),
			/*'metadata'           => array( // Once length exceeds 50 Chars, causes issues.
                'meta_order_id'      => $order_id,
                'meta_customer_id'   => $order->get_user_id(),
                'meta_cancel_action' => wc_get_cart_url(),
            ),*/
        );

		if ( ! empty( $_channels ) ) {
			$korapay_params['channels'] = $_channels;
		} if ( ! empty( $_default_channel ) ) {
			 $korapay_params['default_channel'] = $_default_channel;
		} if ( ! empty( $webhook_url ) ) {
			$korapay_params['notification_url'] = $webhook_url;
		}

		// $korapay_params['metadata']['custom_fields'] = $this->get_custom_fields( $order_id );

		$order->update_meta_data( '_korapay_txn_ref', $txn_ref );
		$order->save();

		$response = WC_Korapay_API::send_request( 'charges/initialize', $korapay_params );

		if ( is_wp_error( $response ) ) {
			$errors     = $response->errors ?? null;
			$error_data = $response->error_data ?? null;

			if ( ! is_null( $errors ) && true === apply_filters( 'wc_korapay_log_error', true, $response, $korapay_params ) ) {
				$errors_json     = json_encode( $errors );
				$error_data_json = json_encode( $error_data );
				$error_msg       = 'Korapay response error: ' . $errors_json .' \n Error data: ' . $error_data_json;

				error_log( $error_msg );
	            ( new \WC_Logger() )->debug( $error_msg, array( 'source' => 'korapay-payments-gateway' ) );
			}

            do_action( 'wc_korapay_redirect_payment_error', $response, $korapay_params, $order_id );

            wc_add_notice( apply_filters( 'wc_korapay_redirect_payment_error_msg', __( 'Unable to process payment at this time, try again later.', 'korapay-payments-gateway' ), $response, $order_id ) , 'error' );

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
        }

        // All good! Empty cart and proceed.
        WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $response['data']['checkout_url'],
		);
    }

	/**
	 * Process a token payment.
	 *
     * @TODO - not necessary.
     * 
	 * @param $token
	 * @param $order_id
	 *
	 * @return bool
	 */
	public function _process_token_payment( $token, $order_id ) {

	}

    /**
     * Verify our payment transaction
     * 
     * Verify Payment Transactiona and handle order.
	 * Let's avoid stories that touch abeg.
     */
	public function handle_transaction_verification() {
		if ( isset( $_REQUEST['korapay_txn_ref'] ) ) {
			$txn_ref = sanitize_text_field( $_REQUEST['korapay_txn_ref'] );
		} elseif ( isset( $_REQUEST['reference'] ) ) {
			$txn_ref = sanitize_text_field( $_REQUEST['reference'] );
		} else {
			$txn_ref = false;
		}

		@ob_clean(); // Inspo from Tubiz :).

		if ( ! $txn_ref ) {
			wp_redirect( wc_get_page_permalink( 'cart' ) );
			exit;
		}

		$order_details = explode( '_', $txn_ref );
		$order_id = (int) $order_details[1];
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_redirect( wc_get_page_permalink( 'cart' ) );
			exit;
		}

		$result = $this->process_korapay_transaction( $txn_ref, $order );

		if ( ! $result ) {
			wp_redirect( $this->get_return_url( $order ) );
			exit;
		}

		// If successful, redirect to the order success page.
		wp_redirect( $this->get_return_url( $order ) );
		exit;
	}
	
	/**
	 * Process KoraPay transaction.
	 *
	 * This function verifies and processes KoraPay transactions.
	 *
	 * @param string $txn_ref The transaction reference.
	 * @param WC_Order $order The WooCommerce order object.
	 * @return bool Returns true on success, false on failure.
	 */
	public function process_korapay_transaction( $txn_ref, $order ) {

		$response = WC_Korapay_API::verify_transaction( $txn_ref );

		if ( is_wp_error( $response ) || false === $response['status'] ) {
			$order->update_status( 'failed', __( 'An error occurred while verifying payment on Kora.', 'korapay-payments-gateway' ) );

			$response_data = is_array( $response ) ? json_encode( $response ) : print_r( $response, true );
			$error_msg     = 'Korapay Error: for reference: ' . $txn_ref . '. Response: ' . $response_data;

			error_log( $error_msg );
			( new \WC_Logger() )->debug(
				$error_msg,
				array( 'source' => 'korapay-payments-gateway' )
			);
		
			return false;
		}

		if ( 'success' !== $response['data']['status'] ) {
			$order->update_status( 'failed', __( 'Payment was declined by Kora.', 'korapay-payments-gateway' ) );
			return false;
		}

		// Handle already processed orders.
		if ( in_array( $order->get_status(), array( 'processing', 'completed', 'on-hold' ) ) ) {
			return false;
		}

		// Validate amounts and currencies.
		$order_total      = $order->get_total();
		$order_currency   = $order->get_currency();
		$amount_paid      = $response['data']['amount'];
		$payment_currency = strtoupper( $response['data']['currency'] );
		$korapay_ref      = $response['data']['reference'];

		if ( $amount_paid < absint( $order_total ) ) {
			// Partial payment, hold order.
			$order->update_status( 'on-hold', '' );
			$order->add_meta_data( '_transaction_id', $korapay_ref, true );

			$notice = sprintf(
				__( 'Your payment was successful, but the amount paid is less than the order total. Your order is on hold.', 'korapay-payments-gateway' )
			);
			$order->add_order_note( $notice );
			wc_add_notice( $notice, 'notice' );

			function_exists( 'wc_reduce_stock_levels' ) ? wc_reduce_stock_levels( $order->get_id() ) : $order->reduce_order_stock();
		} elseif ( $payment_currency !== $order_currency ) {
			// Currency mismatch, hold order.
			$order->update_status( 'on-hold', '' );
			$order->update_meta_data( '_transaction_id', $korapay_ref );

			$notice = sprintf(
				__( 'Payment was successful, but the payment currency (%s) is different from the order currency (%s). Order is on hold.', 'korapay-payments-gateway' ),
				$payment_currency, $order_currency
			);
			$order->add_order_note( $notice );
			wc_add_notice( $notice, 'notice' );

			function_exists( 'wc_reduce_stock_levels' ) ? wc_reduce_stock_levels( $order->get_id() ) : $order->reduce_order_stock();
		} elseif ( $korapay_ref !== $order->get_meta( '_korapay_txn_ref' ) ) { // If this isn't same, something was tampered with.
			$order->update_status( 'on-hold', '' );
			$order->update_meta_data( '_transaction_id', $korapay_ref );

			$notice      = sprintf( __( 'Thank you for shopping with us.%1$sYour payment was successful, but transaction reference comparison seems differnet.%2$sYour order is currently on-hold.%3$sKindly contact us for more information regarding your order and payment status.', 'korapay-payments-gateway' ), '<br />', '<br />', '<br />' );
			$notice_type = 'notice';

			// Add Customer Order Note.
			$order->add_order_note( $notice, 1 );

			// Add Admin Order Note.
			$admin_order_note = sprintf( __( '<strong>Issue! Look into this order</strong>%1$sThis order is currently on hold.%2$sReason: Transaction reference comparison failed.%3$sOrder Transaction reference is <strong>%4$s</strong> while the transaction reference from Kora is <strong>%5$s</strong>%6$s<strong>Kora Transaction Reference:</strong> %7$s', 'korapay-payments-gateway' ), '<br />', '<br />', '<br />', $order->get_meta( '_korapay_txn_ref' ), $korapay_ref, '<br />', $korapay_ref );
			$order->add_order_note( $admin_order_note );

			function_exists( 'wc_reduce_stock_levels' ) ? wc_reduce_stock_levels( $order_id ) : $order->reduce_order_stock();
			wc_add_notice( $notice, $notice_type );
		} else {
			// Success, complete the order.
			$order->payment_complete( $korapay_ref );
			$order->add_order_note( sprintf( __( 'Payment via Kora successful (Transaction Reference: %s)', 'korapay-payments-gateway' ), $korapay_ref ) );

			if ( $this->is_autocomplete_order_enabled( $order ) ) {
				$order->update_status( 'completed' );
			}
		}

		$order->save();
		WC()->cart->empty_cart();

		return true;
	}

	/*
	 * Process a refund request from the Order details screen.
     * 
     * Kora doesn't have a refund endpoint, so this is dormant for now.
	 *
	 * @param int $order_id WC Order ID.
	 * @param float|null $amount Refund Amount.
	 * @param string $reason Refund Reason
	 *
	 * @return bool|WP_Error
	 */
	/*public function _process_refund( $order_id, $amount = null, $reason = '' ) {
        return false;

		if ( ! ( $this->active_public_key && $this->active_secret_key ) ) {
			return false;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		$currency = $order->get_currency();
		$txn_id   = $order->get_transaction_id(); // TODO: Confirm what this produces.

        // Get transaction details.
        $response = WC_Korapay_API::verify_transaction( $transaction_id );

		if ( false == $response ) {
            return false;
        }

		if ( 'success' === $response['data']['status'] ) {

			$merchant_note = sprintf( __( 'Refund for Order ID: #%1$s on %2$s', 'korapay-payments-gateway' ), $order_id, get_site_url() );

			$body = array(
				'transaction'   => $transaction_id,
				'amount'        => $amount * 100,
				'currency'      => $currency,
				'customer_note' => $reason,
				'merchant_note' => $merchant_note,
			);

			$headers = array(
				'Authorization' => 'Bearer ' . $this->active_secret_key,
			);

			$args = array(
				'headers' => $headers,
				'timeout' => 60,
				'body'    => $body,
			);

			$refund_endpoint = '/refund';
			$refund_response  = WC_Korapay_API::send_request( $refund_endpoint, $args );

			if ( ! is_wp_error( $refund_response ) ) {

				if ( $refund_response['status'] ) {
					$amount         = wc_price( $amount, array( 'currency' => $currency ) );
					$refund_id      = $refund_response['data']['id'];
					$refund_message = sprintf( __( 'Refunded %1$s. Refund ID: %2$s. Reason: %3$s', 'korapay-payments-gateway' ), $amount, $refund_id, $reason );
					$order->add_order_note( $refund_message );

					return true;
				}

			} else {
				if ( isset( $refund_response->message ) ) {
					return new WP_Error( 'error', $refund_response['message'] );
				} else {
					return new WP_Error( 'error', __( 'Can&#39;t process refund at the moment. Try again later.', 'korapay-payments-gateway' ) );
				}
			}

		}

	}*/
	
	/**
	 * Process Webhook.
	 * 
	 * @TODO: STILL TEST AGAIN.
	 */
	public function process_webhook() {
		if ( ! array_key_exists( 'HTTP_X_KORAPAY_SIGNATURE', $_SERVER ) || ( strtoupper( $_SERVER['REQUEST_METHOD'] ) !== 'POST' ) ) {
			exit;
		}

		$json = file_get_contents( 'php://input' );

		// Validate event to avoid timing attack.
		if ( $_SERVER['HTTP_X_KORAPAY_SIGNATURE'] !== hash_hmac( 'sha512', $json, $this->secret_key ) ) {
			exit;
		}

		$event = json_decode( $json, true );

		if ( 'charge.success' !== strtolower( $event['event'] ) ) {			
			return;
		}

		sleep( 10 );

		$txn_ref = $event['data']['reference'];
		$order_details = explode( '_', $txn_ref );
		$order_id = (int) $order_details[1];
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$result = $this->process_korapay_transaction( $txn_ref, $order );

		if ( ! $result ) {
			exit; // Webhook should exit if transaction is not successful.
		}

		http_response_code( 200 );
		exit;
	}

    // HELPER FUNCTIONS.

	/**
	 * Check if this gateway is enabled and available in the user's country.
	 */
	public function is_valid_for_use() {
		if ( ! in_array( get_woocommerce_currency(), apply_filters( 'wc_korapay_supported_currencies', array( 'NGN', 'GHS', 'KES', 'XAF', 'XOF', 'ZAR' ) ) ) ) {
			$this->msg = sprintf( 
				__( 'Sorry, Kora does not support your store currency. Kindly set it to either NGN (&#8358;), GHS (&#x20b5;), KES (KSh), XAF (FCFA), XOF (CFA), or ZAR (R) <a href="%s">here</a>', 'korapay-payments-gateway' ), 
				admin_url( 'admin.php?page=wc-settings&tab=general' ) 
			);
			return false;
		}
		return true;
	}


    /**
	 * Checks if autocomplete order is enabled for the payment method.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	protected function is_autocomplete_order_enabled( $order ) {
		$payment_method = $order->get_payment_method();
		$settings       = get_option( 'woocommerce_' . $payment_method . '_settings' );

		if ( isset( $settings['autocomplete_order'] ) && 'yes' === $settings['autocomplete_order'] ) {
			return true;
		}

		return false;
	}

}
