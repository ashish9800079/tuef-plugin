<?php
/**
 * Template for multi-step booking process
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get court ID from query parameter
$court_id = isset($_GET['court_id']) ? absint($_GET['court_id']) : 0;

// Check if court ID is valid
if (!$court_id) {
    echo '<div class="tb-error-message">';
    echo '<p>' . __('Invalid court ID. Please select a court first.', 'turf-booking') . '</p>';
    echo '<p><a href="' . esc_url(get_post_type_archive_link('tb_court')) . '" class="tb-button">' . __('View All Courts', 'turf-booking') . '</a></p>';
    echo '</div>';
    return;
}

// Get court data
$court = get_post($court_id);

if (!$court || $court->post_type !== 'tb_court') {
    echo '<div class="tb-error-message">';
    echo '<p>' . __('Court not found. Please select a valid court.', 'turf-booking') . '</p>';
    echo '<p><a href="' . esc_url(get_post_type_archive_link('tb_court')) . '" class="tb-button">' . __('View All Courts', 'turf-booking') . '</a></p>';
    echo '</div>';
    return;
}

// Get court meta
$court_name = $court->post_title;
$court_image = get_the_post_thumbnail_url($court_id, 'medium');
$base_price = get_post_meta($court_id, '_tb_court_base_price', true);
$opening_hours = get_post_meta($court_id, '_tb_court_opening_hours', true);
$time_slot = get_post_meta($court_id, '_tb_court_time_slot', true);

// Get available addons for this court
$post_types = new Turf_Booking_Post_Types(); // This should be passed as a parameter in a real implementation
$available_addons = $post_types->get_court_addons($court_id);

// Get currency symbol
$general_settings = get_option('tb_general_settings');
$currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';

// Get user data if logged in
$user_data = array(
    'name' => '',
    'email' => '',
    'phone' => ''
);

if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $user_data['name'] = $current_user->display_name;
    $user_data['email'] = $current_user->user_email;
    $user_data['phone'] = get_user_meta($current_user->ID, 'phone', true);
}
?>

<div class="tb-booking-wizard-container" id="tb-booking-wizard" data-court-id="<?php echo esc_attr($court_id); ?>">
    <!-- Booking wizard header -->
    <div class="tb-booking-wizard-header">
        <h2><?php printf(__('Book %s', 'turf-booking'), esc_html($court_name)); ?></h2>
        
        <!-- Progress bar -->
        <div class="tb-booking-progress">
            <div class="tb-progress-step active" data-step="1">
                <div class="tb-step-number">1</div>
                <div class="tb-step-label"><?php _e('Date & Time', 'turf-booking'); ?></div>
            </div>
            
            <?php if (!empty($available_addons)) : ?>
                <div class="tb-progress-step" data-step="2">
                    <div class="tb-step-number">2</div>
                    <div class="tb-step-label"><?php _e('Addons', 'turf-booking'); ?></div>
                </div>
                
                <div class="tb-progress-step" data-step="3">
                    <div class="tb-step-number">3</div>
                    <div class="tb-step-label"><?php _e('Details', 'turf-booking'); ?></div>
                </div>
                
                <div class="tb-progress-step" data-step="4">
                    <div class="tb-step-number">4</div>
                    <div class="tb-step-label"><?php _e('Payment', 'turf-booking'); ?></div>
                </div>
            <?php else : ?>
                <div class="tb-progress-step" data-step="2">
                    <div class="tb-step-number">2</div>
                    <div class="tb-step-label"><?php _e('Details', 'turf-booking'); ?></div>
                </div>
                
                <div class="tb-progress-step" data-step="3">
                    <div class="tb-step-number">3</div>
                    <div class="tb-step-label"><?php _e('Payment', 'turf-booking'); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Court info panel (visible on all steps) -->
    <div class="tb-booking-court-info">
        <div class="tb-booking-court-image">
            <?php if ($court_image) : ?>
                <img src="<?php echo esc_url($court_image); ?>" alt="<?php echo esc_attr($court_name); ?>">
            <?php else : ?>
                <div class="tb-no-image"><?php _e('No Image', 'turf-booking'); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="tb-booking-court-details">
            <h3><?php echo esc_html($court_name); ?></h3>
            <div class="tb-booking-court-price">
                <?php echo esc_html($currency_symbol . number_format($base_price, 2)); ?> <span class="tb-price-unit"><?php _e('/ hour', 'turf-booking'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Booking wizard content -->
    <div class="tb-booking-wizard-content">
        <!-- Step 1: Date & Time Selection -->
        <div class="tb-booking-step" id="tb-step-1">
            <h3><?php _e('Select Date & Time', 'turf-booking'); ?></h3>
            
            <div class="tb-date-time-container">
                <div class="tb-date-picker-container">
                    <div id="tb-date-picker"></div>
                </div>
                
                <div class="tb-time-slots-container">
                    <h4><?php _e('Available Time Slots', 'turf-booking'); ?></h4>
                    <div id="tb-time-slots" class="tb-time-slots">
                        <div class="tb-select-date-message">
                            <?php _e('Please select a date to view available time slots.', 'turf-booking'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tb-booking-wizard-actions">
                <button type="button" class="tb-button tb-next-step" id="tb-to-step-2" disabled><?php _e('Next Step', 'turf-booking'); ?></button>
            </div>
        </div>
        
        <?php if (!empty($available_addons)) : ?>
            <!-- Step 2: Addons Selection -->
            <div class="tb-booking-step" id="tb-step-2" style="display: none;">
                <h3><?php _e('Select Optional Addons', 'turf-booking'); ?></h3>
                
                <div class="tb-addons-container">
                    <?php foreach ($available_addons as $addon) : 
                        $addon_id = $addon->ID;
                        $addon_price = get_post_meta($addon_id, '_tb_addon_price', true);
                        $addon_type = get_post_meta($addon_id, '_tb_addon_type', true);
                        $price_label = ($addon_type === 'per_hour') ? __('per hour', 'turf-booking') : __('per booking', 'turf-booking');
                    ?>
                        <div class="tb-addon-item" data-addon-id="<?php echo esc_attr($addon_id); ?>" data-addon-price="<?php echo esc_attr($addon_price); ?>" data-addon-type="<?php echo esc_attr($addon_type); ?>">
                            <div class="tb-addon-checkbox">
                                <input type="checkbox" id="tb-addon-<?php echo esc_attr($addon_id); ?>" name="tb_addons[]" value="<?php echo esc_attr($addon_id); ?>">
                            </div>
                            
                            <div class="tb-addon-details">
                                <h4><?php echo esc_html($addon->post_title); ?></h4>
                                <div class="tb-addon-description"><?php echo wp_trim_words($addon->post_content, 20); ?></div>
                            </div>
                            
                            <div class="tb-addon-price">
                                <?php echo esc_html($currency_symbol . number_format($addon_price, 2)); ?>
                                <span class="tb-addon-price-type"><?php echo esc_html($price_label); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="tb-booking-wizard-actions">
                    <button type="button" class="tb-button tb-prev-step" id="tb-back-to-1"><?php _e('Previous Step', 'turf-booking'); ?></button>
                    <button type="button" class="tb-button tb-next-step" id="tb-to-step-3"><?php _e('Next Step', 'turf-booking'); ?></button>
                </div>
            </div>
            
            <!-- Step 3: Review Details -->
            <div class="tb-booking-step" id="tb-step-3" style="display: none;">
        <?php else : ?>
            <!-- Step 2: Review Details (if no addons) -->
            <div class="tb-booking-step" id="tb-step-2" style="display: none;">
        <?php endif; ?>
                <h3><?php _e('Review Details', 'turf-booking'); ?></h3>
                
                <div class="tb-booking-review">
                    <div class="tb-booking-summary">
                        <h4><?php _e('Booking Summary', 'turf-booking'); ?></h4>
                        
                        <div class="tb-summary-item">
                            <span class="tb-summary-label"><?php _e('Court:', 'turf-booking'); ?></span>
                            <span class="tb-summary-value" id="tb-summary-court"><?php echo esc_html($court_name); ?></span>
                        </div>
                        
                        <div class="tb-summary-item">
                            <span class="tb-summary-label"><?php _e('Date:', 'turf-booking'); ?></span>
                            <span class="tb-summary-value" id="tb-summary-date"></span>
                        </div>
                        
                        <div class="tb-summary-item">
                            <span class="tb-summary-label"><?php _e('Time:', 'turf-booking'); ?></span>
                            <span class="tb-summary-value" id="tb-summary-time"></span>
                        </div>
                        
                        <div class="tb-summary-item">
                            <span class="tb-summary-label"><?php _e('Duration:', 'turf-booking'); ?></span>
                            <span class="tb-summary-value" id="tb-summary-duration"></span>
                        </div>
                    </div>
                    
                    <div class="tb-price-breakdown">
                        <h4><?php _e('Price Breakdown', 'turf-booking'); ?></h4>
                        
                        <div class="tb-price-item">
                            <span class="tb-price-label"><?php _e('Court Rental:', 'turf-booking'); ?></span>
                            <span class="tb-price-value" id="tb-price-court"></span>
                        </div>
                        
                        <div id="tb-addons-breakdown"></div>
                        
                        <div class="tb-price-item tb-price-total">
                            <span class="tb-price-label"><?php _e('Total:', 'turf-booking'); ?></span>
                            <span class="tb-price-value" id="tb-price-total"></span>
                        </div>
                    </div>
                    
                    <div class="tb-user-details">
                        <h4><?php _e('Contact Information', 'turf-booking'); ?></h4>
                        
                        <?php if (is_user_logged_in()) : ?>
                            <div class="tb-form-row">
                                <div class="tb-form-group">
                                    <label for="tb-booking-name"><?php _e('Name', 'turf-booking'); ?></label>
                                    <input type="text" id="tb-booking-name" name="booking_name" value="<?php echo esc_attr($user_data['name']); ?>" required>
                                </div>
                                
                                <div class="tb-form-group">
                                    <label for="tb-booking-email"><?php _e('Email', 'turf-booking'); ?></label>
                                    <input type="email" id="tb-booking-email" name="booking_email" value="<?php echo esc_attr($user_data['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="tb-form-group">
                                <label for="tb-booking-phone"><?php _e('Phone', 'turf-booking'); ?></label>
                                <input type="tel" id="tb-booking-phone" name="booking_phone" value="<?php echo esc_attr($user_data['phone']); ?>" required>
                            </div>
                        <?php else : ?>
                            <div class="tb-login-required">
                                <p><?php _e('Please log in to complete your booking.', 'turf-booking'); ?></p>
                                <a href="<?php echo esc_url(wp_login_url(add_query_arg(array('court_id' => $court_id), get_permalink()))); ?>" class="tb-button"><?php _e('Login', 'turf-booking'); ?></a>
                                
                                <?php if (get_option('users_can_register')) : ?>
                                    <p><?php _e("Don't have an account?", 'turf-booking'); ?> <a href="<?php echo esc_url(wp_registration_url()); ?>"><?php _e('Register', 'turf-booking'); ?></a></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="tb-booking-wizard-actions">
                    <?php if (!empty($available_addons)) : ?>
                        <button type="button" class="tb-button tb-prev-step" id="tb-back-to-2"><?php _e('Previous Step', 'turf-booking'); ?></button>
                    <?php else : ?>
                        <button type="button" class="tb-button tb-prev-step" id="tb-back-to-1"><?php _e('Previous Step', 'turf-booking'); ?></button>
                    <?php endif; ?>
                    
                    <?php if (is_user_logged_in()) : ?>
                        <button type="button" class="tb-button tb-next-step" id="tb-to-step-4"><?php _e('Proceed to Payment', 'turf-booking'); ?></button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Step 4: Payment -->
            <div class="tb-booking-step" id="tb-step-<?php echo (!empty($available_addons)) ? '4' : '3'; ?>" style="display: none;">
                <h3><?php _e('Payment', 'turf-booking'); ?></h3>
                
                <div id="tb-payment-container">
                    <div class="tb-payment-loading">
                        <div class="tb-spinner"></div>
                        <p><?php _e('Processing your booking...', 'turf-booking'); ?></p>
                    </div>
                </div>
<div class="tb-booking-wizard-actions">
                    <button type="button" class="tb-button tb-prev-step" id="tb-back-to-<?php echo (!empty($available_addons)) ? '3' : '2'; ?>"><?php _e('Previous Step', 'turf-booking'); ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Error message container -->
    <div id="tb-booking-error" class="tb-booking-error" style="display: none;"></div>
</div>

<!-- Hidden fields for form submission -->
<form id="tb-booking-form" style="display: none;">
    <input type="hidden" name="court_id" value="<?php echo esc_attr($court_id); ?>">
    <input type="hidden" name="booking_date" id="hidden-booking-date">
    <input type="hidden" name="booking_time_from" id="hidden-booking-time-from">
    <input type="hidden" name="booking_time_to" id="hidden-booking-time-to">
    <input type="hidden" name="booking_addons" id="hidden-booking-addons">
    <?php wp_nonce_field('tb_booking_nonce', 'booking_nonce'); ?>
</form>
