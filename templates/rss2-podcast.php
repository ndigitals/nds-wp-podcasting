<?php
/**
 * RSS2 Feed Template for displaying RSS2 Posts feed.
 *
 * @package   NDS_WordPress_Podcasting\Feeds
 */

header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
$more = 1;

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<rss version="2.0"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:wfw="http://wellformedweb.org/CommentAPI/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
     xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
    <?php
    /**
     * Fires at the end of the RSS root to add namespaces.
     *
     * @since 2.0.0
     */
    do_action( 'podcast_ns' );
    ?>
    >

    <channel>
        <title><?php bloginfo_rss('name'); ?>: Weekly Message Podcast</title>
        <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
        <link><?php bloginfo_rss('url') ?></link>
        <description><?php bloginfo_rss("description") ?></description>
        <lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
        <language><?php bloginfo_rss( 'language' ); ?></language>
        <?php
        $duration = 'hourly';
        /**
         * Filter how often to update the RSS feed.
         *
         * @since 2.1.0
         *
         * @param string $duration The update period.
         *                         Default 'hourly'. Accepts 'hourly', 'daily', 'weekly', 'monthly', 'yearly'.
         */
        ?>
        <sy:updatePeriod><?php echo apply_filters( 'rss_update_period', $duration ); ?></sy:updatePeriod>
        <?php
        $frequency = '1';
        /**
         * Filter the RSS update frequency.
         *
         * @since 2.1.0
         *
         * @param string $frequency An integer passed as a string representing the frequency
         *                          of RSS updates within the update period. Default '1'.
         */
        ?>
        <sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', $frequency ); ?></sy:updateFrequency>
        <?php
        /**
         * Fires at the end of the RSS2 Feed Header.
         *
         * @since 2.0.0
         */
        do_action( 'podcast_head');

        while( $posts->have_posts()) : $posts->the_post();
            $series_title = '';
            $series_list  = wp_get_post_terms( $post->ID, 'nds_wp_podcast_series' );
            if ( $series_list )
            {
                foreach ( $series_list as $series )
                {
                    $series_title = $series->name;;
                    break;
                }
            }
            ?>
            <item>
                <title><?php echo $series_title; ?>: <?php the_title_rss() ?></title>
                <link><?php the_permalink_rss() ?></link>
                <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
                <dc:creator><![CDATA[<?php the_author() ?>]]></dc:creator>
                <?php the_category_rss('rss2') ?>

                <guid isPermaLink="false"><?php the_guid(); ?></guid>
                <?php if (get_option('rss_use_excerpt')) : ?>
                    <description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
                <?php else : ?>
                    <description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
                    <?php $content = get_the_content_feed('rss2'); ?>
                    <?php if ( strlen( $content ) > 0 ) : ?>
                        <content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded>
                    <?php else : ?>
                        <content:encoded><![CDATA[<?php the_excerpt_rss(); ?>]]></content:encoded>
                    <?php endif; ?>
                <?php endif; ?>
                <?php
                /**
                 * Fires at the end of each RSS2 feed item.
                 *
                 * @since 2.0.0
                 */
                do_action( 'podcast_item' );
                ?>
            </item>
        <?php endwhile; ?>
    </channel>
</rss>
