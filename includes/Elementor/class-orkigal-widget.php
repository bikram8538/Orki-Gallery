<?php
namespace OrkiGallery\Elementor;

use OrkiGallery\Repository;
use OrkiGallery\Frontend\Renderer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Widget extends \Elementor\Widget_Base {
    public function get_name() { return 'orki_gallery'; }
    public function get_title() { return __( 'Orki Gallery', 'orki-gallery' ); }
    public function get_icon() { return 'eicon-gallery-grid'; }
    public function get_categories() { return array( 'orki', 'general' ); }
    public function get_keywords() { return array( 'gallery', 'images', 'photos', 'video', 'orki' ); }

    private function gallery_options() {
        $options = array();
        foreach ( Repository::all() as $gallery ) {
            $options[ $gallery['id'] ] = sprintf( '%s (#%d)', $gallery['title'], $gallery['id'] );
        }
        return $options;
    }

    protected function register_controls() {
        $this->start_controls_section( 'section_gallery', array( 'label' => __( 'Gallery', 'orki-gallery' ) ) );
        $this->add_control( 'source', array(
            'label' => __( 'Source', 'orki-gallery' ),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'inline',
            'options' => array( 'inline' => __( 'Create Here', 'orki-gallery' ), 'saved' => __( 'Use Saved Gallery', 'orki-gallery' ) ),
        ) );
        $this->add_control( 'saved_gallery_id', array(
            'label' => __( 'Choose Gallery', 'orki-gallery' ),
            'type' => \Elementor\Controls_Manager::SELECT2,
            'options' => $this->gallery_options(),
            'label_block' => true,
            'condition' => array( 'source' => 'saved' ),
        ) );
        $this->add_control( 'inline_images', array(
            'label' => __( 'Images', 'orki-gallery' ),
            'type' => \Elementor\Controls_Manager::GALLERY,
            'condition' => array( 'source' => 'inline' ),
        ) );
        $this->add_control( 'inline_video_note', array(
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw' => __( 'To mix WordPress-hosted videos with images, create a saved Orki Gallery and choose it here.', 'orki-gallery' ),
            'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            'condition' => array( 'source' => 'inline' ),
        ) );
        $this->add_control( 'use_saved_settings', array(
            'label' => __( 'Use Saved Gallery Settings', 'orki-gallery' ),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => __( 'Yes', 'orki-gallery' ),
            'label_off' => __( 'No', 'orki-gallery' ),
            'return_value' => 'yes',
            'default' => 'yes',
            'condition' => array( 'source' => 'saved' ),
        ) );
        $this->end_controls_section();

        $this->start_controls_section( 'section_layout', array( 'label' => __( 'Layout & Images', 'orki-gallery' ) ) );
        $this->add_control( 'layout', array(
            'label' => __( 'Layout', 'orki-gallery' ),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'grid',
            'options' => Repository::layout_options(),
        ) );
        $this->add_responsive_control( 'columns', array(
            'label' => __( 'Columns', 'orki-gallery' ),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 4,
            'tablet_default' => 3,
            'mobile_default' => 2,
            'min' => 1,
            'max' => 8,
            'condition' => array( 'layout!' => 'slideshow' ),
        ) );
        $this->add_control( 'gap', array(
            'label' => __( 'Gap', 'orki-gallery' ),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'default' => array( 'size' => 12 ),
            'range' => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
        ) );
        $this->add_control( 'ratio', array(
            'label' => __( 'Image Ratio', 'orki-gallery' ),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'square',
            'options' => array(
                'auto' => __( 'Original', 'orki-gallery' ),
                'square' => __( 'Square 1:1', 'orki-gallery' ),
                'landscape' => __( 'Landscape 4:3', 'orki-gallery' ),
                'portrait' => __( 'Portrait 3:4', 'orki-gallery' ),
                'wide' => __( 'Wide 16:9', 'orki-gallery' ),
            ),
        ) );
        $this->add_control( 'fit', array(
            'label' => __( 'Image Fit', 'orki-gallery' ),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'cover',
            'options' => array( 'cover' => __( 'Cover', 'orki-gallery' ), 'contain' => __( 'Contain', 'orki-gallery' ) ),
        ) );
        $this->add_control( 'image_size', array(
            'label' => __( 'Image Resolution', 'orki-gallery' ),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'large',
            'options' => Repository::image_size_options(),
        ) );
        $this->add_control( 'radius', array(
            'label' => __( 'Corner Radius', 'orki-gallery' ),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'default' => array( 'size' => 8 ),
            'range' => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
        ) );
        $this->add_control( 'stage_height', array(
            'label' => __( 'Stage Height', 'orki-gallery' ),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 420,
            'min' => 180,
            'max' => 1200,
            'condition' => array( 'layout' => 'slideshow' ),
        ) );
        $this->end_controls_section();

        $this->start_controls_section( 'section_features', array( 'label' => __( 'Viewer & Effects', 'orki-gallery' ) ) );
        $this->add_control( 'lightbox', array( 'label' => __( 'Lightbox', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
        $this->add_control( 'captions', array( 'label' => __( 'Show Captions', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );
        $this->add_control( 'hover_zoom', array( 'label' => __( 'Hover Zoom', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
        $this->end_controls_section();

        $this->start_controls_section( 'section_motion', array( 'label' => __( 'Carousel / Slideshow', 'orki-gallery' ), 'condition' => array( 'layout' => array( 'carousel', 'slideshow' ) ) ) );
        $this->add_control( 'autoplay', array( 'label' => __( 'Autoplay', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
        $this->add_control( 'autoplay_speed', array( 'label' => __( 'Autoplay Speed (ms)', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 3500, 'min' => 1000, 'max' => 20000, 'step' => 100, 'condition' => array( 'autoplay' => 'yes' ) ) );
        $this->add_control( 'transition_speed', array( 'label' => __( 'Transition Speed (ms)', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 400, 'min' => 100, 'max' => 3000, 'step' => 50 ) );
        $this->add_control( 'pause_on_hover', array( 'label' => __( 'Pause on Hover', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
        $this->add_control( 'loop', array( 'label' => __( 'Infinite Loop', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
        $this->add_control( 'show_arrows', array( 'label' => __( 'Show Arrows', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
        $this->add_control( 'show_dots', array( 'label' => __( 'Show Dots', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
        $this->end_controls_section();

        $this->start_controls_section( 'style_caption', array( 'label' => __( 'Caption', 'orki-gallery' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
        $this->add_control( 'caption_color', array( 'label' => __( 'Text Color', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .orkigal-caption' => 'color: {{VALUE}};' ) ) );
        $this->add_control( 'caption_bg', array( 'label' => __( 'Background', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .orkigal-caption' => 'background-color: {{VALUE}};' ) ) );
        if ( class_exists( '\\Elementor\\Group_Control_Typography' ) ) {
            $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'caption_typography', 'selector' => '{{WRAPPER}} .orkigal-caption' ) );
        }
        $this->add_responsive_control( 'caption_padding', array( 'label' => __( 'Padding', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .orkigal-caption' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
        $this->end_controls_section();

        $this->start_controls_section( 'style_navigation', array( 'label' => __( 'Navigation', 'orki-gallery' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
        $this->add_control( 'nav_color', array( 'label' => __( 'Arrow Color', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .orkigal-nav-button' => 'color: {{VALUE}};' ) ) );
        $this->add_control( 'nav_bg', array( 'label' => __( 'Arrow Background', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .orkigal-nav-button' => 'background-color: {{VALUE}};' ) ) );
        $this->add_control( 'dot_color', array( 'label' => __( 'Dot Color', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .orkigal-dot' => 'background-color: {{VALUE}};' ) ) );
        $this->add_control( 'dot_active_color', array( 'label' => __( 'Active Dot Color', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .orkigal-dot.is-active' => 'background-color: {{VALUE}};' ) ) );
        $this->end_controls_section();

        $this->start_controls_section( 'style_lightbox', array( 'label' => __( 'Lightbox', 'orki-gallery' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
        $this->add_control( 'lightbox_background', array( 'label' => __( 'Backdrop', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .orkigal-root' => '--orkigal-lightbox-bg: {{VALUE}};' ) ) );
        $this->add_control( 'lightbox_control_bg', array( 'label' => __( 'Control Background', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .orkigal-root' => '--orkigal-lightbox-control-bg: {{VALUE}};' ) ) );
        $this->add_control( 'lightbox_control_color', array( 'label' => __( 'Control Color', 'orki-gallery' ), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .orkigal-root' => '--orkigal-lightbox-control-color: {{VALUE}};' ) ) );
        $this->end_controls_section();
    }

    private function inline_image_ids( $items ) {
        $ids = array();
        foreach ( (array) $items as $item ) {
            if ( ! empty( $item['id'] ) ) {
                $ids[] = absint( $item['id'] );
            }
        }
        return array_values( array_filter( $ids ) );
    }

    private function widget_settings( $settings ) {
        return Repository::sanitize_settings( array(
            'layout' => isset( $settings['layout'] ) ? $settings['layout'] : 'grid',
            'columns_desktop' => isset( $settings['columns'] ) ? $settings['columns'] : 4,
            'columns_tablet' => isset( $settings['columns_tablet'] ) ? $settings['columns_tablet'] : 3,
            'columns_mobile' => isset( $settings['columns_mobile'] ) ? $settings['columns_mobile'] : 2,
            'gap' => isset( $settings['gap']['size'] ) ? $settings['gap']['size'] : 12,
            'ratio' => isset( $settings['ratio'] ) ? $settings['ratio'] : 'square',
            'fit' => isset( $settings['fit'] ) ? $settings['fit'] : 'cover',
            'image_size' => isset( $settings['image_size'] ) ? $settings['image_size'] : 'large',
            'radius' => isset( $settings['radius']['size'] ) ? $settings['radius']['size'] : 8,
            'lightbox' => ! empty( $settings['lightbox'] ),
            'captions' => ! empty( $settings['captions'] ),
            'hover_zoom' => ! empty( $settings['hover_zoom'] ),
            'autoplay' => ! empty( $settings['autoplay'] ),
            'autoplay_speed' => isset( $settings['autoplay_speed'] ) ? $settings['autoplay_speed'] : 3500,
            'transition_speed' => isset( $settings['transition_speed'] ) ? $settings['transition_speed'] : 400,
            'pause_on_hover' => ! empty( $settings['pause_on_hover'] ),
            'loop' => ! empty( $settings['loop'] ),
            'show_arrows' => ! empty( $settings['show_arrows'] ),
            'show_dots' => ! empty( $settings['show_dots'] ),
            'stage_height' => isset( $settings['stage_height'] ) ? $settings['stage_height'] : 420,
        ) );
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $source = isset( $settings['source'] ) ? $settings['source'] : 'inline';
        $image_ids = array();
        $video_ids = array();
        $render_settings = $this->widget_settings( $settings );

        if ( 'inline' === $source ) {
            $image_ids = $this->inline_image_ids( isset( $settings['inline_images'] ) ? $settings['inline_images'] : array() );
        } else {
            $gallery_id = isset( $settings['saved_gallery_id'] ) ? absint( $settings['saved_gallery_id'] ) : 0;
            $gallery = $gallery_id ? Repository::get( $gallery_id ) : null;
            if ( $gallery ) {
                $image_ids = $gallery['images'];
                $video_ids = isset( $gallery['videos'] ) ? $gallery['videos'] : array();
                if ( ! empty( $settings['use_saved_settings'] ) ) {
                    $render_settings = $gallery['settings'];
                }
            }
        }

        if ( empty( $image_ids ) && empty( $video_ids ) ) {
            if ( isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<div class="orkigal-empty">' . esc_html__( 'Add images or choose a saved Orki Gallery.', 'orki-gallery' ) . '</div>';
            }
            return;
        }
        echo Renderer::render( $image_ids, $render_settings, array( 'elementor' => true, 'videos' => $video_ids ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
