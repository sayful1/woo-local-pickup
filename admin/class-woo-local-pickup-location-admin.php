<?php
/**
 * Woo Local Pickup Location Admin
 *
 */
class Woo_Local_Pickup_Location_Admin {

	/**
	 * Instance of this class.
	 */

	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 */

	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 */
	private function __construct() {

		/*
		 * Call $plugin_slug from front plugin class.
		 */
		$plugin = Woo_Local_Pickup_Location::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		add_action('init', array( $this, 'woo_register_pickup_location')); 
		add_action('add_meta_boxes', array( $this,'pickup_location_custom_box'));
        add_action('save_post', array( $this,'woo_pickup_location_metabox_save'));

		// Add Metabox on Admin Order page
		add_action( 'add_meta_boxes', array( $this, 'woo_add_meta_box_admin'));

		// Save Tracking Number field
		add_action( 'save_post', array( $this, 'woo_save_field_for_tracking'));
		
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


	/*****-------------------------------------------------------
						Admin Pickup Location
	-------------------------------------------------------*****/


	/**
	 * Register Local Pickup Location
	 *
	 */
    public function woo_register_pickup_location()
    {
    	register_post_type( 'pickup_location', array(
                'labels' => array(
                    'name'               => __( 'Pickup Locations','woo-local-pickup'),
                    'singular_name'      => __( 'Pickup Location','woo-local-pickup'),
                    'menu_name'          => __( 'Pickup Location','woo-local-pickup'),
                    'name_admin_bar'     => __( 'Pickup Location','woo-local-pickup'),
                    'add_new'            => __( 'Add New', 'pickup_location','woo-local-pickup'),
                    'add_new_item'       => __( 'Add New Pickup Location','woo-local-pickup'),
                    'new_item'           => __( 'New Pickup Location','woo-local-pickup'),
                    'edit_item'          => __( 'Edit Pickup Location','woo-local-pickup'),
                    'view_item'          => __( 'View Pickup Location','woo-local-pickup'),
                    'all_items'          => __( 'All Pickup Locations','woo-local-pickup'),
                    'search_items'       => __( 'Search Pickup Location','woo-local-pickup'),
                    'parent_item_colon'  => __( 'Parent Pickup Location:','woo-local-pickup'),
                    'not_found'          => __( 'No Pickup Location found.','woo-local-pickup'),
                    'not_found_in_trash' => __( 'No Pickup Location found in Trash.','woo-local-pickup'), 
                ),
                // Frontend
                'has_archive'        => false,
                'public'             => false,
                'publicly_queryable' => false,             
                // Admin
                'capability_type' => 'post',
                'menu_icon'     => 'dashicons-admin-post',
                'menu_position' => 25,
                'query_var'     => true,
                'show_in_menu'  => true,
                'show_ui'       => true,
                'supports'      => array('title'), 
            ));    

    }

    /**
     * Add Metabox in Pickup Location
     *
     */

    function pickup_location_custom_box()
    {
        add_meta_box('pickup_location_meta_box', __( 'Enter pickup address','woo-local-pickup'), array( $this,'woo_pickup_location_metabox_html'), 'pickup_location', 'advanced', 'low' );
    }
        

    /**
     * Add Location Field in Metabox
     */

    function woo_pickup_location_metabox_html($post)
    {
    	echo '<input type="hidden" name="location_noncename" id="location__noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
        echo '<textarea rows="4" cols="80" name="pickup_location">'.get_post_meta($post->ID,'pickup_location',true).'</textarea> ';
    } 
        


    /**
     * Save Local Pickup Location Field
     */

    function woo_pickup_location_metabox_save($post_id)
    {  
    	if(isset($_POST['pickup_location']))
    	{
    		if ( !wp_verify_nonce( $_POST['location_noncename'], plugin_basename(__FILE__) )) {
			return $post->ID;
			}

			if ( !current_user_can( 'edit_post', $post->ID ))
				return $post->ID;

			// OK, we're authenticated: we need to find and save the data
			// We'll put it into an array to make it easier to loop though.
			
			$pickup_location = $_POST['pickup_location'];

			update_post_meta($post_id,'pickup_location',$pickup_location);
    	}
		
    }


	/*****-------------------------------------------------------
						Admin Order Page
	-------------------------------------------------------*****/


	/**
	 * Add Metaboxes in Admin Order Page
	 *
	 */
    public function woo_add_meta_box_admin()
    {
        global $woocommerce, $order, $post;

        add_meta_box( 'woo_pickup_detail', __('Pickup Location Details',$this->plugin_slug), array( $this, 'woo_pickup_field_admin') , 'shop_order', 'advanced', 'core' );
    }


	/**
	 * Display local pickup location detail in Admin Order Page
	 *
	 */
	public function woo_pickup_field_admin( $order ){?>
	    <div class="admin_pickup_details">
	        <?php 
	        if(get_post_meta( $_GET['post'], 'user_pickup_location', true ) == '')
	        	echo '<p>'.__( 'Not Applicable', $this->plugin_slug ).'</p>';
	        else
	        {
                $loc_id = get_post_meta( $_GET['post'], 'user_pickup_location', true );
	        	echo '<p><strong>'.get_the_title($loc_id).'<br>'.get_post_meta( $loc_id, 'pickup_location', true ).'</p>';
	        }
	        ?>
	    </div>
	<?php }

}