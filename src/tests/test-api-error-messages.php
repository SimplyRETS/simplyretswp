<?php
if (! defined('ABSPATH')) {
    exit;
}

class ApiErrorMessagesTest extends WP_UnitTestCase {

    /**
     * @dataProvider apiMessageProvider
     */
    public function testApiMessagesAreEscaped($message, $escaped_message) {
        $expected = '<br><p><strong>' . $escaped_message . '</br></p></strong>';

        $this->assertSame($expected, SrMessages::noResultsMsg(array('message' => $message)));

        // Exercise the single-listing error path used by the reported exploit.
        $response = array(
            'lastUpdate' => null,
            'response' => (object)array('message' => $message)
        );
        $this->assertSame($expected, SimplyRetsRenderer::srResidentialDetailsGenerator($response));
    }

    public static function apiMessageProvider() {
        return array(
            'reported SVG payload' => array(
                'No property found wih MLS ID "<svg onload=alert(document.domain)>"',
                'No property found wih MLS ID &quot;&lt;svg onload=alert(document.domain)&gt;&quot;'
            ),
            'script element' => array(
                '<script>alert(document.domain)</script>',
                '&lt;script&gt;alert(document.domain)&lt;/script&gt;'
            ),
            'wrapper breakout' => array(
                '</strong></p><img src=x onerror="alert(1)">',
                '&lt;/strong&gt;&lt;/p&gt;&lt;img src=x onerror=&quot;alert(1)&quot;&gt;'
            ),
            'encoded HTML entities' => array(
                '&lt;svg onload=alert(1)&gt;',
                '&lt;svg onload=alert(1)&gt;'
            ),
            'ordinary numeric ID' => array(
                'No property found wih MLS ID "99999999"',
                'No property found wih MLS ID &quot;99999999&quot;'
            ),
            'text with ampersands and apostrophes' => array(
                "Listing isn't available & cannot be displayed.",
                'Listing isn&#039;t available &amp; cannot be displayed.'
            )
        );
    }

    public function testObjectApiResponseIsEscaped() {
        $this->assertSame(
            '<br><p><strong>&lt;svg onload=alert(1)&gt;</br></p></strong>',
            SrMessages::noResultsMsg((object)array('message' => '<svg onload=alert(1)>'))
        );
    }

    public function testDefaultNoResultsMessageIsPreserved() {
        delete_option('sr_custom_no_results_message');

        $this->assertSame(
            '<p><strong>0 listings matched your search. '
                . 'Please try to broaden your search criteria or try again later.</strong></p>',
            SrMessages::noResultsMsg(array())
        );
    }

    public function testCustomNoResultsMarkupIsPreserved() {
        $custom_message = '<p>No listings. <a href="/contact/">Contact us</a>.</p>';
        update_option('sr_custom_no_results_message', $custom_message);

        $this->assertSame($custom_message, SrMessages::noResultsMsg(array()));
        $this->assertSame(
            '<br><p><strong>&lt;svg onload=alert(1)&gt;</br></p></strong>',
            SrMessages::noResultsMsg(array('message' => '<svg onload=alert(1)>'))
        );
    }
}
