<?php
if (!defined('ABSPATH')) {
    exit;
}

class PostCountCacheTest extends WP_UnitTestCase {

    public function testNonAdminWritesInvalidateCachedLegacyCounts() {
        // Use the hooks installed by the real, non-admin plugin bootstrap.
        $this->assertFalse(is_admin());
        $this->assertFalse(has_action('admin_notices', array('SrAdminSettings', 'legacyPagesNotice')));

        $post_id = $this->factory->post->create(array(
            'post_type' => 'sr-listings',
            'post_status' => 'publish',
        ));
        $this->assertSame(1, (int) wp_count_posts('sr-listings')->publish);

        // Post-type changes keep the status unchanged, so core's status
        // transition invalidation does not refresh the cached counts.
        wp_update_post(array('ID' => $post_id, 'post_type' => 'page'));
        $this->assertSame('publish', get_post_status($post_id));
        $this->assertSame(0, (int) wp_count_posts('sr-listings')->publish);

        wp_update_post(array('ID' => $post_id, 'post_type' => 'sr-listings'));
        $this->assertSame(1, (int) wp_count_posts('sr-listings')->publish);

        wp_delete_post($post_id, true);
        $this->assertSame(0, (int) wp_count_posts('sr-listings')->publish);
    }
}
