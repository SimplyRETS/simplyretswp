<?php
if (! defined('ABSPATH')) {
    exit;
}

class SampleTest extends WP_UnitTestCase {

    public $slug = 'sr-listings';

    // sample tests
    function testCreatePost() {
        $this->factory->post->create_many(5);
    }

    function testSingleListingRequest() {
        $params = '/123456';
        $result = SimplyRetsApiClient::srRequestUrlBuilder($params, "properties", true);

        $this->assertTrue(is_string($result));
    }

    function testWidgetSearchFormsUseUniqueControlIds() {
        $first_form = SimplyRetsRenderer::renderWidgetSearchForm('', '', 'https://example.com');
        $second_form = SimplyRetsRenderer::renderWidgetSearchForm('', '', 'https://example.com');

        preg_match('/<input\s+id="([^"]+-keywords)"/', $first_form, $first_matches);
        preg_match('/<input\s+id="([^"]+-keywords)"/', $second_form, $second_matches);

        $this->assertCount(2, $first_matches);
        $this->assertCount(2, $second_matches);
        $this->assertNotSame($first_matches[1], $second_matches[1]);
        $this->assertStringContainsString('for="' . $first_matches[1] . '"', $first_form);
        $this->assertStringContainsString('for="' . $second_matches[1] . '"', $second_form);
    }
}
