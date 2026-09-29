<?php
/**
 * Orki Gallery uninstall cleanup.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( ! (int) get_option( 'orkigal_delete_data_on_uninstall', 0 ) ) {
    return;
}

$gallery_ids = get_posts(
    array(
        'post_type'      => 'orkigal_gallery',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    )
);
foreach ( $gallery_ids as $gallery_id ) {
    wp_delete_post( (int) $gallery_id, true );
}

delete_option( 'orkigal_default_settings' );
delete_option( 'orkigal_setup_complete' );
delete_option( 'orkigal_do_activation_redirect' );
delete_option( 'orkigal_delete_data_on_uninstall' );
