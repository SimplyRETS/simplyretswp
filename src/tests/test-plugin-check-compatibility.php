<?php
if (!defined('ABSPATH')) {
    exit;
}

class PluginCheckCompatibilityTest extends WP_UnitTestCase {

    private function openHouse($start = '2026-01-11T21:00:00Z') {
        return (object) array(
            'startTime' => $start,
            'endTime' => gmdate('Y-m-d\TH:i:s\Z', strtotime($start) + 7200),
            'listing' => (object) array(
                'mlsId' => 123,
                'listingId' => 'MLS123',
                'listPrice' => 250000,
                'internetAddressDisplay' => true,
                'photos' => array(),
                'address' => (object) array('full' => '123 Main St', 'city' => 'Austin', 'state' => 'TX', 'postalCode' => '78701'),
                'property' => (object) array('bedrooms' => 2, 'bathrooms' => 2, 'bathsFull' => 2, 'area' => 1000),
                'mls' => (object) array('status' => 'Active', 'area' => ''),
                'geo' => (object) array('county' => ''),
                'office' => (object) array('name' => 'Example Office'),
                'agent' => (object) array('firstName' => 'Example', 'lastName' => 'Agent'),
            ),
        );
    }

    public function testSiteTimezoneKeepsDaylightSavingRules() {
        delete_option('sr_date_default_timezone');
        update_option('timezone_string', 'America/Chicago');
        $winter = SimplyRetsOpenHouses::getOpenHouseDateTimes($this->openHouse());
        $summer = SimplyRetsOpenHouses::getOpenHouseDateTimes($this->openHouse('2026-07-11T21:00:00Z'));
        $this->assertSame('<span>3:00pm - 5:00pm</span>', $winter['time']);
        $this->assertSame('<span>4:00pm - 6:00pm</span>', $summer['time']);
    }

    public function testSiteTimezoneSupportsFractionalOffsets() {
        delete_option('sr_date_default_timezone');
        update_option('timezone_string', '');
        foreach (array('5.75' => '2:45am - 4:45am', '-3.5' => '5:30pm - 7:30pm', '0' => '9:00pm - 11:00pm') as $offset => $expected) {
            update_option('gmt_offset', $offset);
            $times = SimplyRetsOpenHouses::getOpenHouseDateTimes($this->openHouse());
            $this->assertSame('<span>' . $expected . '</span>', $times['time']);
        }
    }

    public function testConfiguredTimezoneOverridesSiteTimezone() {
        update_option('timezone_string', 'America/Chicago');
        foreach (array('UTC', new DateTimeZone('UTC')) as $timezone) {
            update_option('sr_date_default_timezone', $timezone);
            $times = SimplyRetsOpenHouses::getOpenHouseDateTimes($this->openHouse());
            $this->assertSame('<span>9:00pm - 11:00pm</span>', $times['time']);
        }
        foreach (array('', false, 'Invalid/Timezone') as $timezone) {
            update_option('sr_date_default_timezone', $timezone);
            // PHP warns about the deliberately invalid timezone before falling back.
            $times = @SimplyRetsOpenHouses::getOpenHouseDateTimes($this->openHouse());
            $this->assertSame('<span>3:00pm - 5:00pm</span>', $times['time']);
        }
    }

    public function testSearchHeadingFiltersPreserveTagsAndFormInterfaces() {
        foreach (array('h2', 'div', 'h3 onclick="alert(1)"', '<>', '') as $raw_tag) {
            $filter = function() use ($raw_tag) { return $raw_tag; };
            add_filter('simplyrets_search_heading_tag', $filter);
            try {
                $tag = tag_escape($raw_tag);
                $tag = $tag ? $tag : 'h3';
                foreach (array(false, true) as $advanced) {
                    $markup = SimplyRetsRenderer::renderSearchForm(array('advanced' => $advanced ? 'true' : 'false'));
                    $text = $advanced ? 'Advanced Listings Search' : 'Search Listings';
                    $this->assertStringContainsString('<' . $tag . ' class="sr-search-heading">' . $text . '</' . $tag . '>', $markup);
                    $this->assertStringContainsString('name="sr-listings" value="sr-search"', $markup);
                    $this->assertStringNotContainsString('onclick=', $markup);
                }
            } finally {
                remove_filter('simplyrets_search_heading_tag', $filter);
            }
        }
    }

    public function testOpenHouseHeadingFilterPreservesTagsAndListingWrapper() {
        foreach (array('h2', 'div', 'h4 onclick="alert(1)"', '<>', '') as $raw_tag) {
            $filter = function() use ($raw_tag) { return $raw_tag; };
            add_filter('simplyrets_listing_heading_tag', $filter);
            try {
                $tag = tag_escape($raw_tag);
                $tag = $tag ? $tag : 'h4';
                $markup = SimplyRetsOpenHouses::openHouseSearchResultMarkup($this->openHouse(), array());
                $this->assertStringContainsString('<' . $tag . ' class="sr-listing-address">', $markup);
                $this->assertStringContainsString('</' . $tag . '>', $markup);
                $this->assertStringContainsString('class="sr-listing-data-wrapper"', $markup);
                $this->assertStringNotContainsString('onclick=', $markup);
            } finally {
                remove_filter('simplyrets_listing_heading_tag', $filter);
            }
        }
    }

    public function testFallbackPhotoIsBundledAndRemainsOverridable() {
        $listing = $this->openHouse()->listing;
        $default_photo = SrListing::$default_photo;
        $this->assertSame(plugins_url('assets/img/default-property.jpg', dirname(dirname(__FILE__)) . '/simply-rets-utils.php'), $default_photo);
        $this->assertFileExists(dirname(__FILE__) . '/../assets/img/default-property.jpg');
        try {
            foreach (array($default_photo, 'https://example.com/custom-placeholder.jpg') as $url) {
                SrListing::$default_photo = $url;
                $this->assertSame($url, SrListing::mainPhotoOrDefault($listing));
                $gallery = SimplyRetsRenderer::srDetailsGallery($listing);
                $this->assertSame("<img src='" . esc_url($url) . "'>", $gallery['markup']);
                $this->assertSame('', $gallery['more']);
            }
            $listing->photos = array('http://example.com/listing.jpg');
            update_option('sr_listing_force_image_https', false);
            $this->assertSame('http://example.com/listing.jpg', SrListing::mainPhotoOrDefault($listing));
            update_option('sr_listing_force_image_https', true);
            $this->assertSame('https://example.com/listing.jpg', SrListing::mainPhotoOrDefault($listing));
        } finally {
            SrListing::$default_photo = $default_photo;
        }
    }
}
