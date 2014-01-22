<?php
/**
 * NDS WordPress Podcasting.
 *
 * @package   NDS_WordPress_Podcasting\Widgets\Episodes
 * @author    Tim Nolte <tim.nolte@ndigitals.com>
 * @license   GPL-2.0+
 * @link      http://www.ndigitals.com
 * @copyright 2013 NDigital Solutions
 */


/**
 * Adds NDS_WP_Podcasting_Episodes_Widget widget.
 */
class NDS_WP_Podcasting_Episodes_Widget extends WP_Widget
{
    /**
     * Widget template file name part. Should be prepended with
     * the plugin_slug when used to find/load the widget template.
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $template_filename_base = 'episodes-widget';

    /**
     * Register widget with WordPress.
     */
    function __construct()
    {

        // Call $plugin_slug from initial plugin class.
        $plugin                 = NDS_WP_Podcasting::get_instance();
        $this->plugin_slug      = $plugin->get_plugin_slug();
        $this->plugin_post_type = $plugin->get_plugin_post_type();
        // Defining a class attribute for the widget template filename
        $this->widget_template  = $this->plugin_slug . '-' .$this->template_filename_base . '.php';

        parent::__construct(
              $this->plugin_slug . '_episode_widget', // Base ID
              'Podcast Episode', // Name
              array( 'description' => __( 'Podcast Episode Widget', 'text_domain' ), ) // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance )
    {
        $title = apply_filters( 'widget_title', $instance['title'] );

        echo $args['before_widget'];
        if ( !empty( $title ) )
        {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $episodes = NDS_WP_Podcasting::get_latest_episode();
        Chromephp::log($episodes);

        if ( $episodes->have_posts() )
        {
            while ( $episodes->have_posts() )
            {
                $post = $episodes->the_post();
                Chromephp::log($post);

                $date_format    = get_option( 'date_format' );
                $podcast_series = get_the_term_list( $post->ID, 'nds_wp_podcast_series' );
                $speaker_list   = wp_get_post_terms( $post->ID, 'nds_wp_podcast_speaker' );
                $podcast_audio  = get_post_meta( $post->ID, 'nds_wp_podcast_audio', TRUE );
                $podcast_video  = get_post_meta( $post->ID, 'nds_wp_podcast_video', TRUE );
                $podcast_notes  = get_post_meta( $post->ID, 'nds_wp_podcast_notes', TRUE );
                // Check for, then use, images from the following sources; episode featured image -> speaker -> series
                $podcast_image = NDS_WP_Podcasting::get_episode_image( $post->ID, 'podcast' );
                if (!$podcast_image)
                {
                    $podcast_image = NDS_WP_Podcasting::get_speaker_image( $post->ID, 'podcast' );
                    if (!$podcast_image)
                    {
                        $podcast_image = NDS_WP_Podcasting::get_series_image( $post->ID, 'podcast' );
                    }
                }

                if ( $overridden_template = locate_template( $this->widget_template ) ) {
                    // locate_template() returns path to file
                    // if either the child theme or the parent theme have overridden the template
                    load_template( $overridden_template );
                } else {
                    // If neither the child nor parent theme have overridden the template,
                    // we load the template from the 'templates' sub-directory of the plugin directory
                    load_template( NDSWP_PODCASTING_PATH . 'templates/' . $this->widget_template );
                }
            }
        }

        // Reset Post Data
        wp_reset_postdata();

        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance )
    {
        if ( isset( $instance['title'] ) )
        {
            $title = $instance['title'];
        }
        else
        {
            $title = __( 'New title', 'text_domain' );
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>"/>
        </p>
    <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance )
    {
        $instance          = array();
        $instance['title'] = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

        return $instance;
    }

} // class NDS_WP_Podcasting_Episodes_Widget