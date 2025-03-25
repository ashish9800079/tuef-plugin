<?php
/**
 * Template for displaying single court
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Get court data
global $post;
$court_id = $post->ID;

// Get court meta
$court_size = get_post_meta($court_id, '_tb_court_size', true);
$court_capacity = get_post_meta($court_id, '_tb_court_capacity', true);
$court_rating = get_post_meta($court_id, '_tb_court_rating', true);
$base_price = get_post_meta($court_id, '_tb_court_base_price', true);
$opening_hours = get_post_meta($court_id, '_tb_court_opening_hours', true);
$gallery_images = get_post_meta($court_id, '_tb_court_gallery', true);
$address = get_post_meta($court_id, '_tb_court_address', true);
$latitude = get_post_meta($court_id, '_tb_court_latitude', true);
$longitude = get_post_meta($court_id, '_tb_court_longitude', true);

// Get court taxonomies
$sport_types = get_the_terms($court_id, 'sport_type');
$facilities = get_the_terms($court_id, 'facility');
$locations = get_the_terms($court_id, 'location');

// Format price with currency symbol
$general_settings = get_option('tb_general_settings');
$currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';

// Get current day
$current_day = strtolower(date('l'));

// Create stars for rating
$stars_html = '';
if ($court_rating) {
    $full_stars = floor($court_rating);
    $half_star = ($court_rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
    
    for ($i = 0; $i < $full_stars; $i++) {
        $stars_html .= '<i class="fas fa-star"></i>';
    }
    
    if ($half_star) {
        $stars_html .= '<i class="fas fa-star-half-alt"></i>';
    }
    
    for ($i = 0; $i < $empty_stars; $i++) {
        $stars_html .= '<i class="far fa-star"></i>';
    }
}

// Gallery images array
$gallery_ids = [];
if ($gallery_images) {
    $gallery_ids = explode(',', $gallery_images);
}
?>

<div class="tb-court-single-container">
    <!-- Court header section -->
    <div class="tb-court-header">
        <?php if (has_post_thumbnail()) : ?>
            <div class="tb-court-featured-image">
                <?php echo get_the_post_thumbnail($court_id, 'full'); ?>
            </div>
        <?php endif; ?>
        
        <div class="tb-court-header-content">
            <div class="tb-court-header-info">
                <?php if ($sport_types) : ?>
                    <div class="tb-court-categories">
                        <?php foreach ($sport_types as $sport_type) : ?>
                            <span class="tb-court-category"><?php echo esc_html($sport_type->name); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <h1 class="tb-court-title"><?php the_title(); ?></h1>
                
                <div class="tb-court-meta">
                    <?php if ($locations) : ?>
                        <div class="tb-court-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo esc_html($locations[0]->name); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($court_rating) : ?>
                        <div class="tb-court-rating">
                            <span class="tb-rating-stars"><?php echo $stars_html; ?></span>
                            <span class="tb-rating-value"><?php echo esc_html(number_format($court_rating, 1)); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="tb-court-price-box">
                <div class="tb-court-price">
                    <span class="tb-price-currency"><?php echo esc_html($currency_symbol); ?></span>
                    <span class="tb-price-value"><?php echo esc_html(number_format($base_price, 2)); ?></span>
                    <span class="tb-price-unit"><?php _e('/ hour', 'turf-booking'); ?></span>
                </div>
                
                <?php
                // Get the booking page URL
                $booking_page_id = isset(get_option('tb_page_settings')['booking']) ? get_option('tb_page_settings')['booking'] : 0;
                $booking_page_url = $booking_page_id ? add_query_arg('court_id', $court_id, get_permalink($booking_page_id)) : '#';
                ?>
                
                <a href="<?php echo esc_url($booking_page_url); ?>" class="tb-book-now-btn"><?php _e('Book Now', 'turf-booking'); ?></a>
            </div>

        </div>
    </div>
    
    <!-- Quick info box -->
    <div class="tb-court-quick-info">
        <div class="tb-quick-info-item">
            <i class="fas fa-ruler"></i>
            <span><?php _e('Size:', 'turf-booking'); ?></span>
            <strong><?php echo esc_html($court_size); ?></strong>
        </div>
        
        <div class="tb-quick-info-item">
            <i class="fas fa-users"></i>
            <span><?php _e('Capacity:', 'turf-booking'); ?></span>
            <strong><?php echo esc_html($court_capacity); ?> <?php _e('players', 'turf-booking'); ?></strong>
        </div>
        
        <?php if ($facilities) : ?>
            <div class="tb-quick-info-item">
                <i class="fas fa-cubes"></i>
                <span><?php _e('Facilities:', 'turf-booking'); ?></span>
                <strong><?php echo count($facilities); ?> <?php _e('available', 'turf-booking'); ?></strong>
            </div>
        <?php endif; ?>
        
        <?php if (isset($opening_hours[$current_day]) && !$opening_hours[$current_day]['closed']) : ?>
            <div class="tb-quick-info-item">
                <i class="fas fa-clock"></i>
                <span><?php _e('Today:', 'turf-booking'); ?></span>
                <strong><?php echo esc_html($opening_hours[$current_day]['from'] . ' - ' . $opening_hours[$current_day]['to']); ?></strong>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Main content section with tabs -->
    <div class="tb-court-content">
        <div class="tb-court-tabs">
            <ul class="tb-tabs-nav">
                <li class="active"><a href="#about" data-tab="about"><?php _e('About', 'turf-booking'); ?></a></li>
                <?php if ($facilities) : ?>
                    <li><a href="#facilities" data-tab="facilities"><?php _e('Facilities', 'turf-booking'); ?></a></li>
                <?php endif; ?>
                <li><a href="#timing" data-tab="timing"><?php _e('Timing', 'turf-booking'); ?></a></li>
                <?php if ($address && ($latitude || $longitude)) : ?>
                    <li><a href="#location" data-tab="location"><?php _e('Location', 'turf-booking'); ?></a></li>
                <?php endif; ?>
                <?php if (!empty($gallery_ids)) : ?>
                    <li><a href="#gallery" data-tab="gallery"><?php _e('Gallery', 'turf-booking'); ?></a></li>
                <?php endif; ?>
                <li><a href="#reviews" data-tab="reviews"><?php _e('Reviews', 'turf-booking'); ?></a></li>
            </ul>
            
            <div class="tb-tabs-content">
                <!-- About Tab -->
                <div id="about" class="tb-tab-pane active">
                    <h3><?php _e('About This Court', 'turf-booking'); ?></h3>
                    <div class="tb-court-description">
                        <?php the_content(); ?>
                    </div>
                </div>
                
                <!-- Facilities Tab -->
                <?php if ($facilities) : ?>
                    <div id="facilities" class="tb-tab-pane">
                        <h3><?php _e('Available Facilities', 'turf-booking'); ?></h3>
                        <div class="tb-facilities-list">
                            <?php foreach ($facilities as $facility) : ?>
                                <div class="tb-facility-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span><?php echo esc_html($facility->name); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Timing Tab -->
                <div id="timing" class="tb-tab-pane">
                    <h3><?php _e('Operating Hours', 'turf-booking'); ?></h3>
                    <div class="tb-timing-list">
                        <?php
                        $days = array(
                            'monday'    => __('Monday', 'turf-booking'),
                            'tuesday'   => __('Tuesday', 'turf-booking'),
                            'wednesday' => __('Wednesday', 'turf-booking'),
                            'thursday'  => __('Thursday', 'turf-booking'),
                            'friday'    => __('Friday', 'turf-booking'),
                            'saturday'  => __('Saturday', 'turf-booking'),
                            'sunday'    => __('Sunday', 'turf-booking'),
                        );
                        
                        foreach ($days as $day_key => $day_name) :
                            $is_today = ($day_key === $current_day);
                            $is_closed = isset($opening_hours[$day_key]['closed']) && $opening_hours[$day_key]['closed'];
                            $time_from = isset($opening_hours[$day_key]['from']) ? $opening_hours[$day_key]['from'] : '09:00';
                            $time_to = isset($opening_hours[$day_key]['to']) ? $opening_hours[$day_key]['to'] : '18:00';
                        ?>
                            <div class="tb-timing-item <?php echo $is_today ? 'tb-today' : ''; ?>">
                                <div class="tb-day-name">
                                    <?php echo esc_html($day_name); ?>
                                    <?php if ($is_today) : ?>
                                        <span class="tb-today-label"><?php _e('Today', 'turf-booking'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="tb-day-hours">
                                    <?php if ($is_closed) : ?>
                                        <span class="tb-closed"><?php _e('Closed', 'turf-booking'); ?></span>
                                    <?php else : ?>
                                        <?php echo esc_html($time_from . ' - ' . $time_to); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Location Tab -->
                <?php if ($address && ($latitude || $longitude)) : ?>
                    <div id="location" class="tb-tab-pane">
                        <h3><?php _e('Court Location', 'turf-booking'); ?></h3>
                        <div class="tb-location-container">
                            <div class="tb-court-address">
                                <i class="fas fa-map-marker-alt"></i>
                                <p><?php echo nl2br(esc_html($address)); ?></p>
                            </div>
                            
                            <?php if ($latitude && $longitude) : ?>
                                <div class="tb-court-map" id="court-map" data-lat="<?php echo esc_attr($latitude); ?>" data-lng="<?php echo esc_attr($longitude); ?>"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Gallery Tab -->
                <?php if (!empty($gallery_ids)) : ?>
                    <div id="gallery" class="tb-tab-pane">
                        <h3><?php _e('Court Gallery', 'turf-booking'); ?></h3>
                        <div class="tb-gallery-container">
                            <div class="tb-gallery-grid">
                                <?php foreach ($gallery_ids as $image_id) : ?>
                                    <?php $image = wp_get_attachment_image_src($image_id, 'large'); ?>
                                    <?php if ($image) : ?>
                                        <div class="tb-gallery-item">
                                            <a href="<?php echo esc_url($image[0]); ?>" class="tb-gallery-link">
                                                <?php echo wp_get_attachment_image($image_id, 'medium'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Reviews Tab -->
                <div id="reviews" class="tb-tab-pane">
                    <h3><?php _e('Court Reviews', 'turf-booking'); ?></h3>
                    <div class="tb-reviews-container">
                        <?php
                        // Get existing reviews using WordPress comments
                        $args = array(
                            'post_id' => $court_id,
                            'status' => 'approve',
                        );
                        $comments = get_comments($args);
                        
                        if ($comments) :
                        ?>
                            <div class="tb-reviews-list">
                                <?php foreach ($comments as $comment) : ?>
                                    <div class="tb-review-item">
                                        <div class="tb-review-header">
                                            <div class="tb-reviewer-avatar">
                                                <?php echo get_avatar($comment, 50); ?>
                                            </div>
                                            <div class="tb-reviewer-info">
                                                <h4><?php echo esc_html($comment->comment_author); ?></h4>
                                                <div class="tb-review-meta">
                                                    <span class="tb-review-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($comment->comment_date))); ?></span>
                                                    <?php
                                                    $rating = get_comment_meta($comment->comment_ID, 'rating', true);
                                                    if ($rating) :
                                                        $stars_html = '';
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            if ($i <= $rating) {
                                                                $stars_html .= '<i class="fas fa-star"></i>';
                                                            } else {
                                                                $stars_html .= '<i class="far fa-star"></i>';
                                                            }
                                                        }
                                                    ?>
                                                        <div class="tb-review-rating">
                                                            <?php echo $stars_html; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tb-review-content">
                                            <?php echo wpautop($comment->comment_content); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p class="tb-no-reviews"><?php _e('There are no reviews yet.', 'turf-booking'); ?></p>
                        <?php endif; ?>
                        
                        <?php if (comments_open()) : ?>
                            <div class="tb-review-form">
                                <h4><?php _e('Write a Review', 'turf-booking'); ?></h4>
                                
                                <?php
                                comment_form(array(
                                    'title_reply' => '',
                                    'comment_notes_before' => '',
                                    'comment_field' => '<p class="comment-form-comment"><label for="comment">' . __('Your Review', 'turf-booking') . '</label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>',
                                    'fields' => array(
                                        'author' => '<p class="comment-form-author"><label for="author">' . __('Name', 'turf-booking') . '</label><input id="author" name="author" type="text" value="" size="30" required /></p>',
                                        'email' => '<p class="comment-form-email"><label for="email">' . __('Email', 'turf-booking') . '</label><input id="email" name="email" type="email" value="" size="30" required /></p>',
                                        'rating' => '<p class="comment-form-rating"><label for="rating">' . __('Rating', 'turf-booking') . '</label>
                                            <select name="rating" id="rating" required>
                                                <option value="">' . __('Rate&hellip;', 'turf-booking') . '</option>
                                                <option value="5">' . __('Perfect', 'turf-booking') . '</option>
                                                <option value="4">' . __('Good', 'turf-booking') . '</option>
                                                <option value="3">' . __('Average', 'turf-booking') . '</option>
                                                <option value="2">' . __('Not that bad', 'turf-booking') . '</option>
                                                <option value="1">' . __('Very poor', 'turf-booking') . '</option>
                                            </select></p>',
                                    ),
                                    'submit_button' => '<input name="%1$s" type="submit" id="%2$s" class="%3$s tb-button" value="%4$s" />',
                                ));
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    



    </div>
    
    <!-- Similar courts section -->
    <?php
    // Get similar courts based on sport type
    $similar_courts_args = array(
        'post_type' => 'tb_court',
        'posts_per_page' => 3,
        'post__not_in' => array($court_id),
        'orderby' => 'rand',
    );
    
    if ($sport_types) {
        $similar_courts_args['tax_query'] = array(
            array(
                'taxonomy' => 'sport_type',
                'field' => 'term_id',
                'terms' => wp_list_pluck($sport_types, 'term_id'),
            ),
        );
    }
    
    $similar_courts = new WP_Query($similar_courts_args);
    
    if ($similar_courts->have_posts()) :
    ?>
        <div class="tb-similar-courts">
            <h3><?php _e('Similar Courts', 'turf-booking'); ?></h3>
            
            <div class="tb-courts-grid">
                <?php while ($similar_courts->have_posts()) : $similar_courts->the_post(); ?>
                    <div class="tb-court-card">
                        <div class="tb-court-card-image">
                            <?php if (has_post_thumbnail()) : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                            <?php else : ?>
                                <a href="<?php the_permalink(); ?>" class="tb-no-image">
                                    <span><?php _e('No Image', 'turf-booking'); ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tb-court-card-content">
                            <h4 class="tb-court-card-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            
                            <?php
                            $similar_court_rating = get_post_meta(get_the_ID(), '_tb_court_rating', true);
                            $similar_court_base_price = get_post_meta(get_the_ID(), '_tb_court_base_price', true);
                            $similar_court_locations = get_the_terms(get_the_ID(), 'location');
                            
                            // Create stars for rating
                            $similar_stars_html = '';
                            if ($similar_court_rating) {
                                $full_stars = floor($similar_court_rating);
                                $half_star = ($similar_court_rating - $full_stars) >= 0.5;
                                $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                                
                                for ($i = 0; $i < $full_stars; $i++) {
                                    $similar_stars_html .= '<i class="fas fa-star"></i>';
                                }
                                
                                if ($half_star) {
                                    $similar_stars_html .= '<i class="fas fa-star-half-alt"></i>';
                                }
                                
                                for ($i = 0; $i < $empty_stars; $i++) {
                                    $similar_stars_html .= '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                            
                            <div class="tb-court-card-meta">
                                <?php if ($similar_court_locations) : ?>
                                    <div class="tb-court-card-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo esc_html($similar_court_locations[0]->name); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($similar_court_rating) : ?>
                                    <div class="tb-court-card-rating">
                                        <span class="tb-rating-stars"><?php echo $similar_stars_html; ?></span>
                                        <span class="tb-rating-value"><?php echo esc_html(number_format($similar_court_rating, 1)); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="tb-court-card-price">
                                <?php if ($similar_court_base_price) : ?>
                                    <span class="tb-price-value"><?php echo esc_html($currency_symbol . number_format($similar_court_base_price, 2)); ?></span>
                                    <span class="tb-price-unit"><?php _e('/ hour', 'turf-booking'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="tb-court-card-action">
                                <a href="<?php the_permalink(); ?>" class="tb-button-link"><?php _e('View Details', 'turf-booking'); ?></a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tabs functionality
    $('.tb-tabs-nav li a').on('click', function(e) {
        e.preventDefault();
        
        var tabId = $(this).data('tab');
        
        // Update active tab
        $('.tb-tabs-nav li').removeClass('active');
        $(this).parent().addClass('active');
        
        // Show selected tab content
        $('.tb-tab-pane').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Initialize map if available
    if ($('#court-map').length > 0) {
        var mapElement = document.getElementById('court-map');
        var lat = parseFloat(mapElement.getAttribute('data-lat'));
        var lng = parseFloat(mapElement.getAttribute('data-lng'));
        
        if (lat && lng) {
            var mapOptions = {
                center: { lat: lat, lng: lng },
                zoom: 15
            };
            
            var map = new google.maps.Map(mapElement, mapOptions);
            
            var marker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                title: '<?php echo esc_js(get_the_title()); ?>'
            });
        }
    }
    
    // Initialize booking functionality
    var courtId = <?php echo $court_id; ?>;
    var selectedDate = '';
    var selectedTimeFrom = '';
    var selectedTimeTo = '';
    var selectedPrice = 0;
    
    // Date selection
    $('#tb-booking-date').on('change', function() {
        selectedDate = $(this).val();
        loadTimeSlots();
    });
    
    // Load time slots for selected date
    function loadTimeSlots() {
        if (!selectedDate) {
            return;
        }
        
        $('#tb-time-slots').html('<div class="tb-loading"><div class="tb-spinner"></div><p><?php _e('Loading available time slots...', 'turf-booking'); ?></p></div>');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'get_court_availability',
                court_id: courtId,
                date: selectedDate,
                nonce: '<?php echo wp_create_nonce('tb_availability_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var slots = response.data.slots;
                    var courtData = response.data.court_data;
                    
                    if (slots.length === 0) {
                        $('#tb-time-slots').html('<p class="tb-no-slots"><?php _e('No time slots available for this date.', 'turf-booking'); ?></p>');
                        return;
                    }
                    
                    var slotsHtml = '<div class="tb-slots-grid">';
                    
                    for (var i = 0; i < slots.length; i++) {
                        var slot = slots[i];
                        var slotClass = slot.available ? 'tb-slot-available' : 'tb-slot-booked';
                        
                        slotsHtml += '<div class="tb-time-slot ' + slotClass + '" ' + 
                            (slot.available ? 'data-from="' + slot.from + '" data-to="' + slot.to + '" data-price="' + slot.price + '"' : '') + '>' +
                            '<span class="tb-slot-time">' + slot.from + ' - ' + slot.to + '</span>' +
                            (slot.available ? '<span class="tb-slot-price"><?php echo esc_html($currency_symbol); ?>' + parseFloat(slot.price).toFixed(2) + '</span>' : '<span class="tb-slot-status"><?php _e('Booked', 'turf-booking'); ?></span>') +
                            '</div>';
                    }
                    
                    slotsHtml += '</div>';
                    
                    $('#tb-time-slots').html(slotsHtml);
                    
                    // Time slot selection
                    $('.tb-time-slot.tb-slot-available').on('click', function() {
                        $('.tb-time-slot').removeClass('selected');
                        $(this).addClass('selected');
                        
                        selectedTimeFrom = $(this).data('from');
                        selectedTimeTo = $(this).data('to');
                        selectedPrice = $(this).data('price');
                        
                        // Update booking details
                        $('#tb-summary-date strong').text(formatDate(selectedDate));
                        $('#tb-summary-time strong').text(selectedTimeFrom + ' - ' + selectedTimeTo);
                        $('#tb-summary-price strong').text('<?php echo esc_html($currency_symbol); ?>' + parseFloat(selectedPrice).toFixed(2));
                        
                        $('#tb-booking-details').show();
                    });
                } else {
                    $('#tb-time-slots').html('<p class="tb-error-message">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#tb-time-slots').html('<p class="tb-error-message"><?php _e('Error loading time slots. Please try again.', 'turf-booking'); ?></p>');
            }
        });
    }
    
    // Format date for display
    function formatDate(dateString) {
        var date = new Date(dateString);
        var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('<?php echo get_locale(); ?>', options);
    }
    
    // Book now button click
    $('#tb-book-now').on('click', function() {
        if (!selectedDate || !selectedTimeFrom || !selectedTimeTo) {
            $('#tb-booking-error').html('<p><?php _e('Please select a date and time slot.', 'turf-booking'); ?></p>').show();
            return;
        }
        
        var name = $('#tb-booking-name').val();
        var email = $('#tb-booking-email').val();
        var phone = $('#tb-booking-phone').val();
        
        if (!name || !email || !phone) {
            $('#tb-booking-error').html('<p><?php _e('Please fill in all contact information.', 'turf-booking'); ?></p>').show();
            return;
        }
        
        // Hide error and show loading
        $('#tb-booking-error').hide();
        $('#tb-booking-response').html('<div class="tb-loading"><div class="tb-spinner"></div><p><?php _e('Processing your booking...', 'turf-booking'); ?></p></div>').show();
        
        // Submit booking
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'create_booking',
                court_id: courtId,
                date: selectedDate,
                time_from: selectedTimeFrom,
                time_to: selectedTimeTo,
                name: name,
                email: email,
                phone: phone,
                nonce: '<?php echo wp_create_nonce('tb_booking_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to appropriate page
                    window.location.href = response.data.redirect_url;
                } else {
                    $('#tb-booking-response').hide();
                    $('#tb-booking-error').html('<p>' + response.data.message + '</p>').show();
                }
            },
            error: function() {
                $('#tb-booking-response').hide();
                $('#tb-booking-error').html('<p><?php _e('Error processing booking. Please try again.', 'turf-booking'); ?></p>').show();
            }
        });
    });
    
    // Initialize lightbox for gallery
    if ($('.tb-gallery-link').length > 0) {
        $('.tb-gallery-link').on('click', function(e) {
            e.preventDefault();
            
            var imageUrl = $(this).attr('href');
            var overlay = $('<div class="tb-lightbox-overlay"></div>');
            var content = $('<div class="tb-lightbox-content"><img src="' + imageUrl + '"><span class="tb-lightbox-close">&times;</span></div>');
            
            $('body').append(overlay).append(content);
            
            $('.tb-lightbox-close, .tb-lightbox-overlay').on('click', function() {
                $('.tb-lightbox-overlay, .tb-lightbox-content').remove();
            });
            
            $(document).keyup(function(e) {
                if (e.key === "Escape") {
                    $('.tb-lightbox-overlay, .tb-lightbox-content').remove();
                }
            });
        });
    }
});
</script>

<?php if ($latitude && $longitude) : ?>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY"></script>
<?php endif; ?>

<style>
/* Court Single Page Styles */
.tb-court-single-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: Arial, sans-serif;
}

/* Court Header */
.tb-court-header {
    position: relative;
    margin-bottom: 30px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.tb-court-featured-image img {
    width: 100%;
    height: auto;
    max-height: 400px;
    object-fit: cover;
}

.tb-court-header-content {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0));
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}

.tb-court-header-info {
    flex: 1;
}

.tb-court-categories {
    margin-bottom: 10px;
}

.tb-court-category {
    display: inline-block;
    padding: 5px 10px;
    background-color: #3399cc;
    color: #fff;
    border-radius: 3px;
    font-size: 12px;
    margin-right: 5px;
    margin-bottom: 5px;
}

.tb-court-title {
    margin: 0 0 10px;
    font-size: 28px;
    font-weight: bold;
}

.tb-court-meta {
    display: flex;
    align-items: center;
}

.tb-court-location {
    display: flex;
    align-items: center;
    margin-right: 20px;
}

.tb-court-location i {
    margin-right: 5px;
    color: #3399cc;
}

.tb-court-rating {
    display: flex;
    align-items: center;
}

.tb-rating-stars {
    color: #FFD700;
    margin-right: 5px;
}

.tb-court-price-box {
    background-color: #3399cc;
    padding: 15px;
    border-radius: 5px;
    text-align: center;
    min-width: 200px;
}

.tb-court-price {
    margin-bottom: 10px;
    font-size: 24px;
}

.tb-price-currency {
    font-size: 0.8em;
    vertical-align: top;
}

.tb-price-unit {
    font-size: 0.6em;
    color: rgba(255, 255, 255, 0.8);
}

.tb-book-now-btn {
    display: inline-block;
    padding: 10px 20px;
    background-color: #fff;
    color: #3399cc;
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
}

.tb-book-now-btn:hover {
    background-color: #f0f0f0;
    color: #2980b9;
}

/* Quick Info */
.tb-court-quick-info {
    display: flex;
    flex-wrap: wrap;
    margin: -10px;
    margin-bottom: 30px;
}

.tb-quick-info-item {
    flex: 1;
    min-width: 150px;
    margin: 10px;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.tb-quick-info-item i {
    font-size: 24px;
    color: #3399cc;
    margin-bottom: 10px;
}

.tb-quick-info-item span {
    color: #666;
    margin-bottom: 5px;
}

.tb-quick-info-item strong {
    font-size: 16px;
    color: #333;
}

/* Content Section */
.tb-court-content {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.tb-court-tabs {
    flex: 7;
    margin-right: 30px;
}

.tb-tabs-nav {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
    overflow-x: auto;
    white-space: nowrap;
}

.tb-tabs-nav li {
    margin-right: 5px;
    margin-bottom: -1px;
}

.tb-tabs-nav li a {
    display: block;
    padding: 10px 15px;
    background-color: #f5f5f5;
    border: 1px solid #ddd;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    text-decoration: none;
    color: #333;
    font-weight: bold;
}

.tb-tabs-nav li.active a {
    background-color: #fff;
    border-bottom: 1px solid #fff;
    color: #3399cc;
}

.tb-tab-pane {
    display: none;
    padding: 20px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
}

.tb-tab-pane.active {
    display: block;
}

.tb-tab-pane h3 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
    font-size: 20px;
}

/* Facilities Tab */
.tb-facilities-list {
    display: flex;
    flex-wrap: wrap;
    margin: -5px;
}

.tb-facility-item {
    flex: 0 0 33.333%;
    padding: 5px;
    display: flex;
    align-items: center;
}

.tb-facility-item i {
    color: #3399cc;
    margin-right: 5px;
}

/* Timing Tab */
.tb-timing-list {
    max-width: 600px;
}

.tb-timing-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.tb-timing-item:last-child {
    border-bottom: none;
}

.tb-today {
    background-color: #f0f7ff;
    border-radius: 4px;
    padding: 10px;
}

.tb-today-label {
    display: inline-block;
    padding: 2px 5px;
    background-color: #3399cc;
    color: #fff;
    border-radius: 3px;
    font-size: 10px;
    margin-left: 5px;
    vertical-align: middle;
}

.tb-closed {
    color: #e74c3c;
    font-weight: bold;
}

/* Location Tab */
.tb-location-container {
    margin-bottom: 20px;
}

.tb-court-address {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
}

.tb-court-address i {
    margin-right: 10px;
    color: #3399cc;
    margin-top: 3px;
}

.tb-court-map {
    height: 400px;
    border-radius: 4px;
    overflow: hidden;
}

/* Gallery Tab */
.tb-gallery-grid {
    display: flex;
    flex-wrap: wrap;
    margin: -5px;
}

.tb-gallery-item {
    flex: 0 0 calc(25% - 10px);
    margin: 5px;
    border-radius: 4px;
    overflow: hidden;
}

.tb-gallery-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.tb-gallery-item:hover img {
    transform: scale(1.05);
}

/* Lightbox */
.tb-lightbox-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 9998;
}

.tb-lightbox-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    max-width: 90%;
    max-height: 90%;
    z-index: 9999;
}

.tb-lightbox-content img {
    max-width: 100%;
    max-height: 90vh;
    display: block;
    margin: 0 auto;
    border: 5px solid #fff;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
}

.tb-lightbox-close {
    position: absolute;
    top: -20px;
    right: -20px;
    color: #fff;
    background-color: #000;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    text-align: center;
    line-height: 30px;
    font-size: 20px;
    cursor: pointer;
}

/* Reviews Tab */
.tb-reviews-list {
    margin-bottom: 30px;
}

.tb-review-item {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.tb-review-item:last-child {
    border-bottom: none;
}

.tb-review-header {
    display: flex;
    margin-bottom: 10px;
}

.tb-reviewer-avatar {
    margin-right: 15px;
}

.tb-reviewer-avatar img {
    border-radius: 50%;
}

.tb-reviewer-info h4 {
    margin: 0 0 5px;
}

.tb-review-meta {
    display: flex;
    align-items: center;
    color: #666;
}

.tb-review-date {
    margin-right: 10px;
}

.tb-review-rating {
    color: #FFD700;
}

.tb-no-reviews {
    text-align: center;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 4px;
    font-style: italic;
    color: #666;
}

/* Booking Widget */
.tb-booking-widget {
    flex: 3;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    align-self: flex-start;
    position: sticky;
    top: 20px;
}

.tb-booking-widget h3 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
    font-size: 20px;
    text-align: center;
}

.tb-booking-form-row {
    display: flex;
    flex-wrap: wrap;
    margin: -5px;
    margin-bottom: 15px;
}

.tb-form-group {
    flex: 1;
    min-width: 200px;
    margin: 5px;
}

.tb-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.tb-form-group input,
.tb-form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.tb-loading {
    text-align: center;
    padding: 20px;
}

.tb-spinner {
    display: inline-block;
    width: 30px;
    height: 30px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #3399cc;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.tb-slots-grid {
    display: flex;
    flex-wrap: wrap;
    margin: -5px;
}

.tb-time-slot {
    flex: 0 0 calc(50% - 10px);
    margin: 5px;
    padding: 10px;
    border-radius: 4px;
    display: flex;
    flex-direction: column;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tb-slot-available {
    background-color: #f0f7ff;
    border: 1px solid #cce5ff;
}

.tb-slot-booked {
    background-color: #f5f5f5;
    border: 1px solid #e0e0e0;
    opacity: 0.7;
    cursor: not-allowed;
}

.tb-time-slot.selected {
    background-color: #3399cc;
    border-color: #2980b9;
    color: #fff;
}

.tb-time-slot.selected .tb-slot-price {
    color: #fff;
}

.tb-slot-time {
    font-weight: bold;
    margin-bottom: 5px;
}

.tb-slot-price {
    color: #3399cc;
}

.tb-slot-status {
    color: #e74c3c;
    font-weight: bold;
}

.tb-booking-summary {
    background-color: #fff;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.tb-summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.tb-summary-item:last-child {
    margin-bottom: 0;
    padding-top: 10px;
    border-top: 1px solid #eee;
    font-weight: bold;
}

.tb-user-details {
    margin-bottom: 20px;
}

.tb-user-details h4 {
    margin-top: 0;
    margin-bottom: 15px;
}

.tb-booking-actions {
    text-align: center;
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
    font-weight: bold;
}

.tb-button:hover {
    background-color: #2980b9;
}

.tb-booking-response,
.tb-booking-error {
    margin-top: 20px;
    padding: 15px;
    border-radius: 4px;
}

.tb-booking-error {
    background-color: #ffebee;
    border: 1px solid #f44336;
    color: #d32f2f;
}

.tb-login-required {
    text-align: center;
    padding: 20px;
}

.tb-login-required p {
    margin-bottom: 15px;
}

/* Similar Courts */
.tb-similar-courts {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #ddd;
}

.tb-similar-courts h3 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 24px;
}

.tb-courts-grid {
    display: flex;
    flex-wrap: wrap;
    margin: -10px;
}

.tb-court-card {
    flex: 1;
    min-width: 300px;
    margin: 10px;
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.tb-court-card-image {
    height: 200px;
    overflow: hidden;
}

.tb-court-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.tb-court-card:hover .tb-court-card-image img {
    transform: scale(1.05);
}

.tb-no-image {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    background-color: #f5f5f5;
    color: #999;
    font-style: italic;
}

.tb-court-card-content {
    padding: 15px;
}

.tb-court-card-title {
    margin: 0 0 10px;
    font-size: 18px;
}

.tb-court-card-title a {
    color: #333;
    text-decoration: none;
}

.tb-court-card-title a:hover {
    color: #3399cc;
}

.tb-court-card-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    color: #666;
}

.tb-court-card-location {
    display: flex;
    align-items: center;
}

.tb-court-card-location i {
    margin-right: 5px;
    color: #3399cc;
}

.tb-court-card-rating {
    display: flex;
    align-items: center;
}

.tb-court-card-price {
    margin-bottom: 15px;
    font-size: 18px;
    color: #3399cc;
}

.tb-button-link {
    display: inline-block;
    padding: 8px 15px;
    background-color: #3399cc;
    color: #fff;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s ease;
}

.tb-button-link:hover {
    background-color: #2980b9;
    color: #fff;
}

.tb-court-card-action {
    text-align: center;
}

/* Responsive Styles */
@media screen and (max-width: 992px) {
    .tb-court-content {
        flex-direction: column;
    }
    
    .tb-court-tabs {
        margin-right: 0;
        margin-bottom: 30px;
    }
    
    .tb-booking-widget {
        position: static;
    }
    
    .tb-gallery-item {
        flex: 0 0 calc(33.333% - 10px);
    }
}

@media screen and (max-width: 768px) {
    .tb-court-header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .tb-court-price-box {
        width: 100%;
        margin-top: 15px;
    }
    
    .tb-quick-info-item {
        min-width: calc(50% - 20px);
    }
    
    .tb-facility-item {
        flex: 0 0 50%;
    }
    
    .tb-gallery-item {
        flex: 0 0 calc(50% - 10px);
    }
    
    .tb-court-card {
        min-width: calc(50% - 20px);
    }
}

@media screen and (max-width: 576px) {
    .tb-quick-info-item {
        min-width: 100%;
    }
    
    .tb-facility-item {
        flex: 0 0 100%;
    }
    
    .tb-gallery-item {
        flex: 0 0 calc(100% - 10px);
    }
    
    .tb-court-card {
        min-width: 100%;
    }
    
    .tb-time-slot {
        flex: 0 0 100%;
    }
}
</style>

<?php get_footer(); ?>
