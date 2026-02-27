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

    public static function retrieveRetsListings( $params, $settings = NULL ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $params );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsRenderer::srResidentialResultsGenerator($request_response, $settings);

        return $response_markup;
    }

    public static function retrieveOpenHousesResults($params, $settings = NULL) {
        $api_url = SimplyRetsApiHelper::srRequestUrlBuilder($params, "openhouses");
        $api_response = SimplyRetsApiHelper::srApiRequest($api_url);

        return SimplyRetsOpenHouses::openHousesSearchResults(
            $api_response,
            $settings
        );
    }

    public static function retrieveListingDetails( $listing_id ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder($listing_id, "properties", true);
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsRenderer::srResidentialDetailsGenerator( $request_response );

        return $response_markup;
    }

    public static function retrieveWidgetListing( $listing_id, $settings = NULL ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $listing_id );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsRenderer::srWidgetListingGenerator( $request_response, $settings );

        return $response_markup;
    }

    public static function retrieveListingsSlider( $params, $settings = NULL ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $params );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsRenderer::srListingSliderGenerator( $request_response, $settings );

        return $response_markup;
    }


    public static function makeApiRequest($params, $endpoint = "properties") {
        $request_url = SimplyRetsApiHelper::srRequestUrlBuilder(
            $params,
            $endpoint
        );

        $request_response = SimplyRetsApiHelper::srApiRequest($request_url);

        return $request_response;
    }

    /*
     * This function build a URL from a set of parameters that we'll use to
     * requst our listings from the SimplyRETS API.
     *
     * @params is either an associative array in the form of [filter] => "val"
     * or it is a single listing id as a string, ie "123456".
     *
     * query variables for filtering will always come in as an array, so it
     * this is true, we can build a query off the standard /properties URL.
     *
     * If we do /not/ get an array, thenw we know we are requesting a single
     * listing, so we can just build the url with /properties/{ID}
     *
     * base url for local development: http://localhost:3001/properties
    */
    public static function srRequestUrlBuilder(
        $params,
        $endpoint = "properties",
        $single_listing = false
    ) {

        $authid   = get_option( 'sr_api_name' );
        $authkey  = get_option( 'sr_api_key' );
        $base_url = "https://{$authid}:{$authkey}@api.simplyrets.com/{$endpoint}";

        // Return early for /properties/{mlsId} requests
        if ($single_listing === true) {
            return $base_url . $params;
        }

        // Parse params into an array
        $params_arr = !is_array($params)
                    ? SrUtils::proper_parse_str(ltrim(urldecode($params), "?"))
                    : $params;

        // Apply the default `idx` setting if not provided
        if (!array_key_exists("idx", $params_arr)) {
            $def_idx_setting = get_option("sr_default_idx_filter", "null");
            $params_arr["idx"] = $def_idx_setting;
        }

        // Apply `include=compliance` if not set
        if (!array_key_exists("include", $params_arr)) {
            $params_arr["include"] = "compliance";
        }

        // Disable `count` parameter if not already set
        if (!array_key_exists("count", $params_arr)) {
            $params_arr["count"] = "false";
        }

        // Build query string from parameters
        $params_str = SrUtils::proper_build_query($params_arr);
        $request_url = $base_url . "?" . $params_str;

        return $request_url;
    }

    public static function srApiOptionsRequest( $url ) {
        $wp_version = get_bloginfo('version');
        $php_version = phpversion();
        $site_url = get_site_url();
        $plugin_version = SIMPLYRETSWP_VERSION;

        $ua_string = "SimplyRETSWP/{$plugin_version} "
                   . "Wordpress/{$wp_version} "
                   . "PHP/{$php_version}";

        $accept_header = "Accept: application/json; "
                       . "q=0.2, application/vnd.simplyrets-v0.1+json";

        if( is_callable( 'curl_init' ) ) {
            $curl_info = curl_version();

            // init curl and set options
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init
            $ch = curl_init();
            $curl_version = $curl_info['version'];
            $headers[] = $accept_header;

            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
            curl_setopt( $ch, CURLOPT_URL, $url );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
            curl_setopt( $ch, CURLOPT_USERAGENT, $ua_string . " cURL/{$curl_version}" );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
            curl_setopt( $ch, CURLOPT_USERAGENT, $ua_string . " cURL/{$curl_version}" );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
            curl_setopt( $ch, CURLOPT_REFERER, $site_url );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "OPTIONS" );

            // make request to api
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
            $request = curl_exec( $ch );

            // decode the reponse body
            $response_array = json_decode( $request );

            // close curl connection and return value
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
            curl_close( $ch );
            return $response_array;

        } else {
            return;
        }
    }

    public static function srUpdateAdvSearchOptions() {
        $authid   = get_option('sr_api_name');
        $authkey  = get_option('sr_api_key');
        $url      = "https://{$authid}:{$authkey}@api.simplyrets.com/";
        $options  = SimplyRetsApiHelper::srApiOptionsRequest( $url );
        $vendors  = $options->vendors;
        $endpoints = $options->endpoints;

        update_option("sr_adv_search_meta_vendors", $vendors);
        update_option("sr_adv_search_meta_endpoints", $endpoints);

        foreach((array)$vendors as $vendor) {
            $vendorUrl = $url . "properties?vendor=$vendor";
            $vendorOptions = SimplyRetsApiHelper::srApiOptionsRequest($vendorUrl);

            $defaultArray   = array();
            $defaultTypes   = array("Residential", "Condominium", "Rental");
            $defaultExpires = time();

            $types = $vendorOptions->fields->type;
            !isset( $types ) || empty( $types )
                ? $types = $defaultTypes
                : $types = $vendorOptions->fields->type;

            $expires = $vendorOptions->expires;
            !isset( $expires ) || empty( $expires )
                ? $expires = $defaultExpires
                : $expires = $vendorOptions->expires;

            $status = $vendorOptions->fields->status;
            !isset( $status ) || empty( $status )
                ? $status = $defaultArray
                : $status = $vendorOptions->fields->status;

            $counties = $vendorOptions->fields->counties;
            !isset( $counties ) || empty( $counties )
                ? $counties = $defaultArray
                : $counties = $vendorOptions->fields->counties;

            $cities = $vendorOptions->fields->cities;
            !isset( $cities ) || empty( $cities )
                ? $cities = $defaultArray
                : $cities = $vendorOptions->fields->cities;

            $features = $vendorOptions->fields->features;
            !isset( $features ) || empty( $features )
                ? $features = $defaultArray
                : $features = $vendorOptions->fields->features;

            $neighborhoods = $vendorOptions->fields->neighborhoods;
            !isset( $neighborhoods ) || empty( $neighborhoods )
                ? $neighborhoods = $defaultArray
                : $neighborhoods = $vendorOptions->fields->neighborhoods;

            update_option( "sr_adv_search_meta_timestamp_$vendor", $expires );
            update_option( "sr_adv_search_meta_status_$vendor", $status );
            update_option( "sr_adv_search_meta_types_$vendor", $types );
            update_option( "sr_adv_search_meta_county_$vendor", $counties );
            update_option( "sr_adv_search_meta_city_$vendor", $cities );
            update_option( "sr_adv_search_meta_features_$vendor", $features );
            update_option( "sr_adv_search_meta_neighborhoods_$vendor", $neighborhoods );

        }

        return;
    }


    /**
     * Make the request the SimplyRETS API. We try to use
     * cURL first, but if it's not enabled on the server, we
     * fall back to file_get_contents().
    */
    public static function srApiRequest( $url ) {
        $wp_version = get_bloginfo('version');
        $php_version = phpversion();
        $plugin_version = SIMPLYRETSWP_VERSION;

        $ua_string = "SimplyRETSWP/{$plugin_version}"
                   . "Wordpress/{$wp_version} "
                   . "PHP/{$php_version}";

        $accept_header = "Accept: application/json; "
                       . "q=0.2, application/vnd.simplyrets-v0.1+json";

        if( is_callable( 'curl_init' ) ) {
            // init curl and set options
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init
            $ch = curl_init();
            $curl_info = curl_version();
            $curl_version = $curl_info['version'];
            $headers[] = $accept_header;
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
            curl_setopt( $ch, CURLOPT_URL, $url );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
            curl_setopt( $ch, CURLOPT_USERAGENT, $ua_string . " cURL/{$curl_version}" );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
            curl_setopt( $ch, CURLOPT_HEADER, true );

            // make request to api
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
            $request = curl_exec( $ch );

            // get header size to parse out of response
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo
            $header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );

            // separate header/body out of response
            $header = substr( $request, 0, $header_size );
            $body   = substr( $request, $header_size );

            $headers = http_parse_headers($header);
            $pag_links = SimplyRetsApiHelper::srPaginationParser($headers);
            $last_update = SimplyRetsApiHelper::srLastUpdateHeaderParser($headers);

            // decode the reponse body
            $response_array = json_decode( $body );

            $srResponse = array();
            $srResponse['pagination'] = $pag_links;
            $srResponse['lastUpdate'] = $last_update;
            $srResponse['response'] = $response_array;

            // close curl connection
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
            curl_close( $ch );
            return $srResponse;

        } else {
            $options = array(
                'http' => array(
                    'header' => $accept_header,
                    'user_agent' => $ua_string
                )
            );
            $context = stream_context_create( $options );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $request = file_get_contents( $url, false, $context );
            $response_array = json_decode( $request );

            $srResponse = array();
            $srResponse['pagination'] = array("prev" => "", "next" => "");
            $srResponse['response'] = $response_array;

            return $srResponse;
        }

        if( $response_array === FALSE || empty($response_array) ) {
            $error =
                "Sorry, SimplyRETS could not complete this search." .
                "Please double check that your API credentials are valid " .
                "and that the search filters you used are correct. If this " .
                "is a new listing you may also try back later.";
            $response_err = array(
                "error" => $error
            );
            return  $response_err;
        }

        return $response_array;
    }

    // Parse 'X-SimplyRETS-LastUpdate' from API response headers
    // and return the value
    public static function srLastUpdateHeaderParser($headers) {
        $last_update = SrUtils::getHeader($headers, 'X-SimplyRETS-LastUpdate');

        // Use current timestamp if API doesn't have one
        if (empty($last_update)) {
            return gmdate(DATE_ATOM, time());
        }

        return $last_update;
    }

    public static function srPaginationParser($headers) {
        $link_header = SrUtils::getHeader($headers, "Link");

        // get link val from header
        $pag_links = array();
        preg_match('/^([^\r\n]*)[\r\n]*$/m', $link_header, $matches);
        unset($matches[0]);

        foreach( $matches as $key => $val ) {
            $parts = explode( ",", $val );
            foreach( $parts as $key => $part ) {
                if( strpos( $part, 'rel="prev"' ) == true ) {
                    $part = trim( $part );
                    preg_match( '/^<(.*)>/', $part, $prevLink );
                }
                if( strpos( $part, 'rel="next"' ) == true ) {
                    $part = trim( $part );
                    preg_match( '/^<(.*)>/', $part, $nextLink );
                }
            }
        }
        $prev_link = (!empty($prevLink) AND $prevLink[1]) ? $prevLink[1] : "";
        $next_link = (!empty($nextLink) AND $nextLink[1]) ? $nextLink[1] : "";

        $pag_links['prev'] = $prev_link;
        $pag_links['next'] = $next_link;

        /**
         * Transform query parameters to what the Wordpress client needs
         */
        foreach( $pag_links as $key=>$link ) {
            $link_parts = wp_parse_url( $link );
            $no_prefix = array('offset', 'limit', 'type', 'water', 'grid_view', "show_map");

            $query_part = !empty($link_parts['query']) ? $link_parts['query'] : NULL;
            $output = SrUtils::proper_parse_str($query_part);

            if (!empty( $output ) && !in_array(NULL, $output, true)) {
                foreach( $output as $query=>$parameter) {
                    if( $query == 'type' ) {
                        $output['sr_p' . $query] = $output[$query];
                        unset( $output[$query] );
                    }
                    /** There a few queries that we don't prefix with sr_ */
                    if(!in_array($query, $no_prefix)) {
                        $output['sr_' . $query] = $output[$query];
                        unset( $output[$query] );
                    }
                }
                $link_parts['query'] = http_build_query( $output );
                $pag_link_modified = $link_parts['scheme']
                                     . '://'
                                     . $link_parts['host']
                                     . $link_parts['path']
                                     . '?'
                                     . $link_parts['query'];
                $pag_links[$key] = $pag_link_modified;
            }
        }

        return $pag_links;
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

        if(get_option('sr_listing_gallery') == 'fancy') {
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
        if ( isset( $_POST['sr-cf-submitted'] ) ) {

            // sanitize form values
            $listing = sanitize_text_field( $_POST["sr-cf-listing"] );
            $name    = sanitize_text_field( $_POST["sr-cf-name"] );
            $email   = sanitize_email( $_POST["sr-cf-email"] );
            $subject = sanitize_text_field( $_POST["sr-cf-subject"] );
            $message = esc_textarea( $_POST["sr-cf-message"] )
                     . "\r\n" . "\r\n"
                     . "Form submission information: "
                     . "\r\n"
                     . "Listing: " . $listing
                     . "\r\n"
                     . "Name: " . $name
                     . "\r\n"
                     . "Email: " . $email
                     ;

            // get the blog administrator's email address
            $to = get_option('sr_leadcapture_recipient', '');
            $to = empty($to) ? get_option('admin_email') : $to;

            $headers = "From: $name <$email>" . "\r\n";

            // If email has been process for sending, display a success message
            if ( wp_mail( $to, $subject, $message, $headers ) ) {
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


    public static function srListhubSendDetails( $m, $t, $mlsid, $zip=NULL ) {
        $metrics_id = $m;
        $test       = wp_json_encode($t);
        $zipcode    = $zip;

        $lh_send_details = "lh('init', {provider: '$metrics_id', test: $test}); "
            . "lh('submit', 'DETAIL_PAGE_VIEWED', {mlsn: '$mlsid', zip: '$zipcode'});";

        return $lh_send_details;

    }
}
