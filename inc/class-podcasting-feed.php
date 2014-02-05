<?php
/**
 * NDS WordPress Podcasting.
 *
 * @package   NDS_WordPress_Podcasting\Feeds
 * @author    Tim Nolte <tim.nolte@ndigitals.com>
 * @license   GPL-2.0+
 * @link      http://www.ndigitals.com
 * @copyright 2013 NDigital Solutions
 */


/**
 * Adds NDS_WP_Podcasting_Feed custom RSS feed.
 *
 * @package   NDS_WP_Podcasting_Feed
 * @author    Tim Nolte <tim.nolte@ndigitals.com>
 */
class NDS_WP_Podcasting_Feed
{

    /**
     * Unique identifier for the WordPress core feed type
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $base_feed_type = 'rss2';

    /**
     * Unique identifier for the custom feed type
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $custom_feed_type = 'podcast';

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = NULL;

    /**
     * Initialize the plugin by loading admin scripts & styles and adding a settings page and menu.
     *
     * @since     1.0.0
     */
    private function __construct()
    {

        global $wp_version;

        // Call $plugin_slug from initial plugin class.
        $plugin                 = NDS_WP_Podcasting::get_instance();
        $this->plugin_slug      = $plugin->get_plugin_slug();
        $this->plugin_post_type = $plugin->get_plugin_post_type();
        // Defining a class attribute for the feed template filename
        $this->feed_template = $this->base_feed_type . '-' . $this->custom_feed_type . '.php';

        add_action( 'init', array( $this, 'add_podcast_feed' ) );
        add_filter( 'podcast_ns', array( $this, 'itunes_namespace' ) );
        add_filter( 'podcast_head', array( $this, 'itunes_head' ) );
        add_filter( 'podcast_item', array( $this, 'itunes_attached_audio' ) );

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
     * Method to register the new podcast specific feed.
     *
     * @since     1.0.0
     */
    public function add_podcast_feed()
    {
        add_feed( 'feed/' . $this->custom_feed_type, array( $this, 'get_feed_content' ) );
    }

    /**
     * Method to return the podcast feed content in RSS 2.0 XML.
     *
     * TODO: Need a plugin option for specifying the number of posts to include in the feed
     *
     * @since     1.0.0
     */
    public function get_feed_content()
    {
        global $posts;
        $opt_feed_episode_count = 10;
        $posts               = NDS_WP_Podcasting::get_latest_episodes( $opt_feed_episode_count );

        if ( $overridden_template = locate_template( $this->feed_template ) )
        {
            // locate_template() returns path to file
            // if either the child theme or the parent theme have overridden the template
            load_template( $overridden_template );
        }
        else
        {
            // If neither the child nor parent theme have overridden the template,
            // we load the template from the 'templates' sub-directory of the plugin directory
            load_template( NDSWP_PODCASTING_PATH . 'templates/' . $this->feed_template );
        }

        // Reset Post Data
        wp_reset_postdata();
    }

    /**
     * Add iTunes Namespace
     *
     * @since     1.0.0
     */
    public function itunes_namespace() {
        echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"';
    }

    /**
     * Add iTunes Header Metadata
     *
     * TODO: Setup plugins options for these values
     *
     * @since     1.0.0
     */
    public function itunes_head()
    {
        ?>
        <itunes:subtitle></itunes:subtitle>
        <itunes:author>Daybreak Church</itunes:author>
        <itunes:summary>Weekly messages from Pastor Wes Dupin and guest speakers.</itunes:summary>
        <itunes:owner>
            <itunes:name>Daybreak Church</itunes:name>
            <itunes:email>webmaster@daybreak.tv</itunes:email>
        </itunes:owner>
        <itunes:image href="http://example.com/podcasts/everything/AllAboutEverything.jpg"/>
        <itunes:category text="Technology">
            <itunes:category text="Gadgets"/>
        </itunes:category>
    <?php
    }

    /**
     * Add iTunes specific RSS feed elements
     *
     * TODO: Change item author to speaker (not sure if we need an email address).
     *
     * @since     1.0.0
     */
    public function itunes_attached_audio()
    {

        global $post;

        $podcast_audio = NDS_WP_Podcasting::get_podcast_field( 'nds_wp_podcast_audio' );

        if ( $podcast_audio )
        {
            // Check for then use images from the following sources; episode featured image -> series -> speaker
            $podcast_image = NDS_WP_Podcasting::get_episode_image( $post->ID );
            if ( !$podcast_image )
            {
                $podcast_image = NDS_WP_Podcasting::get_series_image( $post->ID );
                if ( !$podcast_image )
                {
                    $podcast_image = NDS_WP_Podcasting::get_speaker_image( $post->ID );
                }
            }

            // use the post tags for itunes:keywords
            $itunes_keywords     = array();
            $itunes_keywords_arr = get_the_tags();
            if ( $itunes_keywords_arr )
            {
                foreach ( $itunes_keywords_arr as $tag )
                {
                    $itunes_keywords[] = $tag->name;
                }
            }

            $headers  = get_headers( $podcast_audio, 1 );
            $filesize = $headers['Content-Length'];
            ?>
            <itunes:author><?php echo get_the_author(); ?></itunes:author>
            <itunes:subtitle><?php echo $post->post_title; ?></itunes:subtitle>
            <itunes:summary><?php echo $post->post_excerpt; ?></itunes:summary>
            <itunes:image href="<?php echo $podcast_image[0]; ?>"/>
            <enclosure url="<?php echo $podcast_audio; ?>" length="<?php echo $filesize; ?>" type="<?php echo $att->post_mime_type; ?>"/>
            <guid><?php the_permalink(); ?></guid>
            <itunes:duration><?php echo $post->post_content; ?></itunes:duration>
            <itunes:keywords><?php echo implode( ',', $itunes_keywords ); ?></itunes:keywords>
        <?php
        }

    }
}