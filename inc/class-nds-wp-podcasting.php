<?php
/**
 * NDS WordPress Podcasting.
 *
 * @package   NDS_WP_Podcasting
 * @author    Tim Nolte <tim.nolte@ndigitals.com>
 * @license   GPL-2.0+
 * @link      http://www.ndigitals.com
 * @copyright 2013 NDigital Solutions
 */

/**
 * Include Podcasts widget class.
 */
require_once( NDSWP_PODCASTING_PATH . 'inc/widgets/class-podcasting-episodes.php' );

/**
 * Plugin class.
 *
 * @package NDS_WP_Podcasting
 * @author  Tim Nolte <tim.nolte@ndigitals.com>
 */
class NDS_WP_Podcasting
{

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since   1.0.0
     *
     * @var     string
     */
    const VERSION = '1.0.0';

    /**
     * Unique identifier for your plugin.
     *
     * The variable name is used as the text domain when internationalizing strings of text.
     * Its value should match the Text Domain file header in the main plugin file.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    protected $plugin_slug = 'nds-wp-podcasting';

    /**
     * Unique identifier for the plugin custom post type
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $plugin_post_type = 'nds_wp_podcasting';

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = NULL;

    /**
     * Initialize the plugin by setting localization and loading public scripts and styles.
     *
     * @since     1.0.0
     */
    private function __construct()
    {

        // Load plugin text domain
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

        // Activate plugin when new blog is added
        add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

        // Load public-facing style sheet and JavaScript.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // Define custom functionality. Read more about actions and filters: http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
        /*add_action( 'init', array( $this, 'events_register' ) );
        add_action( 'init', array( $this, 'events_category_taxonomy' ), 0 );
        add_action( 'init', array( $this, 'events_tag_taxonomy' ), 0 );
        add_action( 'pre_get_posts', array( $this, 'events_query' ) );
        if ( function_exists( 'register_sidebar' ) )
        {
            add_action( 'widgets_init', array( $this, 'events_widget_areas_init' ) );
        }
        add_action( 'widgets_init', array( $this, 'events_widgets_register' ) );*/

    }

    /**
     * Return the plugin slug.
     *
     * @since    1.0.0
     *
     * @return    Plugin slug variable.
     */
    public function get_plugin_slug()
    {
        return $this->plugin_slug;
    }

    /**
     * Return the plugin custom post type identifier.
     *
     * @since   1.0.0
     *
     * @return  string      Plugin custom post type identifier variable.
     */
    public function get_plugin_post_type()
    {
        return $this->plugin_post_type;
    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance()
    {

        // If the single instance hasn't been set, set it now.
        if ( NULL == self::$instance )
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Fired when the plugin is activated.
     *
     * @since    1.0.0
     *
     * @param    boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
     */
    public static function activate( $network_wide )
    {
        if ( function_exists( 'is_multisite' ) && is_multisite() )
        {
            if ( $network_wide )
            {
                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ( $blog_ids as $blog_id )
                {
                    switch_to_blog( $blog_id );
                    self::single_activate();
                }
                restore_current_blog();
            }
            else
            {
                self::single_activate();
            }
        }
        else
        {
            self::single_activate();
        }
    }

    /**
     * Fired when the plugin is deactivated.
     *
     * @since    1.0.0
     *
     * @param    boolean $network_wide True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
     */
    public static function deactivate( $network_wide )
    {
        if ( function_exists( 'is_multisite' ) && is_multisite() )
        {
            if ( $network_wide )
            {
                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ( $blog_ids as $blog_id )
                {
                    switch_to_blog( $blog_id );
                    self::single_deactivate();
                }
                restore_current_blog();
            }
            else
            {
                self::single_deactivate();
            }
        }
        else
        {
            self::single_deactivate();
        }
    }

    /**
     * Fired when a new site is activated with a WPMU environment.
     *
     * @since    1.0.0
     *
     * @param    int $blog_id ID of the new blog.
     */
    public function activate_new_site( $blog_id )
    {
        if ( 1 !== did_action( 'wpmu_new_blog' ) )
        {
            return;
        }

        switch_to_blog( $blog_id );
        self::single_activate();
        restore_current_blog();
    }

    /**
     * Get all blog ids of blogs in the current network that are:
     * - not archived
     * - not spam
     * - not deleted
     *
     * @since    1.0.0
     *
     * @return   array|false    The blog ids, false if no matches.
     */
    private static function get_blog_ids()
    {
        global $wpdb;

        // get an array of blog ids
        $sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

        return $wpdb->get_col( $sql );
    }

    /**
     * Fired for each blog when the plugin is activated.
     *
     * @since    1.0.0
     */
    private static function single_activate()
    {
        // TODO: Define activation functionality here
    }

    /**
     * Fired for each blog when the plugin is deactivated.
     *
     * @since    1.0.0
     */
    private static function single_deactivate()
    {
        // TODO: Define deactivation functionality here
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain()
    {

        $domain = $this->plugin_slug;
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, FALSE, NDSWP_PODCASTING_DIR . '/languages' );
    }

    /**
     * Register and enqueue public-facing style sheet.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_slug . '-plugin-styles',
            NDSWP_PODCASTING_URL . 'assets/css/frontend.css',
            array(),
            self::VERSION
        );
    }

    /**
     * Register and enqueues public-facing JavaScript files.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script(
            $this->plugin_slug . '-plugin-script',
            NDSWP_PODCASTING_URL . 'assets/js/frontend.js',
            array( 'jquery' ),
            self::VERSION
        );
    }

    /**
     * NOTE:  Actions are points in the execution of a page or process
     *        lifecycle that WordPress fires.
     *
     *        WordPress Actions: http://codex.wordpress.org/Plugin_API#Actions
     *        Action Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
     *
     * @since    1.0.0
     */
    public function action_method_name()
    {
        // TODO: Define your action hook callback here
    }

    /**
     * NOTE:  Filters are points of execution in which WordPress modifies data
     *        before saving it or sending it to the browser.
     *
     *        WordPress Filters: http://codex.wordpress.org/Plugin_API#Filters
     *        Filter Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
     *
     * @since    1.0.0
     */
    public function filter_method_name()
    {
        // TODO: Define your filter hook callback here
    }

}