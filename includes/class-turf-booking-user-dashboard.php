<?php
/**
 * Handle user dashboard functionality.
 */
class Turf_Booking_User_Dashboard {

    /**
     * Initialize the class.
     */
    public function __construct() {
        // Register shortcode
        add_shortcode('turf_booking_account', array($this, 'account_dashboard_shortcode'));
        
        // Process dashboard actions
        add_action('template_redirect', array($this, 'process_dashboard_actions'));
        
        // Add AJAX handlers
        add_action('wp_ajax_tb_dashboard_actions', array($this, 'ajax_dashboard_actions'));
        add_action('wp_ajax_nopriv_tb_dashboard_actions', array($this, 'ajax_dashboard_actions'));
        
        // Register user profile update handlers
        add_action('admin_post_tb_update_profile', array($this, 'process_profile_update'));
        add_action('admin_post_nopriv_tb_update_profile', array($this, 'process_profile_update'));
    }
    
    /**
     * Process user dashboard actions
     */
    public function process_dashboard_actions() {
        // Check if we're on the account page
        global $post;
        if (!$post || !is_page()) {
            return;
        }
        
        $page_settings = get_option('tb_page_settings');
        if (!isset($page_settings['my-account']) || $post->ID != $page_settings['my-account']) {
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return;
        }
        
        // Check if there's an action
        if (!isset($_GET['action'])) {
            return;
        }
        
        $action = sanitize_text_field($_GET['action']);
        
        switch ($action) {
            case 'view-booking':
                // Check if there's a booking ID
                if (!isset($_GET['id'])) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                $booking_id = absint($_GET['id']);
                $user_id = get_current_user_id();
                
                // Check if user owns this booking
                $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
                
                if ($booking_user_id != $user_id) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                break;
                
            case 'cancel-booking':
                // Check if there's a booking ID
                if (!isset($_GET['id'])) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                // Check nonce
                if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'tb_cancel_booking_' . $_GET['id'])) {
                    wp_redirect(add_query_arg('error', 'invalid_nonce', get_permalink($page_settings['my-account'])));
                    exit;
                }
                
                $booking_id = absint($_GET['id']);
                $user_id = get_current_user_id();
                
                // Check if user owns this booking
                $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
                
                if ($booking_user_id != $user_id) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                // Check booking status
                $booking_status = get_post_meta($booking_id, '_tb_booking_status', true);
                
                if ($booking_status === 'cancelled' || $booking_status === 'completed') {
                    wp_redirect(add_query_arg('error', 'cannot_cancel', get_permalink($page_settings['my-account'])));
                    exit;
                }
                
                // Get cancellation policy
                $general_settings = get_option('tb_general_settings');
                $cancellation_hours = isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24;
                
                // Check if booking can be cancelled according to policy
                $booking_date = get_post_meta($booking_id, '_tb_booking_date', true);
                $booking_time = get_post_meta($booking_id, '_tb_booking_time_from', true);
                $booking_datetime = strtotime($booking_date . ' ' . $booking_time);
                
                if ($booking_datetime - time() < $cancellation_hours * 3600) {
                    wp_redirect(add_query_arg('error', 'cancellation_period', get_permalink($page_settings['my-account'])));
                    exit;
                }
                
                // Update booking status
                update_post_meta($booking_id, '_tb_booking_status', 'cancelled');
                
                // Update booking slot status
                global $wpdb;
                $table_name = $wpdb->prefix . 'tb_booking_slots';
                
                $wpdb->update(
                    $table_name,
                    array(
                        'status' => 'available',
                        'booking_id' => null,
                    ),
                    array(
                        'booking_id' => $booking_id,
                    ),
                    array('%s', null),
                    array('%d')
                );
                
                // Record in booking slot history
                $table_name_history = $wpdb->prefix . 'tb_booking_slot_history';
                
                // Get booking slots
                $slots = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $table_name WHERE booking_id = %d",
                        $booking_id
                    )
                );
                
                foreach ($slots as $slot) {
                    $wpdb->insert(
                        $table_name_history,
                        array(
                            'slot_id' => $slot->id,
                            'court_id' => $slot->court_id,
                            'booking_id' => $booking_id,
                            'booking_date' => $slot->booking_date,
                            'time_from' => $slot->time_from,
                            'time_to' => $slot->time_to,
                            'status' => 'cancelled',
                            'created_at' => current_time('mysql'),
                            'user_id' => $user_id,
                        ),
                        array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
                    );
                }
                
                // Check if payment was made
                $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
                
                if ($payment_status === 'completed') {
                    // Process refund according to policy
                    $refund_policy = isset($general_settings['refund_policy']) ? $general_settings['refund_policy'] : 'full';
                    
                    if ($refund_policy === 'full') {
                        update_post_meta($booking_id, '_tb_booking_payment_status', 'refunded');
                    } else if ($refund_policy === 'partial') {
                        update_post_meta($booking_id, '_tb_booking_payment_status', 'partially_refunded');
                    } else {
                        update_post_meta($booking_id, '_tb_booking_payment_status', 'no_refund');
                    }
                }
                
                // Send cancellation email
                $this->send_booking_cancelled_email($booking_id);
                
                wp_redirect(add_query_arg('message', 'booking_cancelled', get_permalink($page_settings['my-account'])));
                exit;
                break;
                
            case 'invoice':
                // Check if there's a booking ID
                if (!isset($_GET['id'])) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                $booking_id = absint($_GET['id']);
                $user_id = get_current_user_id();
                
                // Check if user owns this booking
                $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
                
                if ($booking_user_id != $user_id) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                // Check if payment is completed
                $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
                
                if ($payment_status !== 'completed') {
                    wp_redirect(add_query_arg('error', 'no_invoice', get_permalink($page_settings['my-account'])));
                    exit;
                }
                break;
                
            case 'pay':
                // Check if there's a booking ID
                if (!isset($_GET['id'])) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                $booking_id = absint($_GET['id']);
                $user_id = get_current_user_id();
                
                // Check if user owns this booking
                $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
                
                if ($booking_user_id != $user_id) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                // Check booking status
                $booking_status = get_post_meta($booking_id, '_tb_booking_status', true);
                $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
                
                if ($booking_status === 'cancelled' || $payment_status === 'completed') {
                    wp_redirect(add_query_arg('error', 'cannot_pay', get_permalink($page_settings['my-account'])));
                    exit;
                }
                
                // Redirect to checkout page
                wp_redirect(add_query_arg('booking_id', $booking_id, get_permalink($page_settings['checkout'])));
                exit;
                break;
        }
    }
    
    /**
     * Account dashboard shortcode
     */
    public function account_dashboard_shortcode($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return $this->get_login_form();
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        // Check if there's an action
        if (isset($_GET['action'])) {
            $action = sanitize_text_field($_GET['action']);
            
            switch ($action) {
                case 'view-booking':
                    // Check if there's a booking ID
                    if (!isset($_GET['id'])) {
                        return $this->get_dashboard_content($user_id);
                    }
                    
                    $booking_id = absint($_GET['id']);
                    
                    // Check if user owns this booking
                    $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
                    
                    if ($booking_user_id != $user_id) {
                        return $this->get_dashboard_content($user_id);
                    }
                    
                    return $this->get_booking_details($booking_id);
                    break;
                    
                case 'invoice':
                    // Check if there's a booking ID
                    if (!isset($_GET['id'])) {
                        return $this->get_dashboard_content($user_id);
                    }
                    
                    $booking_id = absint($_GET['id']);
                    
                    // Check if user owns this booking
                    $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
                    
                    if ($booking_user_id != $user_id) {
                        return $this->get_dashboard_content($user_id);
                    }
                    
                    // Check if payment is completed
                    $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
                    
                    if ($payment_status !== 'completed') {
                        return $this->get_dashboard_content($user_id);
                    }
                    
                    // Get invoice
                    $payments = new Turf_Booking_Payments();
                    return $payments->generate_invoice($booking_id);
                    break;
                    
                default:
                    return $this->get_dashboard_content($user_id);
                    break;
            }
        }
        
        return $this->get_dashboard_content($user_id);
    }
    
    /**
     * Get login form
     */
    private function get_login_form() {
        ob_start();
        ?>
        <div class="tb-login-container">
            <h2><?php _e('Login to Your Account', 'turf-booking'); ?></h2>
            
            <?php if (isset($_GET['login']) && $_GET['login'] === 'failed') : ?>
                <div class="tb-error">
                    <p><?php _e('Invalid username or password. Please try again.', 'turf-booking'); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo esc_url(wp_login_url()); ?>" class="tb-login-form">
                <div class="tb-form-group">
                    <label for="user_login"><?php _e('Username or Email', 'turf-booking'); ?></label>
                    <input type="text" name="log" id="user_login" class="tb-input" required>
                </div>
                
                <div class="tb-form-group">
                    <label for="user_pass"><?php _e('Password', 'turf-booking'); ?></label>
                    <input type="password" name="pwd" id="user_pass" class="tb-input" required>
                </div>
                
                <div class="tb-form-group">
                    <label for="rememberme" class="tb-checkbox-label">
                        <input type="checkbox" name="rememberme" id="rememberme" value="forever">
                        <?php _e('Remember Me', 'turf-booking'); ?>
                    </label>
                </div>
                
                <div class="tb-form-group">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url(get_permalink()); ?>">
                    <button type="submit" class="tb-button"><?php _e('Login', 'turf-booking'); ?></button>
                </div>
            </form>
            
            <div class="tb-register-link">
                <p><?php _e('Don\'t have an account?', 'turf-booking'); ?> <a href="<?php echo esc_url(wp_registration_url()); ?>"><?php _e('Register Now', 'turf-booking'); ?></a></p>
                <p><a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php _e('Forgot Password?', 'turf-booking'); ?></a></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get dashboard content
     */
    private function get_dashboard_content($user_id) {
        // Get user data
        $user = get_userdata($user_id);
        
        // Get bookings
        $bookings_obj = new Turf_Booking_Bookings();
        $all_bookings = $bookings_obj->get_user_bookings($user_id);
        
        // Sort bookings by date (newest first)
        usort($all_bookings, function($a, $b) {
            $a_date = get_post_meta($a->ID, '_tb_booking_date', true);
            $b_date = get_post_meta($b->ID, '_tb_booking_date', true);
            
            return strtotime($b_date) - strtotime($a_date);
        });
        
        // Filter bookings by status
        $upcoming_bookings = array_filter($all_bookings, function($booking) {
            $status = get_post_meta($booking->ID, '_tb_booking_status', true);
            $date = get_post_meta($booking->ID, '_tb_booking_date', true);
            
            return ($status === 'pending' || $status === 'confirmed') && strtotime($date) >= strtotime('today');
        });
        
        $past_bookings = array_filter($all_bookings, function($booking) {
            $status = get_post_meta($booking->ID, '_tb_booking_status', true);
            $date = get_post_meta($booking->ID, '_tb_booking_date', true);
            
            return $status === 'completed' || strtotime($date) < strtotime('today');
        });
        
        $cancelled_bookings = array_filter($all_bookings, function($booking) {
            $status = get_post_meta($booking->ID, '_tb_booking_status', true);
            
            return $status === 'cancelled';
        });
        
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        
        ob_start();
        ?>
        <div class="tb-dashboard-container">
            <div class="tb-dashboard-header">
                <h2><?php _e('My Account', 'turf-booking'); ?></h2>
                
                <?php if (isset($_GET['message']) && $_GET['message'] === 'booking_cancelled') : ?>
                    <div class="tb-success-message">
                        <p><?php _e('Your booking has been cancelled successfully.', 'turf-booking'); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])) : ?>
                    <div class="tb-error-message">
                        <?php if ($_GET['error'] === 'invalid_nonce') : ?>
                            <p><?php _e('Security check failed. Please try again.', 'turf-booking'); ?></p>
                        <?php elseif ($_GET['error'] === 'cannot_cancel') : ?>
                            <p><?php _e('This booking cannot be cancelled.', 'turf-booking'); ?></p>
                        <?php elseif ($_GET['error'] === 'cancellation_period') : ?>
                            <?php
                            $general_settings = get_option('tb_general_settings');
                            $cancellation_hours = isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24;
                            ?>
                            <p><?php printf(__('Bookings can only be cancelled at least %d hours in advance.', 'turf-booking'), $cancellation_hours); ?></p>
                        <?php elseif ($_GET['error'] === 'no_invoice') : ?>
                            <p><?php _e('No invoice is available for this booking.', 'turf-booking'); ?></p>
                        <?php elseif ($_GET['error'] === 'cannot_pay') : ?>
                            <p><?php _e('This booking cannot be processed for payment.', 'turf-booking'); ?></p>
                        <?php else : ?>
                            <p><?php _e('An error occurred. Please try again.', 'turf-booking'); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tb-dashboard-nav">
                <ul class="tb-dashboard-tabs">
                    <li class="<?php echo ($current_tab === 'dashboard') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'dashboard', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                            <i class="dashicons dashicons-dashboard"></i> <?php _e('Dashboard', 'turf-booking'); ?>
                        </a>
                    </li>
                    <li class="<?php echo ($current_tab === 'bookings') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'bookings', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                            <i class="dashicons dashicons-calendar-alt"></i> <?php _e('My Bookings', 'turf-booking'); ?>
                        </a>
                    </li>
                    <li class="<?php echo ($current_tab === 'profile') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'profile', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                            <i class="dashicons dashicons-admin-users"></i> <?php _e('Profile', 'turf-booking'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>">
                            <i class="dashicons dashicons-exit"></i> <?php _e('Logout', 'turf-booking'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="tb-dashboard-content">
                <?php if ($current_tab === 'dashboard') : ?>
                    <div class="tb-dashboard-welcome">
                        <h3><?php printf(__('Welcome, %s!', 'turf-booking'), $user->display_name); ?></h3>
                        <p><?php _e('From your account dashboard you can view your recent bookings, manage your profile and more.', 'turf-booking'); ?></p>
                    </div>
                    
                    <div class="tb-dashboard-stats">
                        <div class="tb-stat-box">
                            <div class="tb-stat-icon">
                                <i class="dashicons dashicons-calendar-alt"></i>
                            </div>
                            <div class="tb-stat-content">
                                <h4><?php _e('Upcoming Bookings', 'turf-booking'); ?></h4>
                                <p class="tb-stat-number"><?php echo count($upcoming_bookings); ?></p>
                            </div>
                        </div>
                        
                        <div class="tb-stat-box">
                            <div class="tb-stat-icon">
                                <i class="dashicons dashicons-backup"></i>
                            </div>
                            <div class="tb-stat-content">
                                <h4><?php _e('Past Bookings', 'turf-booking'); ?></h4>
                                <p class="tb-stat-number"><?php echo count($past_bookings); ?></p>
                            </div>
                        </div>
                        
                        <div class="tb-stat-box">
                            <div class="tb-stat-icon">
                                <i class="dashicons dashicons-dismiss"></i>
                            </div>
                            <div class="tb-stat-content">
                                <h4><?php _e('Cancelled Bookings', 'turf-booking'); ?></h4>
                                <p class="tb-stat-number"><?php echo count($cancelled_bookings); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($upcoming_bookings)) : ?>
                        <div class="tb-dashboard-section">
                            <h3><?php _e('Upcoming Bookings', 'turf-booking'); ?></h3>
                            <div class="tb-bookings-grid">
                                <?php foreach (array_slice($upcoming_bookings, 0, 3) as $booking) : ?>
                                    <?php echo $this->get_booking_card($booking->ID); ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($upcoming_bookings) > 3) : ?>
                                <p class="tb-view-all">
                                    <a href="<?php echo esc_url(add_query_arg('tab', 'bookings', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>" class="tb-button-link">
                                        <?php _e('View All Bookings', 'turf-booking'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="tb-dashboard-section">
                        <h3><?php _e('Quick Links', 'turf-booking'); ?></h3>
                        <div class="tb-quick-links">
                            <a href="<?php echo esc_url(get_permalink(get_option('tb_page_settings')['courts'])); ?>" class="tb-quick-link">
                                <i class="dashicons dashicons-location"></i>
                                <span><?php _e('Book a Court', 'turf-booking'); ?></span>
                            </a>
                            <a href="<?php echo esc_url(add_query_arg('tab', 'bookings', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>" class="tb-quick-link">
                                <i class="dashicons dashicons-calendar-alt"></i>
                                <span><?php _e('My Bookings', 'turf-booking'); ?></span>
                            </a>
                            <a href="<?php echo esc_url(add_query_arg('tab', 'profile', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>" class="tb-quick-link">
                                <i class="dashicons dashicons-admin-users"></i>
                                <span><?php _e('My Profile', 'turf-booking'); ?></span>
                            </a>
                        </div>
                    </div>
                <?php elseif ($current_tab === 'bookings') : ?>
                    <div class="tb-bookings-container">
                        <h3><?php _e('My Bookings', 'turf-booking'); ?></h3>
                        
                        <div class="tb-bookings-tabs">
                            <ul class="tb-booking-status-tabs">
                                <li class="active">
                                    <a href="#upcoming" data-tab="upcoming"><?php _e('Upcoming', 'turf-booking'); ?></a>
                                </li>
                                <li>
                                    <a href="#past" data-tab="past"><?php _e('Past', 'turf-booking'); ?></a>
                                </li>
                                <li>
                                    <a href="#cancelled" data-tab="cancelled"><?php _e('Cancelled', 'turf-booking'); ?></a>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="tb-bookings-tab-content">
                            <div id="upcoming" class="tb-tab-pane active">
                                <?php if (empty($upcoming_bookings)) : ?>
                                    <p class="tb-no-bookings"><?php _e('You have no upcoming bookings.', 'turf-booking'); ?></p>
                                <?php else : ?>
                                    <div class="tb-bookings-grid">
                                        <?php foreach ($upcoming_bookings as $booking) : ?>
                                            <?php echo $this->get_booking_card($booking->ID); ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div id="past" class="tb-tab-pane">
                                <?php if (empty($past_bookings)) : ?>
                                    <p class="tb-no-bookings"><?php _e('You have no past bookings.', 'turf-booking'); ?></p>
                                <?php else : ?>
                                    <div class="tb-bookings-grid">
                                        <?php foreach ($past_bookings as $booking) : ?>
                                            <?php echo $this->get_booking_card($booking->ID); ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div id="cancelled" class="tb-tab-pane">
                                <?php if (empty($cancelled_bookings)) : ?>
                                    <p class="tb-no-bookings"><?php _e('You have no cancelled bookings.', 'turf-booking'); ?></p>
                                <?php else : ?>
                                    <div class="tb-bookings-grid">
                                        <?php foreach ($cancelled_bookings as $booking) : ?>
                                            <?php echo $this->get_booking_card($booking->ID); ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php elseif ($current_tab === 'profile') : ?>
                    <div class="tb-profile-container">
                        <h3><?php _e('My Profile', 'turf-booking'); ?></h3>
                        
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="tb-profile-form">
                            <input type="hidden" name="action" value="tb_update_profile">
                            <?php wp_nonce_field('tb_update_profile', 'tb_profile_nonce'); ?>
                            
                            <div class="tb-form-row">
                                <div class="tb-form-group">
                                    <label for="first_name"><?php _e('First Name', 'turf-booking'); ?></label>
                                    <input type="text" name="first_name" id="first_name" class="tb-input" value="<?php echo esc_attr($user->first_name); ?>">
                                </div>
                                
                                <div class="tb-form-group">
                                    <label for="last_name"><?php _e('Last Name', 'turf-booking'); ?></label>
                                    <input type="text" name="last_name" id="last_name" class="tb-input" value="<?php echo esc_attr($user->last_name); ?>">
                                </div>
                            </div>
                            
                            <div class="tb-form-group">
                                <label for="display_name"><?php _e('Display Name', 'turf-booking'); ?></label>
                                <input type="text" name="display_name" id="display_name" class="tb-input" value="<?php echo esc_attr($user->display_name); ?>">
                            </div>
                            
                            <div class="tb-form-group">
                                <label for="email"><?php _e('Email Address', 'turf-booking'); ?></label>
                                <input type="email" name="email" id="email" class="tb-input" value="<?php echo esc_attr($user->user_email); ?>">
                            </div>
                            
                            <div class="tb-form-group">
                                <label for="phone"><?php _e('Phone Number', 'turf-booking'); ?></label>
                                <input type="text" name="phone" id="phone" class="tb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'phone', true)); ?>">
                            </div>
                            
                            <h4><?php _e('Change Password', 'turf-booking'); ?></h4>
                            <p class="tb-form-note"><?php _e('Leave blank to keep current password', 'turf-booking'); ?></p>
                            
                            <div class="tb-form-group">
                                <label for="current_password"><?php _e('Current Password', 'turf-booking'); ?></label>
                                <input type="password" name="current_password" id="current_password" class="tb-input">
                            </div>
                            
                            <div class="tb-form-row">
                                <div class="tb-form-group">
                                    <label for="new_password"><?php _e('New Password', 'turf-booking'); ?></label>
                                    <input type="password" name="new_password" id="new_password" class="tb-input">
                                </div>
                                
                                <div class="tb-form-group">
                                    <label for="confirm_password"><?php _e('Confirm New Password', 'turf-booking'); ?></label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="tb-input">
                                </div>
                            </div>
                            
                            <div class="tb-form-group">
                                <button type="submit" class="tb-button"><?php _e('Update Profile', 'turf-booking'); ?></button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get booking details
     */
    private function get_booking_details($booking_id) {
        // Get booking data
        $bookings_obj = new Turf_Booking_Bookings();
        $booking_details = $bookings_obj->get_booking_details($booking_id);
        
        if (!$booking_details) {
            return '<div class="tb-error-message"><p>' . __('Booking not found', 'turf-booking') . '</p></div>';
        }
        
        // Get court data
        $court_id = $booking_details['court_id'];
        $court = get_post($court_id);
        
        if (!$court) {
            return '<div class="tb-error-message"><p>' . __('Court not found', 'turf-booking') . '</p></div>';
        }
        
        // Format date and time
        $general_settings = get_option('tb_general_settings');
        $date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
        $time_format = isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i';
        
        $formatted_date = date($date_format, strtotime($booking_details['date']));
        $formatted_time_from = date($time_format, strtotime($booking_details['time_from']));
        $formatted_time_to = date($time_format, strtotime($booking_details['time_to']));
        
        // Get currency symbol
        $currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';
        
        ob_start();
        ?>
        <div class="tb-booking-details-container">
            <div class="tb-booking-details-header">
                <h2><?php _e('Booking Details', 'turf-booking'); ?></h2>
                <a href="<?php echo esc_url(add_query_arg('tab', 'bookings', remove_query_arg(array('action', 'id')))); ?>" class="tb-back-link">
                    <i class="dashicons dashicons-arrow-left-alt"></i> <?php _e('Back to Bookings', 'turf-booking'); ?>
                </a>
            </div>
            
            <div class="tb-booking-details-content">
                <div class="tb-booking-details-main">
                    <div class="tb-booking-court-info">
                        <div class="tb-booking-court-image">
                            <?php if (has_post_thumbnail($court_id)) : ?>
                                <?php echo get_the_post_thumbnail($court_id, 'medium'); ?>
                            <?php else : ?>
                                <div class="tb-no-image"><?php _e('No Image', 'turf-booking'); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tb-booking-court-details">
                            <h3><?php echo esc_html($court->post_title); ?></h3>
                            
                            <div class="tb-booking-status-badge">
                                <span class="tb-booking-status tb-status-<?php echo esc_attr($booking_details['status']); ?>">
                                    <?php echo esc_html(ucfirst($booking_details['status'])); ?>
                                </span>
                            </div>
                            
                            <div class="tb-booking-info">
                                <div class="tb-booking-info-item">
                                    <i class="dashicons dashicons-calendar-alt"></i>
                                    <span><?php echo esc_html($formatted_date); ?></span>
                                </div>
                                
                                <div class="tb-booking-info-item">
                                    <i class="dashicons dashicons-clock"></i>
                                    <span><?php echo esc_html($formatted_time_from . ' - ' . $formatted_time_to); ?></span>
                                </div>
                                
                                <?php if ($booking_details['payment_amount']) : ?>
                                    <div class="tb-booking-info-item">
                                        <i class="dashicons dashicons-money-alt"></i>
                                        <span><?php echo esc_html($currency_symbol . number_format($booking_details['payment_amount'], 2)); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="tb-booking-description">
                                <?php echo apply_filters('the_content', $court->post_content); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tb-booking-meta-info">
                        <div class="tb-booking-meta-section">
                            <h4><?php _e('Booking Information', 'turf-booking'); ?></h4>
                            
                            <div class="tb-booking-meta-item">
                                <div class="tb-booking-meta-label"><?php _e('Booking ID', 'turf-booking'); ?></div>
                                <div class="tb-booking-meta-value"><?php echo esc_html($booking_id); ?></div>
                            </div>
                            
                            <div class="tb-booking-meta-item">
                                <div class="tb-booking-meta-label"><?php _e('Booking Date', 'turf-booking'); ?></div>
                                <div class="tb-booking-meta-value"><?php echo esc_html($formatted_date); ?></div>
                            </div>
                            
                            <div class="tb-booking-meta-item">
                                <div class="tb-booking-meta-label"><?php _e('Booking Time', 'turf-booking'); ?></div>
                                <div class="tb-booking-meta-value"><?php echo esc_html($formatted_time_from . ' - ' . $formatted_time_to); ?></div>
                            </div>
                            
                            <div class="tb-booking-meta-item">
                                <div class="tb-booking-meta-label"><?php _e('Status', 'turf-booking'); ?></div>
                                <div class="tb-booking-meta-value">
                                    <span class="tb-booking-status tb-status-<?php echo esc_attr($booking_details['status']); ?>">
                                        <?php echo esc_html(ucfirst($booking_details['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="tb-booking-meta-item">
                                <div class="tb-booking-meta-label"><?php _e('Created On', 'turf-booking'); ?></div>
                                <div class="tb-booking-meta-value"><?php echo esc_html(date($date_format, strtotime($booking_details['created_at']))); ?></div>
                            </div>
                        </div>
                        
                        <div class="tb-booking-meta-section">
                            <h4><?php _e('Payment Information', 'turf-booking'); ?></h4>
                            
                            <div class="tb-booking-meta-item">
                                <div class="tb-booking-meta-label"><?php _e('Amount', 'turf-booking'); ?></div>
                                <div class="tb-booking-meta-value"><?php echo esc_html($currency_symbol . number_format($booking_details['payment_amount'], 2)); ?></div>
                            </div>
                            
                            <div class="tb-booking-meta-item">
                                <div class="tb-booking-meta-label"><?php _e('Payment Method', 'turf-booking'); ?></div>
                                <div class="tb-booking-meta-value">
                                    <?php 
                                    if ($booking_details['payment_method'] === 'razorpay') {
                                        _e('Razorpay', 'turf-booking');
                                    } else if ($booking_details['payment_method']) {
                                        echo esc_html(ucfirst($booking_details['payment_method']));
                                    } else {
                                        _e('N/A', 'turf-booking');
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="tb-booking-meta-item">
                                <div class="tb-booking-meta-label"><?php _e('Payment Status', 'turf-booking'); ?></div>
                                <div class="tb-booking-meta-value">
                                    <?php 
                                    if ($booking_details['payment_status'] === 'completed') {
                                        echo '<span class="tb-payment-status tb-payment-completed">' . __('Paid', 'turf-booking') . '</span>';
                                    } else if ($booking_details['payment_status'] === 'pending') {
                                        echo '<span class="tb-payment-status tb-payment-pending">' . __('Pending', 'turf-booking') . '</span>';
                                    } else if ($booking_details['payment_status'] === 'refunded') {
                                        echo '<span class="tb-payment-status tb-payment-refunded">' . __('Refunded', 'turf-booking') . '</span>';
                                    } else if ($booking_details['payment_status'] === 'partially_refunded') {
                                        echo '<span class="tb-payment-status tb-payment-partially-refunded">' . __('Partially Refunded', 'turf-booking') . '</span>';
                                    } else if ($booking_details['payment_status'] === 'failed') {
                                        echo '<span class="tb-payment-status tb-payment-failed">' . __('Failed', 'turf-booking') . '</span>';
                                    } else {
                                        echo '<span class="tb-payment-status">' . esc_html(ucfirst($booking_details['payment_status'])) . '</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <?php if ($booking_details['payment_id']) : ?>
                                <div class="tb-booking-meta-item">
                                    <div class="tb-booking-meta-label"><?php _e('Payment ID', 'turf-booking'); ?></div>
                                    <div class="tb-booking-meta-value"><?php echo esc_html($booking_details['payment_id']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($booking_details['payment_date']) : ?>
                                <div class="tb-booking-meta-item">
                                    <div class="tb-booking-meta-label"><?php _e('Payment Date', 'turf-booking'); ?></div>
                                    <div class="tb-booking-meta-value"><?php echo esc_html(date($date_format, strtotime($booking_details['payment_date']))); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="tb-booking-actions">
                    <?php
                    // Show different actions based on booking status
                    if ($booking_details['status'] === 'pending' || $booking_details['status'] === 'confirmed') {
                        // Check if booking is in the future
                        if (strtotime($booking_details['date']) > strtotime('today')) {
                            // Check cancellation policy
                            $general_settings = get_option('tb_general_settings');
                            $cancellation_hours = isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24;
                            
                            $booking_datetime = strtotime($booking_details['date'] . ' ' . $booking_details['time_from']);
                            
                            if ($booking_datetime - time() > $cancellation_hours * 3600) {
                                // Allow cancellation
                                $cancel_url = add_query_arg(
                                    array(
                                        'action' => 'cancel-booking',
                                        'id' => $booking_id,
                                        '_wpnonce' => wp_create_nonce('tb_cancel_booking_' . $booking_id),
                                    ),
                                    get_permalink()
                                );
                                
                                echo '<a href="' . esc_url($cancel_url) . '" class="tb-button-link danger" onclick="return confirm(\'' . __('Are you sure you want to cancel this booking?', 'turf-booking') . '\')">' . __('Cancel Booking', 'turf-booking') . '</a>';
                            } else {
                                echo '<p class="tb-cancellation-note">' . sprintf(__('Bookings can only be cancelled at least %d hours in advance.', 'turf-booking'), $cancellation_hours) . '</p>';
                            }
                        }
                        
                        // If payment is pending, show pay now button
                        if ($booking_details['payment_status'] === 'pending') {
                            $pay_url = add_query_arg(
                                array(
                                    'action' => 'pay',
                                    'id' => $booking_id,
                                ),
                                get_permalink()
                            );
                            
                            echo '<a href="' . esc_url($pay_url) . '" class="tb-button-link">' . __('Pay Now', 'turf-booking') . '</a>';
                        }
                    }
                    
                    // If payment is completed, show invoice button
                    if ($booking_details['payment_status'] === 'completed') {
                        $invoice_url = add_query_arg(
                            array(
                                'action' => 'invoice',
                                'id' => $booking_id,
                            ),
                            get_permalink()
                        );
                        
                        echo '<a href="' . esc_url($invoice_url) . '" class="tb-button-link">' . __('View Invoice', 'turf-booking') . '</a>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get booking card
     */
    private function get_booking_card($booking_id) {
        // Get booking data
        $bookings_obj = new Turf_Booking_Bookings();
        $booking_details = $bookings_obj->get_booking_details($booking_id);
        
        if (!$booking_details) {
            return '';
        }
        
        // Get court data
        $court_id = $booking_details['court_id'];
        $court = get_post($court_id);
        
        if (!$court) {
            return '';
        }
        
        // Format date and time
        $general_settings = get_option('tb_general_settings');
        $date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
        $time_format = isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i';
        
        $formatted_date = date($date_format, strtotime($booking_details['date']));
        $formatted_time_from = date($time_format, strtotime($booking_details['time_from']));
        $formatted_time_to = date($time_format, strtotime($booking_details['time_to']));
        
        // Get currency symbol
        $currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';
        
        ob_start();
        ?>
        <div class="tb-booking-card">
            <div class="tb-booking-card-header">
                <div class="tb-booking-card-image" style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url($court_id, 'thumbnail') ?: ''); ?>')"></div>
                <div class="tb-booking-card-title">
                    <h4><?php echo esc_html($court->post_title); ?></h4>
                    <span class="tb-booking-status tb-status-<?php echo esc_attr($booking_details['status']); ?>">
                        <?php echo esc_html(ucfirst($booking_details['status'])); ?>
                    </span>
                </div>
            </div>
            
            <div class="tb-booking-card-details">
                <div class="tb-booking-detail">
                    <div class="tb-booking-detail-label"><?php _e('Date:', 'turf-booking'); ?></div>
                    <div class="tb-booking-detail-value"><?php echo esc_html($formatted_date); ?></div>
                </div>
                
                <div class="tb-booking-detail">
                    <div class="tb-booking-detail-label"><?php _e('Time:', 'turf-booking'); ?></div>
                    <div class="tb-booking-detail-value"><?php echo esc_html($formatted_time_from . ' - ' . $formatted_time_to); ?></div>
                </div>
                
                <div class="tb-booking-detail">
                    <div class="tb-booking-detail-label"><?php _e('Amount:', 'turf-booking'); ?></div>
                    <div class="tb-booking-detail-value"><?php echo esc_html($currency_symbol . number_format($booking_details['payment_amount'], 2)); ?></div>
                </div>
            </div>
            
            <div class="tb-booking-card-actions">
                <a href="<?php echo esc_url(add_query_arg(array('action' => 'view-booking', 'id' => $booking_id))); ?>" class="tb-button-link"><?php _e('View Details', 'turf-booking'); ?></a>
                
                <?php if ($booking_details['status'] === 'pending' && $booking_details['payment_status'] === 'pending') : ?>
                    <a href="<?php echo esc_url(add_query_arg(array('action' => 'pay', 'id' => $booking_id))); ?>" class="tb-button-link secondary"><?php _e('Pay Now', 'turf-booking'); ?></a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle dashboard actions through AJAX
     */
    public function ajax_dashboard_actions() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_dashboard_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in', 'turf-booking')));
        }
        
        // Get action
        $action = isset($_POST['dashboard_action']) ? sanitize_text_field($_POST['dashboard_action']) : '';
        
        // Process action
        switch ($action) {
            case 'load_bookings':
                $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
                $bookings = $this->load_user_bookings($status);
                
                wp_send_json_success(array('bookings' => $bookings));
                break;
                
            case 'cancel_booking':
                $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
                $result = $this->cancel_booking($booking_id);
                
                if (is_wp_error($result)) {
                    wp_send_json_error(array('message' => $result->get_error_message()));
                } else {
                    wp_send_json_success(array('message' => __('Booking cancelled successfully', 'turf-booking')));
                }
                break;
                
            default:
                wp_send_json_error(array('message' => __('Invalid action', 'turf-booking')));
                break;
        }
    }
    
    /**
     * Process profile update
     */
    public function process_profile_update() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink(get_option('tb_page_settings')['my-account'])));
            exit;
        }
        
        // Check nonce
        if (!isset($_POST['tb_profile_nonce']) || !wp_verify_nonce($_POST['tb_profile_nonce'], 'tb_update_profile')) {
            wp_redirect(add_query_arg('error', 'invalid_nonce', get_permalink(get_option('tb_page_settings')['my-account'])));
            exit;
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        // Get form data
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        
        // Update user data
        $userdata = array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name,
        );
        
        // Only update email if it has changed
        if ($email && $email !== $user->user_email) {
            $userdata['user_email'] = $email;
        }
        
        $result = wp_update_user($userdata);
        
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg('error', 'update_failed', get_permalink(get_option('tb_page_settings')['my-account'])));
            exit;
        }
        
        // Update phone number
        update_user_meta($user_id, 'phone', $phone);
        
        // Process password update if provided
        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        if ($current_password && $new_password && $confirm_password) {
            // Check if current password is correct
            if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
                wp_redirect(add_query_arg('error', 'invalid_password', get_permalink(get_option('tb_page_settings')['my-account'])));
                exit;
            }
            
            // Check if new passwords match
            if ($new_password !== $confirm_password) {
                wp_redirect(add_query_arg('error', 'password_mismatch', get_permalink(get_option('tb_page_settings')['my-account'])));
                exit;
            }
            
            // Update password
            wp_set_password($new_password, $user_id);
            
            // Log the user back in
            wp_set_auth_cookie($user_id, true);
        }
        
        // Redirect to profile page with success message
        wp_redirect(add_query_arg(array('tab' => 'profile', 'message' => 'profile_updated'), get_permalink(get_option('tb_page_settings')['my-account'])));
        exit;
    }
    
    /**
     * Load user bookings for AJAX
     */
    private function load_user_bookings($status = '') {
        $user_id = get_current_user_id();
        $bookings_obj = new Turf_Booking_Bookings();
        $bookings = $bookings_obj->get_user_bookings($user_id, $status);
        
        $formatted_bookings = array();
        
        foreach ($bookings as $booking) {
            $booking_details = $bookings_obj->get_booking_details($booking->ID);
            
            if ($booking_details) {
                $formatted_bookings[] = array(
                    'id' => $booking->ID,
                    'court_id' => $booking_details['court_id'],
                    'court_name' => $booking_details['court_name'],
                    'court_image' => $booking_details['court_image'],
                    'date' => $booking_details['date'],
                    'time_from' => $booking_details['time_from'],
                    'time_to' => $booking_details['time_to'],
                    'status' => $booking_details['status'],
                    'payment_status' => $booking_details['payment_status'],
                    'payment_amount' => $booking_details['payment_amount'],
                    'created_at' => $booking_details['created_at'],
                    'card_html' => $this->get_booking_card($booking->ID),
                );
            }
        }
        
        return $formatted_bookings;
    }
    
    /**
     * Cancel a booking via AJAX
     */
    private function cancel_booking($booking_id) {
        if (!$booking_id) {
            return new WP_Error('invalid_booking', __('Invalid booking ID', 'turf-booking'));
        }
        
        $user_id = get_current_user_id();
        
        // Check if user owns this booking
        $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
        
        if ($booking_user_id != $user_id) {
            return new WP_Error('not_owner', __('You do not have permission to cancel this booking', 'turf-booking'));
        }
        
        // Check booking status
        $booking_status = get_post_meta($booking_id, '_tb_booking_status', true);
        
        if ($booking_status === 'cancelled' || $booking_status === 'completed') {
            return new WP_Error('cannot_cancel', __('This booking cannot be cancelled', 'turf-booking'));
        }
        
        // Get cancellation policy
        $general_settings = get_option('tb_general_settings');
        $cancellation_hours = isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24;
        
        // Check if booking can be cancelled according to policy
        $booking_date = get_post_meta($booking_id, '_tb_booking_date', true);
        $booking_time = get_post_meta($booking_id, '_tb_booking_time_from', true);
        $booking_datetime = strtotime($booking_date . ' ' . $booking_time);
        
        if ($booking_datetime - time() < $cancellation_hours * 3600) {
            return new WP_Error('cancellation_period', sprintf(
                __('Bookings can only be cancelled at least %d hours in advance', 'turf-booking'),
                $cancellation_hours
            ));
        }
        
        // Update booking status
        update_post_meta($booking_id, '_tb_booking_status', 'cancelled');
        
        // Update booking slot status
        global $wpdb;
        $table_name = $wpdb->prefix . 'tb_booking_slots';
        
        $wpdb->update(
            $table_name,
            array(
                'status' => 'available',
                'booking_id' => null,
            ),
            array(
                'booking_id' => $booking_id,
            ),
            array('%s', null),
            array('%d')
        );
        
        // Record in booking slot history
        $table_name_history = $wpdb->prefix . 'tb_booking_slot_history';
        
        // Get booking slots
        $slots = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE booking_id = %d",
                $booking_id
            )
        );
        
        foreach ($slots as $slot) {
            $wpdb->insert(
                $table_name_history,
                array(
                    'slot_id' => $slot->id,
                    'court_id' => $slot->court_id,
                    'booking_id' => $booking_id,
                    'booking_date' => $slot->booking_date,
                    'time_from' => $slot->time_from,
                    'time_to' => $slot->time_to,
                    'status' => 'cancelled',
                    'created_at' => current_time('mysql'),
                    'user_id' => $user_id,
                ),
                array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
            );
        }
        
        // Check if payment was made
        $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
        
        if ($payment_status === 'completed') {
            // Process refund according to policy
            $refund_policy = isset($general_settings['refund_policy']) ? $general_settings['refund_policy'] : 'full';
            
            if ($refund_policy === 'full') {
                update_post_meta($booking_id, '_tb_booking_payment_status', 'refunded');
            } else if ($refund_policy === 'partial') {
                update_post_meta($booking_id, '_tb_booking_payment_status', 'partially_refunded');
            } else {
                update_post_meta($booking_id, '_tb_booking_payment_status', 'no_refund');
            }
        }
        
        // Send cancellation email
        $this->send_booking_cancelled_email($booking_id);
        
        return true;
    }
    
    /**
     * Send booking cancelled email
     */
    private function send_booking_cancelled_email($booking_id) {
        $email_settings = get_option('tb_email_settings');
        
        $to = get_post_meta($booking_id, '_tb_booking_user_email', true);
        $subject = $email_settings['booking_cancelled_subject'];
        
        $message = $this->replace_email_placeholders(
            $email_settings['booking_cancelled_message'],
            $booking_id
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $email_settings['email_from_name'] . ' <' . $email_settings['email_from_address'] . '>',
        );
        
        wp_mail($to, $subject, nl2br($message), $headers);
    }
    
    /**
     * Replace email placeholders with actual booking data
     */
    private function replace_email_placeholders($content, $booking_id) {
        $court_id = get_post_meta($booking_id, '_tb_booking_court_id', true);
        $customer_name = get_post_meta($booking_id, '_tb_booking_user_name', true);
        $customer_email = get_post_meta($booking_id, '_tb_booking_user_email', true);
        $customer_phone = get_post_meta($booking_id, '_tb_booking_user_phone', true);
        $booking_date = get_post_meta($booking_id, '_tb_booking_date', true);
        $booking_time_from = get_post_meta($booking_id, '_tb_booking_time_from', true);
        $booking_time_to = get_post_meta($booking_id, '_tb_booking_time_to', true);
        $booking_total = get_post_meta($booking_id, '_tb_booking_payment_amount', true);
        
        $general_settings = get_option('tb_general_settings');
        $currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';
        
        // Format date and time according to settings
        $date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
        $time_format = isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i';
        
        $formatted_date = date($date_format, strtotime($booking_date));
        $formatted_time_from = date($time_format, strtotime($booking_time_from));
        $formatted_time_to = date($time_format, strtotime($booking_time_to));
        
        // Replace placeholders
        $placeholders = array(
            '{booking_id}' => $booking_id,
            '{court_name}' => get_the_title($court_id),
            '{customer_name}' => $customer_name,
            '{customer_email}' => $customer_email,
            '{customer_phone}' => $customer_phone,
            '{booking_date}' => $formatted_date,
            '{booking_time_from}' => $formatted_time_from,
            '{booking_time_to}' => $formatted_time_to,
            '{booking_total}' => $currency_symbol . number_format($booking_total, 2),
        );
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }
}