<?php
/**
 * Collectco Local Pickup
 *
 * Plugin extends woocommerce local pickup shipping method to add the Pickup Location in Admin panel and user can select the pickup location in front during checkout.
 *
 * Plugin Name: 	  Woo Local PickUp
 * Description: 	  Plugin extends woocommerce local pickup shipping method to add the Pickup Location in Admin panel and user can select the pickup location in front during checkout.
 * Version:     	  1.0.0
 * Author:      	  Swetanka Jaiswal
 * Author URI:  	  #
 * Text Domain: 	  woo-local-pickup
 */


/**
 * If this file is called directly, abort.
 **/
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	/*----------------------------------------------------------------------------*
	 * Front-Facing Functionality
	 *----------------------------------------------------------------------------*/

	/*
	 * Require public facing functionality
	 */
	require_once( plugin_dir_path( __FILE__ ) . 'front/class-woo-local-pickup-location.php' );

	/*
	 * Register hooks that are fired when the plugin is activated or deactivated.
	 * When the plugin is deleted, the uninstall.php file is loaded.
	 */
	register_activation_hook( __FILE__, array( 'Woo_Local_Pickup_Location', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'Woo_Local_Pickup_Location', 'deactivate' ) );

	/*
	 * Get instance
	 */
	add_action( 'plugins_loaded', array( 'Woo_Local_Pickup_Location', 'get_instance' ) );


	/*----------------------------------------------------------------------------*
	 * Administrative Functionality
	 *----------------------------------------------------------------------------*/

	/*
	 * Require admin functionality
	 */
	if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

		require_once( plugin_dir_path( __FILE__ ) . 'admin/class-woo-local-pickup-location-admin.php' );
		add_action( 'plugins_loaded', array( 'Woo_Local_Pickup_Location_Admin', 'get_instance' ) );

	}

}