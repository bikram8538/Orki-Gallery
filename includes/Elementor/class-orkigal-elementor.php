<?php
namespace OrkiGallery\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Integration {
    public function __construct() {
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
        add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
    }

    public function register_category( $elements_manager ) {
        $elements_manager->add_category(
            'orki',
            array(
                'title' => __( 'Orki', 'orki-gallery' ),
                'icon'  => 'fa fa-plug',
            )
        );
    }

    public function register_widget( $widgets_manager ) {
        if ( ! class_exists( '\\Elementor\\Widget_Base' ) ) {
            return;
        }
        require_once ORKIGAL_PATH . 'includes/Elementor/class-orkigal-widget.php';
        $widgets_manager->register( new Widget() );
    }
}
