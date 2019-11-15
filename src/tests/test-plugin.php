<?php

class SampleTest extends WP_UnitTestCase {

    public $slug = 'sr-listings';

    // sample tests
    function testCreatePost() {
        $this->factory->post->create_many( 5 );
    }

    function testSingleListingRequest() {
        $params = '/123456';
        $result = SimplyRetsApiHelper::srRequestUrlBuilder($params, "properties", true);

        $this->assertTrue(is_string($result));
    }
}
