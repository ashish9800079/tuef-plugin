<?php
/**
 * Register and handle shortcodes for the plugin.
 */
class Turf_Booking_Shortcodes {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('turf_booking_courts', array($this, 'courts_shortcode'));
        add_shortcode('turf_booking_account', array($this, 'account_shortcode'));
        add_shortcode('turf_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('turf_booking_checkout', array($this, 'checkout_shortcode'));
        add_shortcode('turf_booking_confirmation', array($this, 'confirmation_shortcode'));
    }

    /**
     * Shortcode for displaying courts
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function courts_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'count' => 12,
            'category' => '',
            'location' => '',
            'featured' => false,
            'columns' => 3,
        ), $atts, 'turf_booking_courts');
        
        // Convert attributes
        $count = intval($atts['count']);
        $columns = intval($atts['columns']);
        $featured = filter_var($atts['featured'], FILTER_VALIDATE_BOOLEAN);
        
        // Set up query args
        $args = array(
            'post_type' => 'tb_court',
            'posts_per_page' => $count,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        // Add taxonomy queries if specified
        $tax_query = array();
        
        if (!empty($atts['category'])) {
            $tax_query[] = array(
                'taxonomy' => 'sport_type',
                'field' => 'slug',
                'terms' => explode(',', $atts['category']),
            );
        }
        
        if (!empty($atts['location'])) {
            $tax_query[] = array(
                'taxonomy' => 'location',
                'field' => 'slug',
                'terms' => explode(',', $atts['location']),
            );
        }
        
        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }
        
        // Add featured meta query if needed
        if ($featured) {
            $args['meta_query'] = array(
                array(
                    'key' => '_tb_court_featured',
                    'value' => '1',
                    'compare' => '=',
                )
            );
        }
        
        // Run the query
        $courts_query = new WP_Query($args);
        
        // Start output buffer
        ob_start();
        
        if ($courts_query->have_posts()) {
            // Get column class based on column count
            $column_class = 'tb-col-' . $columns;
            
            echo '<div class="tb-courts-container">';
            echo '<div class="tb-courts-grid ' . esc_attr($column_class) . '">';
            
            while ($courts_query->have_posts()) {
                $courts_query->the_post();
                include(TURF_BOOKING_PLUGIN_DIR . 'public/templates/content-court-card.php');
            }
            
            echo '</div>';
            echo '</div>';
            
        } else {
            echo '<p>' . __('No courts found.', 'turf-booking') . '</p>';
        }
        
        // Reset post data
        wp_reset_postdata();
        
        // Return the output
        return ob_get_clean();
    }

    /**
     * Shortcode for user account dashboard
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function account_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'redirect' => '',
        ), $atts, 'turf_booking_account');
        
        // Get user dashboard instance
        $user_dashboard = new Turf_Booking_User_Dashboard();
        
        // Return dashboard content
        return $user_dashboard->account_dashboard_shortcode($atts);
    }

    /**
     * Shortcode for booking form
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function booking_form_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'court_id' => 0,
            'date' => '',
            'redirect' => '',
        ), $atts, 'turf_booking_form');
        
        $court_id = intval($atts['court_id']);
        
        // Start output buffer
        ob_start();
        
        // Check if we have a valid court ID
        if ($court_id <= 0) {
            echo '<p>' . __('Please specify a valid court ID.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Check if the court exists
        $court = get_post($court_id);
        
        if (!$court || $court->post_type !== 'tb_court') {
            echo '<p>' . __('The specified court does not exist.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Include booking form template
        include(TURF_BOOKING_PLUGIN_DIR . 'public/templates/booking-form.php');
        
        // Return the output
        return ob_get_clean();
    }

    /**
     * Shortcode for checkout page
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function checkout_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'booking_id' => 0,
        ), $atts, 'turf_booking_checkout');
        
        // Get booking ID from attributes or URL
        $booking_id = intval($atts['booking_id']);
        
        if ($booking_id <= 0 && isset($_GET['booking_id'])) {
            $booking_id = intval($_GET['booking_id']);
        }
        
        // Start output buffer
        ob_start();
        
        if ($booking_id <= 0) {
            echo '<p>' . __('No booking ID specified.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Check if the booking exists
        $booking = get_post($booking_id);
        
        if (!$booking || $booking->post_type !== 'tb_booking') {
            echo '<p>' . __('The specified booking does not exist.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Check if user is authorized to view this booking
        $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
        
        if (!current_user_can('manage_options') && $booking_user_id != $user_id) {
            echo '<p>' . __('You do not have permission to view this booking.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Include checkout template
        include(TURF_BOOKING_PLUGIN_DIR . 'public/templates/checkout.php');
        
        // Return the output
        return ob_get_clean();
    }

    /**
     * Shortcode for booking confirmation page
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function confirmation_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'booking_id' => 0,
        ), $atts, 'turf_booking_confirmation');
        
        // Get booking ID from attributes or URL
        $booking_id = intval($atts['booking_id']);
        
        if ($booking_id <= 0 && isset($_GET['booking_id'])) {
            $booking_id = intval($_GET['booking_id']);
        }
        
        // Start output buffer
        ob_start();
        
        if ($booking_id <= 0) {
            echo '<p>' . __('No booking ID specified.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Check if the booking exists
        $booking = get_post($booking_id);
        
        if (!$booking || $booking->post_type !== 'tb_booking') {
            echo '<p>' . __('The specified booking does not exist.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Check if user is authorized to view this booking
        $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
        
        if (!current_user_can('manage_options') && $booking_user_id != $user_id) {
            echo '<p>' . __('You do not have permission to view this booking.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Get payment status
        $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
        $booking_status = get_post_meta($booking_id, '_tb_booking_status', true);
        
        // Include confirmation template
        include(TURF_BOOKING_PLUGIN_DIR . 'public/templates/booking-confirmation.php');
        
        // Return the output
        return ob_get_clean();
    }
}