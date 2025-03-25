<?php
/**
 * Template for the checkout page
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get booking data
$bookings = new Turf_Booking_Bookings();
$booking_details = $bookings->get_booking_details($booking_id);

if (!$booking_details) {
    echo '<p class="tb-error-message">' . __('Booking not found', 'turf-booking') . '</p>';
    return;
}

// Get court data
$court_id = $booking_details['court_id'];
$court = get_post($court_id);

if (!$court) {
    echo '<p class="tb-error-message">' . __('Court not found', 'turf-booking') . '</p>';
    return;
}

// Check booking status
if ($booking_details['status'] === 'cancelled') {
    echo '<p class="tb-error-message">' . __('This booking has been cancelled and cannot be processed for payment', 'turf-booking') . '</p>';
    return;
}

// Check payment status
if ($booking_details['payment_status'] === 'completed') {
    echo '<p class="tb-success-message">' . __('Payment has already been processed for this booking', 'turf-booking') . '</p>';
    
    // Add view booking button
    $page_settings = get_option('tb_page_settings');
    $my_account_url = get_permalink($page_settings['my-account']);
    
    echo '<p><a href="' . esc_url(add_query_arg(array('action' => 'view-booking', 'id' => $booking_id), $my_account_url)) . '" class="tb-button">' . __('View Booking', 'turf-booking') . '</a></p>';
    
    return;
}

// Get payment settings
$payment_settings = get_option('tb_payment_settings');
$razorpay_enabled = isset($payment_settings['razorpay_enabled']) ? $payment_settings['razorpay_enabled'] : 'yes';

// Get general settings
$general_settings = get_option('tb_general_settings');
$currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';

// Format date and time
$date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
$time_format = isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i';

$formatted_date = date($date_format, strtotime($booking_details['date']));
$formatted_time_from = date($time_format, strtotime($booking_details['time_from']));
$formatted_time_to = date($time_format, strtotime($booking_details['time_to']));
?>

<div class="tb-checkout-container">
    <h2><?php _e('Checkout', 'turf-booking'); ?></h2>
    
    <div class="tb-checkout-order-summary">
        <h3><?php _e('Order Summary', 'turf-booking'); ?></h3>
        
        <div class="tb-checkout-court-info">
            <div class="tb-checkout-court-image">
                <?php if (has_post_thumbnail($court_id)) : ?>
                    <?php echo get_the_post_thumbnail($court_id, 'thumbnail'); ?>
                <?php else : ?>
                    <div class="tb-no-image"><?php _e('No Image', 'turf-booking'); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="tb-checkout-court-details">
                <h4><?php echo esc_html($court->post_title); ?></h4>
                
                <div class="tb-checkout-booking-details">
                    <div class="tb-checkout-detail">
                        <span class="tb-checkout-label"><?php _e('Date:', 'turf-booking'); ?></span>
                        <span class="tb-checkout-value"><?php echo esc_html($formatted_date); ?></span>
                    </div>
                    
                    <div class="tb-checkout-detail">
                        <span class="tb-checkout-label"><?php _e('Time:', 'turf-booking'); ?></span>
                        <span class="tb-checkout-value"><?php echo esc_html($formatted_time_from . ' - ' . $formatted_time_to); ?></span>
                    </div>
                    
                    <div class="tb-checkout-detail">
                        <span class="tb-checkout-label"><?php _e('Booking ID:', 'turf-booking'); ?></span>
                        <span class="tb-checkout-value"><?php echo esc_html($booking_id); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="tb-checkout-price-summary">
            <div class="tb-checkout-price-row">
                <span class="tb-checkout-price-label"><?php _e('Court Rental', 'turf-booking'); ?></span>
                <span class="tb-checkout-price-value"><?php echo esc_html($currency_symbol . number_format($booking_details['payment_amount'], 2)); ?></span>
            </div>
            
            <div class="tb-checkout-price-row tb-checkout-total">
                <span class="tb-checkout-price-label"><?php _e('Total', 'turf-booking'); ?></span>
                <span class="tb-checkout-price-value"><?php echo esc_html($currency_symbol . number_format($booking_details['payment_amount'], 2)); ?></span>
            </div>
        </div>
    </div>
    
    <div class="tb-checkout-payment-methods">
        <h3><?php _e('Payment Method', 'turf-booking'); ?></h3>
        
        <?php if ($razorpay_enabled === 'yes') : ?>
            <?php
            // Initialize Razorpay checkout
            $payments = new Turf_Booking_Payments();
            echo $payments->get_razorpay_checkout_script($booking_id);
            ?>
        <?php else : ?>
            <p class="tb-error-message"><?php _e('No payment methods are currently available. Please contact site administrator.', 'turf-booking'); ?></p>
        <?php endif; ?>
    </div>
</div>

<style>
.tb-checkout-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.tb-checkout-container h2 {
    margin-top: 0;
    margin-bottom: 30px;
    text-align: center;
}

.tb-checkout-order-summary {
    background-color: #f9f9f9;
    border-radius: 5px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.tb-checkout-order-summary h3 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.tb-checkout-court-info {
    display: flex;
    margin-bottom: 20px;
    background-color: #fff;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.tb-checkout-court-image {
    flex: 0 0 100px;
    height: 100px;
    overflow: hidden;
}

.tb-checkout-court-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.tb-no-image {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    background-color: #f5f5f5;
    color: #999;
    font-style: italic;
    font-size: 12px;
    text-align: center;
}

.tb-checkout-court-details {
    flex: 1;
    padding: 15px;
}

.tb-checkout-court-details h4 {
    margin: 0 0 10px;
    font-size: 18px;
}

.tb-checkout-booking-details {
    font-size: 14px;
    color: #666;
}

.tb-checkout-detail {
    margin-bottom: 5px;
}

.tb-checkout-label {
    font-weight: bold;
    margin-right: 5px;
}

.tb-checkout-price-summary {
    background-color: #fff;
    border-radius: 5px;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.tb-checkout-price-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.tb-checkout-price-row:last-child {
    border-bottom: none;
}

.tb-checkout-total {
    font-weight: bold;
    font-size: 18px;
    color: #3399cc;
}

.tb-checkout-payment-methods {
    background-color: #f9f9f9;
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.tb-checkout-payment-methods h3 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.tb-button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #3399cc;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    text-decoration: none;
    font-weight: bold;
}

.tb-button:hover {
    background-color: #2980b9;
}

@media screen and (max-width: 576px) {
    .tb-checkout-court-info {
        flex-direction: column;
    }
    
    .tb-checkout-court-image {
        flex: none;
        height: 150px;
    }
}
</style>


