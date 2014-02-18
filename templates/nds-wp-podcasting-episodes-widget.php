<?php
/**
 * Created by PhpStorm.
 * User: timnolte
 * Date: 1/18/14
 * Time: 3:03 PM
 */

$plugin = NDS_WP_Podcasting::get_instance();
$date_format = get_option( 'date_format' );
$css_post_id = 'post-' . $post->ID;
$podcast_series = get_the_term_list( $post->ID, 'nds_wp_podcast_series' );
$speaker_list = wp_get_post_terms( $post->ID, 'nds_wp_podcast_speaker' );
$podcast_audio = get_post_meta( $post->ID, 'nds_wp_podcast_audio', true );
$podcast_video = get_post_meta( $post->ID, 'nds_wp_podcast_video', true );
$podcast_notes = get_post_meta( $post->ID, 'nds_wp_podcast_notes', true );
// Check for, then use, images from the following sources; episode featured image -> speaker -> series
$podcast_image = $plugin->get_speaker_image( $post->ID, 'podcast-small' );
if (!$podcast_image)
{
    $podcast_image = $plugin->get_episode_image( $post->ID, 'podcast-small' );
    if (!$podcast_image)
    {
        $podcast_image = $plugin->get_series_image( $post->ID, 'podcast-small' );
    }
}
?>
<section id="<?php echo $css_post_id; ?>" <?php post_class("section columns-12 left nds_wp_podcast_widget clearfix"); ?>>
    <a name="<?php echo $css_post_id; ?>"></a>
    <?php
    if ( $podcast_image ) : ?>
        <img src="<?php echo $podcast_image[0]; ?>"
             class="podcast-featured-img"
             alt=""
             width="<?php echo $podcast_image[1]; ?>"
             height="<?php echo $podcast_image[2]; ?>"/>
    <?php endif; ?>
    <!--<a href="#" class="img-link" title="image3"><img src="img/wes.jpg" align="left" alt="image3" width="220" height="220"/></a>-->
    <h5><?php the_title( $podcast_series . ': <span class="small-visible-inline large-hidden"><br/></span>' ); ?></h5>
    <em><?php echo get_the_term_list(
            $post->ID,
            'nds_wp_podcast_speaker',
            ''
        ); ?></em>
    <p>
        <?php the_content(); ?>
    </p>
    <?php if ( strlen( $podcast_audio ) > 0 ) : ?>
        <div class="podcast-audio-embed columns-12">
            <?php echo do_shortcode(
                '[audio mp3="' . $podcast_audio . '"][/audio]'
            ); ?>
        </div>
    <?php endif;
    if ( strlen( $podcast_video ) > 0 ) : ?>
        <div class="podcast-video-embed embed-youtube columns-12">
            <?php echo wp_oembed_get( $podcast_video ); ?>
        </div>
    <?php endif; ?>
    <ul class="podcast-episodes-widget-links">
        <?php if ( strlen( $podcast_audio ) > 0 ) : ?>
            <li>
                <a href="#<?php echo $css_post_id; ?>" onclick="jQuery('#<?php echo $css_post_id; ?> .podcast-video-embed').slideUp();jQuery('#<?php echo $css_post_id; ?> .podcast-audio-embed').slideToggle();"><span class="icon-headphones"></span> Listen</a>
            </li>
        <?php endif; ?>
        <?php if ( strlen( $podcast_video ) > 0 ) : ?>
            <li>
                <a href="#<?php echo $css_post_id; ?>" onclick="jQuery('#<?php echo $css_post_id; ?> .podcast-audio-embed').slideUp();jQuery('#<?php echo $css_post_id; ?> .podcast-video-embed').slideToggle();"><span class="icon-play"></span> Watch</a>
            </li>
        <?php endif; ?>
        <?php if ( strlen( $podcast_audio ) > 0 ) : ?>
            <li>
                <a href="<?php echo $podcast_audio; ?>" target="_blank"><span class="icon-cloud-download"></span> Download</a>
            </li>
        <?php endif; ?>
        <?php if ( strlen( $podcast_notes ) > 0 ) : ?>
            <li>
                <a href="<?php echo $podcast_notes; ?>" target="_blank"><span class="icon-notebook"></span> Notes</a>
            </li>
        <?php endif; ?>
    </ul>
</section>
<style>
    .nds_wp_podcast_widget a {
        text-decoration: none;
    }
    .podcast-episodes-widget-links {
        clear: both;
        list-style: none;
        margin: 0;
    }
    .podcast-episodes-widget-links li {
        float: left;
        font-size: 1.5em;
        margin: 4px 8px;
    }
</style>