<?php
namespace OrkiGallery\Admin;

use OrkiGallery\Repository;
use OrkiGallery\Frontend\Renderer;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Admin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_init', array( $this, 'maybe_redirect_setup' ) );
        add_action( 'admin_post_orkigal_create_gallery', array( $this, 'create_gallery' ) );
        add_action( 'admin_post_orkigal_save_gallery', array( $this, 'save_gallery' ) );
        add_action( 'admin_post_orkigal_delete_gallery', array( $this, 'delete_gallery' ) );
        add_action( 'admin_post_orkigal_duplicate_gallery', array( $this, 'duplicate_gallery' ) );
        add_action( 'admin_post_orkigal_save_advanced', array( $this, 'save_advanced' ) );
        add_action( 'admin_post_orkigal_save_defaults', array( $this, 'save_defaults' ) );
        add_action( 'admin_post_orkigal_setup_save', array( $this, 'setup_save' ) );
        add_action( 'admin_post_orkigal_restart_setup', array( $this, 'restart_setup' ) );
    }

    public function menu() {
        add_menu_page( __( 'Orki Gallery', 'orki-gallery' ), __( 'Orki Gallery', 'orki-gallery' ), 'upload_files', 'orki-gallery', array( $this, 'dashboard' ), 'dashicons-images-alt2', 26 );
        add_submenu_page( 'orki-gallery', __( 'Galleries', 'orki-gallery' ), __( 'Galleries', 'orki-gallery' ), 'upload_files', 'orki-gallery', array( $this, 'dashboard' ) );
        add_submenu_page( 'orki-gallery', __( 'Add New Gallery', 'orki-gallery' ), __( 'Add New Gallery', 'orki-gallery' ), 'upload_files', 'orki-gallery-editor', array( $this, 'editor' ) );
        add_submenu_page( 'orki-gallery', __( 'Settings', 'orki-gallery' ), __( 'Settings', 'orki-gallery' ), 'manage_options', 'orki-gallery-settings', array( $this, 'settings' ) );
        add_submenu_page( 'orki-gallery', __( 'About Orki Gallery', 'orki-gallery' ), __( 'About Orki Gallery', 'orki-gallery' ), 'upload_files', 'orki-gallery-about', array( $this, 'about' ) );
        add_submenu_page( null, __( 'Orki Gallery Setup', 'orki-gallery' ), __( 'Orki Gallery Setup', 'orki-gallery' ), 'manage_options', 'orki-gallery-setup', array( $this, 'setup_wizard' ) );
    }

    public function maybe_redirect_setup() {
        if ( ! current_user_can( 'manage_options' ) || wp_doing_ajax() ) { return; }
        if ( ! get_option( 'orkigal_do_activation_redirect' ) ) { return; }
        delete_option( 'orkigal_do_activation_redirect' );
        if ( isset( $_GET['activate-multi'] ) ) { return; } // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        wp_safe_redirect( admin_url( 'admin.php?page=orki-gallery-setup' ) );
        exit;
    }

    private function is_orki_page() {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return 0 === strpos( $page, 'orki-gallery' );
    }

    public function assets( $hook ) {
        if ( ! $this->is_orki_page() ) { return; }
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $is_editor = 'orki-gallery-editor' === $page;
        if ( $is_editor ) {
            wp_enqueue_media();
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_style( 'orkigal-frontend', ORKIGAL_URL . 'assets/frontend/frontend.css', array(), ORKIGAL_VERSION );
            wp_enqueue_script( 'orkigal-frontend', ORKIGAL_URL . 'assets/frontend/frontend.js', array(), ORKIGAL_VERSION, true );
        }
        wp_enqueue_style( 'orkigal-admin', ORKIGAL_URL . 'assets/admin/admin.css', array(), ORKIGAL_VERSION );
        wp_enqueue_script( 'orkigal-admin', ORKIGAL_URL . 'assets/admin/admin.js', array( 'jquery' ), ORKIGAL_VERSION, true );
        wp_localize_script( 'orkigal-admin', 'orkigalAdmin', array(
            'mediaTitle' => __( 'Choose images for your Orki Gallery', 'orki-gallery' ),
            'mediaButton' => __( 'Use these images', 'orki-gallery' ),
            'videoTitle' => __( 'Choose videos for your Orki Gallery', 'orki-gallery' ),
            'videoButton' => __( 'Use these videos', 'orki-gallery' ),
            'copied' => __( 'Shortcode copied', 'orki-gallery' ),
        ) );
    }

    private function brand() {
        echo '<div class="orkigal-brand-lockup"><span class="orkigal-brand-mark"><i></i><i></i><i></i><i></i></span><span><b>Orki</b> Gallery</span></div>';
    }

    private function page_header( $title, $description = '', $button = true ) {
        ?>
        <div class="orkigal-admin-wrap wrap">
            <div class="orkigal-topbar"><?php $this->brand(); ?><a href="<?php echo esc_url( 'https://orki.in/' ); ?>" target="_blank" rel="noopener noreferrer">orki.in ↗</a></div>
            <div class="orkigal-page-header">
                <div><h1><?php echo esc_html( $title ); ?></h1><?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?></div>
                <?php if ( $button ) : ?><a class="button button-primary orkigal-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=orki-gallery-editor' ) ); ?>">+ <?php esc_html_e( 'Add New Gallery', 'orki-gallery' ); ?></a><?php endif; ?>
            </div>
        <?php
    }

    private function page_footer() { echo '</div>'; }

    public function dashboard() {
        if ( ! current_user_can( 'upload_files' ) ) { wp_die( esc_html__( 'You do not have permission to access this page.', 'orki-gallery' ) ); }
        $page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $result = Repository::paged( $page, 10, $search );
        $galleries = $result['items'];
        $this->page_header( __( 'Galleries', 'orki-gallery' ), __( 'Create, manage, and embed your galleries from one place.', 'orki-gallery' ) );
        ?>
        <section class="orkigal-welcome-panel">
            <div><span class="orkigal-kicker"><?php esc_html_e( 'WELCOME TO ORKI GALLERY', 'orki-gallery' ); ?></span><h2><?php esc_html_e( 'Beautiful galleries without a complicated workflow.', 'orki-gallery' ); ?></h2><p><?php esc_html_e( 'Add media, choose a visual layout, preview it, and publish with a shortcode or Elementor.', 'orki-gallery' ); ?></p><a class="button button-primary orkigal-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=orki-gallery-editor' ) ); ?>"><?php esc_html_e( 'Create a Gallery', 'orki-gallery' ); ?> →</a></div>
            <div class="orkigal-welcome-art"><span></span><span></span><span></span><span></span><span></span></div>
        </section>
        <section class="orkigal-card orkigal-manage-card">
            <div class="orkigal-table-head"><div><h2><?php esc_html_e( 'Manage Galleries', 'orki-gallery' ); ?></h2><p><?php esc_html_e( 'Shortcodes are always available next to the gallery.', 'orki-gallery' ); ?></p></div><form method="get" class="orkigal-search"><input type="hidden" name="page" value="orki-gallery"><span class="dashicons dashicons-search"></span><input name="s" type="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search galleries', 'orki-gallery' ); ?>"><button class="button" type="submit"><?php esc_html_e( 'Search', 'orki-gallery' ); ?></button></form></div>
            <?php if ( empty( $galleries ) ) : ?>
                <div class="orkigal-empty-state"><span class="dashicons dashicons-images-alt2"></span><h3><?php esc_html_e( 'No galleries yet', 'orki-gallery' ); ?></h3><p><?php esc_html_e( 'Create your first gallery and it will appear here.', 'orki-gallery' ); ?></p></div>
            <?php else : ?>
                <div class="orkigal-gallery-table" id="orkigal-gallery-table">
                    <div class="orkigal-gallery-table-row is-head"><span><?php esc_html_e( 'Gallery', 'orki-gallery' ); ?></span><span><?php esc_html_e( 'Media', 'orki-gallery' ); ?></span><span><?php esc_html_e( 'Shortcode', 'orki-gallery' ); ?></span><span><?php esc_html_e( 'Actions', 'orki-gallery' ); ?></span></div>
                    <?php foreach ( $galleries as $gallery ) : $this->gallery_row( $gallery, true ); endforeach; ?>
                </div>
                <?php if ( $result['total_pages'] > 1 ) : ?>
                    <div class="orkigal-pagination">
                        <?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( array( 'page' => 'orki-gallery', 's' => $search, 'paged' => '%#%' ), admin_url( 'admin.php' ) ), 'format' => '', 'current' => $result['page'], 'total' => $result['total_pages'], 'type' => 'list', 'prev_text' => '‹', 'next_text' => '›' ) ) ); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        <?php $this->page_footer();
    }

    public function galleries() { $this->dashboard(); }

    private function gallery_row( $gallery, $show_delete ) {
        $thumb = ! empty( $gallery['images'] ) ? wp_get_attachment_image_url( $gallery['images'][0], 'thumbnail' ) : '';
        $edit_url = add_query_arg( array( 'page' => 'orki-gallery-editor', 'gallery_id' => $gallery['id'], 'step' => 'media' ), admin_url( 'admin.php' ) );
        $finish_url = add_query_arg( array( 'page' => 'orki-gallery-editor', 'gallery_id' => $gallery['id'], 'step' => 'finish' ), admin_url( 'admin.php' ) );
        $delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'orkigal_delete_gallery', 'gallery_id' => $gallery['id'] ), admin_url( 'admin-post.php' ) ), 'orkigal_delete_gallery_' . $gallery['id'] );
        $duplicate_url = wp_nonce_url( add_query_arg( array( 'action' => 'orkigal_duplicate_gallery', 'gallery_id' => $gallery['id'] ), admin_url( 'admin-post.php' ) ), 'orkigal_duplicate_gallery_' . $gallery['id'] );
        $count = count( $gallery['images'] ) + count( $gallery['videos'] );
        ?>
        <div class="orkigal-gallery-table-row" data-gallery-name="<?php echo esc_attr( strtolower( $gallery['title'] ) ); ?>">
            <div class="orkigal-table-gallery"><div class="orkigal-gallery-thumb <?php echo $thumb ? '' : 'is-empty'; ?>"<?php echo $thumb ? ' style="background-image:url(' . esc_url( $thumb ) . ')"' : ''; ?>></div><div><a href="<?php echo esc_url( $edit_url ); ?>" class="orkigal-gallery-title"><?php echo esc_html( $gallery['title'] ); ?></a><small><?php echo esc_html( ucfirst( $gallery['status'] ) ); ?> · #<?php echo esc_html( (string) $gallery['id'] ); ?></small></div></div>
            <span><?php echo esc_html( sprintf( _n( '%d item', '%d items', $count, 'orki-gallery' ), $count ) ); ?></span>
            <button type="button" class="orkigal-shortcode-copy" data-copy="<?php echo esc_attr( Repository::shortcode( $gallery['id'] ) ); ?>"><code><?php echo esc_html( Repository::shortcode( $gallery['id'] ) ); ?></code><span class="dashicons dashicons-admin-page"></span></button>
            <div class="orkigal-gallery-actions"><a class="button" href="<?php echo esc_url( $edit_url ); ?>" title="<?php esc_attr_e( 'Edit', 'orki-gallery' ); ?>"><span class="dashicons dashicons-edit"></span></a><a class="button" href="<?php echo esc_url( $finish_url ); ?>" title="<?php esc_attr_e( 'Preview', 'orki-gallery' ); ?>"><span class="dashicons dashicons-visibility"></span></a><a class="button" href="<?php echo esc_url( $duplicate_url ); ?>" title="<?php esc_attr_e( 'Duplicate', 'orki-gallery' ); ?>"><span class="dashicons dashicons-admin-page"></span></a><?php if ( $show_delete ) : ?><a class="button orkigal-danger" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this gallery?', 'orki-gallery' ) ); ?>')" title="<?php esc_attr_e( 'Delete', 'orki-gallery' ); ?>"><span class="dashicons dashicons-trash"></span></a><?php endif; ?></div>
        </div>
        <?php
    }

    public function editor() {
        if ( ! current_user_can( 'upload_files' ) ) { wp_die( esc_html__( 'You do not have permission to access this page.', 'orki-gallery' ) ); }
        $gallery_id = isset( $_GET['gallery_id'] ) ? absint( $_GET['gallery_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $gallery = $gallery_id ? Repository::get( $gallery_id ) : null;
        if ( ! $gallery ) { $this->new_gallery_screen(); return; }
        $step = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : 'media'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! in_array( $step, array( 'media', 'design', 'finish' ), true ) ) { $step = 'media'; }

        $this->page_header( __( 'Edit Gallery', 'orki-gallery' ), sprintf( __( '%s · %d media items', 'orki-gallery' ), $gallery['title'], count( $gallery['images'] ) + count( $gallery['videos'] ) ), false );
        $this->editor_progress( $gallery['id'], $step );
        if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Gallery saved successfully.', 'orki-gallery' ); ?></p></div><?php
        endif;
        if ( 'media' === $step ) { $this->editor_media( $gallery ); }
        elseif ( 'design' === $step ) { $this->editor_design( $gallery ); }
        else { $this->editor_finish( $gallery ); }
        $this->page_footer();
    }

    private function editor_progress( $gallery_id, $step ) {
        $steps = array( 'media' => array( '01', __( 'Images', 'orki-gallery' ), __( 'Add & arrange media', 'orki-gallery' ) ), 'design' => array( '02', __( 'Design', 'orki-gallery' ), __( 'Layout & appearance', 'orki-gallery' ) ), 'finish' => array( '03', __( 'Finish', 'orki-gallery' ), __( 'Review & publish', 'orki-gallery' ) ) );
        echo '<div class="orkigal-editor-progress">';
        foreach ( $steps as $key => $data ) {
            $url = add_query_arg( array( 'page' => 'orki-gallery-editor', 'gallery_id' => $gallery_id, 'step' => $key ), admin_url( 'admin.php' ) );
            $class = $key === $step ? 'active' : '';
            echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '"><b>' . esc_html( $data[0] ) . '</b><span><strong>' . esc_html( $data[1] ) . '</strong><small>' . esc_html( $data[2] ) . '</small></span></a>';
        }
        echo '</div>';
    }

    private function save_form_open( $gallery, $next_step ) {
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="orkigal-step-form">
            <input type="hidden" name="action" value="orkigal_save_gallery"><input type="hidden" name="gallery_id" value="<?php echo esc_attr( (string) $gallery['id'] ); ?>"><input type="hidden" name="status" value="<?php echo esc_attr( $gallery['status'] ); ?>"><input type="hidden" name="next_step" value="<?php echo esc_attr( $next_step ); ?>">
            <?php wp_nonce_field( 'orkigal_save_gallery_' . $gallery['id'], 'orkigal_nonce' ); ?>
        <?php
    }

    private function editor_media( $gallery ) {
        $this->save_form_open( $gallery, 'design' );
        ?>
        <section class="orkigal-editor-workspace">
            <div class="orkigal-workspace-toolbar"><div><h2><?php esc_html_e( 'Add media', 'orki-gallery' ); ?></h2><p><?php esc_html_e( 'Select multiple files, drag to reorder, or remove anything you do not need.', 'orki-gallery' ); ?></p></div><div class="orkigal-media-actions"><button type="button" class="button" id="orkigal-add-video"><span class="dashicons dashicons-video-alt3"></span><?php esc_html_e( 'Add Video', 'orki-gallery' ); ?></button><button type="button" class="button button-primary orkigal-primary" id="orkigal-add-images"><span class="dashicons dashicons-format-image"></span><?php esc_html_e( 'Add Images', 'orki-gallery' ); ?></button></div></div>
            <input type="hidden" name="images" id="orkigal-image-ids" value="<?php echo esc_attr( implode( ',', $gallery['images'] ) ); ?>"><input type="hidden" name="videos" id="orkigal-video-ids" value="<?php echo esc_attr( implode( ',', $gallery['videos'] ) ); ?>">
            <div class="orkigal-media-strip"><label><input type="checkbox" id="orkigal-select-all"> <?php esc_html_e( 'All Images', 'orki-gallery' ); ?></label><select id="orkigal-sort-images"><option value="manual"><?php esc_html_e( 'Manual order', 'orki-gallery' ); ?></option><option value="title"><?php esc_html_e( 'Sort by title', 'orki-gallery' ); ?></option><option value="id"><?php esc_html_e( 'Sort by ID', 'orki-gallery' ); ?></option><option value="reverse"><?php esc_html_e( 'Reverse order', 'orki-gallery' ); ?></option></select></div>
            <div id="orkigal-image-grid" class="orkigal-image-grid orkigal-image-grid-large"><?php foreach ( $gallery['images'] as $image_id ) { $this->image_card( $image_id ); } ?></div>
            <div class="orkigal-image-empty <?php echo empty( $gallery['images'] ) ? '' : 'is-hidden'; ?>" id="orkigal-image-empty"><span class="dashicons dashicons-format-image"></span><p><?php esc_html_e( 'No images selected yet.', 'orki-gallery' ); ?></p></div>
            <div class="orkigal-video-section"><div class="orkigal-subheading"><strong><?php esc_html_e( 'Videos', 'orki-gallery' ); ?></strong><span><?php esc_html_e( 'WordPress-hosted videos', 'orki-gallery' ); ?></span></div><div id="orkigal-video-grid" class="orkigal-video-grid"><?php foreach ( $gallery['videos'] as $video_id ) { $this->video_card( $video_id ); } ?></div><div class="orkigal-video-empty <?php echo empty( $gallery['videos'] ) ? '' : 'is-hidden'; ?>" id="orkigal-video-empty"><?php esc_html_e( 'No videos added.', 'orki-gallery' ); ?></div></div>
        </section>
        <div class="orkigal-step-footer"><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=orki-gallery' ) ); ?>"><?php esc_html_e( 'Cancel', 'orki-gallery' ); ?></a><button class="button button-primary orkigal-primary" type="submit"><?php esc_html_e( 'Next: Design', 'orki-gallery' ); ?> →</button></div>
        </form>
        <?php
    }

    private function layout_card( $value, $title, $description, $current ) {
        $checked = $value === $current;
        ?><label class="orkigal-layout-card <?php echo $checked ? 'is-selected' : ''; ?>"><input type="radio" name="settings[layout]" value="<?php echo esc_attr( $value ); ?>" <?php checked( $checked ); ?>><span class="orkigal-layout-visual layout-<?php echo esc_attr( $value ); ?>"><i></i><i></i><i></i><i></i><i></i><i></i></span><strong><?php echo esc_html( $title ); ?></strong><small><?php echo esc_html( $description ); ?></small></label><?php
    }

    private function editor_design( $gallery ) {
        $s = $gallery['settings']; $this->save_form_open( $gallery, 'finish' );
        ?>
        <section class="orkigal-design-workspace">
            <div class="orkigal-design-controls">
                <div class="orkigal-panel-heading"><h2><?php esc_html_e( 'Choose a layout', 'orki-gallery' ); ?></h2><p><?php esc_html_e( 'Visual presets make it easy to understand how each gallery behaves.', 'orki-gallery' ); ?></p></div>
                <div class="orkigal-layout-cards">
                    <?php $this->layout_card( 'grid', __( 'Grid', 'orki-gallery' ), __( 'Clean equal-size rows and columns.', 'orki-gallery' ), $s['layout'] ); ?>
                    <?php $this->layout_card( 'masonry', __( 'Masonry', 'orki-gallery' ), __( 'Keeps natural image heights.', 'orki-gallery' ), $s['layout'] ); ?>
                    <?php $this->layout_card( 'justified', __( 'Justified', 'orki-gallery' ), __( 'Flexible photo rows that fill the width.', 'orki-gallery' ), $s['layout'] ); ?>
                    <?php $this->layout_card( 'tiles', __( 'Tiles', 'orki-gallery' ), __( 'Editorial mix of large and small tiles.', 'orki-gallery' ), $s['layout'] ); ?>
                    <?php $this->layout_card( 'carousel', __( 'Carousel', 'orki-gallery' ), __( 'Horizontal gallery with navigation.', 'orki-gallery' ), $s['layout'] ); ?>
                    <?php $this->layout_card( 'slideshow', __( 'Slideshow', 'orki-gallery' ), __( 'One large image at a time.', 'orki-gallery' ), $s['layout'] ); ?>
                </div>

                <div class="orkigal-design-settings">
                    <h3><?php esc_html_e( 'Gallery appearance', 'orki-gallery' ); ?></h3>
                    <div class="orkigal-form-grid compact">
                        <label class="orkigal-field"><span><?php esc_html_e( 'Desktop columns', 'orki-gallery' ); ?></span><select name="settings[columns_desktop]" data-preview-setting="columns_desktop"><?php for ( $i=1;$i<=8;$i++ ) : ?><option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( (int) $s['columns_desktop'], $i ); ?>><?php echo esc_html( (string) $i ); ?></option><?php endfor; ?></select></label>
                        <label class="orkigal-field"><span><?php esc_html_e( 'Tablet', 'orki-gallery' ); ?></span><select name="settings[columns_tablet]" data-preview-setting="columns_tablet"><?php for ( $i=1;$i<=6;$i++ ) : ?><option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( (int) $s['columns_tablet'], $i ); ?>><?php echo esc_html( (string) $i ); ?></option><?php endfor; ?></select></label>
                        <label class="orkigal-field"><span><?php esc_html_e( 'Mobile', 'orki-gallery' ); ?></span><select name="settings[columns_mobile]" data-preview-setting="columns_mobile"><?php for ( $i=1;$i<=4;$i++ ) : ?><option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( (int) $s['columns_mobile'], $i ); ?>><?php echo esc_html( (string) $i ); ?></option><?php endfor; ?></select></label>
                        <label class="orkigal-field"><span><?php esc_html_e( 'Gap', 'orki-gallery' ); ?></span><input type="number" min="0" max="100" name="settings[gap]" value="<?php echo esc_attr( (string) $s['gap'] ); ?>" data-preview-setting="gap"></label>
                        <label class="orkigal-field"><span><?php esc_html_e( 'Image ratio', 'orki-gallery' ); ?></span><select name="settings[ratio]" data-preview-setting="ratio"><option value="auto" <?php selected( $s['ratio'], 'auto' ); ?>><?php esc_html_e( 'Original', 'orki-gallery' ); ?></option><option value="square" <?php selected( $s['ratio'], 'square' ); ?>>1:1</option><option value="landscape" <?php selected( $s['ratio'], 'landscape' ); ?>>4:3</option><option value="portrait" <?php selected( $s['ratio'], 'portrait' ); ?>>3:4</option><option value="wide" <?php selected( $s['ratio'], 'wide' ); ?>>16:9</option></select></label>
                        <label class="orkigal-field"><span><?php esc_html_e( 'Image fit', 'orki-gallery' ); ?></span><select name="settings[fit]" data-preview-setting="fit"><option value="cover" <?php selected( $s['fit'], 'cover' ); ?>><?php esc_html_e( 'Cover', 'orki-gallery' ); ?></option><option value="contain" <?php selected( $s['fit'], 'contain' ); ?>><?php esc_html_e( 'Contain', 'orki-gallery' ); ?></option></select></label>
                        <label class="orkigal-field"><span><?php esc_html_e( 'Image resolution', 'orki-gallery' ); ?></span><select name="settings[image_size]"><?php foreach ( Repository::image_size_options() as $size_key => $size_label ) : ?><option value="<?php echo esc_attr( $size_key ); ?>" <?php selected( $s['image_size'], $size_key ); ?>><?php echo esc_html( $size_label ); ?></option><?php endforeach; ?></select></label>
                        <label class="orkigal-field"><span><?php esc_html_e( 'Corner radius', 'orki-gallery' ); ?></span><input type="number" min="0" max="100" name="settings[radius]" value="<?php echo esc_attr( (string) $s['radius'] ); ?>" data-preview-setting="radius"></label>
                        <label class="orkigal-field orkigal-stage-setting"><span><?php esc_html_e( 'Stage height', 'orki-gallery' ); ?></span><input type="number" min="180" max="1200" name="settings[stage_height]" value="<?php echo esc_attr( (string) $s['stage_height'] ); ?>" data-preview-setting="stage_height"></label>
                        <label class="orkigal-field orkigal-motion-setting"><span><?php esc_html_e( 'Transition speed (ms)', 'orki-gallery' ); ?></span><input type="number" min="100" max="3000" step="50" name="settings[transition_speed]" value="<?php echo esc_attr( (string) $s['transition_speed'] ); ?>"></label>
                    </div>
                    <div class="orkigal-feature-toggles">
                        <label><input type="hidden" name="settings[lightbox]" value="0"><input type="checkbox" name="settings[lightbox]" value="1" <?php checked( $s['lightbox'] ); ?>><span><b><?php esc_html_e( 'Lightbox', 'orki-gallery' ); ?></b><small><?php esc_html_e( 'Open images in a focused viewer.', 'orki-gallery' ); ?></small></span></label>
                        <label><input type="hidden" name="settings[captions]" value="0"><input type="checkbox" name="settings[captions]" value="1" <?php checked( $s['captions'] ); ?>><span><b><?php esc_html_e( 'Captions', 'orki-gallery' ); ?></b><small><?php esc_html_e( 'Show Media Library captions.', 'orki-gallery' ); ?></small></span></label>
                        <label><input type="hidden" name="settings[hover_zoom]" value="0"><input type="checkbox" name="settings[hover_zoom]" value="1" <?php checked( $s['hover_zoom'] ); ?>><span><b><?php esc_html_e( 'Hover zoom', 'orki-gallery' ); ?></b><small><?php esc_html_e( 'A subtle interactive zoom.', 'orki-gallery' ); ?></small></span></label>
                        <label class="orkigal-motion-setting"><input type="hidden" name="settings[autoplay]" value="0"><input type="checkbox" name="settings[autoplay]" value="1" <?php checked( $s['autoplay'] ); ?>><span><b><?php esc_html_e( 'Autoplay', 'orki-gallery' ); ?></b><small><?php esc_html_e( 'Automatically move carousel/slideshow.', 'orki-gallery' ); ?></small></span></label>
                        <label class="orkigal-motion-setting"><input type="hidden" name="settings[pause_on_hover]" value="0"><input type="checkbox" name="settings[pause_on_hover]" value="1" <?php checked( $s['pause_on_hover'] ); ?>><span><b><?php esc_html_e( 'Pause on hover', 'orki-gallery' ); ?></b><small><?php esc_html_e( 'Pause autoplay while visitors interact.', 'orki-gallery' ); ?></small></span></label>
                        <label class="orkigal-motion-setting"><input type="hidden" name="settings[loop]" value="0"><input type="checkbox" name="settings[loop]" value="1" <?php checked( $s['loop'] ); ?>><span><b><?php esc_html_e( 'Infinite loop', 'orki-gallery' ); ?></b><small><?php esc_html_e( 'Wrap from the last slide back to the first.', 'orki-gallery' ); ?></small></span></label>
                        <label class="orkigal-motion-setting"><input type="hidden" name="settings[show_arrows]" value="0"><input type="checkbox" name="settings[show_arrows]" value="1" <?php checked( $s['show_arrows'] ); ?>><span><b><?php esc_html_e( 'Arrows', 'orki-gallery' ); ?></b><small><?php esc_html_e( 'Show previous/next controls.', 'orki-gallery' ); ?></small></span></label>
                        <label class="orkigal-motion-setting"><input type="hidden" name="settings[show_dots]" value="0"><input type="checkbox" name="settings[show_dots]" value="1" <?php checked( $s['show_dots'] ); ?>><span><b><?php esc_html_e( 'Dots', 'orki-gallery' ); ?></b><small><?php esc_html_e( 'Show navigation position.', 'orki-gallery' ); ?></small></span></label>
                    </div>
                </div>
            </div>
            <aside class="orkigal-live-preview"><div class="orkigal-preview-head"><div><strong><?php esc_html_e( 'Live Preview', 'orki-gallery' ); ?></strong><small><?php esc_html_e( '100% responsive width', 'orki-gallery' ); ?></small></div><div class="orkigal-device-icons"><span class="dashicons dashicons-desktop"></span><span class="dashicons dashicons-tablet"></span><span class="dashicons dashicons-smartphone"></span></div></div><div class="orkigal-preview-canvas"><?php echo Renderer::render( $gallery['images'], $s, array( 'gallery_id' => $gallery['id'], 'videos' => $gallery['videos'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></aside>
        </section>
        <div class="orkigal-step-footer"><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page'=>'orki-gallery-editor','gallery_id'=>$gallery['id'],'step'=>'media' ), admin_url( 'admin.php' ) ) ); ?>">← <?php esc_html_e( 'Back', 'orki-gallery' ); ?></a><button class="button button-primary orkigal-primary" type="submit"><?php esc_html_e( 'Next: Finish', 'orki-gallery' ); ?> →</button></div>
        </form>
        <?php
    }

    private function editor_finish( $gallery ) {
        ?>
        <section class="orkigal-finish-workspace">
            <div class="orkigal-finish-main"><div class="orkigal-panel-heading"><h2><?php esc_html_e( 'Review your gallery', 'orki-gallery' ); ?></h2><p><?php esc_html_e( 'Everything looks good? Publish it and use the shortcode anywhere.', 'orki-gallery' ); ?></p></div><div class="orkigal-final-preview"><?php echo Renderer::render( $gallery['images'], $gallery['settings'], array( 'gallery_id'=>$gallery['id'], 'videos'=>$gallery['videos'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></div>
            <aside class="orkigal-publish-card"><div class="orkigal-success-orb"><span class="dashicons dashicons-yes-alt"></span></div><h3><?php echo esc_html( $gallery['title'] ); ?></h3><p><?php echo 'publish' === $gallery['status'] ? esc_html__( 'This gallery is published and ready to embed.', 'orki-gallery' ) : esc_html__( 'Publish when you are ready.', 'orki-gallery' ); ?></p><button type="button" class="orkigal-shortcode-copy orkigal-shortcode-large" data-copy="<?php echo esc_attr( Repository::shortcode( $gallery['id'] ) ); ?>"><code><?php echo esc_html( Repository::shortcode( $gallery['id'] ) ); ?></code><span class="dashicons dashicons-admin-page"></span></button>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="orkigal_save_gallery"><input type="hidden" name="gallery_id" value="<?php echo esc_attr( (string) $gallery['id'] ); ?>"><input type="hidden" name="next_step" value="finish"><?php wp_nonce_field( 'orkigal_save_gallery_' . $gallery['id'], 'orkigal_nonce' ); ?><label class="orkigal-field"><span><?php esc_html_e( 'Gallery title', 'orki-gallery' ); ?></span><input type="text" name="title" value="<?php echo esc_attr( $gallery['title'] ); ?>" required></label><div class="orkigal-publish-actions"><button type="submit" name="status" value="draft" class="button"><?php esc_html_e( 'Save Draft', 'orki-gallery' ); ?></button><button type="submit" name="status" value="publish" class="button button-primary orkigal-primary"><?php echo 'publish' === $gallery['status'] ? esc_html__( 'Update Gallery', 'orki-gallery' ) : esc_html__( 'Publish Gallery', 'orki-gallery' ); ?></button></div></form>
            </aside>
        </section>
        <div class="orkigal-step-footer"><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page'=>'orki-gallery-editor','gallery_id'=>$gallery['id'],'step'=>'design' ), admin_url( 'admin.php' ) ) ); ?>">← <?php esc_html_e( 'Back to Design', 'orki-gallery' ); ?></a><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=orki-gallery' ) ); ?>"><?php esc_html_e( 'View Galleries', 'orki-gallery' ); ?></a></div>
        <?php
    }

    private function new_gallery_screen() {
        $this->page_header( __( 'Create a New Gallery', 'orki-gallery' ), __( 'Start with a title, then add your media.', 'orki-gallery' ), false );
        ?>
        <section class="orkigal-new-gallery-shell"><div class="orkigal-new-gallery-card"><div class="orkigal-create-icon"><span class="dashicons dashicons-images-alt2"></span></div><h2><?php esc_html_e( 'Give your gallery a name', 'orki-gallery' ); ?></h2><p><?php esc_html_e( 'You can change it later. The shortcode will be generated automatically.', 'orki-gallery' ); ?></p><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="orkigal_create_gallery"><?php wp_nonce_field( 'orkigal_create_gallery', 'orkigal_nonce' ); ?><label class="orkigal-field"><span><?php esc_html_e( 'Gallery Title', 'orki-gallery' ); ?></span><input type="text" name="title" placeholder="<?php esc_attr_e( 'e.g. Summer Collection', 'orki-gallery' ); ?>" required autofocus></label><button type="submit" class="button button-primary orkigal-primary orkigal-big-button"><?php esc_html_e( 'Create & Add Media', 'orki-gallery' ); ?> →</button></form></div></section>
        <?php $this->page_footer();
    }

    private function image_card( $image_id ) {
        $thumb = wp_get_attachment_image_url( $image_id, 'thumbnail' ); $title = get_the_title( $image_id ); if ( ! $thumb ) { return; }
        ?><div class="orkigal-image-card" data-id="<?php echo esc_attr( (string) $image_id ); ?>" data-title="<?php echo esc_attr( strtolower( $title ) ); ?>"><span class="orkigal-drag"><span class="dashicons dashicons-move"></span></span><img src="<?php echo esc_url( $thumb ); ?>" alt=""><div><span title="<?php echo esc_attr( $title ); ?>"><?php echo esc_html( $title ); ?></span><button type="button" class="orkigal-remove-image" aria-label="<?php esc_attr_e( 'Remove image', 'orki-gallery' ); ?>">×</button></div></div><?php
    }

    private function video_card( $video_id ) {
        $url = wp_get_attachment_url( $video_id ); $title = get_the_title( $video_id ); if ( ! $url ) { return; }
        ?><div class="orkigal-video-card" data-id="<?php echo esc_attr( (string) $video_id ); ?>"><div class="orkigal-video-thumb"><span class="dashicons dashicons-video-alt3"></span></div><div class="orkigal-video-meta"><span title="<?php echo esc_attr( $title ); ?>"><?php echo esc_html( $title ? $title : basename( $url ) ); ?></span><button type="button" class="orkigal-remove-video" aria-label="<?php esc_attr_e( 'Remove video', 'orki-gallery' ); ?>">×</button></div></div><?php
    }

    public function create_gallery() {
        if ( ! current_user_can( 'upload_files' ) ) { wp_die( esc_html__( 'Permission denied.', 'orki-gallery' ) ); }
        check_admin_referer( 'orkigal_create_gallery', 'orkigal_nonce' );
        $title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $id = Repository::create( $title ); if ( is_wp_error( $id ) ) { wp_die( esc_html( $id->get_error_message() ) ); }
        wp_safe_redirect( add_query_arg( array( 'page'=>'orki-gallery-editor','gallery_id'=>$id,'step'=>'media' ), admin_url( 'admin.php' ) ) ); exit;
    }

    public function save_gallery() {
        $gallery_id = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0;
        if ( ! current_user_can( 'upload_files' ) || ! $gallery_id ) { wp_die( esc_html__( 'Permission denied.', 'orki-gallery' ) ); }
        check_admin_referer( 'orkigal_save_gallery_' . $gallery_id, 'orkigal_nonce' );
        $gallery = Repository::get( $gallery_id );
        $data = array();
        if ( isset( $_POST['title'] ) ) { $data['title'] = sanitize_text_field( wp_unslash( $_POST['title'] ) ); }
        $data['status'] = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : ( $gallery ? $gallery['status'] : 'draft' );
        if ( isset( $_POST['images'] ) ) { $data['images'] = sanitize_text_field( wp_unslash( $_POST['images'] ) ); }
        if ( isset( $_POST['videos'] ) ) { $data['videos'] = sanitize_text_field( wp_unslash( $_POST['videos'] ) ); }
        if ( isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ) { $data['settings'] = wp_unslash( $_POST['settings'] ); }
        $result = Repository::save( $gallery_id, $data ); if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ) ); }
        $next = isset( $_POST['next_step'] ) ? sanitize_key( wp_unslash( $_POST['next_step'] ) ) : 'finish'; if ( ! in_array( $next, array( 'media','design','finish' ), true ) ) { $next='finish'; }
        wp_safe_redirect( add_query_arg( array( 'page'=>'orki-gallery-editor','gallery_id'=>$gallery_id,'step'=>$next,'saved'=>1 ), admin_url( 'admin.php' ) ) ); exit;
    }

    public function delete_gallery() {
        $gallery_id = isset( $_GET['gallery_id'] ) ? absint( $_GET['gallery_id'] ) : 0;
        if ( ! current_user_can( 'upload_files' ) || ! $gallery_id ) { wp_die( esc_html__( 'Permission denied.', 'orki-gallery' ) ); }
        check_admin_referer( 'orkigal_delete_gallery_' . $gallery_id ); Repository::delete( $gallery_id ); wp_safe_redirect( admin_url( 'admin.php?page=orki-gallery' ) ); exit;
    }

    public function duplicate_gallery() {
        $gallery_id = isset( $_GET['gallery_id'] ) ? absint( $_GET['gallery_id'] ) : 0;
        if ( ! current_user_can( 'upload_files' ) || ! $gallery_id ) { wp_die( esc_html__( 'Permission denied.', 'orki-gallery' ) ); }
        check_admin_referer( 'orkigal_duplicate_gallery_' . $gallery_id );
        $copy_id = Repository::duplicate( $gallery_id );
        if ( is_wp_error( $copy_id ) ) { wp_die( esc_html( $copy_id->get_error_message() ) ); }
        wp_safe_redirect( add_query_arg( array( 'page' => 'orki-gallery-editor', 'gallery_id' => $copy_id, 'step' => 'finish' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function about() {
        if ( ! current_user_can( 'upload_files' ) ) { wp_die( esc_html__( 'Permission denied.', 'orki-gallery' ) ); }
        $this->page_header( __( 'About Orki Gallery', 'orki-gallery' ), __( 'A lightweight WordPress gallery builder created for a clear, visual publishing workflow.', 'orki-gallery' ), false );
        ?>
        <section class="orkigal-about-hero">
            <div class="orkigal-about-copy">
                <span class="orkigal-kicker"><?php esc_html_e( 'ORKI GALLERY', 'orki-gallery' ); ?></span>
                <h2><?php esc_html_e( 'Create galleries visually, then publish them anywhere.', 'orki-gallery' ); ?></h2>
                <p><?php esc_html_e( 'Orki Gallery helps you build responsive image and video galleries with visual layouts, live preview, lightbox viewing, shortcode, Gutenberg, and Elementor support.', 'orki-gallery' ); ?></p>
                <div class="orkigal-about-actions"><a class="button button-primary orkigal-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=orki-gallery-editor' ) ); ?>"><?php esc_html_e( 'Create a Gallery', 'orki-gallery' ); ?></a><a class="button" href="<?php echo esc_url( 'https://orki.in/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit orki.in', 'orki-gallery' ); ?> ↗</a></div>
            </div>
            <div class="orkigal-about-brand-card"><?php $this->brand(); ?><p><?php esc_html_e( 'Responsive galleries with a simple WordPress-native workflow.', 'orki-gallery' ); ?></p></div>
        </section>

        <section class="orkigal-card orkigal-about-section">
            <div class="orkigal-about-section-head"><div><span class="orkigal-kicker"><?php esc_html_e( 'PLUGIN DETAILS', 'orki-gallery' ); ?></span><h2><?php esc_html_e( 'Orki Gallery', 'orki-gallery' ); ?></h2></div><span class="orkigal-version-badge">v<?php echo esc_html( ORKIGAL_VERSION ); ?></span></div>
            <div class="orkigal-meta-table">
                <div><b><?php esc_html_e( 'Product website', 'orki-gallery' ); ?></b><span><a href="<?php echo esc_url( 'https://orki.in/' ); ?>" target="_blank" rel="noopener noreferrer">orki.in</a></span></div>
                <div><b><?php esc_html_e( 'Version', 'orki-gallery' ); ?></b><span><?php echo esc_html( ORKIGAL_VERSION ); ?></span></div>
                <div><b><?php esc_html_e( 'Requires at least', 'orki-gallery' ); ?></b><span>6.0</span></div>
                <div><b><?php esc_html_e( 'Tested up to', 'orki-gallery' ); ?></b><span>7.1</span></div>
                <div><b><?php esc_html_e( 'Requires PHP', 'orki-gallery' ); ?></b><span>7.4</span></div>
                <div><b><?php esc_html_e( 'License', 'orki-gallery' ); ?></b><span><a href="<?php echo esc_url( 'https://www.gnu.org/licenses/gpl-2.0.html' ); ?>" target="_blank" rel="noopener noreferrer">GPLv2 or later</a></span></div>
            </div>
        </section>

        <section class="orkigal-card orkigal-about-section">
            <div class="orkigal-about-section-head"><div><span class="orkigal-kicker"><?php esc_html_e( 'DEVELOPERS', 'orki-gallery' ); ?></span><h2><?php esc_html_e( 'Built by', 'orki-gallery' ); ?></h2><p><?php esc_html_e( 'The developers currently listed for Orki Gallery are:', 'orki-gallery' ); ?></p></div></div>
            <div class="orkigal-feature-grid">
                <div><span class="dashicons dashicons-admin-users"></span><b>Kunal Debnath</b><small><a href="<?php echo esc_url( 'https://github.com/infronixdigital' ); ?>" target="_blank" rel="noopener noreferrer">github.com/infronixdigital ↗</a></small></div>
                <div><span class="dashicons dashicons-admin-users"></span><b>Bikram Bagdi</b><small><a href="<?php echo esc_url( 'https://github.com/bikram8538' ); ?>" target="_blank" rel="noopener noreferrer">github.com/bikram8538 ↗</a></small></div>
            </div>
        </section>
        <?php
        $this->page_footer();
    }

    public function settings() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'orki-gallery' ) ); }
        $defaults = Repository::sanitize_settings( get_option( 'orkigal_default_settings', Repository::defaults() ) );
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'gallery'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! in_array( $tab, array( 'gallery','images','viewer','advanced' ), true ) ) { $tab='gallery'; }
        $this->page_header( __( 'Settings', 'orki-gallery' ), __( 'Set defaults for new galleries. Individual galleries can override them.', 'orki-gallery' ), false );
        ?>
        <div class="orkigal-settings-layout"><nav class="orkigal-settings-nav"><a class="<?php echo 'gallery'===$tab?'active':''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=orki-gallery-settings&tab=gallery' ) ); ?>"><span class="dashicons dashicons-layout"></span><?php esc_html_e( 'Gallery Defaults', 'orki-gallery' ); ?></a><a class="<?php echo 'images'===$tab?'active':''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=orki-gallery-settings&tab=images' ) ); ?>"><span class="dashicons dashicons-format-image"></span><?php esc_html_e( 'Image Display', 'orki-gallery' ); ?></a><a class="<?php echo 'viewer'===$tab?'active':''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=orki-gallery-settings&tab=viewer' ) ); ?>"><span class="dashicons dashicons-visibility"></span><?php esc_html_e( 'Viewer & Effects', 'orki-gallery' ); ?></a><a class="<?php echo 'advanced'===$tab?'active':''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=orki-gallery-settings&tab=advanced' ) ); ?>"><span class="dashicons dashicons-admin-generic"></span><?php esc_html_e( 'Advanced', 'orki-gallery' ); ?></a></nav><main class="orkigal-settings-content">
        <?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?><div class="notice notice-success inline"><p><?php esc_html_e( 'Settings saved.', 'orki-gallery' ); ?></p></div><?php endif; ?>
        <?php if ( 'advanced' === $tab ) : ?>
            <section class="orkigal-settings-section"><h2><?php esc_html_e( 'Advanced', 'orki-gallery' ); ?></h2><p><?php esc_html_e( 'Maintenance controls for Orki Gallery. Your media files are never deleted by these actions.', 'orki-gallery' ); ?></p><div class="orkigal-advanced-stack"><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="orkigal_restart_setup"><?php wp_nonce_field( 'orkigal_restart_setup', 'orkigal_nonce' ); ?><h3><?php esc_html_e( 'Setup wizard', 'orki-gallery' ); ?></h3><p><?php esc_html_e( 'Run the welcome wizard again without changing existing galleries.', 'orki-gallery' ); ?></p><button class="button" type="submit"><?php esc_html_e( 'Restart Setup Wizard', 'orki-gallery' ); ?></button></form><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="orkigal_save_advanced"><?php wp_nonce_field( 'orkigal_save_advanced', 'orkigal_nonce' ); ?><h3><?php esc_html_e( 'Uninstall behavior', 'orki-gallery' ); ?></h3><label class="orkigal-danger-option"><input type="checkbox" name="delete_data" value="1" <?php checked( (int) get_option( 'orkigal_delete_data_on_uninstall', 0 ), 1 ); ?>><span><b><?php esc_html_e( 'Delete gallery data when the plugin is uninstalled', 'orki-gallery' ); ?></b><small><?php esc_html_e( 'Off by default. Media Library files are never deleted.', 'orki-gallery' ); ?></small></span></label><p><button class="button" type="submit"><?php esc_html_e( 'Save Advanced Settings', 'orki-gallery' ); ?></button></p></form></div></section>
        <?php else : ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="orkigal_save_defaults"><input type="hidden" name="return_tab" value="<?php echo esc_attr( $tab ); ?>"><?php wp_nonce_field( 'orkigal_save_defaults', 'orkigal_nonce' ); ?>
            <section class="orkigal-settings-section">
                <?php if ( 'gallery' === $tab ) : ?><h2><?php esc_html_e( 'Gallery Defaults', 'orki-gallery' ); ?></h2><p><?php esc_html_e( 'Choose the starting layout and responsive columns for new galleries.', 'orki-gallery' ); ?></p><div class="orkigal-form-grid"><label class="orkigal-field"><span><?php esc_html_e( 'Default layout', 'orki-gallery' ); ?></span><select name="settings[layout]"><?php foreach ( Repository::layout_options() as $key=>$label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $defaults['layout'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label><label class="orkigal-field"><span><?php esc_html_e( 'Desktop columns', 'orki-gallery' ); ?></span><input type="number" min="1" max="8" name="settings[columns_desktop]" value="<?php echo esc_attr( (string)$defaults['columns_desktop'] ); ?>"></label><label class="orkigal-field"><span><?php esc_html_e( 'Tablet columns', 'orki-gallery' ); ?></span><input type="number" min="1" max="6" name="settings[columns_tablet]" value="<?php echo esc_attr( (string)$defaults['columns_tablet'] ); ?>"></label><label class="orkigal-field"><span><?php esc_html_e( 'Mobile columns', 'orki-gallery' ); ?></span><input type="number" min="1" max="4" name="settings[columns_mobile]" value="<?php echo esc_attr( (string)$defaults['columns_mobile'] ); ?>"></label></div>
                <?php elseif ( 'images' === $tab ) : ?><h2><?php esc_html_e( 'Image Display', 'orki-gallery' ); ?></h2><p><?php esc_html_e( 'Control framing and spacing for new galleries.', 'orki-gallery' ); ?></p><div class="orkigal-form-grid"><label class="orkigal-field"><span><?php esc_html_e( 'Image ratio', 'orki-gallery' ); ?></span><select name="settings[ratio]"><option value="auto" <?php selected($defaults['ratio'],'auto'); ?>><?php esc_html_e('Original','orki-gallery'); ?></option><option value="square" <?php selected($defaults['ratio'],'square'); ?>>1:1</option><option value="landscape" <?php selected($defaults['ratio'],'landscape'); ?>>4:3</option><option value="portrait" <?php selected($defaults['ratio'],'portrait'); ?>>3:4</option><option value="wide" <?php selected($defaults['ratio'],'wide'); ?>>16:9</option></select></label><label class="orkigal-field"><span><?php esc_html_e( 'Image fit', 'orki-gallery' ); ?></span><select name="settings[fit]"><option value="cover" <?php selected($defaults['fit'],'cover'); ?>><?php esc_html_e('Cover','orki-gallery'); ?></option><option value="contain" <?php selected($defaults['fit'],'contain'); ?>><?php esc_html_e('Contain','orki-gallery'); ?></option></select></label><label class="orkigal-field"><span><?php esc_html_e( 'Image resolution', 'orki-gallery' ); ?></span><select name="settings[image_size]"><?php foreach ( Repository::image_size_options() as $size_key => $size_label ) : ?><option value="<?php echo esc_attr( $size_key ); ?>" <?php selected( $defaults['image_size'], $size_key ); ?>><?php echo esc_html( $size_label ); ?></option><?php endforeach; ?></select></label><label class="orkigal-field"><span><?php esc_html_e( 'Gap', 'orki-gallery' ); ?></span><input type="number" min="0" max="100" name="settings[gap]" value="<?php echo esc_attr((string)$defaults['gap']); ?>"></label><label class="orkigal-field"><span><?php esc_html_e( 'Corner radius', 'orki-gallery' ); ?></span><input type="number" min="0" max="100" name="settings[radius]" value="<?php echo esc_attr((string)$defaults['radius']); ?>"></label><label class="orkigal-field"><span><?php esc_html_e( 'Slideshow height', 'orki-gallery' ); ?></span><input type="number" min="180" max="1200" name="settings[stage_height]" value="<?php echo esc_attr((string)$defaults['stage_height']); ?>"></label></div>
                <?php else : ?><h2><?php esc_html_e( 'Viewer & Effects', 'orki-gallery' ); ?></h2><p><?php esc_html_e( 'Choose the default interaction behavior.', 'orki-gallery' ); ?></p><div class="orkigal-settings-toggles"><label><input type="hidden" name="settings[lightbox]" value="0"><input type="checkbox" name="settings[lightbox]" value="1" <?php checked($defaults['lightbox']); ?>><span><b><?php esc_html_e('Lightbox','orki-gallery'); ?></b><small><?php esc_html_e('Open images in a viewer.','orki-gallery'); ?></small></span></label><label><input type="hidden" name="settings[captions]" value="0"><input type="checkbox" name="settings[captions]" value="1" <?php checked($defaults['captions']); ?>><span><b><?php esc_html_e('Captions','orki-gallery'); ?></b><small><?php esc_html_e('Show attachment captions.','orki-gallery'); ?></small></span></label><label><input type="hidden" name="settings[hover_zoom]" value="0"><input type="checkbox" name="settings[hover_zoom]" value="1" <?php checked($defaults['hover_zoom']); ?>><span><b><?php esc_html_e('Hover zoom','orki-gallery'); ?></b><small><?php esc_html_e('Subtle image zoom on hover.','orki-gallery'); ?></small></span></label><label><input type="hidden" name="settings[autoplay]" value="0"><input type="checkbox" name="settings[autoplay]" value="1" <?php checked($defaults['autoplay']); ?>><span><b><?php esc_html_e('Autoplay','orki-gallery'); ?></b><small><?php esc_html_e('For carousel and slideshow.','orki-gallery'); ?></small></span></label><label><input type="hidden" name="settings[pause_on_hover]" value="0"><input type="checkbox" name="settings[pause_on_hover]" value="1" <?php checked($defaults['pause_on_hover']); ?>><span><b><?php esc_html_e('Pause on hover','orki-gallery'); ?></b><small><?php esc_html_e('Pause while visitors interact.','orki-gallery'); ?></small></span></label><label><input type="hidden" name="settings[loop]" value="0"><input type="checkbox" name="settings[loop]" value="1" <?php checked($defaults['loop']); ?>><span><b><?php esc_html_e('Infinite loop','orki-gallery'); ?></b><small><?php esc_html_e('Wrap navigation at the ends.','orki-gallery'); ?></small></span></label><label><input type="hidden" name="settings[show_arrows]" value="0"><input type="checkbox" name="settings[show_arrows]" value="1" <?php checked($defaults['show_arrows']); ?>><span><b><?php esc_html_e('Navigation arrows','orki-gallery'); ?></b><small><?php esc_html_e('Previous and next buttons.','orki-gallery'); ?></small></span></label><label><input type="hidden" name="settings[show_dots]" value="0"><input type="checkbox" name="settings[show_dots]" value="1" <?php checked($defaults['show_dots']); ?>><span><b><?php esc_html_e('Navigation dots','orki-gallery'); ?></b><small><?php esc_html_e('Position indicators.','orki-gallery'); ?></small></span></label></div><div class="orkigal-form-grid compact"><label class="orkigal-field"><span><?php esc_html_e('Autoplay speed (ms)','orki-gallery'); ?></span><input type="number" min="1000" max="20000" step="100" name="settings[autoplay_speed]" value="<?php echo esc_attr((string)$defaults['autoplay_speed']); ?>"></label><label class="orkigal-field"><span><?php esc_html_e('Transition speed (ms)','orki-gallery'); ?></span><input type="number" min="100" max="3000" step="50" name="settings[transition_speed]" value="<?php echo esc_attr((string)$defaults['transition_speed']); ?>"></label></div>
                <?php endif; ?>
                <p class="orkigal-settings-save"><button type="submit" class="button button-primary orkigal-primary"><?php esc_html_e( 'Save Settings', 'orki-gallery' ); ?></button></p>
            </section></form>
        <?php endif; ?>
        </main></div>
        <?php $this->page_footer();
    }

    public function save_defaults() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'orki-gallery' ) ); }
        check_admin_referer( 'orkigal_save_defaults', 'orkigal_nonce' );
        $current = Repository::sanitize_settings( get_option( 'orkigal_default_settings', Repository::defaults() ) );
        $posted = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();
        $defaults = Repository::sanitize_settings( array_merge( $current, $posted ) ); update_option( 'orkigal_default_settings', $defaults, false );
        $tab = isset( $_POST['return_tab'] ) ? sanitize_key( wp_unslash( $_POST['return_tab'] ) ) : 'gallery';
        wp_safe_redirect( add_query_arg( array( 'page'=>'orki-gallery-settings','tab'=>$tab,'saved'=>1 ), admin_url( 'admin.php' ) ) ); exit;
    }

    public function save_advanced() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'orki-gallery' ) ); }
        check_admin_referer( 'orkigal_save_advanced', 'orkigal_nonce' );
        update_option( 'orkigal_delete_data_on_uninstall', empty( $_POST['delete_data'] ) ? 0 : 1, false );
        wp_safe_redirect( add_query_arg( array( 'page' => 'orki-gallery-settings', 'tab' => 'advanced', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function setup_wizard() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'orki-gallery' ) ); }
        $step = isset( $_GET['step'] ) ? max(1,min(4,absint($_GET['step']))) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $defaults = Repository::sanitize_settings( get_option( 'orkigal_default_settings', Repository::defaults() ) );
        ?><style>#adminmenumain,#wpadminbar,#wpfooter{display:none!important}html.wp-toolbar{padding-top:0!important}#wpcontent{margin-left:0!important;padding-left:0!important}#wpbody-content{padding-bottom:0!important}</style>
        <div class="orkigal-setup-screen"><div class="orkigal-setup-brand"><?php $this->brand(); ?></div><div class="orkigal-setup-progress"><?php for($i=1;$i<=4;$i++): ?><span class="<?php echo $i<=$step?'active':''; ?>"></span><?php endfor; ?></div>
            <section class="orkigal-setup-card">
            <?php if ( 1 === $step ) : ?><div class="orkigal-setup-welcome"><div class="orkigal-setup-hero-icon"><span class="dashicons dashicons-images-alt2"></span></div><span class="orkigal-kicker"><?php esc_html_e('WELCOME','orki-gallery'); ?></span><h1><?php esc_html_e( 'Welcome to Orki Gallery', 'orki-gallery' ); ?></h1><p><?php esc_html_e( 'A simple gallery workflow designed for WordPress. Set your defaults now and your first gallery will be ready in minutes.', 'orki-gallery' ); ?></p><a class="button button-primary orkigal-primary orkigal-big-button" href="<?php echo esc_url( admin_url('admin.php?page=orki-gallery-setup&step=2') ); ?>"><?php esc_html_e("Let's Get Started",'orki-gallery'); ?> →</a></div>
            <?php elseif ( 2 === $step ) : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="orkigal_setup_save"><input type="hidden" name="setup_step" value="2"><?php wp_nonce_field('orkigal_setup_save','orkigal_nonce'); ?><span class="orkigal-step-label"><?php esc_html_e('Step 2 of 4','orki-gallery'); ?></span><h2><?php esc_html_e('Choose your default gallery style','orki-gallery'); ?></h2><p><?php esc_html_e('You can change this for every gallery later.','orki-gallery'); ?></p><div class="orkigal-layout-cards setup"><?php foreach ( Repository::layout_options() as $key=>$label ) { $desc = array('grid'=>__('Balanced rows and columns.','orki-gallery'),'masonry'=>__('Natural image heights.','orki-gallery'),'justified'=>__('Photo rows fill the width.','orki-gallery'),'tiles'=>__('Editorial tile pattern.','orki-gallery'),'carousel'=>__('Horizontal browsing.','orki-gallery'),'slideshow'=>__('One hero image at a time.','orki-gallery')); $this->layout_card($key,$label,$desc[$key],$defaults['layout']); } ?></div><div class="orkigal-setup-actions"><a href="<?php echo esc_url(admin_url('admin.php?page=orki-gallery-setup&step=1')); ?>">← <?php esc_html_e('Back','orki-gallery'); ?></a><button class="button button-primary orkigal-primary" type="submit"><?php esc_html_e('Save and Continue','orki-gallery'); ?> →</button></div></form>
            <?php elseif ( 3 === $step ) : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="orkigal_setup_save"><input type="hidden" name="setup_step" value="3"><?php wp_nonce_field('orkigal_setup_save','orkigal_nonce'); ?><span class="orkigal-step-label"><?php esc_html_e('Step 3 of 4','orki-gallery'); ?></span><h2><?php esc_html_e('Choose the gallery experience','orki-gallery'); ?></h2><p><?php esc_html_e('These are only defaults. No account, license, or upgrade is required.','orki-gallery'); ?></p><div class="orkigal-settings-toggles setup"><label><input type="hidden" name="settings[lightbox]" value="0"><input type="checkbox" name="settings[lightbox]" value="1" <?php checked($defaults['lightbox']); ?>><span><b><?php esc_html_e('Lightbox viewer','orki-gallery'); ?></b><small><?php esc_html_e('Open images larger without leaving the page.','orki-gallery'); ?></small></span></label><label><input type="hidden" name="settings[captions]" value="0"><input type="checkbox" name="settings[captions]" value="1" <?php checked($defaults['captions']); ?>><span><b><?php esc_html_e('Captions','orki-gallery'); ?></b><small><?php esc_html_e('Use Media Library captions under images.','orki-gallery'); ?></small></span></label><label><input type="hidden" name="settings[hover_zoom]" value="0"><input type="checkbox" name="settings[hover_zoom]" value="1" <?php checked($defaults['hover_zoom']); ?>><span><b><?php esc_html_e('Hover zoom','orki-gallery'); ?></b><small><?php esc_html_e('A small, polished interaction on desktop.','orki-gallery'); ?></small></span></label><label><input type="hidden" name="settings[autoplay]" value="0"><input type="checkbox" name="settings[autoplay]" value="1" <?php checked($defaults['autoplay']); ?>><span><b><?php esc_html_e('Motion autoplay','orki-gallery'); ?></b><small><?php esc_html_e('Useful for carousel and slideshow layouts.','orki-gallery'); ?></small></span></label></div><div class="orkigal-setup-actions"><a href="<?php echo esc_url(admin_url('admin.php?page=orki-gallery-setup&step=2')); ?>">← <?php esc_html_e('Back','orki-gallery'); ?></a><button class="button button-primary orkigal-primary" type="submit"><?php esc_html_e('Save and Continue','orki-gallery'); ?> →</button></div></form>
            <?php else : ?><div class="orkigal-setup-welcome"><div class="orkigal-success-orb large"><span class="dashicons dashicons-yes"></span></div><span class="orkigal-step-label"><?php esc_html_e('Step 4 of 4','orki-gallery'); ?></span><h1><?php esc_html_e('Orki Gallery is ready','orki-gallery'); ?></h1><p><?php esc_html_e('Create your first gallery now, or open Settings to fine-tune global defaults.','orki-gallery'); ?></p><div class="orkigal-ready-actions"><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="orkigal_setup_save"><input type="hidden" name="setup_step" value="complete"><input type="hidden" name="destination" value="create"><?php wp_nonce_field('orkigal_setup_save','orkigal_nonce'); ?><button class="button button-primary orkigal-primary orkigal-big-button" type="submit"><?php esc_html_e('Create Your First Gallery','orki-gallery'); ?> →</button></form><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="orkigal_setup_save"><input type="hidden" name="setup_step" value="complete"><input type="hidden" name="destination" value="settings"><?php wp_nonce_field('orkigal_setup_save','orkigal_nonce'); ?><button class="button orkigal-big-button" type="submit"><?php esc_html_e('Open Settings','orki-gallery'); ?></button></form></div></div><?php endif; ?>
            </section><a class="orkigal-exit-setup" href="<?php echo esc_url(admin_url('admin.php?page=orki-gallery')); ?>"><?php esc_html_e('Close setup wizard','orki-gallery'); ?></a></div>
        <?php
    }

    public function setup_save() {
        if ( ! current_user_can('manage_options') ) { wp_die(esc_html__('Permission denied.','orki-gallery')); }
        check_admin_referer('orkigal_setup_save','orkigal_nonce');
        $step = isset($_POST['setup_step']) ? sanitize_key(wp_unslash($_POST['setup_step'])) : '';
        $current = Repository::sanitize_settings(get_option('orkigal_default_settings',Repository::defaults()));
        $posted = isset($_POST['settings']) && is_array($_POST['settings']) ? wp_unslash($_POST['settings']) : array();
        if ('2' === $step && isset($posted['layout'])) { $current['layout'] = sanitize_key($posted['layout']); }
        if ('3' === $step) { $current = array_merge($current,$posted); }
        update_option('orkigal_default_settings',Repository::sanitize_settings($current),false);
        if ('2' === $step) { wp_safe_redirect(admin_url('admin.php?page=orki-gallery-setup&step=3')); exit; }
        if ('3' === $step) { wp_safe_redirect(admin_url('admin.php?page=orki-gallery-setup&step=4')); exit; }
        if ('complete' === $step) { update_option('orkigal_setup_complete',1,false); $destination = isset($_POST['destination']) ? sanitize_key(wp_unslash($_POST['destination'])) : 'create'; $url = 'settings'===$destination ? admin_url('admin.php?page=orki-gallery-settings') : admin_url('admin.php?page=orki-gallery-editor'); wp_safe_redirect($url); exit; }
        wp_safe_redirect(admin_url('admin.php?page=orki-gallery-setup')); exit;
    }

    public function restart_setup() {
        if ( ! current_user_can('manage_options') ) { wp_die(esc_html__('Permission denied.','orki-gallery')); }
        check_admin_referer('orkigal_restart_setup','orkigal_nonce'); update_option('orkigal_setup_complete',0,false); wp_safe_redirect(admin_url('admin.php?page=orki-gallery-setup')); exit;
    }
}
