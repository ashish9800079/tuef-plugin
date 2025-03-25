<?php
/**
 * Template for the user dashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check if user is logged in
if (!is_user_logged_in()) {
    // Show login form
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
    return;
}

// Get current user
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

// Get user bookings
$bookings_obj = new Turf_Booking_Bookings();
$all_bookings = $bookings_obj->get_user_bookings($user_id);

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

// Check if there's an action
if (isset($_GET['action'])) {
    $action = sanitize_text_field($_GET['action']);
    
    if ($action === 'view-booking' && isset($_GET['id'])) {
        $booking_id = absint($_GET['id']);
        $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
        
        if ($booking_user_id == $user_id || current_user_can('manage_options')) {
            // Show booking details
            $booking_details = $bookings_obj->get_booking_details($booking_id);
            
            // Include booking details template
            include_once TURF_BOOKING_PLUGIN_DIR . 'public/partials/booking-details.php';
            return;
        }
    }
    
    if ($action === 'invoice' && isset($_GET['id'])) {
        $booking_id = absint($_GET['id']);
        $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
        
        if ($booking_user_id == $user_id || current_user_can('manage_options')) {
            // Show invoice
            $payments = new Turf_Booking_Payments();
            echo $payments->generate_invoice($booking_id);
            return;
        }
    }
}
?>

<div class="tb-dashboard-container">
    <div class="tb-dashboard-header">
        <h2><?php _e('My Account', 'turf-booking'); ?></h2>
        
        <?php if (isset($_GET['message']) && $_GET['message'] === 'profile_updated') : ?>
            <div class="tb-success-message">
                <p><?php _e('Your profile has been updated successfully.', 'turf-booking'); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['message']) && $_GET['message'] === 'booking_cancelled') : ?>
            <div class="tb-success-message">
                <p><?php _e('Your booking has been cancelled successfully.', 'turf-booking'); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])) : ?>
            <div class="tb-error-message">
                <?php
                $error = sanitize_text_field($_GET['error']);
                
                switch ($error) {
                    case 'email_exists':
                        _e('Email address is already registered.', 'turf-booking');
                        break;
                    case 'invalid_password':
                        _e('Current password is incorrect.', 'turf-booking');
                        break;
                    case 'password_mismatch':
                        _e('New passwords do not match.', 'turf-booking');
                        break;
                    case 'update_failed':
                        _e('Failed to update profile.', 'turf-booking');
                        break;
                    default:
                        _e('An error occurred. Please try again.', 'turf-booking');
                        break;
                }
                ?>
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
                <h3><?php printf(__('Welcome, %s!', 'turf-booking'), $current_user->display_name); ?></h3>
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
                        <?php 
                        $count = 0;
                        foreach ($upcoming_bookings as $booking) {
                            if ($count >= 3) break;
                            
                            $booking_details = $bookings_obj->get_booking_details($booking->ID);
                            if ($booking_details) {
                                include TURF_BOOKING_PLUGIN_DIR . 'public/partials/booking-card.php';
                                $count++;
                            }
                        }
                        ?>
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
                                <?php 
                                foreach ($upcoming_bookings as $booking) {
                                    $booking_details = $bookings_obj->get_booking_details($booking->ID);
                                    if ($booking_details) {
                                        include TURF_BOOKING_PLUGIN_DIR . 'public/partials/booking-card.php';
                                    }
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div id="past" class="tb-tab-pane">
                        <?php if (empty($past_bookings)) : ?>
                            <p class="tb-no-bookings"><?php _e('You have no past bookings.', 'turf-booking'); ?></p>
                        <?php else : ?>
                            <div class="tb-bookings-grid">
                                <?php 
                                foreach ($past_bookings as $booking) {
                                    $booking_details = $bookings_obj->get_booking_details($booking->ID);
                                    if ($booking_details) {
                                        include TURF_BOOKING_PLUGIN_DIR . 'public/partials/booking-card.php';
                                    }
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div id="cancelled" class="tb-tab-pane">
                        <?php if (empty($cancelled_bookings)) : ?>
                            <p class="tb-no-bookings"><?php _e('You have no cancelled bookings.', 'turf-booking'); ?></p>
                        <?php else : ?>
                            <div class="tb-bookings-grid">
                                <?php 
                                foreach ($cancelled_bookings as $booking) {
                                    $booking_details = $bookings_obj->get_booking_details($booking->ID);
                                    if ($booking_details) {
                                        include TURF_BOOKING_PLUGIN_DIR . 'public/partials/booking-card.php';
                                    }
                                }
                                ?>
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
                            <input type="text" name="first_name" id="first_name" class="tb-input" value="<?php echo esc_attr($current_user->first_name); ?>">
                        </div>
                        
                        <div class="tb-form-group">
                            <label for="last_name"><?php _e('Last Name', 'turf-booking'); ?></label>
                            <input type="text" name="last_name" id="last_name" class="tb-input" value="<?php echo esc_attr($current_user->last_name); ?>">
                        </div>
                    </div>
                    
                    <div class="tb-form-group">
                        <label for="display_name"><?php _e('Display Name', 'turf-booking'); ?></label>
                        <input type="text" name="display_name" id="display_name" class="tb-input" value="<?php echo esc_attr($current_user->display_name); ?>">
                    </div>
                    
                    <div class="tb-form-group">
                        <label for="email"><?php _e('Email Address', 'turf-booking'); ?></label>
                        <input type="email" name="email" id="email" class="tb-input" value="<?php echo esc_attr($current_user->user_email); ?>">
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
                            <input type="password" name="new_password" id="new_password" class="tb-input tb-password-field">
                            <div id="password-strength" class="tb-password-strength-meter" style="display: none;"></div>
                        </div>
                        
                        <div class="tb-form-group">
                            <label for="confirm_password"><?php _e('Confirm New Password', 'turf-booking'); ?></label>
                            <input type="password" name="confirm_password" id="confirm_password" class="tb-input tb-confirm-password-field">
                            <div id="password-match" class="tb-password-match" style="display: none;"></div>
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