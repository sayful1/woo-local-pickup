<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Woo_Local_Pickup_Location {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 */
	const VERSION = '2.0.0';

	/**
	 * Unique identifier for plugin.
	 *
	 */
	protected $plugin_slug = 'woo-local-pickup';

	/**
	 * Instance of this class.
	 *
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

	/**
	 * Woo_Local_Pickup_Location constructor.
	 */
	private function __construct() {

		// Add the pickup location field to the checkout page
		add_action( 'woocommerce_after_order_notes', array( $this, 'woo_pickup_location_field' ) );

		// Validate the pickup location field
		add_action( 'woocommerce_checkout_process', array( $this, 'woo_pickup_field_validate' ) );

		// Update the order meta with pickup location value
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'woo_update_order_meta' ) );

		// Add pickup location field to order emails
		add_filter( 'woocommerce_email_order_meta_keys', array( $this, 'woo_update_order_email' ) );

		// Add pickup location value to order confirmation page
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'woo_pickup_detail_front' ) );
	}

	/**
	 * Add pickup field to the checkout page
	 */
	public function woo_pickup_location_field() {
		$chosen_methods  = WC()->session->get( 'chosen_shipping_methods' );
		$chosen_shipping = explode( ':', $chosen_methods[0] );

		$style = '';
		if ( $chosen_shipping[0] != 'local_pickup' ) {
			$style = "display:none;";
		} else {
			?>
            <script type="text/javascript">
                jQuery(window).on("load", function () {
                    jQuery("#ship-to-different-address").hide();
                });
            </script>

			<?php
		}
		$args = array(
			'post_type'      => 'pickup_location',
			'posts_per_page' => - 1
		);

		$locations = get_posts( $args );
		if ( ! empty( $locations ) ) {
			echo '<p class="form-row" style="' . $style . '" id="pickup_location_field"><label for="pickup_location_field" class="">' . __( 'Pickup Location', $this->plugin_slug ) . '<abbr class="required" title="required">*</abbr></label><select name="user_pickup_location" id="user_pickup_location">
 				<option value="">' . __( 'Select Pickup Location', $this->plugin_slug ) . '</option>';
			foreach ( $locations as $loc ) {
				echo '<option value="' . $loc->ID . '">' . $loc->post_title . ': ' . get_post_meta( $loc->ID, 'pickup_location', true ) . '</option>';
			}
			echo '</select></p>'; ?>
            <script type="text/javascript">
                jQuery(document).ajaxComplete(function () {
                    jQuery(".shipping_method").on('click', function () {
                        var ship_val = jQuery(this).val();

                        if (ship_val.indexOf("local_pickup") >= 0) {
                            //console.log(ship_val);
                            jQuery("#pickup_location_field").show();
                            jQuery("#user_pickup_location").val('');
                            jQuery("#ship-to-different-address").hide();
                        }
                        else {
                            jQuery("#pickup_location_field").hide();
                            jQuery("#ship-to-different-address").show();
                        }
                    });
                });
            </script>
			<?php
		}
	}


	/**
	 * Process the checkout
	 */
	public function woo_pickup_field_validate() {

		$searchword = 'local_pickup';
		$matches    = array_filter( $_POST['shipping_method'], function ( $var ) use ( $searchword ) {
			return preg_match( "/\b$searchword\b/i", $var );
		} );
		//echo '<pre>'; print_r($matches); die;
		if ( ! empty( $matches ) ) {
			if ( $_POST['user_pickup_location'] == '' ) {
				wc_add_notice( __( 'Pickup Location is a required field.', $this->plugin_slug ), 'error' );
			}
		}
		// Check if set, if its not set add an error.

	}


	/**
	 * Update the order meta with local pickup location field value
	 *
	 * @param $order_id
	 */
	public function woo_update_order_meta( $order_id ) {
		$_location_id = ! empty( $_POST['user_pickup_location'] ) ? intval( $_POST['user_pickup_location'] ) : 0;
		if ( $_location_id ) {
			update_post_meta( $order_id, 'user_pickup_location', $_location_id );

			$_location_title   = get_the_title( $_location_id );
			$_location_address = get_post_meta( $_location_id, 'pickup_location', true );
			update_post_meta( $order_id, '_pickup_location', $_location_title . ':' . $_location_address );
		}
	}

	/**
	 * Add local pickup location field to order emails
	 *
	 * @param array $keys
	 *
	 * @return mixed
	 */
	public function woo_update_order_email( $keys ) {
		$keys['Pickup Location'] = '_pickup_location';

		return $keys;
	}


	/**
	 * Add local pickup location detail on order confirmation page
	 *
	 * @param \WC_Order $order
	 */
	public function woo_pickup_detail_front( $order ) {
		echo '<header><h2>' . __( 'Pickup Details', $this->plugin_slug ) . '</h2></header>';
		if ( get_post_meta( $order->get_id(), 'user_pickup_location', true ) == '' ) {
			echo '<p>' . __( 'Not Applicable', $this->plugin_slug ) . '</p>';
		} else {
			$loc_id = get_post_meta( $order->get_id(), 'user_pickup_location', true );
			echo '<p>' . get_the_title( $loc_id ) . '<br>' . get_post_meta( $loc_id, 'pickup_location', true ) . '</p>';
		}
	}

}