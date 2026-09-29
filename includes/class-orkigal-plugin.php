<?php
namespace OrkiGallery;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Plugin {
    private static $instance = null;
    private $booted = false;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function activate() {
        self::register_post_type();
        if ( false === get_option( 'orkigal_setup_complete', false ) ) {
            update_option( 'orkigal_do_activation_redirect', 1, false );
        }
        if ( false === get_option( 'orkigal_default_settings', false ) ) {
            require_once ORKIGAL_PATH . 'includes/class-orkigal-repository.php';
            update_option( 'orkigal_default_settings', Repository::defaults(), false );
        }
        if ( false === get_option( 'orkigal_delete_data_on_uninstall', false ) ) {
            add_option( 'orkigal_delete_data_on_uninstall', 0, '', false );
        }
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public function boot() {
        if ( $this->booted ) {
            return;
        }
        $this->booted = true;

        load_plugin_textdomain( 'orki-gallery', false, dirname( plugin_basename( ORKIGAL_FILE ) ) . '/languages' );

        require_once ORKIGAL_PATH . 'includes/class-orkigal-repository.php';
        require_once ORKIGAL_PATH . 'includes/Frontend/class-orkigal-renderer.php';
        require_once ORKIGAL_PATH . 'includes/Admin/class-orkigal-admin.php';

        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_block' ), 20 );
        add_action( 'enqueue_block_editor_assets', array( $this, 'block_editor_data' ) );

        new \OrkiGallery\Frontend\Renderer();
        if ( is_admin() ) {
            new \OrkiGallery\Admin\Admin();
        }

        add_action( 'elementor/loaded', array( $this, 'load_elementor' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( ORKIGAL_FILE ), array( $this, 'action_links' ) );
    }

    public static function register_post_type() {
        register_post_type(
            'orkigal_gallery',
            array(
                'labels' => array(
                    'name'          => __( 'Orki Galleries', 'orki-gallery' ),
                    'singular_name' => __( 'Orki Gallery', 'orki-gallery' ),
                ),
                'public'              => false,
                'show_ui'             => false,
                'show_in_rest'        => false,
                'supports'            => array( 'title' ),
                'capability_type'      => 'post',
                'map_meta_cap'        => true,
                'exclude_from_search' => true,
                'publicly_queryable'   => false,
            )
        );
    }

    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }
        wp_register_script(
            'orkigal-block-editor',
            ORKIGAL_URL . 'blocks/gallery/editor.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n' ),
            ORKIGAL_VERSION,
            true
        );

        register_block_type(
            'orki/gallery',
            array(
                'api_version'     => 2,
                'editor_script'   => 'orkigal-block-editor',
                'render_callback' => array( $this, 'render_block' ),
                'attributes'      => array(
                    'galleryId' => array(
                        'type'    => 'integer',
                        'default' => 0,
                    ),
                ),
            )
        );
    }

    public function block_editor_data() {
        if ( ! wp_script_is( 'orkigal-block-editor', 'registered' ) ) {
            return;
        }
        wp_enqueue_style( 'orkigal-block-editor-style', ORKIGAL_URL . 'assets/frontend/frontend.css', array(), ORKIGAL_VERSION );
        $items = array();
        foreach ( Repository::all() as $gallery ) {
            if ( 'publish' !== $gallery['status'] && ! current_user_can( 'edit_posts' ) ) {
                continue;
            }
            $thumb = ! empty( $gallery['images'] ) ? wp_get_attachment_image_url( $gallery['images'][0], 'thumbnail' ) : '';
            $items[] = array(
                'id'    => $gallery['id'],
                'title' => $gallery['title'],
                'count' => count( $gallery['images'] ) + count( $gallery['videos'] ),
                'thumb' => $thumb ? $thumb : '',
            );
        }
        wp_localize_script(
            'orkigal-block-editor',
            'orkigalBlockData',
            array(
                'galleries' => $items,
                'createUrl' => admin_url( 'admin.php?page=orki-gallery-editor' ),
            )
        );
    }

    public function render_block( $attributes ) {
        $gallery_id = isset( $attributes['galleryId'] ) ? absint( $attributes['galleryId'] ) : 0;
        if ( ! $gallery_id ) {
            return '';
        }
        $gallery = Repository::get( $gallery_id );
        if ( ! $gallery ) {
            return '';
        }
        if ( 'publish' !== $gallery['status'] && ! current_user_can( 'edit_posts' ) ) {
            return '';
        }
        return \OrkiGallery\Frontend\Renderer::render(
            $gallery['images'],
            $gallery['settings'],
            array(
                'gallery_id' => $gallery['id'],
                'videos'     => $gallery['videos'],
            )
        );
    }

    public function action_links( $links ) {
        $custom = array(
            '<a href="' . esc_url( admin_url( 'admin.php?page=orki-gallery' ) ) . '">' . esc_html__( 'Galleries', 'orki-gallery' ) . '</a>',
            '<a href="' . esc_url( admin_url( 'admin.php?page=orki-gallery-settings' ) ) . '">' . esc_html__( 'Settings', 'orki-gallery' ) . '</a>',
        );
        return array_merge( $custom, $links );
    }

    public function load_elementor() {
        require_once ORKIGAL_PATH . 'includes/Elementor/class-orkigal-elementor.php';
        new \OrkiGallery\Elementor\Integration();
    }
}
