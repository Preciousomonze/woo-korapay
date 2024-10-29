jQuery( function( $ ) {
	'use strict';

	/**
	 * Object to handle Korapay admin functions.
	 */
	var wc_korapay_admin = {
		/**
		 * Initialize.
		 */
		init: function() {

			// Toggle API key settings.
			$( '#woocommerce_korapay_testmode' ).on( 'change', function() {
				var isChecked = $( this ).is( ':checked' );
				wc_korapay_admin.toggleKeys( isChecked, '#woocommerce_korapay_test_secret_key', '#woocommerce_korapay_test_public_key', '#woocommerce_korapay_live_secret_key', '#woocommerce_korapay_live_public_key' );
			}).trigger( 'change' );

			// Toggle split payment settings.
			$( '.woocommerce_korapay_split_payment' ).on( 'change', function() {
				var isChecked = $( this ).is( ':checked' );
				wc_korapay_admin.toggleSplitPaymentFields( isChecked );
			}).trigger( 'change' );

			// Toggle Custom Metadata settings.
			$( '.wc-korapay-metadata' ).on( 'change', function() {
				wc_korapay_admin.toggleCustomMetadata( $( this ).is( ':checked' ) );
			}).trigger( 'change' );

			// Toggle Bank filters settings.
			$( '.wc-korapay-payment-channels' ).on( 'change', function() {
				wc_korapay_admin.toggleBankFilters( $( this ).val() );
			}).trigger( 'change' );

			// Secret key visibility toggle.
			$( '#woocommerce_korapay_test_secret_key, #woocommerce_korapay_live_secret_key' ).after(
				'<button class="wc-korapay-toggle-secret" style="height: 30px; margin-left: 2px; cursor: pointer"><span class="dashicons dashicons-visibility"></span></button>'
			);

			$( '.wc-korapay-toggle-secret' ).on( 'click', function( event ) {
				event.preventDefault();
				wc_korapay_admin.toggleSecretVisibility( $( this ) );
			});

			// Restrict webhook endpoint field to allow only lowercase letters, numbers, hyphens, and underscores.
			$( '#woocommerce_korapay_webhook_endpoint' ).on( 'change', function() {
				var sanitizedValue = $( this ).val().toLowerCase().replace( /[^a-z0-9-_]/g, '' );
                sanitizedValue     = sanitizedValue.substring( 0, 15 );
				$( this ).val( sanitizedValue );
                $( '#wc-korapay-wh-url span' ).text( ( sanitizedValue !== '' ? sanitizedValue : '{YOUR URL}' ) );
			});

            $( '#woocommerce_korapay_webhook_endpoint' ).on( 'keyup', function() {
                var currentValue = $( this ).val();
                $( '#wc-korapay-wh-url span' ).text( ( currentValue.length  > 0 ? currentValue : '{YOUR URL}' ) );
            });
            
		},

		/**
		 * Toggle visibility of test/live API keys based on test mode status.
		 */
		toggleKeys: function( isTestMode, testSecretKey, testPublicKey, liveSecretKey, livePublicKey ) {
			$( testSecretKey ).closest( 'tr' ).toggle( isTestMode );
			$( testPublicKey ).closest( 'tr' ).toggle( isTestMode );
			$( liveSecretKey ).closest( 'tr' ).toggle( !isTestMode );
			$( livePublicKey ).closest( 'tr' ).toggle( !isTestMode );
		},

		/**
		 * Toggle split payment related fields.
		 */
		toggleSplitPaymentFields: function( isSplitPaymentEnabled ) {
			$( '.woocommerce_korapay_subaccount_code, .woocommerce_korapay_split_payment_charge_account, .woocommerce_korapay_split_payment_transaction_charge' )
				.closest( 'tr' ).toggle( isSplitPaymentEnabled );
		},

		/**
		 * Toggle Custom Metadata fields.
		 */
		toggleCustomMetadata: function( isMetadataEnabled ) {
			$( '.wc-korapay-meta-order-id, .wc-korapay-meta-name, .wc-korapay-meta-email, .wc-korapay-meta-phone, .wc-korapay-meta-billing-address, .wc-korapay-meta-shipping-address, .wc-korapay-meta-products' )
				.closest( 'tr' ).toggle( isMetadataEnabled );
		},

		/**
		 * Toggle Bank filters (cards and banks) based on selected payment channels.
		 */
		toggleBankFilters: function( channels ) {
			var show = $.inArray( 'card', channels ) !== -1;
			$( '.wc-korapay-cards-allowed, .wc-korapay-banks-allowed' ).closest( 'tr' ).toggle( show );
		},

		/**
		 * Toggle visibility of secret key fields.
		 */
		toggleSecretVisibility: function( button ) {
			var $dashicon = button.find( '.dashicons' ),
				$input = button.closest( 'tr' ).find( '.input-text' ),
				isTextType = $input.attr( 'type' ) === 'text';

			$input.attr( 'type', isTextType ? 'password' : 'text' );
			$dashicon.toggleClass( 'dashicons-visibility dashicons-hidden' );
		}
	};

	wc_korapay_admin.init();

});
