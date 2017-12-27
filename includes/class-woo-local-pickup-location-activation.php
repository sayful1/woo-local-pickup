<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Woo_Local_Pickup_Location_Activation {

	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function __construct() {
		$this->upgrade();
	}

	/**
	 * Update data on plugin activation
	 */
	public function upgrade() {
		$current_version = get_option( '_woo_local_pickup_version', '1.0.0' );

		if ( version_compare( $current_version, '2.0.0', '<' ) ) {
			$this->upgrade_to_2_0_0();
		}

		update_option( '_woo_local_pickup_version', '2.0.0', false );
	}

	/**
	 * Add pickup location on activation
	 */
	private function upgrade_to_2_0_0() {
		$_orders = get_posts( array(
			'post_type'      => 'shop_order',
			'posts_per_page' => - 1,
			'post_status'    => 'any',
			'meta_query'     => array(
				array(
					'key'     => 'user_pickup_location',
					'compare' => 'EXISTS',
				)
			),
		) );

		if ( count( $_orders ) > 0 ) {
			foreach ( $_orders as $order ) {
				$_location_id     = (int) get_post_meta( $order->ID, 'user_pickup_location', true );
				$_pickup_location = get_post_meta( $order->ID, '_pickup_location', true );

				if ( $_location_id && empty( $_pickup_location ) ) {
					$_location_title   = get_the_title( $_location_id );
					$_location_address = get_post_meta( $_location_id, 'pickup_location', true );

					if ( ! empty( $_location_address ) ) {
						$_pickup_location = $_location_title . ':' . $_location_address;
						update_post_meta( $order->ID, '_pickup_location', $_pickup_location );
					}
				}
			}
		}
	}
}