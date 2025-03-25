<?php
/**
 * Register custom post types and taxonomies for the plugin.
 */
class Turf_Booking_Post_Types {

    /**
     * Register the custom post type for courts.
     */
    public function register_court_post_type() {
        $labels = array(
            'name'                  => _x('Courts', 'Post Type General Name', 'turf-booking'),
            'singular_name'         => _x('Court', 'Post Type Singular Name', 'turf-booking'),
            'menu_name'             => __('Courts', 'turf-booking'),
            'name_admin_bar'        => __('Court', 'turf-booking'),
            'archives'              => __('Court Archives', 'turf-booking'),
            'attributes'            => __('Court Attributes', 'turf-booking'),
            'parent_item_colon'     => __('Parent Court:', 'turf-booking'),
            'all_items'             => __('All Courts', 'turf-booking'),
            'add_new_item'          => __('Add New Court', 'turf-booking'),
            'add_new'               => __('Add New', 'turf-booking'),
            'new_item'              => __('New Court', 'turf-booking'),
            'edit_item'             => __('Edit Court', 'turf-booking'),
            'update_item'           => __('Update Court', 'turf-booking'),
            'view_item'             => __('View Court', 'turf-booking'),
            'view_items'            => __('View Courts', 'turf-booking'),
            'search_items'          => __('Search Court', 'turf-booking'),
            'not_found'             => __('Not found', 'turf-booking'),
            'not_found_in_trash'    => __('Not found in Trash', 'turf-booking'),
            'featured_image'        => __('Court Image', 'turf-booking'),
            'set_featured_image'    => __('Set court image', 'turf-booking'),
            'remove_featured_image' => __('Remove court image', 'turf-booking'),
            'use_featured_image'    => __('Use as court image', 'turf-booking'),
            'insert_into_item'      => __('Insert into court', 'turf-booking'),
            'uploaded_to_this_item' => __('Uploaded to this court', 'turf-booking'),
            'items_list'            => __('Courts list', 'turf-booking'),
            'items_list_navigation' => __('Courts list navigation', 'turf-booking'),
            'filter_items_list'     => __('Filter courts list', 'turf-booking'),
        );
        
        $args = array(
            'label'                 => __('Court', 'turf-booking'),
            'description'           => __('Court/Turf details', 'turf-booking'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-location',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array(
                'slug' => 'courts',
                'with_front' => true,
                'pages' => true,
                'feeds' => true,
            ),
        );
        
        register_post_type('tb_court', $args);
    }

    /**
     * Register the custom post type for bookings.
     */
    public function register_booking_post_type() {
        $labels = array(
            'name'                  => _x('Bookings', 'Post Type General Name', 'turf-booking'),
            'singular_name'         => _x('Booking', 'Post Type Singular Name', 'turf-booking'),
            'menu_name'             => __('Bookings', 'turf-booking'),
            'name_admin_bar'        => __('Booking', 'turf-booking'),
            'archives'              => __('Booking Archives', 'turf-booking'),
            'attributes'            => __('Booking Attributes', 'turf-booking'),
            'parent_item_colon'     => __('Parent Booking:', 'turf-booking'),
            'all_items'             => __('All Bookings', 'turf-booking'),
            'add_new_item'          => __('Add New Booking', 'turf-booking'),
            'add_new'               => __('Add New', 'turf-booking'),
            'new_item'              => __('New Booking', 'turf-booking'),
            'edit_item'             => __('Edit Booking', 'turf-booking'),
            'update_item'           => __('Update Booking', 'turf-booking'),
            'view_item'             => __('View Booking', 'turf-booking'),
            'view_items'            => __('View Bookings', 'turf-booking'),
            'search_items'          => __('Search Booking', 'turf-booking'),
            'not_found'             => __('Not found', 'turf-booking'),
            'not_found_in_trash'    => __('Not found in Trash', 'turf-booking'),
        );
        
        $args = array(
            'label'                 => __('Booking', 'turf-booking'),
            'description'           => __('Booking Information', 'turf-booking'),
            'labels'                => $labels,
            'supports'              => array('title', 'custom-fields'),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 6,
            'menu_icon'             => 'dashicons-calendar-alt',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'capabilities'          => array(
                'create_posts' => 'do_not_allow', // Removes 'Add New' button
            ),
            'map_meta_cap'          => true,
        );
        
        register_post_type('tb_booking', $args);
    }

    /**
     * Register taxonomies for courts
     */
    public function register_court_taxonomies() {
        // Sport Type Taxonomy
        $labels = array(
            'name'                       => _x('Sport Types', 'Taxonomy General Name', 'turf-booking'),
            'singular_name'              => _x('Sport Type', 'Taxonomy Singular Name', 'turf-booking'),
            'menu_name'                  => __('Sport Types', 'turf-booking'),
            'all_items'                  => __('All Sport Types', 'turf-booking'),
            'parent_item'                => __('Parent Sport Type', 'turf-booking'),
            'parent_item_colon'          => __('Parent Sport Type:', 'turf-booking'),
            'new_item_name'              => __('New Sport Type Name', 'turf-booking'),
            'add_new_item'               => __('Add New Sport Type', 'turf-booking'),
            'edit_item'                  => __('Edit Sport Type', 'turf-booking'),
            'update_item'                => __('Update Sport Type', 'turf-booking'),
            'view_item'                  => __('View Sport Type', 'turf-booking'),
            'separate_items_with_commas' => __('Separate sport types with commas', 'turf-booking'),
            'add_or_remove_items'        => __('Add or remove sport types', 'turf-booking'),
            'choose_from_most_used'      => __('Choose from the most used', 'turf-booking'),
            'popular_items'              => __('Popular Sport Types', 'turf-booking'),
            'search_items'               => __('Search Sport Types', 'turf-booking'),
            'not_found'                  => __('Not Found', 'turf-booking'),
            'no_terms'                   => __('No sport types', 'turf-booking'),
            'items_list'                 => __('Sport Types list', 'turf-booking'),
            'items_list_navigation'      => __('Sport Types list navigation', 'turf-booking'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'sport-type'),
        );
        
        register_taxonomy('sport_type', array('tb_court'), $args);
        
        // Facilities Taxonomy 
        $labels = array(
            'name'                       => _x('Facilities', 'Taxonomy General Name', 'turf-booking'),
            'singular_name'              => _x('Facility', 'Taxonomy Singular Name', 'turf-booking'),
            'menu_name'                  => __('Facilities', 'turf-booking'),
            'all_items'                  => __('All Facilities', 'turf-booking'),
            'parent_item'                => __('Parent Facility', 'turf-booking'),
            'parent_item_colon'          => __('Parent Facility:', 'turf-booking'),
            'new_item_name'              => __('New Facility Name', 'turf-booking'),
            'add_new_item'               => __('Add New Facility', 'turf-booking'),
            'edit_item'                  => __('Edit Facility', 'turf-booking'),
            'update_item'                => __('Update Facility', 'turf-booking'),
            'view_item'                  => __('View Facility', 'turf-booking'),
            'separate_items_with_commas' => __('Separate facilities with commas', 'turf-booking'),
            'add_or_remove_items'        => __('Add or remove facilities', 'turf-booking'),
            'choose_from_most_used'      => __('Choose from the most used', 'turf-booking'),
            'popular_items'              => __('Popular Facilities', 'turf-booking'),
            'search_items'               => __('Search Facilities', 'turf-booking'),
            'not_found'                  => __('Not Found', 'turf-booking'),
            'no_terms'                   => __('No facilities', 'turf-booking'),
            'items_list'                 => __('Facilities list', 'turf-booking'),
            'items_list_navigation'      => __('Facilities list navigation', 'turf-booking'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'facility'),
        );
        
        register_taxonomy('facility', array('tb_court'), $args);
        
        // Location Taxonomy
        $labels = array(
            'name'                       => _x('Locations', 'Taxonomy General Name', 'turf-booking'),
            'singular_name'              => _x('Location', 'Taxonomy Singular Name', 'turf-booking'),
            'menu_name'                  => __('Locations', 'turf-booking'),
            'all_items'                  => __('All Locations', 'turf-booking'),
            'parent_item'                => __('Parent Location', 'turf-booking'),
            'parent_item_colon'          => __('Parent Location:', 'turf-booking'),
            'new_item_name'              => __('New Location Name', 'turf-booking'),
            'add_new_item'               => __('Add New Location', 'turf-booking'),
            'edit_item'                  => __('Edit Location', 'turf-booking'),
            'update_item'                => __('Update Location', 'turf-booking'),
            'view_item'                  => __('View Location', 'turf-booking'),
            'separate_items_with_commas' => __('Separate locations with commas', 'turf-booking'),
            'add_or_remove_items'        => __('Add or remove locations', 'turf-booking'),
            'choose_from_most_used'      => __('Choose from the most used', 'turf-booking'),
            'popular_items'              => __('Popular Locations', 'turf-booking'),
            'search_items'               => __('Search Locations', 'turf-booking'),
            'not_found'                  => __('Not Found', 'turf-booking'),
            'no_terms'                   => __('No locations', 'turf-booking'),
            'items_list'                 => __('Locations list', 'turf-booking'),
            'items_list_navigation'      => __('Locations list navigation', 'turf-booking'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'location'),
        );
        
        register_taxonomy('location', array('tb_court'), $args);
    }

    /**
     * Add meta boxes for courts
     */
    public function add_court_meta_boxes() {
        add_meta_box(
            'tb_court_details',
            __('Court Details', 'turf-booking'),
            array($this, 'render_court_details_meta_box'),
            'tb_court',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_court_pricing',
            __('Court Pricing', 'turf-booking'),
            array($this, 'render_court_pricing_meta_box'),
            'tb_court',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_court_timing',
            __('Court Timing', 'turf-booking'),
            array($this, 'render_court_timing_meta_box'),
            'tb_court',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_court_gallery',
            __('Court Gallery', 'turf-booking'),
            array($this, 'render_court_gallery_meta_box'),
            'tb_court',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_court_location',
            __('Court Location', 'turf-booking'),
            array($this, 'render_court_location_meta_box'),
            'tb_court',
            'normal',
            'high'
        );
    }

    /**
     * Add meta boxes for bookings
     */
    public function add_booking_meta_boxes() {
        add_meta_box(
            'tb_booking_details',
            __('Booking Details', 'turf-booking'),
            array($this, 'render_booking_details_meta_box'),
            'tb_booking',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_booking_user',
            __('User Information', 'turf-booking'),
            array($this, 'render_booking_user_meta_box'),
            'tb_booking',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_booking_payment',
            __('Payment Information', 'turf-booking'),
            array($this, 'render_booking_payment_meta_box'),
            'tb_booking',
            'normal',
            'high'
        );
    }

    /**
     * Render court details meta box
     */
    public function render_court_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('tb_court_meta_box', 'tb_court_meta_box_nonce');
        
        // Retrieve current values
        $court_size = get_post_meta($post->ID, '_tb_court_size', true);
        $court_capacity = get_post_meta($post->ID, '_tb_court_capacity', true);
        $court_rating = get_post_meta($post->ID, '_tb_court_rating', true);
        
        // Output fields
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tb_court_size"><?php _e('Court Size', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_court_size" name="tb_court_size" value="<?php echo esc_attr($court_size); ?>" class="regular-text">
                    <p class="description"><?php _e('E.g., 100x60 meters', 'turf-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_court_capacity"><?php _e('Court Capacity', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="tb_court_capacity" name="tb_court_capacity" value="<?php echo esc_attr($court_capacity); ?>" class="small-text">
                    <p class="description"><?php _e('Maximum number of players', 'turf-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_court_rating"><?php _e('Court Rating', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="tb_court_rating" name="tb_court_rating" value="<?php echo esc_attr($court_rating); ?>" class="small-text" min="0" max="5" step="0.1">
                    <p class="description"><?php _e('Rating from 0 to 5', 'turf-booking'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render court pricing meta box
     */
    public function render_court_pricing_meta_box($post) {
        // Retrieve current values
        $base_price = get_post_meta($post->ID, '_tb_court_base_price', true);
        $weekend_price = get_post_meta($post->ID, '_tb_court_weekend_price', true);
        $peak_hour_price = get_post_meta($post->ID, '_tb_court_peak_hour_price', true);
        
        // Output fields
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tb_court_base_price"><?php _e('Base Price (per hour)', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="tb_court_base_price" name="tb_court_base_price" value="<?php echo esc_attr($base_price); ?>" class="regular-text" min="0" step="0.01">
                    <p class="description"><?php _e('Regular hourly rate', 'turf-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_court_weekend_price"><?php _e('Weekend Price (per hour)', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="tb_court_weekend_price" name="tb_court_weekend_price" value="<?php echo esc_attr($weekend_price); ?>" class="regular-text" min="0" step="0.01">
                    <p class="description"><?php _e('Weekend hourly rate (leave empty to use base price)', 'turf-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_court_peak_hour_price"><?php _e('Peak Hour Price (per hour)', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="tb_court_peak_hour_price" name="tb_court_peak_hour_price" value="<?php echo esc_attr($peak_hour_price); ?>" class="regular-text" min="0" step="0.01">
                    <p class="description"><?php _e('Peak hour rate (leave empty to use base price)', 'turf-booking'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render court timing meta box
     */
    public function render_court_timing_meta_box($post) {
        // Retrieve current values
        $opening_hours = get_post_meta($post->ID, '_tb_court_opening_hours', true);
        if (!$opening_hours || !is_array($opening_hours)) {
            $opening_hours = array(
                'monday'    => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
                'tuesday'   => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
                'wednesday' => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
                'thursday'  => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
                'friday'    => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
                'saturday'  => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
                'sunday'    => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
            );
        }
        
        $days = array(
            'monday'    => __('Monday', 'turf-booking'),
            'tuesday'   => __('Tuesday', 'turf-booking'),
            'wednesday' => __('Wednesday', 'turf-booking'),
            'thursday'  => __('Thursday', 'turf-booking'),
            'friday'    => __('Friday', 'turf-booking'),
            'saturday'  => __('Saturday', 'turf-booking'),
            'sunday'    => __('Sunday', 'turf-booking'),
        );
        
        // Output fields
        ?>
        <table class="form-table">
            <?php foreach ($days as $day_key => $day_name) : ?>
                <tr>
                    <th scope="row">
                        <?php echo esc_html($day_name); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="tb_court_opening_hours[<?php echo $day_key; ?>][closed]" 
                                   value="1" 
                                   <?php checked(!empty($opening_hours[$day_key]['closed'])); ?>>
                            <?php _e('Closed', 'turf-booking'); ?>
                        </label>
                        <br><br>
                        <label>
                            <?php _e('From:', 'turf-booking'); ?>
                            <input type="time" 
                                   name="tb_court_opening_hours[<?php echo $day_key; ?>][from]" 
                                   value="<?php echo esc_attr($opening_hours[$day_key]['from']); ?>">
                        </label>
                        &nbsp;&nbsp;
                        <label>
                            <?php _e('To:', 'turf-booking'); ?>
                            <input type="time" 
                                   name="tb_court_opening_hours[<?php echo $day_key; ?>][to]" 
                                   value="<?php echo esc_attr($opening_hours[$day_key]['to']); ?>">
                        </label>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th scope="row">
                    <?php _e('Booking Time Slot', 'turf-booking'); ?>
                </th>
                <td>
                    <select name="tb_court_time_slot">
                        <option value="30" <?php selected(get_post_meta($post->ID, '_tb_court_time_slot', true), '30'); ?>><?php _e('30 Minutes', 'turf-booking'); ?></option>
                        <option value="60" <?php selected(get_post_meta($post->ID, '_tb_court_time_slot', true), '60'); ?>><?php _e('1 Hour', 'turf-booking'); ?></option>
                        <option value="90" <?php selected(get_post_meta($post->ID, '_tb_court_time_slot', true), '90'); ?>><?php _e('1.5 Hours', 'turf-booking'); ?></option>
                        <option value="120" <?php selected(get_post_meta($post->ID, '_tb_court_time_slot', true), '120'); ?>><?php _e('2 Hours', 'turf-booking'); ?></option>
                    </select>
                    <p class="description"><?php _e('Default booking time slot duration', 'turf-booking'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render court gallery meta box
     */
    public function render_court_gallery_meta_box($post) {
        // Retrieve current values
        $gallery_images = get_post_meta($post->ID, '_tb_court_gallery', true);
        
        // Output fields
        ?>
        <div class="tb-gallery-container">
            <div class="tb-gallery-images">
                <?php
                if (!empty($gallery_images)) {
                    $gallery_images = explode(',', $gallery_images);
                    foreach ($gallery_images as $image_id) {
                        $image = wp_get_attachment_image($image_id, 'thumbnail');
                        if ($image) {
                            echo '<div class="tb-gallery-image-container">';
                            echo $image;
                            echo '<a href="#" class="tb-remove-image">&times;</a>';
                            echo '<input type="hidden" name="tb_court_gallery_ids[]" value="' . esc_attr($image_id) . '">';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>
            <div class="tb-gallery-actions">
                <button type="button" class="button tb-add-gallery-images"><?php _e('Add Images', 'turf-booking'); ?></button>
            </div>
            <input type="hidden" name="tb_court_gallery" id="tb_court_gallery" value="<?php echo esc_attr($gallery_images); ?>">
            <div class="clear"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Add gallery images
            $('.tb-add-gallery-images').on('click', function(e) {
                e.preventDefault();
                
                var galleryFrame = wp.media({
                    title: '<?php _e('Select Court Gallery Images', 'turf-booking'); ?>',
                    button: {
                        text: '<?php _e('Add to Gallery', 'turf-booking'); ?>'
                    },
                    multiple: true
                });
                
                galleryFrame.on('select', function() {
                    var attachments = galleryFrame.state().get('selection').toJSON();
                    var galleryIDs = [];
                    
                    // Get existing gallery IDs
                    $('.tb-gallery-image-container input[name="tb_court_gallery_ids[]"]').each(function() {
                        galleryIDs.push($(this).val());
                    });
                    
                    // Add new images
                    $.each(attachments, function(index, attachment) {
                        if ($.inArray(attachment.id.toString(), galleryIDs) === -1) {
                            $('.tb-gallery-images').append(
                                '<div class="tb-gallery-image-container">' +
                                '<img src="' + attachment.sizes.thumbnail.url + '" width="150" height="150">' +
                                '<a href="#" class="tb-remove-image">&times;</a>' +
                                '<input type="hidden" name="tb_court_gallery_ids[]" value="' + attachment.id + '">' +
                                '</div>'
                            );
                            galleryIDs.push(attachment.id.toString());
                        }
                    });
                    
                    // Update hidden field
                    $('#tb_court_gallery').val(galleryIDs.join(','));
                });
                
                galleryFrame.open();
            });
            
            // Remove gallery image
            $('.tb-gallery-images').on('click', '.tb-remove-image', function(e) {
                e.preventDefault();
                
                $(this).parent().remove();
                
                // Update hidden field
                var galleryIDs = [];
                $('.tb-gallery-image-container input[name="tb_court_gallery_ids[]"]').each(function() {
                    galleryIDs.push($(this).val());
                });
                
                $('#tb_court_gallery').val(galleryIDs.join(','));
            });
        });
        </script>
        <style>
        .tb-gallery-image-container {
            position: relative;
            float: left;
            margin: 0 10px 10px 0;
        }
        .tb-remove-image {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 18px;
        }
        .tb-remove-image:hover {
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
        }
        .tb-gallery-actions {
            clear: both;
            padding-top: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Render court location meta box
     */
    public function render_court_location_meta_box($post) {
        // Retrieve current values
        $address = get_post_meta($post->ID, '_tb_court_address', true);
        $latitude = get_post_meta($post->ID, '_tb_court_latitude', true);
        $longitude = get_post_meta($post->ID, '_tb_court_longitude', true);
        
        // Output fields
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tb_court_address"><?php _e('Address', 'turf-booking'); ?></label>
                </th>
                <td>
                    <textarea id="tb_court_address" name="tb_court_address" class="large-text" rows="3"><?php echo esc_textarea($address); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_court_latitude"><?php _e('Latitude', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_court_latitude" name="tb_court_latitude" value="<?php echo esc_attr($latitude); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_court_longitude"><?php _e('Longitude', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_court_longitude" name="tb_court_longitude" value="<?php echo esc_attr($longitude); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render booking details meta box
     */
    public function render_booking_details_meta_box($post) {
        // Retrieve current values
        $court_id = get_post_meta($post->ID, '_tb_booking_court_id', true);
        $booking_date = get_post_meta($post->ID, '_tb_booking_date', true);
        $booking_time_from = get_post_meta($post->ID, '_tb_booking_time_from', true);
        $booking_time_to = get_post_meta($post->ID, '_tb_booking_time_to', true);
        $booking_status = get_post_meta($post->ID, '_tb_booking_status', true);
        
        // Get court options
        $courts = get_posts(array(
            'post_type' => 'tb_court',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        
        // Output fields
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tb_booking_court_id"><?php _e('Court', 'turf-booking'); ?></label>
                </th>
                <td>
                    <select id="tb_booking_court_id" name="tb_booking_court_id">
                        <option value=""><?php _e('Select a Court', 'turf-booking'); ?></option>
                        <?php foreach ($courts as $court) : ?>
                            <option value="<?php echo esc_attr($court->ID); ?>" <?php selected($court_id, $court->ID); ?>><?php echo esc_html($court->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_date"><?php _e('Booking Date', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="date" id="tb_booking_date" name="tb_booking_date" value="<?php echo esc_attr($booking_date); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_time_from"><?php _e('Time From', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="time" id="tb_booking_time_from" name="tb_booking_time_from" value="<?php echo esc_attr($booking_time_from); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_time_to"><?php _e('Time To', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="time" id="tb_booking_time_to" name="tb_booking_time_to" value="<?php echo esc_attr($booking_time_to); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_status"><?php _e('Booking Status', 'turf-booking'); ?></label>
                </th>
                <td>
                    <select id="tb_booking_status" name="tb_booking_status">
                        <option value="pending" <?php selected($booking_status, 'pending'); ?>><?php _e('Pending', 'turf-booking'); ?></option>
                        <option value="confirmed" <?php selected($booking_status, 'confirmed'); ?>><?php _e('Confirmed', 'turf-booking'); ?></option>
                        <option value="completed" <?php selected($booking_status, 'completed'); ?>><?php _e('Completed', 'turf-booking'); ?></option>
                        <option value="cancelled" <?php selected($booking_status, 'cancelled'); ?>><?php _e('Cancelled', 'turf-booking'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render booking user meta box
     */
    public function render_booking_user_meta_box($post) {
        // Retrieve current values
        $user_id = get_post_meta($post->ID, '_tb_booking_user_id', true);
        $user_name = get_post_meta($post->ID, '_tb_booking_user_name', true);
        $user_email = get_post_meta($post->ID, '_tb_booking_user_email', true);
        $user_phone = get_post_meta($post->ID, '_tb_booking_user_phone', true);
        
        // Output fields
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tb_booking_user_id"><?php _e('User ID', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_booking_user_id" name="tb_booking_user_id" value="<?php echo esc_attr($user_id); ?>" class="regular-text" readonly>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_user_name"><?php _e('Name', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_booking_user_name" name="tb_booking_user_name" value="<?php echo esc_attr($user_name); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_user_email"><?php _e('Email', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="email" id="tb_booking_user_email" name="tb_booking_user_email" value="<?php echo esc_attr($user_email); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_user_phone"><?php _e('Phone', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_booking_user_phone" name="tb_booking_user_phone" value="<?php echo esc_attr($user_phone); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render booking payment meta box
     */
    public function render_booking_payment_meta_box($post) {
        // Retrieve current values
        $payment_id = get_post_meta($post->ID, '_tb_booking_payment_id', true);
        $payment_method = get_post_meta($post->ID, '_tb_booking_payment_method', true);
        $payment_status = get_post_meta($post->ID, '_tb_booking_payment_status', true);
        $payment_amount = get_post_meta($post->ID, '_tb_booking_payment_amount', true);
        $payment_date = get_post_meta($post->ID, '_tb_booking_payment_date', true);
        
        // Output fields
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tb_booking_payment_id"><?php _e('Payment ID', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_booking_payment_id" name="tb_booking_payment_id" value="<?php echo esc_attr($payment_id); ?>" class="regular-text" readonly>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_payment_method"><?php _e('Payment Method', 'turf-booking'); ?></label>
                </th>
                <td>
                    <select id="tb_booking_payment_method" name="tb_booking_payment_method">
                        <option value="razorpay" <?php selected($payment_method, 'razorpay'); ?>><?php _e('Razorpay', 'turf-booking'); ?></option>
                        <option value="offline" <?php selected($payment_method, 'offline'); ?>><?php _e('Offline', 'turf-booking'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_payment_status"><?php _e('Payment Status', 'turf-booking'); ?></label>
                </th>
                <td>
                    <select id="tb_booking_payment_status" name="tb_booking_payment_status">
                        <option value="pending" <?php selected($payment_status, 'pending'); ?>><?php _e('Pending', 'turf-booking'); ?></option>
                        <option value="completed" <?php selected($payment_status, 'completed'); ?>><?php _e('Completed', 'turf-booking'); ?></option>
                        <option value="failed" <?php selected($payment_status, 'failed'); ?>><?php _e('Failed', 'turf-booking'); ?></option>
                        <option value="refunded" <?php selected($payment_status, 'refunded'); ?>><?php _e('Refunded', 'turf-booking'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_payment_amount"><?php _e('Amount', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="tb_booking_payment_amount" name="tb_booking_payment_amount" value="<?php echo esc_attr($payment_amount); ?>" class="regular-text" step="0.01">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_payment_date"><?php _e('Payment Date', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="datetime-local" id="tb_booking_payment_date" name="tb_booking_payment_date" value="<?php echo esc_attr($payment_date); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save court meta box data
     */
    public function save_court_meta_box_data($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['tb_court_meta_box_nonce'])) {
            return;
        }
        
        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['tb_court_meta_box_nonce'], 'tb_court_meta_box')) {
            return;
        }
        
        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save court details
        if (isset($_POST['tb_court_size'])) {
            update_post_meta($post_id, '_tb_court_size', sanitize_text_field($_POST['tb_court_size']));
        }
        
        if (isset($_POST['tb_court_capacity'])) {
            update_post_meta($post_id, '_tb_court_capacity', absint($_POST['tb_court_capacity']));
        }
        
        if (isset($_POST['tb_court_rating'])) {
            update_post_meta($post_id, '_tb_court_rating', floatval($_POST['tb_court_rating']));
        }
        
        // Save court pricing
        if (isset($_POST['tb_court_base_price'])) {
            update_post_meta($post_id, '_tb_court_base_price', floatval($_POST['tb_court_base_price']));
        }
        
        if (isset($_POST['tb_court_weekend_price'])) {
            update_post_meta($post_id, '_tb_court_weekend_price', floatval($_POST['tb_court_weekend_price']));
        }
        
        if (isset($_POST['tb_court_peak_hour_price'])) {
            update_post_meta($post_id, '_tb_court_peak_hour_price', floatval($_POST['tb_court_peak_hour_price']));
        }
        
        // Save court timing
        if (isset($_POST['tb_court_opening_hours'])) {
            update_post_meta($post_id, '_tb_court_opening_hours', $_POST['tb_court_opening_hours']);
        }
        
        if (isset($_POST['tb_court_time_slot'])) {
            update_post_meta($post_id, '_tb_court_time_slot', sanitize_text_field($_POST['tb_court_time_slot']));
        }
        
        // Save court gallery
        if (isset($_POST['tb_court_gallery_ids'])) {
            $gallery_ids = array_map('absint', $_POST['tb_court_gallery_ids']);
            update_post_meta($post_id, '_tb_court_gallery', implode(',', $gallery_ids));
        } else {
            update_post_meta($post_id, '_tb_court_gallery', '');
        }
        
        // Save court location
        if (isset($_POST['tb_court_address'])) {
            update_post_meta($post_id, '_tb_court_address', sanitize_textarea_field($_POST['tb_court_address']));
        }
        
        if (isset($_POST['tb_court_latitude'])) {
            update_post_meta($post_id, '_tb_court_latitude', sanitize_text_field($_POST['tb_court_latitude']));
        }
        
        if (isset($_POST['tb_court_longitude'])) {
            update_post_meta($post_id, '_tb_court_longitude', sanitize_text_field($_POST['tb_court_longitude']));
        }
    }
    
    /**
     * Save booking meta box data
     */
    public function save_booking_meta_box_data($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save booking details
        if (isset($_POST['tb_booking_court_id'])) {
            update_post_meta($post_id, '_tb_booking_court_id', absint($_POST['tb_booking_court_id']));
        }
        
        if (isset($_POST['tb_booking_date'])) {
            update_post_meta($post_id, '_tb_booking_date', sanitize_text_field($_POST['tb_booking_date']));
        }
        
        if (isset($_POST['tb_booking_time_from'])) {
            update_post_meta($post_id, '_tb_booking_time_from', sanitize_text_field($_POST['tb_booking_time_from']));
        }
        
        if (isset($_POST['tb_booking_time_to'])) {
            update_post_meta($post_id, '_tb_booking_time_to', sanitize_text_field($_POST['tb_booking_time_to']));
        }
        
        if (isset($_POST['tb_booking_status'])) {
            update_post_meta($post_id, '_tb_booking_status', sanitize_text_field($_POST['tb_booking_status']));
        }
        
        // Save user details
        if (isset($_POST['tb_booking_user_name'])) {
            update_post_meta($post_id, '_tb_booking_user_name', sanitize_text_field($_POST['tb_booking_user_name']));
        }
        
        if (isset($_POST['tb_booking_user_email'])) {
            update_post_meta($post_id, '_tb_booking_user_email', sanitize_email($_POST['tb_booking_user_email']));
        }
        
        if (isset($_POST['tb_booking_user_phone'])) {
            update_post_meta($post_id, '_tb_booking_user_phone', sanitize_text_field($_POST['tb_booking_user_phone']));
        }
        
        // Save payment details
        if (isset($_POST['tb_booking_payment_method'])) {
            update_post_meta($post_id, '_tb_booking_payment_method', sanitize_text_field($_POST['tb_booking_payment_method']));
        }
        
        if (isset($_POST['tb_booking_payment_status'])) {
            update_post_meta($post_id, '_tb_booking_payment_status', sanitize_text_field($_POST['tb_booking_payment_status']));
        }
        
        if (isset($_POST['tb_booking_payment_amount'])) {
            update_post_meta($post_id, '_tb_booking_payment_amount', floatval($_POST['tb_booking_payment_amount']));
        }
        
        if (isset($_POST['tb_booking_payment_date'])) {
            update_post_meta($post_id, '_tb_booking_payment_date', sanitize_text_field($_POST['tb_booking_payment_date']));
        }
    }
}