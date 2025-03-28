<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class Turf_Booking {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Turf_Booking_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = TURF_BOOKING_VERSION;
        $this->plugin_name = 'turf-booking';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_post_types();
        $this->define_shortcodes();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once TURF_BOOKING_PLUGIN_DIR . 'includes/class-turf-booking-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once TURF_BOOKING_PLUGIN_DIR . 'includes/class-turf-booking-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once TURF_BOOKING_PLUGIN_DIR . 'admin/class-turf-booking-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once TURF_BOOKING_PLUGIN_DIR . 'public/class-turf-booking-public.php';

        /**
         * The class responsible for defining all custom post types.
         */
        require_once TURF_BOOKING_PLUGIN_DIR . 'includes/class-turf-booking-post-types.php';

        /**
         * The class responsible for handling bookings.
         */
        require_once TURF_BOOKING_PLUGIN_DIR . 'includes/class-turf-booking-bookings.php';

        /**
         * The class responsible for handling courts.
         */
        require_once TURF_BOOKING_PLUGIN_DIR . 'includes/class-turf-booking-courts.php';

        /**
         * The class responsible for handling payments with Razorpay.
         */
        require_once TURF_BOOKING_PLUGIN_DIR . 'includes/class-turf-booking-payments.php';

        /**
         * The class responsible for handling user dashboard.
         */
        require_once TURF_BOOKING_PLUGIN_DIR . 'includes/class-turf-booking-user-dashboard.php';

        /**
         * The class responsible for defining all shortcodes.
         */
        require_once TURF_BOOKING_PLUGIN_DIR . 'includes/class-turf-booking-shortcodes.php';

        $this->loader = new Turf_Booking_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Turf_Booking_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Turf_Booking_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Turf_Booking_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Add template override hooks
        $this->loader->add_filter('single_template', $plugin_public, 'court_single_template');
        $this->loader->add_filter('archive_template', $plugin_public, 'court_archive_template');
        
        // AJAX hooks for booking system
        $this->loader->add_action('wp_ajax_get_court_availability', $plugin_public, 'get_court_availability');
        $this->loader->add_action('wp_ajax_nopriv_get_court_availability', $plugin_public, 'get_court_availability');
        
        $this->loader->add_action('wp_ajax_create_booking', $plugin_public, 'create_booking');
        $this->loader->add_action('wp_ajax_nopriv_create_booking', $plugin_public, 'create_booking');
        
        // Payment callback
        $this->loader->add_action('wp_ajax_razorpay_payment_callback', $plugin_public, 'razorpay_payment_callback');
        $this->loader->add_action('wp_ajax_nopriv_razorpay_payment_callback', $plugin_public, 'razorpay_payment_callback');
    }

    /**
     * Register all custom post types.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_post_types() {
        $plugin_post_types = new Turf_Booking_Post_Types();
        
        $this->loader->add_action('init', $plugin_post_types, 'register_court_post_type');
        $this->loader->add_action('init', $plugin_post_types, 'register_booking_post_type');
        $this->loader->add_action('init', $plugin_post_types, 'register_court_taxonomies');
        
        // Add meta boxes
        $this->loader->add_action('add_meta_boxes', $plugin_post_types, 'add_court_meta_boxes');
        $this->loader->add_action('add_meta_boxes', $plugin_post_types, 'add_booking_meta_boxes');
        
        // Save meta box data
        $this->loader->add_action('save_post_tb_court', $plugin_post_types, 'save_court_meta_box_data');
        $this->loader->add_action('save_post_tb_booking', $plugin_post_types, 'save_booking_meta_box_data');

        // Add these lines after the existing post type registrations
$this->loader->add_action('init', $plugin_post_types, 'register_addon_post_type');
$this->loader->add_action('add_meta_boxes', $plugin_post_types, 'add_addon_meta_boxes');
$this->loader->add_action('save_post_tb_addon', $plugin_post_types, 'save_addon_meta_box_data');

// Add columns for courts
$this->loader->add_filter('manage_tb_court_posts_columns', $plugin_post_types, 'add_court_addon_column');
$this->loader->add_action('manage_tb_court_posts_custom_column', $plugin_post_types, 'display_court_addon_column', 10, 2);
    }

    /**
     * Register all shortcodes.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_shortcodes() {
        $plugin_shortcodes = new Turf_Booking_Shortcodes($this->get_plugin_name(), $this->get_version());
        
        // Register shortcodes
        $this->loader->add_action('init', $plugin_shortcodes, 'register_shortcodes');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Turf_Booking_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
