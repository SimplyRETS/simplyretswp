<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once ABSPATH . 'wp-admin/includes/template.php';
require_once dirname(__FILE__) . '/../simply-rets-admin.php';

class LegacyPagesTest extends WP_UnitTestCase {

    private function signIn($role) {
        $user_id = $this->factory->user->create(array('role' => $role));
        wp_set_current_user($user_id);
        return $user_id;
    }

    private function noticeMarkup() {
        ob_start();
        SrAdminSettings::legacyPagesNotice();
        return ob_get_clean();
    }

    private function dismissalIsRejected() {
        $handler = function() {
            return function() { throw new RuntimeException('Dismissal rejected'); };
        };
        add_filter('wp_die_handler', $handler);
        try {
            SrAdminSettings::dismissLegacyPagesNotice();
        } catch (RuntimeException $error) {
            return $error->getMessage() === 'Dismissal rejected';
        } finally {
            remove_filter('wp_die_handler', $handler);
        }
        return false;
    }

    public function testUnauthorizedUsersCannotDismissNotice() {
        $user_id = $this->signIn('editor');
        $original_request = $_REQUEST;
        try {
            $_REQUEST['_wpnonce'] = wp_create_nonce('sr_dismiss_legacy_pages_notice');
            $this->assertTrue($this->dismissalIsRejected());
            $this->assertFalse(get_user_option('sr_legacy_pages_transition_dismissed', $user_id));
        } finally {
            $_REQUEST = $original_request;
        }
    }

    public function testMissingAndInvalidNoncesCannotDismissNotice() {
        $user_id = $this->signIn('administrator');
        $original_request = $_REQUEST;
        try {
            unset($_REQUEST['_wpnonce']);
            $this->assertTrue($this->dismissalIsRejected());
            $_REQUEST['_wpnonce'] = 'invalid';
            $this->assertTrue($this->dismissalIsRejected());
            $this->assertFalse(get_user_option('sr_legacy_pages_transition_dismissed', $user_id));
        } finally {
            $_REQUEST = $original_request;
        }
    }

    public function testOrdinaryPagesAndAutomaticDraftsDoNotTriggerTransition() {
        $this->signIn('administrator');
        $this->factory->post->create(array('post_type' => 'page'));
        $this->factory->post->create(array('post_type' => 'sr-listings', 'post_status' => 'auto-draft'));
        $this->assertFalse(SrAdminSettings::hasLegacyPages());
        $this->assertSame('', $this->noticeMarkup());
        ob_start();
        SrAdminSettings::sr_admin_page();
        $this->assertFalse(strpos(ob_get_clean(), 'data-sr-panel="legacy-pages"'));
    }

    public function testSavedStatusesIncludingPrivateDraftAndTrashTriggerTransition() {
        foreach (array('publish', 'draft', 'private', 'pending', 'future', 'trash') as $status) {
            $post_id = $this->factory->post->create(array(
                'post_type' => 'sr-listings',
                'post_status' => $status,
                'post_date' => $status === 'future' ? '2099-01-01 00:00:00' : '2020-01-01 00:00:00'
            ));
            $this->assertTrue(SrAdminSettings::hasLegacyPages(), $status);
            wp_delete_post($post_id, true);
            $this->assertFalse(SrAdminSettings::hasLegacyPages(), $status . ' removed');
        }
    }

    public function testSynthesizedListingRoutesDoNotTriggerTransition() {
        global $wp_query;
        $original_query = $wp_query;
        try {
            foreach (array('sr-single', 'sr-search', 'sr-openhouses') as $route) {
                $wp_query = new WP_Query();
                $wp_query->query = array('sr-listings' => $route);
                $posts = SimplyRetsCustomPostPages::srCreateDynamicPost(array());
                $this->assertCount(1, $posts);
                $this->assertSame('sr-listings', $posts[0]->post_type);
                $this->assertFalse(SrAdminSettings::hasLegacyPages());
            }
        } finally {
            $wp_query = $original_query;
        }
    }

    public function testNoticeAndTabRequireSettingsAndPageEditingPermissions() {
        $this->factory->post->create(array('post_type' => 'sr-listings'));
        foreach (array('subscriber', 'editor') as $role) {
            $this->signIn($role);
            $this->assertFalse(SrAdminSettings::canManageLegacyPages());
            $this->assertSame('', $this->noticeMarkup());
        }
        $this->signIn('subscriber');
        $user = wp_get_current_user();
        $user->add_cap('manage_options');
        $this->assertFalse(SrAdminSettings::canManageLegacyPages());
        ob_start();
        SrAdminSettings::sr_admin_page();
        $this->assertFalse(strpos(ob_get_clean(), 'data-sr-panel="legacy-pages"'));
        $user->add_cap('edit_pages');
        $this->assertTrue(SrAdminSettings::canManageLegacyPages());
    }

    public function testNoticeDismissalIsPerUserAndSurvivesNewPageVisits() {
        $this->factory->post->create(array('post_type' => 'sr-listings'));
        $user_id = $this->signIn('administrator');
        $markup = $this->noticeMarkup();
        $this->assertNotFalse(strpos($markup, '#sr-section-legacy-pages'));
        $this->assertNotFalse(strpos($markup, 'name="_wpnonce"'));
        $this->assertNotFalse(strpos($markup, 'sr_dismiss_legacy_pages_notice'));
        update_user_option($user_id, 'sr_legacy_pages_transition_dismissed', 1, false);
        global $wpdb;
        $this->assertSame('1', (string) get_user_meta($user_id, $wpdb->prefix . 'sr_legacy_pages_transition_dismissed', true));
        $this->assertSame('', get_user_meta($user_id, 'sr_legacy_pages_transition_dismissed', true));
        $this->assertSame('', $this->noticeMarkup());
        $this->signIn('administrator');
        $this->assertNotSame('', $this->noticeMarkup());
        wp_set_current_user($user_id);
        $this->assertSame('', $this->noticeMarkup());
    }

    public function testLegacyPanelKeepsManagementWithinSettings() {
        $this->signIn('administrator');
        $this->factory->post->create(array('post_type' => 'sr-listings', 'post_status' => 'trash'));
        ob_start();
        SrAdminSettings::sr_admin_page();
        $markup = ob_get_clean();
        $this->assertNotFalse(strpos($markup, 'data-sr-section="legacy-pages"'));
        $this->assertNotFalse(strpos($markup, 'id="sr-section-legacy-pages"'));
        $this->assertFalse(strpos($markup, 'data-sr-panel="legacy-pages" hidden'));
        $this->assertNotFalse(strpos($markup, esc_url(admin_url('edit.php?post_type=sr-listings'))));
        $this->assertTrue(strpos($markup, 'data-sr-section="messages"') < strpos($markup, 'data-sr-section="legacy-pages"'));
        $this->assertTrue(strpos($markup, 'id="sr-section-legacy-pages"') < strpos($markup, 'Manage existing pages</a>'));
    }

    public function testPostTypeKeepsEditorAndRoutingWithoutSidebarEntry() {
        $post_type = get_post_type_object('sr-listings');
        $this->assertFalse($post_type->show_in_menu);
        $this->assertTrue($post_type->show_ui);
        $this->assertTrue($post_type->public);
        $this->assertSame('sr-listings', $post_type->query_var);
        $this->assertSame('edit_pages', $post_type->cap->edit_posts);
        $this->assertTrue(post_type_supports('sr-listings', 'editor'));
        $post_id = $this->factory->post->create(array('post_type' => 'sr-listings', 'post_status' => 'publish'));
        $this->assertNotEmpty(get_permalink($post_id));
        $this->assertNotFalse(has_filter('the_posts', array('SimplyRetsCustomPostPages', 'srCreateDynamicPost')));
        $this->assertNotFalse(has_action('add_meta_boxes', array('SimplyRetsCustomPostPages', 'postFilterMetaBox')));
        $this->assertNotFalse(has_filter('single_template', array('SimplyRetsCustomPostPages', 'srLoadPostTemplate')));
    }
}
