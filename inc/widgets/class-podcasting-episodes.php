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
        echo __( 'Hello, World!', 'text_domain' );
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