<?php
if (!defined('ABSPATH')) {
    exit;
}

class GalleryAssetsTest extends WP_UnitTestCase {

    public function testGalleryAssetsMatchRenderedMode() {
        $listing = (object) array(
            'photos' => array('https://example.com/photo.jpg'),
            'remarks' => 'Example listing',
            'internetAddressDisplay' => true,
            'address' => (object) array(
                'full' => '123 Main St',
                'city' => 'Austin',
                'state' => 'TX',
                'postalCode' => '78701',
            ),
        );
        $previous_scripts = isset($GLOBALS['wp_scripts']) ? $GLOBALS['wp_scripts'] : null;
        $previous_styles = isset($GLOBALS['wp_styles']) ? $GLOBALS['wp_styles'] : null;

        foreach (array('fancy', 'classic', 'unrecognized', '', false) as $mode) {
            $override = function() use ($mode) { return $mode; };
            // Also cover false, the result of a missing option. A false
            // pre_option result alone would fall through to the saved value.
            $filters = array('pre_option_sr_listing_gallery', 'option_sr_listing_gallery', 'default_option_sr_listing_gallery');
            foreach ($filters as $filter) {
                add_filter($filter, $override);
            }
            try {
                $GLOBALS['wp_scripts'] = new WP_Scripts();
                $GLOBALS['wp_styles'] = new WP_Styles();
                $gallery = SimplyRetsRenderer::srDetailsGallery($listing);
                $fancy = strpos($gallery['markup'], 'id="sr-fancy-gallery"') !== false;
                $this->assertSame($mode != 'classic', $fancy);

                SimplyRetsSetup::simplyRetsClientCss();
                SimplyRetsSetup::simplyRetsClientJs();
                $scripts = wp_scripts();
                $scripts->all_deps($scripts->queue);
                foreach (array('simply-rets-galleria-js', 'simply-rets-galleria-theme-js') as $handle) {
                    $this->assertSame($fancy, in_array($handle, $scripts->to_do, true), $handle);
                }
                $this->assertSame($fancy, wp_style_is('simply-rets-galleria-classic-theme-css', 'enqueued'));
                if ($fancy) {
                    $this->assertTrue(
                        array_search('simply-rets-galleria-js', $scripts->to_do, true)
                        < array_search('simply-rets-galleria-theme-js', $scripts->to_do, true)
                    );
                }
            } finally {
                foreach ($filters as $filter) {
                    remove_filter($filter, $override);
                }
                $GLOBALS['wp_scripts'] = $previous_scripts;
                $GLOBALS['wp_styles'] = $previous_styles;
            }
        }
    }
}
