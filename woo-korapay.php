<?php
/*
 * Plugin Name: Kora Pay WooCommerce Payment Gateway
 * Plugin URI: https://yourwebsite.com/kora-pay
 * Description: A WooCommerce payment gateway for Kora Pay.
 * Version: 1.0.0
 * Author: Precious Omonzejele
 * Author URI: https://yourwebsite.com
 * Text Domain: wc-korapay
 * Domain Path: /languages/
 * WC requires at least: 4.0
 * WC tested up to: 7.0
 */

namespace WC_KORAPAY;

defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'WC_KORAPAY_VERSION', '1.0.0' );
define( 'WC_KORAPAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_KORAPAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


if ( !function_exists( 'WC_KORAPAY\\wc_korapay_gateway_init' ) ) {
    /**
     * Initialize the Kora Pay payment gateway.
     *
     * This function checks if WooCommerce is active, then loads the gateway class and settings.
     *
     * @return void
     */
    function wc_korapay_gateway_init() {
        // Ensure WooCommerce is active before proceeding.
        if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Payment_Gateway' ) ) {
			add_action( 'admin_notices', 'WC_KORAPAY\\missing_wc_notice' );
	        return;
        }

        // Include the necessary classes for the payment gateway.
        require_once WC_KORAPAY_PLUGIN_DIR . 'includes/class-wc-korapay-gateway.php';
        
        // Register the Kora Pay gateway with WooCommerce.
        add_filter( 'woocommerce_payment_gateways', 'WC_KORAPAY\\add_gateway_class' );
    }
}
add_action( 'plugins_loaded', 'WC_KORAPAY\\wc_korapay_gateway_init', 11 );

if ( !function_exists( 'WC_KORAPAY\\add_gateway_class' ) ) {
    /**
     * Add the Kora Pay gateway to WooCommerce's list of payment gateways.
     *
     * @param array $gateways Array of WooCommerce payment gateway classes.
     * @return array Modified array of payment gateway classes including Kora Pay.
     */
    function add_gateway_class( $gateways ) {
        $gateways[] = 'WC_KORAPAY\\WC_Korapay_Gateway';
        return $gateways;
    }
}

if ( !function_exists( 'WC_KORAPAY\\gateway_action_links' ) ) {
    /**
     * Add custom action links to the plugin on the plugins page.
     *
     * @param array $links Array of existing action links.
     * @return array Modified array of action links including the settings link.
     */
    function gateway_action_links( $links ) {
        // Define the settings link.
        $plugin_links = [
            '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=korapay' ) ) . '">' . __( 'Settings', 'wc-korapay' ) . '</a>',
        ];

        // Merge and return the new array of links.
        return array_merge( $plugin_links, $links );
    }
}

// Hook to add the action links to the plugin.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'WC_KORAPAY\\gateway_action_links' );


if ( ! function_exists( 'WC_KORAPAY\\missing_wc_notice' ) ) {

	/**
	 * WooCommerce fallback notice.
	 *
	 * @since 1.0.0
	 */
	function missing_wc_notice() {
		$install_url = wp_nonce_url(
			add_query_arg(
				[
					'action' => 'install-plugin',
					'plugin' => 'woocommerce',
				],
				admin_url( 'update.php' )
			),
			'install-plugin_woocommerce'
		);

		$admin_notice_content = sprintf(
			// translators: 1$-2$: opening and closing <strong> tags, 3$-4$: link tags, takes to woocommerce plugin on wp.org, 5$-6$: opening and closing link tags, leads to plugins.php in admin
			esc_html__( '%1$sWooCommerce Korapay Gateway is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for the Koraypay Gateway to work. Please %5$sinstall & activate WooCommerce &raquo;%6$s', 'wc-korapay' ),
			'<strong>',
			'</strong>',
			'<a href="http://wordpress.org/extend/plugins/woocommerce/">',
			'</a>',
			'<a href="' . esc_url( $install_url ) . '">',
			'</a>'
		);

		echo '<div class="error">';
		echo '<p>' . wp_kses_post( $admin_notice_content ) . '</p>';
		echo '</div>';
	}
}
