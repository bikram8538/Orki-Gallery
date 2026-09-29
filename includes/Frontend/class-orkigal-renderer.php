<?php
namespace OrkiGallery\Frontend;

use OrkiGallery\Repository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Renderer {
    private static $assets_registered = false;

    public function __construct() {
        add_shortcode( 'orki_gallery', array( $this, 'shortcode' ) );
    }

    public function shortcode( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0 ), $atts, 'orki_gallery' );
        $gallery = Repository::get( absint( $atts['id'] ) );
        if ( ! $gallery || 'publish' !== $gallery['status'] ) {
            if ( current_user_can( 'edit_posts' ) && $gallery ) {
                return '<div class="orkigal-notice">' . esc_html__( 'This Orki Gallery is still a draft.', 'orki-gallery' ) . '</div>';
            }
            return '';
        }
        return self::render( $gallery['images'], $gallery['settings'], array( 'gallery_id' => $gallery['id'], 'videos' => $gallery['videos'] ) );
    }

    public static function enqueue_assets() {
        if ( ! self::$assets_registered ) {
            self::$assets_registered = true;
            wp_register_style( 'orkigal-frontend', ORKIGAL_URL . 'assets/frontend/frontend.css', array(), ORKIGAL_VERSION );
            wp_register_script( 'orkigal-frontend', ORKIGAL_URL . 'assets/frontend/frontend.js', array(), ORKIGAL_VERSION, true );
        }
        wp_enqueue_style( 'orkigal-frontend' );
        wp_enqueue_script( 'orkigal-frontend' );
    }

    private static function aspect_ratio( $attachment_id ) {
        $meta = wp_get_attachment_metadata( $attachment_id );
        if ( is_array( $meta ) && ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
            return max( 0.2, min( 5, (float) $meta['width'] / (float) $meta['height'] ) );
        }
        return 1.3333;
    }

    private static function image_markup( $attachment_id, $settings, $index ) {
        $full = wp_get_attachment_image_url( $attachment_id, 'full' );
        if ( ! $full ) {
            return '';
        }
        $caption = wp_get_attachment_caption( $attachment_id );
        $alt     = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
        $title   = get_the_title( $attachment_id );
        $label   = $caption ? $caption : $title;
        $attrs   = array(
            'class'    => 'orkigal-image',
            'loading'  => 'lazy',
            'decoding' => 'async',
            'alt'      => $alt ? $alt : $title,
        );
        $image = wp_get_attachment_image( $attachment_id, $settings['image_size'], false, $attrs );
        if ( ! $image ) {
            return '';
        }
        $ratio = self::aspect_ratio( $attachment_id );
        ob_start();
        ?>
        <figure class="orkigal-item<?php echo ( 'slideshow' === $settings['layout'] && 0 === $index ) ? ' is-active' : ''; ?>" data-media-type="image" data-index="<?php echo esc_attr( (string) $index ); ?>" style="--orkigal-ar:<?php echo esc_attr( (string) $ratio ); ?>;">
            <?php if ( ! empty( $settings['lightbox'] ) ) : ?>
                <button type="button" class="orkigal-media-button" data-full="<?php echo esc_url( $full ); ?>" data-caption="<?php echo esc_attr( $label ); ?>" data-lightbox-index="<?php echo esc_attr( (string) $index ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Open image: %s', 'orki-gallery' ), $title ) ); ?>">
                    <?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </button>
            <?php else : ?>
                <div class="orkigal-media-button is-static"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            <?php endif; ?>
            <?php if ( ! empty( $settings['captions'] ) && $label ) : ?>
                <figcaption class="orkigal-caption"><?php echo esc_html( $label ); ?></figcaption>
            <?php endif; ?>
        </figure>
        <?php
        return ob_get_clean();
    }

    private static function video_markup( $attachment_id, $settings, $index ) {
        $video_url = wp_get_attachment_url( $attachment_id );
        if ( ! $video_url ) {
            return '';
        }
        $video_title = get_the_title( $attachment_id );
        $mime        = get_post_mime_type( $attachment_id );
        ob_start();
        ?>
        <figure class="orkigal-item orkigal-video-item<?php echo ( 'slideshow' === $settings['layout'] && 0 === $index ) ? ' is-active' : ''; ?>" data-media-type="video" data-index="<?php echo esc_attr( (string) $index ); ?>">
            <div class="orkigal-video-frame">
                <video class="orkigal-video" controls preload="metadata" playsinline aria-label="<?php echo esc_attr( $video_title ? $video_title : __( 'Gallery video', 'orki-gallery' ) ); ?>">
                    <source src="<?php echo esc_url( $video_url ); ?>"<?php echo $mime ? ' type="' . esc_attr( $mime ) . '"' : ''; ?>>
                    <?php esc_html_e( 'Your browser does not support HTML5 video.', 'orki-gallery' ); ?>
                </video>
            </div>
            <?php if ( ! empty( $settings['captions'] ) && $video_title ) : ?>
                <figcaption class="orkigal-caption"><?php echo esc_html( $video_title ); ?></figcaption>
            <?php endif; ?>
        </figure>
        <?php
        return ob_get_clean();
    }

    public static function render( $image_ids, $settings = array(), $context = array() ) {
        $image_ids = array_values( array_filter( array_map( 'absint', (array) $image_ids ) ) );
        $video_ids = isset( $context['videos'] ) ? array_values( array_filter( array_map( 'absint', (array) $context['videos'] ) ) ) : array();
        if ( empty( $image_ids ) && empty( $video_ids ) ) {
            return '<div class="orkigal-empty">' . esc_html__( 'No media has been added to this gallery yet.', 'orki-gallery' ) . '</div>';
        }

        self::enqueue_assets();
        $settings = Repository::sanitize_settings( wp_parse_args( $settings, Repository::defaults() ) );
        $uid      = 'orkigal-' . wp_unique_id();
        $classes  = array(
            'orkigal-root',
            'orkigal-layout-' . $settings['layout'],
            'orkigal-ratio-' . $settings['ratio'],
            'orkigal-fit-' . $settings['fit'],
        );
        if ( ! empty( $settings['hover_zoom'] ) ) {
            $classes[] = 'orkigal-hover-zoom';
        }

        $style = sprintf(
            '--orkigal-cols:%1$d;--orkigal-cols-tablet:%2$d;--orkigal-cols-mobile:%3$d;--orkigal-gap:%4$dpx;--orkigal-radius:%5$dpx;--orkigal-stage:%6$dpx;--orkigal-transition:%7$dms;',
            absint( $settings['columns_desktop'] ),
            absint( $settings['columns_tablet'] ),
            absint( $settings['columns_mobile'] ),
            absint( $settings['gap'] ),
            absint( $settings['radius'] ),
            absint( $settings['stage_height'] ),
            absint( $settings['transition_speed'] )
        );

        $is_motion = in_array( $settings['layout'], array( 'carousel', 'slideshow' ), true );
        $all_markup = array();
        $index      = 0;
        foreach ( $image_ids as $attachment_id ) {
            $markup = self::image_markup( $attachment_id, $settings, $index );
            if ( $markup ) {
                $all_markup[] = $markup;
                ++$index;
            }
        }
        foreach ( $video_ids as $attachment_id ) {
            $markup = self::video_markup( $attachment_id, $settings, $index );
            if ( $markup ) {
                $all_markup[] = $markup;
                ++$index;
            }
        }

        ob_start();
        ?>
        <div
            id="<?php echo esc_attr( $uid ); ?>"
            class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
            style="<?php echo esc_attr( $style ); ?>"
            data-lightbox="<?php echo esc_attr( (string) $settings['lightbox'] ); ?>"
            data-autoplay="<?php echo esc_attr( (string) $settings['autoplay'] ); ?>"
            data-autoplay-speed="<?php echo esc_attr( (string) $settings['autoplay_speed'] ); ?>"
            data-transition-speed="<?php echo esc_attr( (string) $settings['transition_speed'] ); ?>"
            data-pause-hover="<?php echo esc_attr( (string) $settings['pause_on_hover'] ); ?>"
            data-loop="<?php echo esc_attr( (string) $settings['loop'] ); ?>"
            <?php echo $is_motion ? 'tabindex="0"' : ''; ?>
            aria-label="<?php esc_attr_e( 'Orki Gallery', 'orki-gallery' ); ?>"
        >
            <div class="orkigal-grid" aria-live="polite">
                <?php
                foreach ( $all_markup as $markup ) {
                    echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                ?>
            </div>

            <?php if ( $is_motion ) : ?>
                <div class="orkigal-nav" aria-label="<?php esc_attr_e( 'Gallery navigation', 'orki-gallery' ); ?>">
                    <?php if ( ! empty( $settings['show_arrows'] ) ) : ?>
                        <button type="button" class="orkigal-nav-button orkigal-prev" aria-label="<?php esc_attr_e( 'Previous', 'orki-gallery' ); ?>">‹</button>
                    <?php endif; ?>
                    <?php if ( ! empty( $settings['show_dots'] ) ) : ?><div class="orkigal-dots"></div><?php endif; ?>
                    <?php if ( ! empty( $settings['show_arrows'] ) ) : ?>
                        <button type="button" class="orkigal-nav-button orkigal-next" aria-label="<?php esc_attr_e( 'Next', 'orki-gallery' ); ?>">›</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
