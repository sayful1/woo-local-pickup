<?php
/**
 * Woo Local Pickup Location
 */

class Woo_Local_Pickup_Location {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 */
	const VERSION = '1.0.0';

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
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Add the collectco pickup location field to the checkout page
		add_action( 'woocommerce_after_order_notes', array( $this, 'woo_pickup_location_field' ) );

		// Validate the collectco pickup location field
		add_action( 'woocommerce_checkout_process', array( $this, 'woo_pickup_field_validate' ) );

		// Update the order meta with collectco pickup location value
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'woo_update_order_meta' ) );

		// Add collectco pickup location field to order emails
		add_filter('woocommerce_email_order_meta_keys', array( $this, 'woo_update_order_email' ) );

		// Add collectco pickup location value to order confirmation page
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'woo_pickup_detail_front') );

		// Add fields in get order api
		add_filter( 'woocommerce_api_order_response',  array( $this, 'woo_api_order_responses'), 20, 4);

		// Add fields in update order api
		add_filter( 'woocommerce_api_edit_order',  array( $this, 'woo_api_edit_order_data'), 20, 4);
	
	}

	/**
	 * Return the plugin slug.
	 *
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 */
	private static function single_activate() {
		// No activation functionality needed... yet
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 */
	private static function single_deactivate() {
		// No deactivation functionality needed... yet
	}


	/**
	 * Load the plugin text domain for translation.
	 *
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Add pickup field to the checkout page
	 *
	 */
	public function woo_pickup_location_field( $checkout ) {
		global $woocommerce;
		global $wpdb;
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
 		$chosen_shipping = explode(':',$chosen_methods[0]); 
 	
 		$style='';
 		if($chosen_shipping[0] != 'local_pickup')
 		{
 			$style = "display:none;";
 		}
 		else
 		{?>
 			<script type="text/javascript">
			jQuery(window).on("load", function(){
				jQuery("#ship-to-different-address").hide();
			});	
			</script>	
			
		<?php
 		}
 		$args = array(
		    'post_type' => 'pickup_location',
		    'posts_per_page'   => -1
		);

		$locations = get_posts($args); 
		if(!empty($locations))
		{
			echo '<p class="form-row" style="'.$style.'" id="pickup_location_field"><label for="pickup_location_field" class="">'.__( 'Pickup Location', $this->plugin_slug ).'<abbr class="required" title="required">*</abbr></label><select name="user_pickup_location" id="user_pickup_location">
 				<option value="">'.__( 'Select Pickup Location', $this->plugin_slug ).'</option>';
	 		foreach($locations as $loc)
	 		{
	 			echo '<option value="'.$loc->ID.'">'.$loc->post_title.': '.get_post_meta($loc->ID,'pickup_location',true).'</option>';
	 		}
	 		echo '</select></p>';?>
			<script type="text/javascript">
			jQuery(document).ajaxComplete(function () {
				jQuery(".shipping_method").on('click', function()
				{
					var ship_val = jQuery(this).val();
					
					if(ship_val.indexOf("local_pickup") >= 0)
					{
						//console.log(ship_val);
						jQuery("#pickup_location_field").show();
						jQuery("#user_pickup_location").val('');
						jQuery("#ship-to-different-address").hide();
					}
					else
					{
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
	 *
	 */
	public function woo_pickup_field_validate() {
		global $woocommerce;

		//echo '<pre>'; print_r($_POST); die;
		$searchword = 'local_pickup';
		$matches = array_filter($_POST['shipping_method'], function($var) use ($searchword) { return preg_match("/\b$searchword\b/i", $var); });
		//echo '<pre>'; print_r($matches); die;
		if(!empty($matches))
		{
			if ( $_POST['user_pickup_location'] == '')
			wc_add_notice(__( 'Pickup Location is a required field.', $this->plugin_slug ),'error');
		}
		// Check if set, if its not set add an error.
		
	}


	/**
	 * Update the order meta with local pickup location field value
	 *
	 */
	public function woo_update_order_meta( $order_id ) {
		if ( $_POST['user_pickup_location'] ) update_post_meta( $order_id, 'user_pickup_location', $_POST['user_pickup_location'] );
	}

	/**
	 * Add local pickup location field to order emails
	 *
	 */
	public function woo_update_order_email( $keys ) {
		$keys['Pickup Location'] = 'user_pickup_location';
		return $keys;
	}


	/**
	 * Add local pickup location detail on order confirmation page
	 *
	 */

	public function woo_pickup_detail_front( $order ) {
		$order = new WC_Order( $order->id );
		echo '<header><h2>'. __( 'Pickup Details', $this->plugin_slug ).'</h2></header>';
		if(get_post_meta( $order->id, 'user_pickup_location', true ) == '')
			echo '<p>'. __( 'Not Applicable', $this->plugin_slug ).'</p>';
		else
		{
			$loc_id = get_post_meta( $order->id, 'user_pickup_location', true );
			echo '<p>'.get_the_title($loc_id).'<br>'.get_post_meta( $loc_id, 'pickup_location', true ).'</p>';
		}
		
		//echo '<div class="clearfix"></div>';
	}

    
    /**
	 * Add Pickup Location detail in api call
	 *
	 */
    public function woo_api_order_responses( $order_data, $order, $fields, $this_server ) {

    	$loc_id = get_post_meta( $order->id, 'user_pickup_location', true );
		if($loc_id != '')
		{
			$order_data['pickup']['id'] = $loc_id;
			$location_name = get_the_title($loc_id).': '.get_post_meta( $loc_id, 'pickup_location', true );
	    	$order_data['pickup']['name'] = $location_name;
	    	//$order_data['weight_unit'] = $this->woo_get_woocommerce_weight_unit();
	    	
		}
    	return $order_data;
  	}


  	/**
	 * Update Pickup Location id via api call
	 *
	 */
  	public function woo_api_edit_order_data($order_id, $data) {
  		$this->data = $data;
    	$c_id = isset( $data['user_pickup_location'] ) ? $data['user_pickup_location'] : '' ;
    	
		if($c_id){
			update_post_meta( $order_id, 'user_pickup_location', $c_id );
		}
    	return $this->data;
  	}

}