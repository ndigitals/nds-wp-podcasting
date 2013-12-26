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
 */
class NDS_WP_Podcasting_Feed extends WP_Widget
{

    /**
     * Register widget with WordPress.
     */
    function __construct()
    {

        // Call $plugin_slug from initial plugin class.
        $plugin                 = NDS_WP_Podcasting::get_instance();
        $this->plugin_slug      = $plugin->get_plugin_slug();
        $this->plugin_post_type = $plugin->get_plugin_post_type();

        parent::__construct(
              $this->plugin_slug . '_episode_widget', // Base ID
              'Podcast Episode', // Name
              array( 'description' => __( 'Podcast Episode Widget', 'text_domain' ), ) // Args
        );
    }

    add_filter( 'rss2_ns', 'itunes_namespace' );
     
    // Add namespace
    function itunes_namespace() {
        echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"';
    }
    
    add_filter( 'rss2_head', 'itunes_head' );
     
    function itunes_head() {
        ?>
        <itunes:subtitle>A show about everything</itunes:subtitle>
        <itunes:author>John Doe</itunes:author>
        <itunes:summary>All About Everything is a show about everything...</itunes:summary>
        <itunes:owner>
            <itunes:name>John Doe</itunes:name>
            <itunes:email>john.doe@example.com<script type="text/javascript">
    /* <![CDATA[ */
    (function(){try{var s,a,i,j,r,c,l,b=document.getElementsByTagName("script");l=b[b.length-1].previousSibling;a=l.getAttribute('data-cfemail');if(a){s='';r=parseInt(a.substr(0,2),16);for(j=2;a.length-j;j+=2){c=parseInt(a.substr(j,2),16)^r;s+=String.fromCharCode(c);}s=document.createTextNode(s);l.parentNode.replaceChild(s,l);}}catch(e){}})();
    /* ]]> */
    </script></itunes:email>
        </itunes:owner>
        <itunes:image href="http://example.com/podcasts/everything/AllAboutEverything.jpg" />
        <itunes:category text="Technology">
            <itunes:category text="Gadgets"/>
        </itunes:category>
        <?php
    }
    
    function itunes_attached_audio() {
        global $post;
        $attachments = get_posts( array(
            'post_type' => 'attachment',
            'post_mime_type' => 'audio', // if you use videos, change here
            'posts_per_page' => -1,
            'post_parent' => $post->ID,
            'exclude' => get_post_thumbnail_id()
        ) );
     
        // use the post tags for itunes:keywords
        $itunes_keywords_arr = get_the_tags();
        if ( $itunes_keywords_arr ) {
            foreach( $itunes_keywords_arr as $tag ) {
                $itunes_keywords .= $tag->name . ',';
            }
            $itunes_keywords = substr_replace( trim( $itunes_keywords ), '', -1 );
        }
     
        // use the post thumb for itunes:image
        $post_thumbnail_id = get_post_thumbnail_id( $post->ID );
        $itunes_image_arr = wp_get_attachment_image_src( $post_thumbnail_id, 'itunes-cover' );
     
        if ( $attachments ) {
            foreach ( $attachments as $att ) {
                $audio_url = wp_get_attachment_url( $att->ID );
                $parts = explode( '|', $att->post_content );
                $headers = get_headers( $audio_url, 1 );
                $filesize = $headers['Content-Length'];
                ?>
                <itunes:author><?php echo get_the_author(); ?></itunes:author>
                <itunes:subtitle><?php echo $att->post_title; ?></itunes:subtitle>
                <itunes:summary><?php echo $att->post_excerpt; ?></itunes:summary>
                <itunes:image href="<?php echo $itunes_image_arr[0]; ?>" />
                <enclosure url="<?php echo $audio_url; ?>" length="<?php echo $filesize; ?>" type="<?php echo $att->post_mime_type; ?>" />
                <guid><?php the_permalink(); ?></guid>
                <itunes:duration><?php echo $att->post_content; ?></itunes:duration>
                <itunes:keywords><?php echo $itunes_keywords; ?></itunes:keywords>
                <?php
            }
        }
    }
}