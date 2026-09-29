<?php
/**
 * Plugin Name: Orki Gallery
 * Plugin URI: https://orki.in/orki-gallery/
 * Description: Build responsive image and video galleries with visual layouts, lightbox, shortcode, Gutenberg, and Elementor support.
 * Version: 1.0.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Kunal Debnath, Bikram Bagdi
 * Author URI: https://orki.in/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: orki-gallery
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ORKIGAL_VERSION', '1.0.2' );
define( 'ORKIGAL_FILE', __FILE__ );
define( 'ORKIGAL_PATH', plugin_dir_path( __FILE__ ) );
define( 'ORKIGAL_URL', plugin_dir_url( __FILE__ ) );

require_once ORKIGAL_PATH . 'includes/class-orkigal-plugin.php';

register_activation_hook( __FILE__, array( 'OrkiGallery\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'OrkiGallery\\Plugin', 'deactivate' ) );

add_action(
    'plugins_loaded',
    static function () {
        OrkiGallery\Plugin::instance()->boot();
    }
);
