<?php
namespace OrkiGallery;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Repository {
    const IMAGES_META   = '_orkigal_images';
    const SETTINGS_META = '_orkigal_settings';
    const VIDEOS_META   = '_orkigal_videos';

    public static function defaults() {
        return array(
            'layout'            => 'grid',
            'columns_desktop'   => 4,
            'columns_tablet'    => 3,
            'columns_mobile'    => 2,
            'gap'               => 12,
            'ratio'             => 'square',
            'fit'               => 'cover',
            'radius'            => 8,
            'image_size'        => 'large',
            'lightbox'          => 1,
            'captions'          => 0,
            'hover_zoom'        => 1,
            'autoplay'          => 1,
            'autoplay_speed'    => 3500,
            'transition_speed'  => 400,
            'pause_on_hover'    => 1,
            'loop'              => 1,
            'show_arrows'       => 1,
            'show_dots'         => 1,
            'stage_height'      => 420,
        );
    }

    public static function layout_options() {
        return array(
            'grid'      => __( 'Grid', 'orki-gallery' ),
            'masonry'   => __( 'Masonry', 'orki-gallery' ),
            'justified' => __( 'Justified', 'orki-gallery' ),
            'tiles'     => __( 'Tiles', 'orki-gallery' ),
            'carousel'  => __( 'Carousel', 'orki-gallery' ),
            'slideshow' => __( 'Slideshow', 'orki-gallery' ),
        );
    }

    public static function image_size_options() {
        return array(
            'medium'       => __( 'Medium', 'orki-gallery' ),
            'medium_large' => __( 'Medium Large', 'orki-gallery' ),
            'large'        => __( 'Large', 'orki-gallery' ),
            'full'         => __( 'Full Size', 'orki-gallery' ),
        );
    }

    public static function get( $gallery_id ) {
        $gallery_id = absint( $gallery_id );
        $post = get_post( $gallery_id );
        if ( ! $post || 'orkigal_gallery' !== $post->post_type ) {
            return null;
        }

        $images   = get_post_meta( $gallery_id, self::IMAGES_META, true );
        $settings = get_post_meta( $gallery_id, self::SETTINGS_META, true );
        $videos   = get_post_meta( $gallery_id, self::VIDEOS_META, true );

        $images   = is_array( $images ) ? $images : array();
        $settings = is_array( $settings ) ? $settings : array();
        $videos   = is_array( $videos ) ? $videos : array();

        return array(
            'id'       => $gallery_id,
            'title'    => get_the_title( $gallery_id ),
            'status'   => $post->post_status,
            'images'   => array_values( array_filter( array_map( 'absint', $images ) ) ),
            'videos'   => array_values( array_filter( array_map( 'absint', $videos ) ) ),
            'settings' => self::sanitize_settings( wp_parse_args( $settings, self::defaults() ) ),
            'updated'  => $post->post_modified,
        );
    }

    public static function all( $limit = -1 ) {
        $query = new \WP_Query(
            array(
                'post_type'              => 'orkigal_gallery',
                'post_status'            => array( 'publish', 'draft' ),
                'posts_per_page'         => intval( $limit ),
                'orderby'                => 'modified',
                'order'                  => 'DESC',
                'no_found_rows'          => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => false,
            )
        );

        $items = array();
        foreach ( $query->posts as $post ) {
            $gallery = self::get( $post->ID );
            if ( $gallery ) {
                $items[] = $gallery;
            }
        }
        wp_reset_postdata();
        return $items;
    }

    public static function paged( $page = 1, $per_page = 10, $search = '' ) {
        $page     = max( 1, absint( $page ) );
        $per_page = max( 5, min( 50, absint( $per_page ) ) );
        $args     = array(
            'post_type'              => 'orkigal_gallery',
            'post_status'            => array( 'publish', 'draft' ),
            'posts_per_page'         => $per_page,
            'paged'                  => $page,
            'orderby'                => 'modified',
            'order'                  => 'DESC',
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        );
        $search = sanitize_text_field( $search );
        if ( '' !== $search ) {
            $args['s'] = $search;
        }
        $query = new \WP_Query( $args );
        $items = array();
        foreach ( $query->posts as $post ) {
            $gallery = self::get( $post->ID );
            if ( $gallery ) {
                $items[] = $gallery;
            }
        }
        wp_reset_postdata();
        return array(
            'items'       => $items,
            'total'       => absint( $query->found_posts ),
            'total_pages' => max( 1, absint( $query->max_num_pages ) ),
            'page'        => $page,
            'per_page'    => $per_page,
        );
    }

    public static function create( $title ) {
        $title = sanitize_text_field( $title );
        if ( '' === $title ) {
            $title = __( 'Untitled Gallery', 'orki-gallery' );
        }

        $id = wp_insert_post(
            array(
                'post_type'   => 'orkigal_gallery',
                'post_title'  => $title,
                'post_status' => 'draft',
            ),
            true
        );

        if ( is_wp_error( $id ) ) {
            return $id;
        }

        $defaults = get_option( 'orkigal_default_settings', self::defaults() );
        update_post_meta( $id, self::IMAGES_META, array() );
        update_post_meta( $id, self::VIDEOS_META, array() );
        update_post_meta( $id, self::SETTINGS_META, self::sanitize_settings( $defaults ) );
        return $id;
    }

    public static function duplicate( $gallery_id ) {
        $gallery = self::get( $gallery_id );
        if ( ! $gallery ) {
            return new \WP_Error( 'orkigal_invalid_gallery', __( 'Gallery not found.', 'orki-gallery' ) );
        }
        $copy_id = self::create( sprintf( __( '%s Copy', 'orki-gallery' ), $gallery['title'] ) );
        if ( is_wp_error( $copy_id ) ) {
            return $copy_id;
        }
        update_post_meta( $copy_id, self::IMAGES_META, $gallery['images'] );
        update_post_meta( $copy_id, self::VIDEOS_META, $gallery['videos'] );
        update_post_meta( $copy_id, self::SETTINGS_META, $gallery['settings'] );
        return $copy_id;
    }

    public static function save( $gallery_id, $data ) {
        $gallery_id = absint( $gallery_id );
        $gallery = self::get( $gallery_id );
        if ( ! $gallery ) {
            return new \WP_Error( 'orkigal_invalid_gallery', __( 'Gallery not found.', 'orki-gallery' ) );
        }

        $title  = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : $gallery['title'];
        $status = ( isset( $data['status'] ) && 'publish' === $data['status'] ) ? 'publish' : 'draft';

        $result = wp_update_post(
            array(
                'ID'          => $gallery_id,
                'post_title'  => $title,
                'post_status' => $status,
            ),
            true
        );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        if ( isset( $data['images'] ) ) {
            $images = is_array( $data['images'] ) ? $data['images'] : explode( ',', (string) $data['images'] );
            $images = array_values( array_unique( array_filter( array_map( 'absint', $images ) ) ) );
            update_post_meta( $gallery_id, self::IMAGES_META, $images );
        }

        if ( isset( $data['videos'] ) ) {
            $videos = is_array( $data['videos'] ) ? $data['videos'] : explode( ',', (string) $data['videos'] );
            $videos = array_values( array_unique( array_filter( array_map( 'absint', $videos ) ) ) );
            update_post_meta( $gallery_id, self::VIDEOS_META, $videos );
        }

        if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
            $merged = array_merge( $gallery['settings'], $data['settings'] );
            update_post_meta( $gallery_id, self::SETTINGS_META, self::sanitize_settings( $merged ) );
        }

        return true;
    }

    public static function sanitize_settings( $settings ) {
        $defaults = self::defaults();
        $settings = wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );

        $layouts = array_keys( self::layout_options() );
        $ratios  = array( 'auto', 'square', 'landscape', 'portrait', 'wide' );
        $fits    = array( 'cover', 'contain' );
        $sizes   = array_keys( self::image_size_options() );

        return array(
            'layout'           => in_array( $settings['layout'], $layouts, true ) ? $settings['layout'] : 'grid',
            'columns_desktop'  => max( 1, min( 8, absint( $settings['columns_desktop'] ) ) ),
            'columns_tablet'   => max( 1, min( 6, absint( $settings['columns_tablet'] ) ) ),
            'columns_mobile'   => max( 1, min( 4, absint( $settings['columns_mobile'] ) ) ),
            'gap'              => max( 0, min( 100, absint( $settings['gap'] ) ) ),
            'ratio'            => in_array( $settings['ratio'], $ratios, true ) ? $settings['ratio'] : 'square',
            'fit'              => in_array( $settings['fit'], $fits, true ) ? $settings['fit'] : 'cover',
            'radius'           => max( 0, min( 100, absint( $settings['radius'] ) ) ),
            'image_size'       => in_array( $settings['image_size'], $sizes, true ) ? $settings['image_size'] : 'large',
            'lightbox'         => empty( $settings['lightbox'] ) ? 0 : 1,
            'captions'         => empty( $settings['captions'] ) ? 0 : 1,
            'hover_zoom'       => empty( $settings['hover_zoom'] ) ? 0 : 1,
            'autoplay'         => empty( $settings['autoplay'] ) ? 0 : 1,
            'autoplay_speed'   => max( 1000, min( 20000, absint( $settings['autoplay_speed'] ) ) ),
            'transition_speed' => max( 100, min( 3000, absint( $settings['transition_speed'] ) ) ),
            'pause_on_hover'   => empty( $settings['pause_on_hover'] ) ? 0 : 1,
            'loop'             => empty( $settings['loop'] ) ? 0 : 1,
            'show_arrows'      => empty( $settings['show_arrows'] ) ? 0 : 1,
            'show_dots'        => empty( $settings['show_dots'] ) ? 0 : 1,
            'stage_height'     => max( 180, min( 1200, absint( $settings['stage_height'] ) ) ),
        );
    }

    public static function delete( $gallery_id ) {
        return wp_delete_post( absint( $gallery_id ), true );
    }

    public static function shortcode( $gallery_id ) {
        return '[orki_gallery id="' . absint( $gallery_id ) . '"]';
    }
}
