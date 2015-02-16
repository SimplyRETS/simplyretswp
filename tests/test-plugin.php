<?php

class SampleTest extends WP_UnitTestCase {

    public $slug = 'sr-listings';

    // sample tests
    function testCreatePost() {
        $this->factory->post->create_many( 5 );
    }
    
    function testSingleListingRequest() {
        $mlsid = '123456';
        $response = SimplyRetsApiHelper::srRequestUrlBuilder( $mlsid );
        $this->assertTrue( is_string( $response ) );
    }
}
