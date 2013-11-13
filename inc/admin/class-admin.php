<?php
/**
 * NDS WordPress Podcasting.
 *
 * @package   NDS_WP_Podcasting\Admin
 * @author    Tim Nolte <tim.nolte@ndigitals.com>
 * @license   GPL-2.0+
 * @link      http://www.ndigitals.com
 * @copyright 2013 NDigital Solutions
 */

/**
 * Plugin Admin class.
 *
 * @package   NDS_WP_Podcasting_Admin
 * @author    Tim Nolte <tim.nolte@ndigitals.com>
 */
class NDS_WP_Podcasting_Admin
{

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = NULL;

    /**
     * Slug of the plugin screen.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    protected $plugin_screen_hook_suffix = NULL;

    /**
     * Initialize the plugin by loading admin scripts & styles and adding a settings page and menu.
     *
     * @since     1.0.0
     */
    private function __construct()
    {

        // Call $plugin_slug from initial plugin class.
        $plugin                 = NDS_WP_Podcasting::get_instance();
        $this->plugin_slug      = $plugin->get_plugin_slug();
        $this->plugin_post_type = $plugin->get_plugin_post_type();

        // Load admin style sheet and JavaScript.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Add the options page and menu item.
        add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

        // Add an action link pointing to the options page.
        $plugin_basename = plugin_basename( NDSWP_PODCASTING_PATH . 'nds-wp-podcasting.php' );
        add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

        // Define custom functionality. Read more about actions and filters: http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
        add_action( 'admin_head', array( $this, 'icons_styles' ) );
        add_action( 'post_updated', array( $this, 'podcasting_do_enclose' ) );
        add_filter( 'manage_daybreak_podcast_posts_columns', array( $this, 'daybreak_podcast_edit_columns' ) );
        add_action( 'manage_posts_custom_column', array( $this, 'daybreak_podcast_custom_columns' ) );
        add_filter( 'manage_edit-daybreak_podcast_sortable_columns', array( $this, 'daybreak_podcast_column_register_sortable' ) );
        add_action( 'restrict_manage_posts', array( $this, 'daybreak_podcast_series_filter_list' ) );
        add_action( 'restrict_manage_posts', array( $this, 'daybreak_podcast_speaker_filter_list' ) );
        add_filter( 'parse_query', array( $this, 'daybreak_podcast_filtering' ) );
        add_action( 'admin_init', array( $this, 'daybreak_podcast_admin_init' ) );
        add_action( 'save_post', array( $this, 'daybreak_save_podcast' ) );
        add_filter( 'post_updated_messages', array( $this, 'daybreak_podcast_updated_messages' ) );
        add_action( 'pre_get_posts', array( $this, 'daybreak_podcast_query' ) );

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
     * Register and enqueue admin-specific style sheet.
     *
     * @since     1.0.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_styles()
    {

        if ( !isset( $this->plugin_screen_hook_suffix ) )
        {
            return;
        }

        $screen = get_current_screen();
        if ( $screen->id == $this->plugin_screen_hook_suffix )
        {
            wp_enqueue_style(
                $this->plugin_slug . '-admin-styles',
                plugins_url( 'css/admin.css', __FILE__ ),
                array(),
                Plugin_Name::VERSION
            );
        }

    }

    /**
     * Register and enqueue admin-specific JavaScript.
     *
     * @since     1.0.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_scripts()
    {

        if ( !isset( $this->plugin_screen_hook_suffix ) )
        {
            return;
        }

        $screen = get_current_screen();
        if ( $screen->id == $this->plugin_screen_hook_suffix )
        {
            wp_enqueue_script(
                $this->plugin_slug . '-admin-script',
                plugins_url( 'js/admin.js', __FILE__ ),
                array( 'jquery' ),
                Plugin_Name::VERSION
            );
        }

    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu()
    {

        /*
         * Add a settings page for this plugin to the Settings menu.
         *
         * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
         *
         *        Administration Menus: http://codex.wordpress.org/Administration_Menus
         */
        $this->plugin_screen_hook_suffix = add_options_page(
            __( 'Podcasting Settings', $this->plugin_slug ),
            __( 'Podcasting Settings', $this->plugin_slug ),
            'manage_options',
            $this->plugin_slug,
            array( $this, 'display_plugin_admin_page' )
        );

    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page()
    {
        include_once( NDSWP_PODCASTING_PATH . 'inc/admin/settings.php' );
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */
    public function add_action_links( $links )
    {

        return array_merge(
            array(
                 'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __(
                         'Settings',
                         $this->plugin_slug
                     ) . '</a>'
            ),
            $links
        );

    }

    /**
     * Define icon styles for the Podcast custom post type
     *
     * @since    1.0.0
     */
    public function icons_styles()
    {
        $menu_post_type_class = '#menu-posts-' . $this->plugin_post_type;
        ?>
        <style type="text/css" media="screen">
            <?php echo $menu_post_type_class; ?> .wp-menu-image {
                background: url(<?php echo NDSWP_PODCASTING_URL, 'assets/images/media-player-cast.png'; ?>) no-repeat 6px -18px !important;
            }

            <?php echo $menu_post_type_class; ?>:hover .wp-menu-image,
            <?php echo $menu_post_type_class; ?>.wp-has-current-submenu .wp-menu-image {
                background-position: 6px 6px !important;
            }

            #icon-edit.icon32-posts-<?php echo $this->plugin_post_type; ?> {
                background: url(<?php echo NDSWP_PODCASTING_URL, 'assets/images/media-player-cast-32x32.png'; ?>) no-repeat;
            }
        </style>
    <?php
    }

    /**
     * Setup Admin Podcast Listing Headers
     *
     * @since    1.0.0
     */
    public function daybreak_podcast_edit_columns( $columns )
    {
        $columns = array(
            "cb"                  => "<input type=\"checkbox\" />",
            "title"               => "Title",
            "podcast_series_fmt"  => "Series",
            "podcast_speaker_fmt" => "Speaker",
            "podcast_tags_fmt"    => "Tags",
            "date"                => "Date"
        );

        return $columns;
    }


    /**
     * Setup Admin Podcast Listing Item Formats
     *
     * @since    1.0.0
     */
    public function daybreak_podcast_custom_columns( $column )
    {
        global $post;
        $custom = get_post_custom();

        switch ( $column )
        {
            case "podcast_series_fmt":
                // - show taxonomy terms -
                $podcast_series      = get_the_terms( $post->ID, "daybreak_podcast_series" );
                $podcast_series_html = array();
                if ( $podcast_series )
                {
                    foreach ( $podcast_series as $podcast_series_single )
                    {
                        array_push( $podcast_series_html, $podcast_series_single->name );
                    }
                    echo implode( $podcast_series_html, ", " );
                }
                else
                {
                    _e( 'None' );
                }
                break;
            case "podcast_speaker_fmt":
                // - show taxonomy terms -
                $podcast_speakers      = get_the_terms( $post->ID, "daybreak_podcast_speaker" );
                $podcast_speakers_html = array();
                if ( $podcast_speakers )
                {
                    foreach ( $podcast_speakers as $podcast_speaker )
                    {
                        array_push( $podcast_speakers_html, $podcast_speaker->name );
                    }
                    echo implode( $podcast_speakers_html, ", " );
                }
                else
                {
                    _e( 'None' );
                }
                break;
            case "podcast_tags_fmt":
                // - show taxonomy terms -
                $podcast_tags      = get_the_terms( $post->ID, "daybreak_podcast_tag" );
                $podcast_tags_html = array();
                if ( $podcast_tags )
                {
                    foreach ( $podcast_tags as $podcast_tag )
                    {
                        array_push(
                            $podcast_tags_html,
                            '<a href="edit.php?post_type=daybreak_podcast&taxonomy=daybreak_podcast_tag&term=' . $podcast_tag->slug . '">' . $podcast_tag->name . '</a>'
                        );
                    }
                    echo implode( $podcast_tags_html, ", " );
                }
                else
                {
                    _e( 'None' );
                }
                break;
        }
    }


    /**
     * Setup which columns are sortable.
     *
     * @since    1.0.0
     */
    public function daybreak_podcast_column_register_sortable( $columns )
    {
        $columns['podcast_series_fmt']  = 'daybreak_podcast_series';
        $columns['podcast_speaker_fmt'] = 'daybreak_podcast_speaker';

        return $columns;
    }


    /**
     * Setup a podcast series filtering list.
     *
     * @since    1.0.0
     */
    public function daybreak_podcast_series_filter_list()
    {
        $screen = get_current_screen();
        global $wp_query;
        if ( is_admin() && $screen->post_type == 'daybreak_podcast' )
        {
            wp_dropdown_categories(
                array(
                     'show_option_all' => 'Show All Series',
                     'taxonomy'        => 'daybreak_podcast_series',
                     'name'            => 'daybreak_podcast_series',
                     'orderby'         => 'name',
                     'selected'        => ( isset( $wp_query->query['daybreak_podcast_series'] ) ? $wp_query->query['daybreak_podcast_series'] : '' ),
                     'hierarchical'    => TRUE,
                     'depth'           => 3,
                     'show_count'      => TRUE,
                     'hide_empty'      => TRUE
                )
            );
        }
    }


    /**
     * Setup a podcast speaker filtering list.
     *
     * @since    1.0.0
     */
    public function daybreak_podcast_speaker_filter_list()
    {
        $screen = get_current_screen();
        global $wp_query;
        if ( is_admin() && $screen->post_type == 'daybreak_podcast' )
        {
            wp_dropdown_categories(
                array(
                     'show_option_all' => 'Show All Speakers',
                     'taxonomy'        => 'daybreak_podcast_speaker',
                     'name'            => 'daybreak_podcast_speaker',
                     'orderby'         => 'name',
                     'selected'        => ( isset( $wp_query->query['daybreak_podcast_speaker'] ) ? $wp_query->query['daybreak_podcast_speaker'] : '' ),
                     'hierarchical'    => TRUE,
                     'depth'           => 3,
                     'show_count'      => TRUE,
                     'hide_empty'      => TRUE
                )
            );
        }
    }


    /**
     * Setup custom filtering for podcast episodes.
     *
     * @since    1.0.0
     */
    public function daybreak_podcast_filtering( $query )
    {
        $query_vars = & $query->query_vars;

        if ( ( $query_vars['daybreak_podcast_series'] ) && is_numeric( $query_vars['daybreak_podcast_series'] ) )
        {
            $term                                  = get_term_by(
                'id',
                $query_vars['daybreak_podcast_series'],
                'daybreak_podcast_series'
            );
            $query_vars['daybreak_podcast_series'] = $term->slug;
        }
        if ( ( $query_vars['daybreak_podcast_speaker'] ) && is_numeric( $query_vars['daybreak_podcast_speaker'] ) )
        {
            $term                                   = get_term_by(
                'id',
                $query_vars['daybreak_podcast_speaker'],
                'daybreak_podcast_speaker'
            );
            $query_vars['daybreak_podcast_speaker'] = $term->slug;
        }
    }


    /**
     * Setup the custom Podcast details meta box
     *
     * @since    1.0.0
     */
    public function daybreak_podcast_details_meta()
    {
        $add_audio_title = 'Add Audio';
        ?>
        <input type="hidden" name="daybreak-podcast-nonce" id="daybreak-podcast-nonce"
               value="<?php echo wp_create_nonce( 'daybreak-podcast-nonce' ); ?>"/>
        <ul class="daybreak-podcast-meta clearfix">
            <li class="clearfix">
                <label>Audio URL: </label>
                <input type="text" size="70" name="daybreak_podcast_audio" id="daybreak_podcast_audio"
                       value="<?php echo get_podcast_field( "daybreak_podcast_audio" ); ?>"/>
                <a href="#" id="podcast_upload_audio_button" class="button"
                   title="<?php esc_html_e( $add_audio_title ); ?>"><?php esc_html_e( $add_audio_title ); ?></a>
            </li>
            <li class="clearfix">
                <label>Video Embed URL: </label>
                <input type="text" size="70" name="daybreak_podcast_video" id="daybreak_podcast_video"
                       value="<?php echo get_podcast_field( "daybreak_podcast_video" ); ?>"/> <em>(optional)</em>
            </li>
            <li class="clearfix">
                <label>Notes URL: </label>
                <input type="text" size="70" name="daybreak_podcast_notes" id="daybreak_podcast_notes"
                       value="<?php echo get_podcast_field( "daybreak_podcast_notes" ); ?>"/> <em>(optional)</em>
            </li>
        </ul>
        <style type="text/css">
            .daybreak-podcast-meta {
                margin: 0 0 24px;
            }

            .daybreak-podcast-meta li {
                clear: left;
                vertical-align: middle;
            }

            .daybreak-podcast-meta label, .daybreak-podcast-meta input, .daybreak-podcast-meta em {
                float: left;
            }

            .daybreak-podcast-meta label, .daybreak-podcast-meta em {
                width: 120px;
                padding: 5px 0 0 0;
            }

            .daybreak-podcast-meta input {
                margin-right: 4px;
            }

            .daybreak-podcast-meta em {
                color: gray;
            }
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                var custom_uploader;

                $('#podcast_upload_audio_button').click(function (e) {

                    e.preventDefault();

                    //If the uploader object has already been created, reopen the dialog
                    if (custom_uploader) {
                        custom_uploader.open();
                        return;
                    }

                    //Extend the wp.media object
                    custom_uploader = wp.media.frames.file_frame = wp.media({
                        title: 'Choose Audio File',
                        button: { text: 'Choose Audio File' },
                        multiple: false,
                        library: { type: 'audio' }
                    });

                    //When a file is selected, grab the URL and set it as the text field's value
                    custom_uploader.on('select', function () {
                        attachment = custom_uploader.state().get('selection').first().toJSON();
                        $('#daybreak_podcast_audio').val(attachment.url);
                    });

                    //Open the uploader dialog
                    custom_uploader.open();

                });
            });
        </script>
    <?php
    }

    public function daybreak_podcast_admin_init()
    {
        add_meta_box(
            "podcast_meta",
            "Podcast Details",
            "daybreak_podcast_details_meta",
            "daybreak_podcast",
            "normal",
            "high"
        );
    }


    /**
     * Save Podcast meta box data.
     *
     * @since    1.0.0
     */
    public function daybreak_save_podcast()
    {
        global $post;

        // - still require nonce
        if ( !wp_verify_nonce( $_POST['daybreak-podcast-nonce'], 'daybreak-podcast-nonce' ) )
        {
            return $post->ID;
        }

        if ( !current_user_can( 'edit_post', $post->ID ) )
        {
            return $post->ID;
        }

        if ( isset( $_POST["daybreak_podcast_audio"] ) )
        {
            update_post_meta(
                $post->ID,
                "daybreak_podcast_audio",
                sanitize_text_field( $_POST["daybreak_podcast_audio"] )
            );
        }

        if ( isset( $_POST["daybreak_podcast_video"] ) )
        {
            update_post_meta(
                $post->ID,
                "daybreak_podcast_video",
                sanitize_text_field( $_POST["daybreak_podcast_video"] )
            );
        }

        if ( isset( $_POST["daybreak_podcast_notes"] ) )
        {
            update_post_meta(
                $post->ID,
                "daybreak_podcast_notes",
                sanitize_text_field( $_POST["daybreak_podcast_notes"] )
            );
        }
    }


    /**
     * Setup nice admin ui messages.
     *
     * @since    1.0.0
     */
    public function daybreak_podcast_updated_messages( $messages )
    {
        global $post, $post_ID;

        $messages['daybreak_podcast'] = array(
            0  => '', // Unused. Messages start at index 1.
            1  => sprintf(
                __( 'Podcast episode updated. <a href="%s">View item</a>' ),
                esc_url( get_permalink( $post_ID ) )
            ),
            2  => __( 'Custom field updated.' ),
            3  => __( 'Custom field deleted.' ),
            4  => __( 'Podcast episode updated.' ),
            /* translators: %s: date and time of the revision */
            5  => isset( $_GET['revision'] ) ? sprintf(
                    __( 'Podcast episode restored to revision from %s' ),
                    wp_post_revision_title( (int)$_GET['revision'], FALSE )
                ) : FALSE,
            6  => sprintf(
                __( 'Podcast episode published. <a href="%s">View epidoes</a>' ),
                esc_url( get_permalink( $post_ID ) )
            ),
            7  => __( 'Podcast episode saved.' ),
            8  => sprintf(
                __( 'Podcast episode submitted. <a target="_blank" href="%s">Preview episode</a>' ),
                esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
            ),
            9  => sprintf(
                __(
                    'Podcast episode scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview episode</a>'
                ),
                // translators: Publish box date format, see http://php.net/date
                date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ),
                esc_url( get_permalink( $post_ID ) )
            ),
            10 => sprintf(
                __( 'Podcast episode draft updated. <a target="_blank" href="%s">Preview episode</a>' ),
                esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
            ),
        );

        return $messages;
    }


    /**
     * Customize Podcast Query using Post Meta
     *
     * @since    1.0.0
     *
     * @param object $query data
     */
    public function daybreak_podcast_query( $query )
    {
        // http://codex.wordpress.org/Function_Reference/current_time
        // $current_time = current_time( 'timestamp' );

        global $wp_the_query;

        // Admin Listing
        if ( $wp_the_query === $query && is_admin() && is_post_type_archive( 'daybreak_podcast' ) )
        {
            $query->set( 'orderby', 'date' );
            $query->set( 'order', 'DESC' );
        }

        // Non-Admin Listing
        /*if ( $wp_the_query === $query && !is_admin() && is_post_type_archive( 'daybreak_podcast' ) ) {
    		$meta_query = array(
    			array(
    				'key' => 'daybreak_event_end_date',
    				'value' => $current_time,
    				'compare' => '>'
    			)
    		);
    		$query->set( 'meta_query', $meta_query );
    		$query->set( 'orderby', 'meta_value_num' );
    		$query->set( 'meta_key', 'daybreak_event_start_date' );
    		$query->set( 'order', 'ASC' );
    	}*/

    }

}