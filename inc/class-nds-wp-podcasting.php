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
 * Include Taxonomy Meta Field Library
 */
require_once(NDSWP_PODCASTING_PATH . 'lib/Tax-meta-class/Tax-meta-class.php');

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
    protected $plugin_post_type = 'nds_wp_podcast';

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

        // Add additional image sizes
        if ( function_exists( 'add_image_size' ) )
        {
            // additional image sizes
            // TODO: Make these sizes user configurable in the plugin settings
            add_image_size( 'podcast-thumb', 98, 98 );
            add_image_size( 'podcast-small', 124, 124 );
            add_image_size( 'podcast', 220, 220 );
            // iTunes prefers square .jpg images that are at least 1400 x 1400 pixels
            add_image_size( 'itunes-cover', 1400, 1400, TRUE );
        }

        add_action( 'init', array( $this, 'register_podcasting_post_type' ) );
        add_action( 'init', array( $this, 'register_speaker_taxonomy' ), 0 );
        add_action( 'init', array( $this, 'register_series_taxonomy' ), 0 );
        add_action( 'init', array( $this, 'register_tag_taxonomy' ), 0 );
        //        add_action( 'pre_get_posts', array( $this, 'frontend_listing_query' ) );
        if ( function_exists( 'register_sidebar' ) )
        {
            add_action( 'widgets_init', array( $this, 'widget_areas_init' ) );
        }
        add_action( 'widgets_init', array( $this, 'register_podcasting_widgets' ) );

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
     * Create a Podcast Post Type
     *
     * @since    1.0.0
     */
    public function register_podcasting_post_type()
    {

        global $wp_version;

        $labels = array(
            'name'               => _x( 'Podcast Episodes', 'post type general name' ),
            'singular_name'      => _x( 'Episode', 'post type singular name' ),
            'menu_name'          => __( 'Podcasting' ),
            'add_new'            => _x( 'Add New', 'episode' ),
            'add_new_item'       => __( 'Add New Episode' ),
            'edit_item'          => __( 'Edit Episode' ),
            'new_item'           => __( 'New Episode' ),
            'all_items'          => __( 'All Episodes' ),
            'view_item'          => __( 'View Episode' ),
            'search_items'       => __( 'Search Podcast Episodes' ),
            'not_found'          => __( 'Nothing found' ),
            'not_found_in_trash' => __( 'Nothing found in Trash' ),
            'parent_item_colon'  => ''
        );

        $args = array(
            'labels'             => $labels,
            'public'             => TRUE,
            'publicly_queryable' => TRUE,
            'show_ui'            => TRUE,
            'show_in_nav_menus'  => FALSE,
            'query_var'          => TRUE,
            'rewrite'            => array( "slug" => "podcast" ), // Permalinks format
            'capability_type'    => 'post',
            'hierarchical'       => FALSE,
            'menu_position'      => NULL,
            'menu_icon'          => ( ( defined( 'MP6' ) && MP6 ) || version_compare( $wp_version, '3.8', '>=' ) ) ? 'dashicons-rss' : NULL,
            'has_archive'        => TRUE,
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
            'taxonomies'         => array(
                $this->plugin_post_type . '_speaker',
                $this->plugin_post_type . '_series',
                $this->plugin_post_type . '_tag'
            )
        );

        register_post_type( $this->plugin_post_type, $args );
    }

    /**
     * Setup custom Speakers category for Podcast.
     *
     * @since    1.0.0
     */
    function register_speaker_taxonomy()
    {

        $labels = array(
            'name'                       => _x( 'Speakers', 'taxonomy general name' ),
            'singular_name'              => _x( 'Speaker', 'taxonomy singular name' ),
            'search_items'               => __( 'Search Speakers' ),
            'popular_items'              => __( 'Popular Speakers' ),
            'all_items'                  => __( 'All Speakers' ),
            'parent_item'                => NULL,
            'parent_item_colon'          => NULL,
            'edit_item'                  => __( 'Edit Speaker' ),
            'update_item'                => __( 'Update Speaker' ),
            'add_new_item'               => __( 'Add New Speaker' ),
            'new_item_name'              => __( 'New Speaker Name' ),
            'separate_items_with_commas' => __( 'Separate speakers with commas' ),
            'add_or_remove_items'        => __( 'Add or remove speakers' ),
            'choose_from_most_used'      => __( 'Choose from the most used speakers' )
        );

        register_taxonomy(
            $this->plugin_post_type . '_speaker',
            NULL,
            array(
                 'label'             => __( 'Podcast Speaker' ),
                 'labels'            => $labels,
                 'show_ui'           => TRUE,
                 'show_in_nav_menus' => FALSE,
                 'query_var'         => TRUE,
                 'rewrite'           => array( 'slug' => 'podcast-speaker' ),
                 'hierarchical'      => TRUE
            )
        );

        /**
         * configure taxonomy custom fields
         */
        $tax_meta_config = array(
            // meta box id, unique per meta box
            'id'             => $this->plugin_post_type . '_speaker_meta_box',
            // meta box title
            'title'          => 'Additional Options',
            // taxonomy name, accept categories, post_tag and custom taxonomies
            'pages'          => array( $this->plugin_post_type . '_speaker' ),
            // where the meta box appear: normal (default), advanced, side; optional
            'context'        => 'normal',
            // list of meta fields (can be added by field arrays)
            'fields'         => array(),
            // Use local or hosted images (meta box images for add/remove)
            'local_images'   => FALSE,
            // change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
            'use_with_theme' => FALSE
        );

        /**
         * Initiate your taxonomy custom fields
         */
        $tax_meta = new Tax_Meta_Class( $tax_meta_config );

        /**
         * Add a speaker image field
         */
        $tax_meta->addImage(
                 $this->plugin_post_type . '_speaker_image',
                 array( 'name' => 'Speaker Image ' )
        );

        // Finish Taxonomy Extra fields Setup
        $tax_meta->Finish();
    }

    /**
     * Setup custom Series category for Podcast.
     *
     * @since    1.0.0
     */
    public function register_series_taxonomy()
    {

        $labels = array(
            'name'                       => _x( 'Series', 'taxonomy general name' ),
            'singular_name'              => _x( 'Series', 'taxonomy singular name' ),
            'search_items'               => __( 'Search Series' ),
            'popular_items'              => __( 'Popular Series' ),
            'all_items'                  => __( 'All Series' ),
            'parent_item'                => NULL,
            'parent_item_colon'          => NULL,
            'edit_item'                  => __( 'Edit Series' ),
            'update_item'                => __( 'Update Series' ),
            'add_new_item'               => __( 'Add New Series' ),
            'new_item_name'              => __( 'New Series Name' ),
            'separate_items_with_commas' => __( 'Separate series with commas' ),
            'add_or_remove_items'        => __( 'Add or remove series' ),
            'choose_from_most_used'      => __( 'Choose from the most used series' )
        );

        register_taxonomy(
            $this->plugin_post_type . '_series',
            NULL,
            array(
                 'label'             => __( 'Podcast Series' ),
                 'labels'            => $labels,
                 'show_ui'           => TRUE,
                 'show_in_nav_menus' => FALSE,
                 'query_var'         => TRUE,
                 'rewrite'           => array( 'slug' => 'podcast-series' ),
                 'hierarchical'      => TRUE
            )
        );

        /**
         * configure taxonomy custom fields
         */
        $tax_meta_config = array(
            // meta box id, unique per meta box
            'id'             => $this->plugin_post_type . '_series_meta_box',
            // meta box title
            'title'          => 'Additional Options',
            // taxonomy name, accept categories, post_tag and custom taxonomies
            'pages'          => array( $this->plugin_post_type . '_series' ),
            // where the meta box appear: normal (default), advanced, side; optional
            'context'        => 'normal',
            // list of meta fields (can be added by field arrays)
            'fields'         => array(),
            // Use local or hosted images (meta box images for add/remove)
            'local_images'   => FALSE,
            // change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
            'use_with_theme' => FALSE
        );

        /**
         * Initiate your taxonomy custom fields
         */
        $tax_meta = new Tax_Meta_Class( $tax_meta_config );

        /**
         * Add a series image field
         */
        $tax_meta->addImage(
                 $this->plugin_post_type . '_series_image',
                 array( 'name' => 'Series Image ' )
        );

        // Finish Taxonomy Extra fields Setup
        $tax_meta->Finish();
    }

    /**
     * Setup custom tags for Podcasting.
     *
     * @since    1.0.0
     */
    public function register_tag_taxonomy()
    {

        $labels = array(
            'name'                       => _x( 'Podcast Tags', 'taxonomy general name' ),
            'singular_name'              => _x( 'Podcast Tag', 'taxonomy singular name' ),
            'menu_name'                  => __( 'Tags' ),
            'search_items'               => __( 'Search Podcast Tags' ),
            'popular_items'              => __( 'Popular Podcast Tags' ),
            'all_items'                  => __( 'All Podcast Tags' ),
            'parent_item'                => NULL,
            'parent_item_colon'          => NULL,
            'edit_item'                  => __( 'Edit Podcast Tag' ),
            'update_item'                => __( 'Update Podcast Tag' ),
            'add_new_item'               => __( 'Add New Podcast Tag' ),
            'new_item_name'              => __( 'New Podcast Tag Name' ),
            'separate_items_with_commas' => __( 'Separate podcast tags with commas' ),
            'add_or_remove_items'        => __( 'Add or remove podcast tags' ),
            'choose_from_most_used'      => __( 'Choose from the most used podcast tags' )
        );

        register_taxonomy(
            $this->plugin_post_type . '_tag',
            NULL,
            array(
                 'label'             => __( 'Podcast Tag' ),
                 'labels'            => $labels,
                 'show_ui'           => TRUE,
                 'show_tagcloud'     => TRUE,
                 'show_in_nav_menus' => FALSE,
                 'query_var'         => TRUE,
                 'rewrite'           => array( 'slug' => 'podcast-tag' ),
                 'hierarchical'      => FALSE
            )
        );
    }

    /**
     * Register site widget sidebars
     */
    public function widget_areas_init()
    {
        register_sidebar(
            array(
                 'name'          => __( 'Podcasting Sidebar', 'podcasting-sidebar' ),
                 'id'            => 'podcasting-sidebar',
                 'before_widget' => '<aside class="podcasting-sidebar columns-4 right clearfix">',
                 'after_widget'  => '</aside>',
                 'before_title'  => NULL,
                 'after_title'   => NULL
            )
        );
    }

    /**
     * Register Podcasting widgets.
     */
    public function register_podcasting_widgets()
    {
        register_widget( 'NDS_WP_Podcasting_Episodes_Widget' );
    }

    /**
     * Add iTunes Namespace
     * add_filter( 'rss2_ns', 'itunes_namespace' );
     */
    public function itunes_namespace() {
        echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"';
    }

    /**
     * Add iTunes Header Metadata
     * add_filter( 'rss2_head', 'itunes_head' );
     * TODO: Setup plugins options for these values
     */
    public function itunes_head() {
        ?>
        <itunes:subtitle></itunes:subtitle>
        <itunes:author>Daybreak Church</itunes:author>
        <itunes:summary>Weekly messages from Pastor Wes Dupin and guest speakers.</itunes:summary>
        <itunes:owner>
            <itunes:name>Daybreak Church</itunes:name>
            <itunes:email>webmaster@daybreak.tv</itunes:email>
        </itunes:owner>
        <itunes:image href="http://example.com/podcasts/everything/AllAboutEverything.jpg" />
        <itunes:category text="Technology">
            <itunes:category text="Gadgets"/>
        </itunes:category>
        <?php
    }

    /**
     * Add iTunes specific RSS feed elements
     * add_action('rss2_item', 'itunes_attached_audio' );
     * TODO: Change item author to speaker (not sure if we need an email address).
     */
    public function itunes_attached_audio() {
        global $post;

        $podcast_audio = get_post_meta( $post->ID, 'nds_wp_podcast_audio', TRUE );

        if ($podcast_audio)
        {
            // Check for then use images from the following sources; episode featured image -> series -> speaker
            $podcast_image = $this->get_episode_image( $post->ID );
            if (!$podcast_image)
            {
                $podcast_image = $this->get_series_image( $post->ID );
                if (!$podcast_image)
                {
                    $podcast_image = $this->get_speaker_image( $post->ID );
                }
            }

            // use the post tags for itunes:keywords
            $itunes_keywords = array();
            $itunes_keywords_arr = get_the_tags();
            if ( $itunes_keywords_arr ) {
                foreach( $itunes_keywords_arr as $tag ) {
                    $itunes_keywords[] = $tag->name;
                }
            }

            $headers = get_headers( $podcast_audio, 1 );
            $filesize = $headers['Content-Length'];
            ?>
            <itunes:author><?php echo get_the_author(); ?></itunes:author>
            <itunes:subtitle><?php echo $post->post_title; ?></itunes:subtitle>
            <itunes:summary><?php echo $post->post_excerpt; ?></itunes:summary>
            <itunes:image href="<?php echo $podcast_image; ?>" />
            <enclosure url="<?php echo $podcast_audio; ?>" length="<?php echo $filesize; ?>" type="<?php echo $att->post_mime_type; ?>" />
            <guid><?php the_permalink(); ?></guid>
            <itunes:duration><?php echo $post->post_content; ?></itunes:duration>
            <itunes:keywords><?php echo implode(',', $itunes_keywords); ?></itunes:keywords>
            <?php
        }
    }

    /**
     * Helper function that provides a Podcast episode image.
     */
    public function get_episode_image( $post_id, $image_size = 'itunes-cover' )
    {
        return wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $image_size );
    }

    /**
     * Helper function that provides a Podcast series image.
     */
    public function get_series_image( $post_id, $image_size = 'itunes-cover' )
    {
        $series_list = wp_get_post_terms( $post_id, 'nds_wp_podcast_series' );

        if ( function_exists( 'get_tax_meta' ) )
        {
            foreach ( $series_list as $series )
            {
                $podcast_image_meta = get_tax_meta( $series->term_id, 'nds_wp_podcast_series_image', TRUE );
                $podcast_image_id   = ( isset( $podcast_image_meta['id'] ) ) ? $podcast_image_meta['id'] : FALSE;
                break;
            }
        }

        return wp_get_attachment_image_src( $podcast_image_id, $image_size );
    }

    /**
     * Helper function that provides a Podcast speaker image.
     */
    public function get_speaker_image( $post_id, $image_size = 'itunes-cover' )
    {
        $speaker_list  = wp_get_post_terms( $post_id, 'nds_wp_podcast_speaker' );

        if ( function_exists( 'get_tax_meta' ) )
        {
            foreach ( $speaker_list as $speaker )
            {
                $podcast_image_meta = get_tax_meta( $speaker->term_id, 'nds_wp_podcast_speaker_image', TRUE );
                $podcast_image_id   = ( isset( $podcast_image_meta['id'] ) ) ? $podcast_image_meta['id'] : FALSE;
                break;
            }
        }

        return wp_get_attachment_image_src( $podcast_image_id, $image_size );
    }

}