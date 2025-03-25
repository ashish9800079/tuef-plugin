<?php
/**
 * Template for displaying court archive (listing)
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Get taxonomy terms for filtering
$sport_types = get_terms(array(
    'taxonomy' => 'sport_type',
    'hide_empty' => true,
));

$locations = get_terms(array(
    'taxonomy' => 'location',
    'hide_empty' => true,
));

$facilities = get_terms(array(
    'taxonomy' => 'facility',
    'hide_empty' => true,
));

// Get currency symbol
$general_settings = get_option('tb_general_settings');
$currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';

// Get filter parameters from URL
$selected_sport = isset($_GET['sport_type']) ? absint($_GET['sport_type']) : 0;
$selected_location = isset($_GET['location']) ? absint($_GET['location']) : 0;
$selected_facilities = isset($_GET['facilities']) ? array_map('absint', (array) $_GET['facilities']) : array();
$selected_rating = isset($_GET['rating']) ? floatval($_GET['rating']) : 0;
$price_min = isset($_GET['price_min']) ? intval($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? intval($_GET['price_max']) : 5000;
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'name_asc';

// Set up query arguments
$args = array(
    'post_type' => 'tb_court',
    'posts_per_page' => 12,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
);

// Add taxonomy filters
$tax_query = array();

if ($selected_sport) {
    $tax_query[] = array(
        'taxonomy' => 'sport_type',
        'field' => 'term_id',
        'terms' => $selected_sport,
    );
}

if ($selected_location) {
    $tax_query[] = array(
        'taxonomy' => 'location',
        'field' => 'term_id',
        'terms' => $selected_location,
    );
}

if (!empty($selected_facilities)) {
    $tax_query[] = array(
        'taxonomy' => 'facility',
        'field' => 'term_id',
        'terms' => $selected_facilities,
        'operator' => 'IN',
    );
}

if (!empty($tax_query)) {
    $tax_query['relation'] = 'AND';
    $args['tax_query'] = $tax_query;
}

// Add meta query for rating and price
$meta_query = array();

if ($selected_rating > 0) {
    $meta_query[] = array(
        'key' => '_tb_court_rating',
        'value' => $selected_rating,
        'compare' => '>=',
        'type' => 'DECIMAL',
    );
}

if ($price_min > 0 || $price_max < 5000) {
    $meta_query[] = array(
        'key' => '_tb_court_base_price',
        'value' => array($price_min, $price_max),
        'compare' => 'BETWEEN',
        'type' => 'NUMERIC',
    );
}

if (!empty($meta_query)) {
    $meta_query['relation'] = 'AND';
    $args['meta_query'] = $meta_query;
}

// Add search query
if (!empty($search_query)) {
    $args['s'] = $search_query;
}

// Add sorting
switch ($sort_by) {
    case 'name_asc':
        $args['orderby'] = 'title';
        $args['order'] = 'ASC';
        break;
    case 'name_desc':
        $args['orderby'] = 'title';
        $args['order'] = 'DESC';
        break;
    case 'price_asc':
        $args['meta_key'] = '_tb_court_base_price';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'ASC';
        break;
    case 'price_desc':
        $args['meta_key'] = '_tb_court_base_price';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;
    case 'rating_desc':
        $args['meta_key'] = '_tb_court_rating';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;
    case 'newest':
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        break;
}

// Get courts
$courts_query = new WP_Query($args);
?>

<div class="tb-courts-archive-container">
    <div class="tb-courts-header">
        <h1 class="tb-courts-title"><?php _e('Available Courts', 'turf-booking'); ?></h1>
        
        <div class="tb-search-box">
            <form method="get" action="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-search-form">
                <input type="text" name="search" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php _e('Search courts...', 'turf-booking'); ?>" class="tb-search-input">
                <button type="submit" class="tb-search-button"><i class="fas fa-search"></i></button>
                
                <?php 
                // Preserve other query parameters
                if ($selected_sport) echo '<input type="hidden" name="sport_type" value="' . esc_attr($selected_sport) . '">';
                if ($selected_location) echo '<input type="hidden" name="location" value="' . esc_attr($selected_location) . '">';
                if (!empty($selected_facilities)) {
                    foreach ($selected_facilities as $facility_id) {
                        echo '<input type="hidden" name="facilities[]" value="' . esc_attr($facility_id) . '">';
                    }
                }
                if ($selected_rating) echo '<input type="hidden" name="rating" value="' . esc_attr($selected_rating) . '">';
                if ($price_min > 0) echo '<input type="hidden" name="price_min" value="' . esc_attr($price_min) . '">';
                if ($price_max < 5000) echo '<input type="hidden" name="price_max" value="' . esc_attr($price_max) . '">';
                if ($sort_by !== 'name_asc') echo '<input type="hidden" name="sort_by" value="' . esc_attr($sort_by) . '">';
                ?>
            </form>
        </div>
    </div>
    
    <div class="tb-courts-main-content">
        <div class="tb-courts-filters">
            <div class="tb-filters-header">
                <h3><?php _e('Filters', 'turf-booking'); ?></h3>
                <?php if (isset($_GET) && !empty($_GET)) : ?>
                    <a href="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-clear-filters"><?php _e('Clear All', 'turf-booking'); ?></a>
                <?php endif; ?>
            </div>
            
            <form method="get" action="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-filters-form">
                <?php if (!empty($search_query)) : ?>
                    <input type="hidden" name="search" value="<?php echo esc_attr($search_query); ?>">
                <?php endif; ?>
                
                <?php if (!empty($sport_types)) : ?>
                    <div class="tb-filter-group">
                        <h4><?php _e('Sport Type', 'turf-booking'); ?></h4>
                        <select name="sport_type" class="tb-filter-select">
                            <option value=""><?php _e('All Sports', 'turf-booking'); ?></option>
                            <?php foreach ($sport_types as $sport_type) : ?>
                                <option value="<?php echo esc_attr($sport_type->term_id); ?>" <?php selected($selected_sport, $sport_type->term_id); ?>>
                                    <?php echo esc_html($sport_type->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($locations)) : ?>
                    <div class="tb-filter-group">
                        <h4><?php _e('Location', 'turf-booking'); ?></h4>
                        <select name="location" class="tb-filter-select">
                            <option value=""><?php _e('All Locations', 'turf-booking'); ?></option>
                            <?php foreach ($locations as $location) : ?>
                                <option value="<?php echo esc_attr($location->term_id); ?>" <?php selected($selected_location, $location->term_id); ?>>
                                    <?php echo esc_html($location->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($facilities)) : ?>
                    <div class="tb-filter-group">
                        <h4><?php _e('Facilities', 'turf-booking'); ?></h4>
                        <div class="tb-filter-checkboxes">
                            <?php foreach ($facilities as $facility) : ?>
                                <label class="tb-filter-checkbox">
                                    <input type="checkbox" name="facilities[]" value="<?php echo esc_attr($facility->term_id); ?>" <?php checked(in_array($facility->term_id, $selected_facilities)); ?>>
                                    <?php echo esc_html($facility->name); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="tb-filter-group">
                    <h4><?php _e('Rating', 'turf-booking'); ?></h4>
                    <select name="rating" class="tb-filter-select">
                        <option value=""><?php _e('Any Rating', 'turf-booking'); ?></option>
                        <option value="4" <?php selected($selected_rating, 4); ?>><?php _e('4+ Stars', 'turf-booking'); ?></option>
                        <option value="3" <?php selected($selected_rating, 3); ?>><?php _e('3+ Stars', 'turf-booking'); ?></option>
                        <option value="2" <?php selected($selected_rating, 2); ?>><?php _e('2+ Stars', 'turf-booking'); ?></option>
                    </select>
                </div>
                
                <div class="tb-filter-group">
                    <h4><?php _e('Price Range', 'turf-booking'); ?></h4>
                    <div class="tb-price-range">
                        <div class="tb-price-slider" id="tb-price-slider" data-min="<?php echo esc_attr($price_min); ?>" data-max="<?php echo esc_attr($price_max); ?>"></div>
                        <div class="tb-price-inputs">
                            <div class="tb-price-input">
                                <span><?php echo esc_html($currency_symbol); ?></span>
                                <input type="number" name="price_min" id="price-min" value="<?php echo esc_attr($price_min); ?>" min="0" max="5000">
                            </div>
                            <span class="tb-price-separator">-</span>
                            <div class="tb-price-input">
                                <span><?php echo esc_html($currency_symbol); ?></span>
                                <input type="number" name="price_max" id="price-max" value="<?php echo esc_attr($price_max); ?>" min="0" max="5000">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="tb-filter-actions">
                    <button type="submit" class="tb-filter-button"><?php _e('Apply Filters', 'turf-booking'); ?></button>
                </div>
            </form>
        </div>
        
        <div class="tb-courts-list">
            <div class="tb-courts-list-header">
                <div class="tb-courts-count">
                    <?php 
                    printf(
                        _n(
                            '%s court found',
                            '%s courts found',
                            $courts_query->found_posts,
                            'turf-booking'
                        ),
                        number_format_i18n($courts_query->found_posts)
                    );
                    ?>
                </div>
                
                <div class="tb-courts-sort">
                    <form method="get" action="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-sort-form">
                        <label for="sort-by"><?php _e('Sort by:', 'turf-booking'); ?></label>
                        <select name="sort_by" id="sort-by" class="tb-sort-select" onchange="this.form.submit()">
                            <option value="name_asc" <?php selected($sort_by, 'name_asc'); ?>><?php _e('Name (A-Z)', 'turf-booking'); ?></option>
                            <option value="name_desc" <?php selected($sort_by, 'name_desc'); ?>><?php _e('Name (Z-A)', 'turf-booking'); ?></option>
                            <option value="price_asc" <?php selected($sort_by, 'price_asc'); ?>><?php _e('Price (Low to High)', 'turf-booking'); ?></option>
                            <option value="price_desc" <?php selected($sort_by, 'price_desc'); ?>><?php _e('Price (High to Low)', 'turf-booking'); ?></option>
                            <option value="rating_desc" <?php selected($sort_by, 'rating_desc'); ?>><?php _e('Rating (Highest)', 'turf-booking'); ?></option>
                            <option value="newest" <?php selected($sort_by, 'newest'); ?>><?php _e('Newest', 'turf-booking'); ?></option>
                        </select>
                        
                        <?php 
                        // Preserve other query parameters
                        if (!empty($search_query)) echo '<input type="hidden" name="search" value="' . esc_attr($search_query) . '">';
                        if ($selected_sport) echo '<input type="hidden" name="sport_type" value="' . esc_attr($selected_sport) . '">';
                        if ($selected_location) echo '<input type="hidden" name="location" value="' . esc_attr($selected_location) . '">';
                        if (!empty($selected_facilities)) {
                            foreach ($selected_facilities as $facility_id) {
                                echo '<input type="hidden" name="facilities[]" value="' . esc_attr($facility_id) . '">';
                            }
                        }
                        if ($selected_rating) echo '<input type="hidden" name="rating" value="' . esc_attr($selected_rating) . '">';
                        if ($price_min > 0) echo '<input type="hidden" name="price_min" value="' . esc_attr($price_min) . '">';
                        if ($price_max < 5000) echo '<input type="hidden" name="price_max" value="' . esc_attr($price_max) . '">';
                        ?>
                    </form>
                </div>
            </div>
            
            <?php if ($courts_query->have_posts()) : ?>
                <div class="tb-courts-grid">
                    <?php while ($courts_query->have_posts()) : $courts_query->the_post(); ?>
                        <?php include(TURF_BOOKING_PLUGIN_DIR . 'public/templates/content-court-card.php'); ?>
                    <?php endwhile; ?>
                </div>
                
                <div class="tb-pagination">
                    <?php
                    $big = 999999999; // need an unlikely integer
                    echo paginate_links(array(
                        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                        'format' => '?paged=%#%',
                        'current' => max(1, get_query_var('paged')),
                        'total' => $courts_query->max_num_pages,
                        'prev_text' => '<i class="fas fa-chevron-left"></i>',
                        'next_text' => '<i class="fas fa-chevron-right"></i>',
                    ));
                    ?>
                </div>
            <?php else : ?>
                <div class="tb-no-courts">
                    <p><?php _e('No courts found matching your criteria.', 'turf-booking'); ?></p>
                    <a href="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-button"><?php _e('Clear Filters', 'turf-booking'); ?></a>
                </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize price slider (if jQuery UI is available)
    if ($.fn.slider) {
        $('#tb-price-slider').slider({
            range: true,
            min: 0,
            max: 5000,
            values: [$('#price-min').val(), $('#price-max').val()],
            slide: function(event, ui) {
                $('#price-min').val(ui.values[0]);
                $('#price-max').val(ui.values[1]);
            }
        });
        
        // Update slider when inputs change
        $('#price-min, #price-max').on('change', function() {
            $('#tb-price-slider').slider('values', [$('#price-min').val(), $('#price-max').val()]);
        });
    }
    
    // Mobile filter toggle
    $('.tb-mobile-filter-toggle').on('click', function() {
        $('.tb-courts-filters').toggleClass('active');
    });
});
</script>

<style>
/* Court Archive Styles */
.tb-courts-archive-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: Arial, sans-serif;
}

.tb-courts-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.tb-courts-title {
    margin: 0;
    font-size: 28px;
}

.tb-search-box {
    max-width: 400px;
    width: 100%;
}

.tb-search-form {
    display: flex;
    position: relative;
}

.tb-search-input {
    width: 100%;
    padding: 10px 40px 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.tb-search-button {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    width: 40px;
    background-color: transparent;
    border: none;
    cursor: pointer;
    color: #666;
}

.tb-courts-main-content {
    display: flex;
    gap: 30px;
}

.tb-courts-filters {
    flex: 0 0 250px;
    background-color: #f9f9f9;
    border-radius: 5px;
    padding: 20px;
    height: fit-content;
}

.tb-filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.tb-filters-header h3 {
    margin: 0;
    font-size: 18px;
}

.tb-clear-filters {
    color: #3399cc;
    text-decoration: none;
    font-size: 14px;
}

.tb-filter-group {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.tb-filter-group:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.tb-filter-group h4 {
    margin: 0 0 10px;
    font-size: 16px;
    color: #333;
}

.tb-filter-select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.tb-filter-checkboxes {
    max-height: 150px;
    overflow-y: auto;
}

.tb-filter-checkbox {
    display: block;
    margin-bottom: 8px;
}

.tb-filter-checkbox input {
    margin-right: 8px;
}

.tb-price-range {
    margin-top: 15px;
}

.tb-price-slider {
    margin-bottom: 15px;
}

.tb-price-inputs {
    display: flex;
    align-items: center;
}

.tb-price-input {
    display: flex;
    align-items: center;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px 8px;
}

.tb-price-input span {
    margin-right: 5px;
    color: #666;
}

.tb-price-input input {
    width: 60px;
    border: none;
    padding: 0;
}

.tb-price-separator {
    margin: 0 10px;
    color: #666;
}

.tb-filter-actions {
    margin-top: 20px;
    text-align: center;
}

.tb-filter-button {
    display: block;
    width: 100%;
    padding: 10px;
    background-color: #3399cc;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

.tb-filter-button:hover {
    background-color: #2980b9;
}

.tb-courts-list {
    flex: 1;
}

.tb-courts-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.tb-courts-count {
    color: #666;
}

.tb-courts-sort {
    display: flex;
    align-items: center;
}

.tb-sort-form {
    display: flex;
    align-items: center;
}

.tb-sort-form label {
    margin-right: 10px;
    color: #666;
}

.tb-sort-select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.tb-courts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.tb-pagination {
    text-align: center;
    margin-top: 30px;
}

.tb-pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
}

.tb-pagination .page-numbers.current {
    background-color: #3399cc;
    color: #fff;
    border-color: #3399cc;
}

.tb-pagination .page-numbers:hover:not(.current) {
    background-color: #f5f5f5;
}

.tb-no-courts {
    text-align: center;
    padding: 50px 0;
    color: #666;
}

.tb-no-courts p {
    margin-bottom: 20px;
    font-size: 18px;
}

.tb-button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #3399cc;
    color: #fff;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    font-size: 16px;
}

.tb-button:hover {
    background-color: #2980b9;
}

.tb-mobile-filter-toggle {
    display: none;
    background-color: #3399cc;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 10px 15px;
    margin-bottom: 20px;
    cursor: pointer;
    font-size: 16px;
    align-items: center;
    justify-content: center;
}

.tb-mobile-filter-toggle i {
    margin-right: 8px;
}

/* Media Queries */
@media screen and (max-width: 992px) {
    .tb-courts-main-content {
        flex-direction: column;
    }
    
    .tb-courts-filters {
        flex: none;
        width: 100%;
        margin-bottom: 20px;
    }
}

@media screen and (max-width: 768px) {
    .tb-courts-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .tb-courts-title {
        margin-bottom: 20px;
        text-align: center;
    }
    
    .tb-search-box {
        max-width: 100%;
    }
    
    .tb-courts-list-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .tb-courts-count {
        margin-bottom: 10px;
        text-align: center;
    }
    
    .tb-courts-sort {
        justify-content: center;
    }
    
    .tb-courts-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }
    
    .tb-mobile-filter-toggle {
        display: flex;
    }
    
    .tb-courts-filters {
        display: none;
    }
    
    .tb-courts-filters.active {
        display: block;
    }
}

@media screen and (max-width: 576px) {
    .tb-courts-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>