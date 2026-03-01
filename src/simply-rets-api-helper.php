<?php

/*
 * simply-rets-api-helper.php - Copyright (C) 2014-2024 SimplyRETS, Inc.
 *
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
*/

/* Code starts here */

class SimplyRetsApiHelper {

    public static function retrieveRetsListings($params, $settings = NULL) {
        $request_url      = SimplyRetsApiClient::srRequestUrlBuilder($params);
        $request_response = SimplyRetsApiClient::srApiRequest($request_url);
        $response_markup  = SimplyRetsRenderer::srResidentialResultsGenerator($request_response, $settings);

        return $response_markup;
    }

    public static function retrieveOpenHousesResults($params, $settings = NULL) {
        $api_url = SimplyRetsApiClient::srRequestUrlBuilder($params, "openhouses");
        $api_response = SimplyRetsApiClient::srApiRequest($api_url);

        return SimplyRetsOpenHouses::openHousesSearchResults($api_response, $settings);
    }

    public static function retrieveListingDetails($listing_id) {
        $request_url      = SimplyRetsApiClient::srRequestUrlBuilder($listing_id, "properties", true);
        $request_response = SimplyRetsApiClient::srApiRequest($request_url);
        $response_markup  = SimplyRetsRenderer::srResidentialDetailsGenerator($request_response);

        return $response_markup;
    }

    public static function retrieveWidgetListing($listing_id, $settings = NULL) {
        $request_url      = SimplyRetsApiClient::srRequestUrlBuilder($listing_id);
        $request_response = SimplyRetsApiClient::srApiRequest($request_url);
        $response_markup  = SimplyRetsRenderer::srWidgetListingGenerator($request_response, $settings);

        return $response_markup;
    }

    public static function retrieveListingsSlider($params, $settings = NULL) {
        $request_url      = SimplyRetsApiClient::srRequestUrlBuilder($params);
        $request_response = SimplyRetsApiClient::srApiRequest($request_url);
        $response_markup  = SimplyRetsRenderer::srListingSliderGenerator($request_response, $settings);

        return $response_markup;
    }


    public static function simplyRetsClientCss() {
        // client side css
        wp_register_style(
            'simply-rets-client-css',
            plugins_url('assets/css/simply-rets-client.css', __FILE__),
            array(),
            SIMPLYRETSWP_VERSION
        );
        wp_enqueue_style('simply-rets-client-css');

        // listings slider css
        wp_register_style(
            'simply-rets-listing-slider-css',
            plugins_url('assets/css/owl.carousel.min.css', __FILE__),
            array(),
            SIMPLYRETSWP_VERSION
        );
        wp_enqueue_style('simply-rets-listing-slider-css');

        // listings slider theme css
        wp_register_style(
            'simply-rets-listing-slider-default-theme-css',
            plugins_url('assets/css/owl.theme.default.min.css', __FILE__),
            array(),
            SIMPLYRETSWP_VERSION
        );
        wp_enqueue_style('simply-rets-listing-slider-theme-css');

        if (get_option('sr_listing_gallery') == 'fancy') {
            wp_register_style(
                'simply-rets-galleria-classic-theme-css',
                plugins_url('assets/galleria/themes/classic/galleria.classic.css', __FILE__),
                array(),
                SIMPLYRETSWP_VERSION
            );
            wp_enqueue_style('simply-rets-galleria-classic-theme-css');
        }
    }

    public static function simplyRetsClientJs() {
        // client-side js
        wp_register_script(
            'simply-rets-client-js',
            plugins_url('assets/js/simply-rets-client.js', __FILE__),
            array('jquery'),
            SIMPLYRETSWP_VERSION,
            array("in_footer" => false)
        );
        wp_enqueue_script('simply-rets-client-js');

        // image gallery js
        wp_register_script(
            'simply-rets-galleria-js',
            plugins_url('assets/galleria/galleria-1.4.2.min.js', __FILE__),
            array('jquery'),
            SIMPLYRETSWP_VERSION,
            array("in_footer" => false)
        );
        wp_enqueue_script('simply-rets-galleria-js');

        // listings slider js
        wp_register_script(
            'simply-rets-listing-slider-js',
            plugins_url('assets/js/owl.carousel.min.js', __FILE__),
            array('jquery'),
            SIMPLYRETSWP_VERSION,
            array("in_footer" => false)
        );
        wp_enqueue_script('simply-rets-listing-slider-js');
    }

    public static function srContactFormDeliver() {

        // if the submit button is clicked, send the email
        if (isset($_POST['sr-cf-submitted'])) {

            // sanitize form values
            $listing = sanitize_text_field($_POST["sr-cf-listing"]);
            $name    = sanitize_text_field($_POST["sr-cf-name"]);
            $email   = sanitize_email($_POST["sr-cf-email"]);
            $subject = sanitize_text_field($_POST["sr-cf-subject"]);
            $message = esc_textarea($_POST["sr-cf-message"])
                . "\r\n" . "\r\n"
                . "Form submission information: "
                . "\r\n"
                . "Listing: " . $listing
                . "\r\n"
                . "Name: " . $name
                . "\r\n"
                . "Email: " . $email;

            // get the blog administrator's email address
            $to = get_option('sr_leadcapture_recipient', '');
            $to = empty($to) ? get_option('admin_email') : $to;

            $headers = "From: $name <$email>" . "\r\n";

            // If email has been process for sending, display a success message
            if (wp_mail($to, $subject, $message, $headers)) {
                echo '<div id="sr-contact-form-success">Your message was delivered successfully.</div>';
            } else {
                echo 'An unexpected error occurred';
            }
        }
    }

    /**
     * Listhub Analytics Tracking Code Snippet
     * We'll insert this in the markup if the admin option
     * sr_listhub_analytics is true.
     */
    public static function srListhubAnalytics() {
        $analytics = "(function(l,i,s,t,h,u,b){l['ListHubAnalyticsObject']=h;l[h]=l[h]||function(){ "
            . "(l[h].q=l[h].q||[]).push(arguments)},l[h].d=1*new Date();u=i.createElement(s),"
            . " b=i.getElementsByTagName(s)[0];u.async=1;u.src=t;b.parentNode.insertBefore(u,b) "
            . " })(window,document,'script','//tracking.listhub.net/la.min.js','lh'); ";
        return $analytics;
    }


    public static function srListhubSendDetails($m, $t, $mlsid, $zip = NULL) {
        $metrics_id = $m;
        $test       = wp_json_encode($t);
        $zipcode    = $zip;

        $lh_send_details = "lh('init', {provider: '$metrics_id', test: $test}); "
            . "lh('submit', 'DETAIL_PAGE_VIEWED', {mlsn: '$mlsid', zip: '$zipcode'});";

        return $lh_send_details;
    }
}
