=== Orki Gallery ===
Contributors: swarnodigital
Tags: gallery, image gallery, photo gallery, elementor, masonry
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build responsive image and video galleries with visual layouts, lightbox, shortcode, Gutenberg, and optional Elementor support.

== Description ==

Orki Gallery is a lightweight WordPress gallery builder focused on a clear workflow and native WordPress media handling.

Create a gallery in three steps:

1. Add and arrange images or WordPress-hosted videos.
2. Choose a layout and appearance with live preview.
3. Review, publish, and copy the shortcode.

Features:

* First-run setup wizard with no account or license requirement.
* Searchable gallery dashboard with thumbnails, status, shortcode, duplicate, edit, preview, and delete actions.
* Guided Images > Design > Finish editor.
* Native WordPress Media Library integration with multi-select.
* Drag-to-reorder images and videos.
* Grid, Masonry, Justified, Tiles, Carousel, and Slideshow layouts.
* Responsive desktop, tablet, and mobile columns.
* Image resolution, ratio, cover/contain fit, gap, and corner radius controls.
* Accessible image lightbox with keyboard navigation and focus return.
* Carousel and Slideshow autoplay, loop, pause-on-hover, arrows, dots, swipe, keyboard navigation, and transition speed.
* Reduced-motion support.
* Media Library captions and subtle hover zoom.
* WordPress responsive image markup and lazy loading.
* Shortcode: `[orki_gallery id="123"]`.
* Gutenberg block for saved Orki Galleries.
* Elementor widget with Create Here and Saved Gallery modes when Elementor is active.
* Elementor style controls for captions, navigation, and lightbox controls.
* Global default settings for new galleries.
* Optional data removal on uninstall. It is disabled by default.
* No tracking, analytics, account connection, or external frontend gallery library.

Orki Gallery always fills the width of its parent content container; the theme or page builder controls the parent width.

Product website: https://orki.in/


== Developers ==

* Kunal Debnath — https://github.com/infronixdigital
* Bikram Bagdi — https://github.com/bikram8538

== Installation ==

1. In WordPress, go to Plugins > Add New Plugin > Upload Plugin.
2. Upload the Orki Gallery ZIP and activate it.
3. Complete the short setup wizard.
4. Open Orki Gallery > Add New Gallery.
5. Add media, choose a design, preview, and publish.
6. Use the shortcode, the Orki Gallery block, or the Elementor widget.

== Frequently Asked Questions ==

= Does deleting a gallery delete Media Library files? =

No. Gallery deletion only removes the Orki Gallery entry and its relationships. WordPress Media Library attachments remain untouched.

= Does uninstalling the plugin delete my galleries? =

Not by default. In Orki Gallery > Settings > Advanced you can explicitly enable data deletion on uninstall. Even then, Media Library attachments are not deleted.

= Can I create a gallery directly inside Elementor? =

Yes. If Elementor is active, add the Orki Gallery widget and choose Create Here. You can also reuse any saved Orki Gallery.

= Does Orki Gallery require Elementor? =

No. Elementor is optional. You can use the shortcode or the Orki Gallery Gutenberg block without Elementor.

= Can galleries contain video? =

Yes. Saved galleries can include videos already uploaded to the WordPress Media Library.

= Does Orki Gallery send data to an external service? =

No. The plugin does not include telemetry, analytics, account connections, or required external API calls.

== Screenshots ==

1. Guided gallery dashboard and management screen.
2. Images step with WordPress Media Library selection.
3. Visual layout chooser with live preview.
4. Publish step with shortcode copy.
5. Global gallery settings.
6. Orki Gallery Elementor widget.

== Changelog ==

= 1.0.2 =
* Renamed the admin page to About Orki Gallery and focused it entirely on the gallery plugin.
* Added developer credits for Kunal Debnath and Bikram Bagdi with GitHub links.
* Added a Developers section to readme.txt and updated plugin author metadata.

= 1.0.1 =
* Added the first About page and plugin information panel.

= 1.0.0 =
* Production release candidate.
* Added paginated gallery management and gallery duplication.
* Added Gutenberg block for saved galleries.
* Added image resolution control.
* Added carousel/slideshow loop, pause-on-hover, transition speed, swipe, keyboard, and reduced-motion behavior.
* Improved lightbox with previous/next navigation, keyboard control, focus handling, and widget-level colors.
* Added Elementor style controls and an Orki widget category.
* Improved Justified and Tiles layout behavior.
* Added safer uninstall behavior with opt-in data deletion.
* Improved frontend responsiveness, accessibility, and dynamic Elementor initialization.

= 0.3.0 =
* Added first-run onboarding wizard.
* Added guided Images > Design > Finish gallery workflow.
* Added visual layout cards with live preview.
* Added organized Settings sections and searchable gallery management.

= 0.2.0 =
* Added additive Media Library selection and WordPress-hosted video selection.
* Rebuilt the editor with compact controls and Orki branding.

= 0.1.0 =
* Initial development release.
