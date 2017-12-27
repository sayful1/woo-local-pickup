<?php
/**
 * Plugin Name: Woo Local PickUp
 * Plugin URI: https://github.com/sayful1/woo-local-pickup
 * Description: Plugin extends WooCommerce local pickup shipping method to add the Pickup Location in Admin panel and user can select the pickup location in front during checkout.
 * Version: 2.1.0
 * Author: Sayful Islam
 * Author URI: https://sayfulislam.com
 * Requires at least: 4.4
 * Tested up to: 4.9
 * Text Domain: woo-local-pickup
 *
 * Original Author: Swetanka Jaiswal
 * Original Plugin URI: https://wordpress.org/plugins/woo-local-pickup/
 *
 * WC requires at least: 2.7
 * WC tested up to: 3.2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins' ) ) ) {

	require_once( dirname( __FILE__ ) . '/includes/class-woo-local-pickup-location-admin.php' );
	require_once( dirname( __FILE__ ) . '/includes/class-woo-local-pickup-location.php' );
	require_once( dirname( __FILE__ ) . '/includes/class-woo-local-pickup-location-activation.php' );

	// Process plugin update
	if ( is_admin() ) {
		require_once( dirname( __FILE__ ) . '/libraries/Woo_Local_Pickup_Location_Updater.php' );
		$updater = Woo_Local_Pickup_Location_Updater::init();
		$updater->setUsername( "sayful1" );
		$updater->setRepository( "woo-local-pickup" );
		$updater->setPluginFile( __FILE__ );
		$updater->run();
	}

	add_action( 'plugins_loaded', array( 'Woo_Local_Pickup_Location', 'get_instance' ) );
	add_action( 'plugins_loaded', array( 'Woo_Local_Pickup_Location_Admin', 'get_instance' ) );
	register_activation_hook( __FILE__, array( 'Woo_Local_Pickup_Location_Activation', 'get_instance' ) );
}