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
        $this->plugin           = NDS_WP_Podcasting::get_instance();
        $this->plugin_slug      = $this->plugin->get_plugin_slug();
        $this->plugin_post_type = $this->plugin->get_plugin_post_type();
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
        add_feed( $this->custom_feed_type, array( $this, 'get_feed_content' ) );
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
        $posts                  = $this->plugin->get_latest_episodes( $opt_feed_episode_count );

        if ($overridden_template = locate_template( $this->feed_template )) {
            // locate_template() returns path to file
            // if either the child theme or the parent theme have overridden the template
            load_template( $overridden_template );
        } else {
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
        <itunes:explicit>no</itunes:explicit>
        <itunes:owner>
            <itunes:name>Daybreak Church</itunes:name>
            <itunes:email>webmaster@daybreak.tv</itunes:email>
        </itunes:owner>
        <itunes:image href="http://www.daybreak.tv/uploads/gallery/podcast/daybreak_300x300.png"/>
        <itunes:category text="Religion &amp; Spirituality">
            <itunes:category text="Christianity"/>
        </itunes:category>
    <?php
    }

    /**
     * Add iTunes specific RSS feed elements
     *
     * TODO: Need to find a non-Daybreak/generic source for subtitle.
     *
     * @since     1.0.0
     */
    public function itunes_attached_audio()
    {

        global $post;

        $podcast_audio = $this->plugin->get_podcast_field( $this->plugin_post_type . '_audio' );

        if ( $podcast_audio )
        {
            // Check for then use images from the following sources; episode featured image -> series -> speaker
            $podcast_image = $this->plugin->get_episode_image( $post->ID );
            if ( !$podcast_image )
            {
                $podcast_image = $this->plugin->get_series_image( $post->ID );
                if ( !$podcast_image )
                {
                    $podcast_image = $this->plugin->get_speaker_image( $post->ID );
                }
            }

            $series_title = '';
            $series_list  = wp_get_post_terms( $post->ID, $this->plugin_post_type . '_series' );
            if ( $series_list )
            {
                foreach ( $series_list as $series )
                {
                    $series_title = $series->name;;
                    break;
                }
            }

            $author       = array();
            $speaker_list = wp_get_post_terms( $post->ID, $this->plugin_post_type . '_speaker' );
            if ( $speaker_list )
            {
                foreach ( $speaker_list as $speaker )
                {
                    $author[] = $speaker->name;
                }
            }

            // use the post tags for itunes:keywords
            $itunes_keywords     = array();
            $podcast_episode_tags = wp_get_post_terms( $post->ID, $this->plugin_post_type . '_tag' );
            if ( $podcast_episode_tags )
            {
                foreach ( $podcast_episode_tags as $tag )
                {
                    $itunes_keywords[] = $tag->name;
                }
            }

            $audio_metadata = wp_get_attachment_metadata($podcast_audio);
            if (class_exists('ChromePhp')) { ChromePhp::log($audio_metadata); }

            $filesize     = isset($audio_metadata['filesize']) ? $audio_metadata['filesize'] : 0;
            $content_type = isset($audio_metadata['mime_type']) ? $audio_metadata['mime_type'] : 'audio/mpeg';
            $duration = isset($audio_metadata['length_formatted']) ? $audio_metadata['length_formatted'] : '00:00';
            ?>
            <itunes:author><?php echo implode(', ', $author); ?></itunes:author>
            <itunes:subtitle><?php echo 'Message by ', implode(', ', $author); ?></itunes:subtitle>
            <itunes:summary><?php echo $post->post_excerpt; ?></itunes:summary>
            <itunes:image href="<?php echo $podcast_image[0]; ?>"/>
            <enclosure url="<?php echo wp_get_attachment_url($podcast_audio); ?>" length="<?php echo $filesize; ?>" type="<?php echo $content_type; ?>"/>
            <guid><?php the_permalink(); ?></guid>
            <itunes:duration><?php echo $duration; ?></itunes:duration>
            <itunes:keywords><?php echo implode( ',', $itunes_keywords ); ?></itunes:keywords>
        <?php
        }

    }
}