<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Woo Local Pickup Location Admin
 *
 */
class Woo_Local_Pickup_Location_Admin {

	/**
	 * Instance of this class.
	 */
	protected static $instance;

	private $post_type = 'pickup_location';

	/**
	 * @return Woo_Local_Pickup_Location_Admin
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Woo_Local_Pickup_Location_Admin constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_pickup_location_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'pickup_location_custom_box' ) );
		add_action( 'save_post', array( $this, 'woo_pickup_location_metabox_save' ) );

		// Add Pickup Location on Admin Order
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array(
			$this,
			'order_pickup_location'
		), 10, 1 );
	}

	/**
	 * @param \WC_Order $order
	 */
	public function order_pickup_location( $order ) {
		$location_id = get_post_meta( $order->get_id(), 'user_pickup_location', true );
		if ( ! empty( $location_id ) ) {
			$_location_title   = get_the_title( $location_id );
			$_location_address = get_post_meta( $location_id, 'pickup_location', true );

			echo '<div class="pickup-location">';
			echo '<h3>Pickup Location</h3>';
			echo '<p>' . $_location_title . '<br>' . $_location_address . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Register Local Pickup Location
	 */
	public function register_pickup_location_post_type() {
		$labels = array(
			'name'               => __( 'Pickup Locations', 'woo-local-pickup' ),
			'singular_name'      => __( 'Pickup Location', 'woo-local-pickup' ),
			'menu_name'          => __( 'Pickup Location', 'woo-local-pickup' ),
			'name_admin_bar'     => __( 'Pickup Location', 'woo-local-pickup' ),
			'add_new'            => __( 'Add New', 'pickup_location', 'woo-local-pickup' ),
			'add_new_item'       => __( 'Add New Pickup Location', 'woo-local-pickup' ),
			'new_item'           => __( 'New Pickup Location', 'woo-local-pickup' ),
			'edit_item'          => __( 'Edit Pickup Location', 'woo-local-pickup' ),
			'view_item'          => __( 'View Pickup Location', 'woo-local-pickup' ),
			'all_items'          => __( 'All Pickup Locations', 'woo-local-pickup' ),
			'search_items'       => __( 'Search Pickup Location', 'woo-local-pickup' ),
			'parent_item_colon'  => __( 'Parent Pickup Location:', 'woo-local-pickup' ),
			'not_found'          => __( 'No Pickup Location found.', 'woo-local-pickup' ),
			'not_found_in_trash' => __( 'No Pickup Location found in Trash.', 'woo-local-pickup' ),
		);

		$args = array(
			'labels'             => $labels,
			// Frontend
			'has_archive'        => false,
			'public'             => false,
			'publicly_queryable' => false,
			// Admin
			'capability_type'    => 'post',
			'menu_icon'          => 'dashicons-location-alt',
			'menu_position'      => 25,
			'query_var'          => true,
			'show_in_menu'       => true,
			'show_ui'            => true,
			'supports'           => array( 'title' ),
		);

		register_post_type( $this->post_type, $args );
		add_filter( 'manage_edit-' . $this->post_type . '_columns', array( $this, 'columns_head' ) );
		add_filter( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );
	}

	public function columns_head() {
		$columns = array(
			'cb'      => '<input type="checkbox">',
			'title'   => __( 'Title', 'carousel-slider' ),
			'address' => __( 'Address', 'carousel-slider' ),
		);

		return $columns;
	}

	public function columns_content( $column, $post_id ) {
		$pickup_location = get_post_meta( $post_id, 'pickup_location', true );

		if ( 'address' == $column ) {
			echo $pickup_location;
		}
	}

	/**
	 * Add Metabox in Pickup Location
	 *
	 */

	function pickup_location_custom_box() {
		add_meta_box(
			'pickup_location_meta_box',
			__( 'Enter pickup address', 'woo-local-pickup' ),
			array( $this, 'woo_pickup_location_metabox_html' ),
			'pickup_location',
			'normal',
			'high'
		);
	}

	/**
	 * Add Location Field in Metabox
	 *
	 * @param \WP_Post $post
	 */
	function woo_pickup_location_metabox_html( $post ) {
		wp_nonce_field( 'save_pickup_location', '_pickup_location_nonce' );
		echo '<textarea rows="4" cols="80" name="pickup_location">' . get_post_meta( $post->ID, 'pickup_location', true ) . '</textarea> ';
	}

	/**
	 * Save Local Pickup Location Field
	 *
	 * @param integer $post_id
	 *
	 * @return integer
	 */
	function woo_pickup_location_metabox_save( $post_id ) {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if ( ! isset( $_POST['_pickup_location_nonce'], $_POST['pickup_location'] ) ) {
			return $post_id;
		}

		if ( ! wp_verify_nonce( $_POST['_pickup_location_nonce'], 'save_pickup_location' ) ) {
			return $post_id;
		}

		if ( isset( $_POST['pickup_location'] ) ) {
			update_post_meta( $post_id, 'pickup_location', $_POST['pickup_location'] );
		}

		return $post_id;
	}
}