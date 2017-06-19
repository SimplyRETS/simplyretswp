<?php

/*
 * simply-rets-api-helper.php - Copyright (C) 2014-2015 SimplyRETS, Inc.
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
        $response_markup  = SimplyRetsApiHelper::srResidentialResultsGenerator( $request_response, $settings );

        return $response_markup;
    }


    public static function retrieveListingDetails( $listing_id ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $listing_id );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsApiHelper::srResidentialDetailsGenerator( $request_response );

        return $response_markup;
    }

    public static function retrieveWidgetListing( $listing_id, $settings = NULL ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $listing_id );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsApiHelper::srWidgetListingGenerator( $request_response, $settings );

        return $response_markup;
    }

    public static function retrieveListingsSlider( $params, $settings = NULL ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $params );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsApiHelper::srListingSliderGenerator( $request_response, $settings );

        return $response_markup;
    }


    public static function makeApiRequest($params) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder($params);
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
    public static function srRequestUrlBuilder( $params ) {
        $authid   = get_option( 'sr_api_name' );
        $authkey  = get_option( 'sr_api_key' );
        $base_url = "https://{$authid}:{$authkey}@api.simplyrets.com/properties";

        if( is_array( $params ) ) {
            $filters_query = http_build_query( array_filter( $params ) );
            $request_url = "{$base_url}?{$filters_query}";
            return $request_url;

        } else {
            $request_url = $base_url . $params;
            return $request_url;
        }

    }

    public static function srApiOptionsRequest( $url ) {
        $wp_version = get_bloginfo('version');
        $php_version = phpversion();
        $site_url = get_site_url();

        $ua_string     = "SimplyRETSWP/2.3.1 Wordpress/{$wp_version} PHP/{$php_version}";
        $accept_header = "Accept: application/json; q=0.2, application/vnd.simplyrets-v0.1+json";

        if( is_callable( 'curl_init' ) ) {
            $curl_info = curl_version();

            // init curl and set options
            $ch = curl_init();
            $curl_version = $curl_info['version'];
            $headers[] = $accept_header;

            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            curl_setopt( $ch, CURLOPT_USERAGENT, $ua_string . " cURL/{$curl_version}" );
            curl_setopt( $ch, CURLOPT_USERAGENT, $ua_string . " cURL/{$curl_version}" );
            curl_setopt( $ch, CURLOPT_REFERER, $site_url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "OPTIONS" );

            // make request to api
            $request = curl_exec( $ch );

            // decode the reponse body
            $response_array = json_decode( $request );

            // close curl connection and return value
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

        update_option("sr_adv_search_meta_vendors", $vendors);

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


        // foreach( $options as $key => $option ) {
        //     if( !$option == NULL ) {
        //         update_option( 'sr_adv_search_option_' . $key, $option );
        //     } else {
        //         echo '';
        //     }
        // }

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

        $ua_string     = "SimplyRETSWP/2.3.1 Wordpress/{$wp_version} PHP/{$php_version}";
        $accept_header = "Accept: application/json; q=0.2, application/vnd.simplyrets-v0.1+json";

        if( is_callable( 'curl_init' ) ) {
            // init curl and set options
            $ch = curl_init();
            $curl_info = curl_version();
            $curl_version = $curl_info['version'];
            $headers[] = $accept_header;
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            curl_setopt( $ch, CURLOPT_USERAGENT, $ua_string . " cURL/{$curl_version}" );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_HEADER, true );

            // make request to api
            $request = curl_exec( $ch );

            // get header size to parse out of response
            $header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );

            // separate header/body out of response
            $header = substr( $request, 0, $header_size );
            $body   = substr( $request, $header_size );

            $pag_links = SimplyRetsApiHelper::srPaginationParser($header);
            $last_update = SimplyRetsApiHelper::srLastUpdateHeaderParser($header);

            // decode the reponse body
            $response_array = json_decode( $body );

            $srResponse = array();
            $srResponse['pagination'] = $pag_links;
            $srResponse['lastUpdate'] = $last_update;
            $srResponse['response'] = $response_array;

            // close curl connection
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
            $request = file_get_contents( $url, false, $context );
            $response_array = json_decode( $request );

            $srResponse = array();
            $srResponse['pagination'] = $pag_links;
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

        $parsed_headers = http_parse_headers($headers);
        $last_update = $parsed_headers['X-SimplyRETS-LastUpdate'];

        // Get LastUpdate header value and format the date/time
        $hdr = date("M, d Y h:i a", strtotime($last_update));

        // Use current timestamp if header didn't exist or failed for
        // some reason.
        if (empty($hdr)) {
            $hdr = date("M, d Y h:i a");
        }

        return $hdr;
    }


    public static function srPaginationParser( $linkHeader ) {

        // get link val from header
        $pag_links = array();
        preg_match('/^Link: ([^\r\n]*)[\r\n]*$/m', $linkHeader, $matches);
        unset($matches[0]);

        foreach( $matches as $key => $val ) {
            $parts = explode( ",", $val );
            foreach( $parts as $key => $part ) {
                if( strpos( $part, 'rel="prev"' ) == true ) {
                    $part = trim( $part );
                    preg_match( '/^<(.*)>/', $part, $prevLink );
                    // $prevLink = $part;
                }
                if( strpos( $part, 'rel="next"' ) == true ) {
                    $part = trim( $part );
                    preg_match( '/^<(.*)>/', $part, $nextLink );
                }
            }
        }

        $prev_link = $prevLink[1];
        $next_link = $nextLink[1];
        $pag_links['prev'] = $prev_link;
        $pag_links['next'] = $next_link;


        /**
         * Transform query parameters to what the Wordpress client needs
         */
        foreach( $pag_links as $key=>$link ) {
            $link_parts = parse_url( $link );

            $no_prefix = array('offset', 'limit', 'type', 'water');

            // Do NOT use the builtin parse_str, use our custom function
            // proper_parse_str instead
            // parse_str( $link_parts['query'], $output );
            $output = SrUtils::proper_parse_str($link_parts['query']);

            if( !empty( $output ) && !in_array(NULL, $output, true) ) {
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
        wp_register_style('simply-rets-client-css',
                          plugins_url('assets/css/simply-rets-client.css', __FILE__));
        wp_enqueue_style('simply-rets-client-css');

        // listings slider css
        wp_register_style('simply-rets-carousel',
                          'https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.carousel.min.css');
        wp_enqueue_style('simply-rets-carousel');

        // listings slider css
        wp_register_style('simply-rets-carousel-theme',
                          'https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.theme.min.css');
        wp_enqueue_style('simply-rets-carousel-theme');

    }

    public static function simplyRetsClientJs() {
        // client-side js
        wp_register_script('simply-rets-client-js',
                           plugins_url('assets/js/simply-rets-client.js', __FILE__),
                           array('jquery'));
        wp_enqueue_script('simply-rets-client-js');

        // image gallery js
        wp_register_script('simply-rets-galleria-js',
                           plugins_url('assets/galleria/galleria-1.4.2.min.js', __FILE__),
                           array('jquery'));
        wp_enqueue_script('simply-rets-galleria-js');

        // listings slider js
        wp_register_script('simply-rets-carousel',
                           'https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.carousel.min.js',
                           array('jquery'));
        wp_enqueue_script('simply-rets-carousel');

    }


    /**
     * Run fields through this function before rendering them on single listing
     * pages to hide fields that are null.
     */
    public static function srDetailsTable($val, $name, $additional = NULL, $desc = NULL) {
        if( $val == "" ) {
            $val = "";
        } else {
            $data_attr = str_replace(" ", "-", strtolower($name));
            if(!$additional && !$desc) {
                $val = <<<HTML
                    <tr data-attribute="$data_attr">
                      <td>$name</td>
                      <td colspan="2">$val</td>
HTML;
            } elseif ($additional && !$desc) {
                $val = <<<HTML
                    <tr data-attribute="$data_attr">
                      <td>$name</td>
                      <td>$val</td>
                      <td>$additional</td>
HTML;
            } else {
                $val = <<<HTML
                    <tr data-attribute="$data_attr">
                      <td rowspan="2" style="vertical-align: middle">$name</td>
                      <td colspan="1">$val</td>
                      <td colspan="1">$additional</td>
                    <tr data-attribute="$data_attr">
                      <td colspan="2">$desc</td>
HTML;
            }
        }
        return $val;
    }



    /**
     * Build the photo gallery shown on single listing details pages
     */
    public static function srDetailsGallery( $photos ) {
        $photo_gallery = array();

        if( empty($photos) ) {
             $main_photo = plugins_url( 'assets/img/defprop.jpg', __FILE__ );
             $markup = "<img src='$main_photo'>";
             $photo_gallery['markup'] = $markup;
             $photo_gallery['more']   = '';
             return $photo_gallery;

        } else {
            $markup = '';
            if(get_option('sr_listing_gallery') == 'classic') {
                $photo_counter = 0;
                $main_photo = $photos[0];
                $more = '<span id="sr-toggle-gallery">See more photos</span> |';
                $markup .= "<div class='sr-slider'><img class='sr-slider-img-act' src='$main_photo'>";
                foreach( $photos as $photo ) {
                    $markup .=
                        "<input class='sr-slider-input' type='radio' name='slide_switch' id='id$photo_counter' value='$photo' />";
                    $markup .= "<label for='id$photo_counter'>";
                    $markup .= "  <img src='$photo' width='100'>";
                    $markup .= "</label>";
                    $photo_counter++;
                }
                $markup .= "</div>";
                $photo_gallery['markup'] = $markup;
                $photo_gallery['more'] = $more;
                return $photo_gallery;

            } else {
                $more = '';
                $markup .= '<div class="sr-gallery" id="sr-fancy-gallery">';
                foreach( $photos as $photo ) {
                    $markup .= "<img src='$photo' data-title='$address'>";
                }
                $markup .= "</div>";
                $photo_gallery['markup'] = $markup;
                $photo_gallery['more'] = $more;
                return $photo_gallery;
            }
        }
        return $photo_gallery;

    }


    public static function srResidentialDetailsGenerator( $listing ) {
        $br = "<br>";
        $cont = "";
        $contact_page = get_option('sr_contact_page');

        $last_update = $listing['lastUpdate'];
        $listing = $listing['response'];
        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if($listing == NULL
           || array_key_exists("error", $listing)
           || array_key_exists("errors", $listing)) {
            $err = SrMessages::noResultsMsg((array)$listing);
            return $err;
        }

        // internal unique id
        $listing_uid = $listing->mlsId;

        /**
         * Get the listing status to show. Note that if the
         * sr_show_mls_status_text admin option is set to true, we
         * will show the listing's "statusText" and not the normalized
         * status.
         */
        $listing_mls_status = SrListing::listingStatus($listing);
        $mls_status = SimplyRetsApiHelper::srDetailsTable($listing_mls_status, "MLS Status");

        // price
        $listing_price = $listing->listPrice;
        $listing_price_USD = '$' . number_format( $listing_price );
        $price = SimplyRetsApiHelper::srDetailsTable($listing_price_USD, "Price");
        // type
        $listing_type = $listing->property->type;
        $type = SimplyRetsApiHelper::srDetailsTable($listing_type, "Property Type");
        // bedrooms
        $listing_bedrooms = $listing->property->bedrooms;
        $bedrooms = SimplyRetsApiHelper::srDetailsTable($listing_bedrooms, "Bedrooms");
        // full baths
        $listing_bathsFull = $listing->property->bathsFull;
        $bathsFull = SimplyRetsApiHelper::srDetailsTable($listing_bathsFull, "Full Baths");
        // half baths
        $listing_bathsHalf = $listing->property->bathsHalf;
        $bathsHalf = SimplyRetsApiHelper::srDetailsTable($listing_bathsHalf, "Half Baths");
        // total baths
        $listing_bathsTotal = $listing->property->bathrooms;
        $bathsTotal = SimplyRetsApiHelper::srDetailsTable($listing_bathsTotal, "Total Baths");
        // stories
        $listing_stories = $listing->property->stories;
        $stories = SimplyRetsApiHelper::srDetailsTable($listing_stories, "Stories");
        // fireplaces
        $listing_fireplaces = $listing->property->fireplaces;
        $fireplaces = SimplyRetsApiHelper::srDetailsTable($listing_fireplaces, "Fireplaces");
        // Long
        $listing_longitude = $listing->geo->lng;
        $geo_longitude = SimplyRetsApiHelper::srDetailsTable($listing_longitude, "Longitude");
        // Long
        $listing_lat = $listing->geo->lat;
        $geo_latitude = SimplyRetsApiHelper::srDetailsTable($listing_lat, "Latitude");
        // County
        $listing_county = $listing->geo->county;
        $geo_county = SimplyRetsApiHelper::srDetailsTable($listing_county, "County");
        // County
        $listing_directions = $listing->geo->directions;
        $geo_directions = SimplyRetsApiHelper::srDetailsTable($listing_directions , "Directions");
        // Market Area
        $listing_market_area = $listing->geo->marketArea;
        $geo_market_area = SimplyRetsApiHelper::srDetailsTable($listing_market_area, "Market Area");
        // mls area
        $listing_mlsarea = $listing->mls->area;
        $mls_area = SimplyRetsApiHelper::srDetailsTable($listing_mlsarea, "MLS Area");
        // tax data
        $listing_taxdata = $listing->tax->id;
        $tax_data = SimplyRetsApiHelper::srDetailsTable($listing_taxdata, "Tax ID");
        // tax year
        $listing_tax_year = $listing->tax->taxYear;
        $tax_year = SimplyRetsApiHelper::srDetailsTable($listing_tax_year, "Tax Year");
        // tax annual amount
        $listing_tax_annual_amount = $listing->tax->taxAnnualAmount;
        $tax_annual_amount = SimplyRetsApiHelper::srDetailsTable($listing_tax_annual_amount, "Tax Annual Amount");
        // roof
        $listing_roof = $listing->property->roof;
        $roof = SimplyRetsApiHelper::srDetailsTable($listing_roof, "Roof");
        // style
        $listing_style = $listing->property->style;
        $style = SimplyRetsApiHelper::srDetailsTable($listing_style, "Property Style");
        // subdivision
        $listing_subdivision = $listing->property->subdivision;
        $subdivision = SimplyRetsApiHelper::srDetailsTable($listing_subdivision, "Subdivision");
        // unit
        $listing_unit = $listing->address->unit;
        $unit = SimplyRetsApiHelper::srDetailsTable($listing_unit, "Unit");
        // int/ext features
        $listing_interiorFeatures = $listing->property->interiorFeatures;
        $interiorFeatures = SimplyRetsApiHelper::srDetailsTable($listing_interiorFeatures, "Features");
        // int/ext features
        $listing_exteriorFeatures = $listing->property->exteriorFeatures;
        $exteriorFeatures = SimplyRetsApiHelper::srDetailsTable($listing_exteriorFeatures, "Exterior Features");
        // year built
        $listing_yearBuilt = $listing->property->yearBuilt;
        $yearBuilt = SimplyRetsApiHelper::srDetailsTable($listing_yearBuilt, "Year Built");
        // listing id (MLS #)
        $listing_mlsid = $listing->listingId;
        $mlsid = SimplyRetsApiHelper::srDetailsTable($listing_mlsid, "MLS #");
        // heating
        $listing_heating = $listing->property->heating;
        $heating = SimplyRetsApiHelper::srDetailsTable($listing_heating, "Heating");
        // foundation
        $listing_foundation = $listing->property->foundation;
        $foundation = SimplyRetsApiHelper::srDetailsTable($listing_foundation, "Foundation");
        // laundry features
        $listing_laundry = $listing->property->laundryFeatures;
        $laundry_features = SimplyRetsApiHelper::srDetailsTable($listing_laundry, "Laundry Features");
        // lot description
        $listing_lot_descrip = $listing->property->lotDescription;
        $lot_description = SimplyRetsApiHelper::srDetailsTable($listing_lot_descrip, "Lot Description");
        // additional rooms
        $listing_rooms = $listing->property->additionalRooms;
        $additional_rooms = SimplyRetsApiHelper::srDetailsTable($listing_rooms, "Additional Rooms");
        // view
        $listing_view = $listing->property->view;
        $view = SimplyRetsApiHelper::srDetailsTable($listing_view, "View");
        // accessibility
        $listing_accessibility = $listing->property->accessibility;
        $accessibility = SimplyRetsApiHelper::srDetailsTable($listing_accessibility, "Accessibility");
        // waterfront
        $listing_water = $listing->property->water;
        $water = SimplyRetsApiHelper::srDetailsTable($listing_water, "Water");
        // listing date
        $listing_list_date = $listing->listDate;
        if($listing_list_date) { $list_date_formatted = date("M j, Y", strtotime($listing_list_date)); }
        $list_date = SimplyRetsApiHelper::srDetailsTable($list_date_formatted, "Listing Date");
        // listing date modified
        $listing_modified = $listing->modified;
        if($listing_modified) { $date_modified = date("M j, Y", strtotime($listing_modified)); }
        $date_modified_markup = SimplyRetsApiHelper::srDetailsTable($date_modified, "Listing Last Modified");
        // lot size
        $listing_lotSize = $listing->property->lotSize;
        $lotsize_markup  = SimplyRetsApiHelper::srDetailsTable($listing_lotSize, "Lot Size");
        // lot size area
        $listing_lotSizeArea = $listing->property->lotSizeArea;
        $lotsizearea_markup  = SimplyRetsApiHelper::srDetailsTable($listing_lotSizeArea, "Lot Size Area");
        // lot size area units
        $listing_lotSizeAreaUnits = $listing->property->lotSizeAreaUnits;
        $lotsizeareaunits_markup  = SimplyRetsApiHelper::srDetailsTable($listing_lotSizeAreaUnits, "Lot Size Area Units");
        // acres
        $listing_acres = $listing->property->acres;
        $acres_markup  = SimplyRetsApiHelper::srDetailsTable($acres, "Acres");
        // street address info
        $listing_postal_code = $listing->address->postalCode;
        $postal_code = SimplyRetsApiHelper::srDetailsTable($listing_postal_code, "Postal Code");

        $listing_country = $listing->address->country;
        $country = SimplyRetsApiHelper::srDetailsTable($listing_country, "Country");

        $listing_address = $listing->address->full;
        $address = SimplyRetsApiHelper::srDetailsTable($listing_address, "Address");

        $listing_city = $listing->address->city;
        $city = SimplyRetsApiHelper::srDetailsTable($listing_city, "City");

        $listing_cross_street = $listing->address->crossStreet;
        $cross_street = SimplyRetsApiHelper::srDetailsTable($listing_cross_street, "Cross Street");

        $listing_state = $listing->address->state;
        $state = SimplyRetsApiHelper::srDetailsTable($listing_state, "State");

        $listing_terms = $listing->terms;
        $terms = SimplyRetsApiHelper::srDetailsTable($listing_terms, "Terms");

        $listing_lease_term = $listing->leaseTerm;
        $lease_term = SimplyRetsApiHelper::srDetailsTable($listing_lease_term, "Lease Term");

        $listing_lease_type = $listing->leaseType;
        $lease_type = SimplyRetsApiHelper::srDetailsTable($listing_lease_type, "Lease Type");

        $listing_pool = $listing->property->pool;
        $pool = SimplyRetsApiHelper::srDetailsTable($listing_pool, "Pool features");

        // Garage and Parking info
        $listing_garage_spaces = $listing->property->garageSpaces;
        $garage_spaces = SimplyRetsApiHelper::srDetailsTable($listing_garage_spaces, "Garage spaces");

        $listing_parking_spaces = $listing->property->parking->spaces;
        $parking_spaces = SimplyRetsApiHelper::srDetailsTable($listing_parking_spaces, "Parking Spaces");

        $listing_parking_description = $listing->property->parking->description;
        $parking_description = SimplyRetsApiHelper::srDetailsTable(
            $listing_parking_description, "Parking Description"
        );

        // association data
        $listing_association_fee = $listing->association->fee;
        $association_fee = SimplyRetsApiHelper::srDetailsTable($listing_association_fee, "Association Fee");

        $listing_association_name = $listing->association->name;
        $association_name = SimplyRetsApiHelper::srDetailsTable($listing_association_name, "Association Name");

        $listing_association_amenities = $listing->association->amenities;
        $association_amenities = SimplyRetsApiHelper::srDetailsTable(
            $listing_association_amenities, "Association Amenities"
        );

        // Virtual tour URL
        $listing_virtual_tour = $listing->virtualTourUrl;
        if (!empty($listing_virtual_tour)) {
            // Make the URL a link
            $listing_virtual_tour = "<a href='$listing_virtual_tour' target='_blank'>"
                                  . $listing_virtual_tour
                                  . "</a>";

        }

        $virtual_tour = SimplyRetsApiHelper::srDetailsTable($listing_virtual_tour, "Virtual Tour URL");


        // area
        $area = $listing->property->area == 0
              ? 'n/a'
              : number_format($listing->property->area);


        // Determine the best field to show in the primary-details section
        $primary_baths = "";
        if(is_numeric($listing_bathsTotal)) {
            $primary_baths = $listing_bathsTotal + 0; // strips extraneous decimals
        } elseif(!empty($listing_bathsFull)) {
            $primary_baths = $listing_bathsFull;
        } else {
            $primary_baths = 'n/a';
        }


        if( $listing_bedrooms == null || $listing_bedrooms == "" ) {
            $listing_bedrooms = 0;
        }
        if( $listing_bathsFull == null || $listing_bathsFull == "" ) {
            $listing_bathsFull = 0;
        }


        // Rooms data
        $roomsMarkup = '';
        if(is_array($listing->property->rooms)) {

            $rooms = $listing->property->rooms;

            usort($rooms, function ($a, $b) {
                return (is_null($a->level) OR $a->level == "") ? 1 : -1;
            });

            $roomsMarkup .= count($rooms) < 1 ? "" : "
              <thead>
                <tr>
                  <th colspan=\"3\"><h5>Room Details</h5></th></tr></thead>";

            foreach($rooms as $room) {

                if(!is_null($room->dimensions)) {
                    $roomSize = $room->dimensions;
                } else {
                    $roomSize = "$room->length" .  " x " . "$room->width";
                }
                $level = $room->level;
                $levelText = empty($level) ? '' : SrUtils::ordinalSuffix($level) . " level";
                $roomsMarkup .= SimplyRetsApiHelper::srDetailsTable(
                    $roomSize,
                    $room->type,
                    $levelText,
                    $room->description
                );
            }
        }

        // photo gallery
        $photos         = $listing->photos;
        $photo_gallery  = SimplyRetsApiHelper::srDetailsGallery( $photos );
        $gallery_markup = $photo_gallery['markup'];
        $more_photos    = $photo_gallery['more'];
        $dummy          = plugins_url( 'assets/img/defprop.jpg', __FILE__ );
        !empty($photos) ? $main_photo = $photos[0] : $main_photo = $dummy;

        // geographic data
        if($geo_directions
           || $listing_lat
           || $listing_longitude
           || $listing_county
           || $listing_market_area
        ) {
            $geo_table_header = <<<HTML
              <thead>
                <tr>
                  <th colspan="3"><h5>Geographic Data</h5></th></tr></thead>
              <tbody>
HTML;
        } else {
            $geo_table_header = "";
        }

        // school data
        $listing_school_district = $listing->school->district;
        $school_district = SimplyRetsApiHelper::srDetailsTable($listing_school_district, "District");
        // elementary school
        $listing_elementary = $listing->school->elementarySchool;
        $school_elementary = SimplyRetsApiHelper::srDetailsTable($listing_elementary, "Elementary School");
        // middle school
        $listing_middle_school = $listing->school->middleSchool;
        $school_middle = SimplyRetsApiHelper::srDetailsTable($listing_middle_school, "Middle School");
        // high school
        $listing_high_school = $listing->school->highSchool;
        $school_high = SimplyRetsApiHelper::srDetailsTable($listing_high_school, "High School");

        if($listing_school_district
           || $listing_elementary
           || $listing_middle_school
           || $listing_high_school
        ) {
            $school_data = <<<HTML
              <thead>
                <tr>
                  <th colspan="3"><h5>School Information</h5></th></tr></thead>
              <tbody>
              $school_district
              $school_elementary
              $school_middle
              $school_high
              </tbody>
HTML;
        } else {
            $school_data = "";
        }

        // list date and listing last modified
        $show_listing_meta = SrUtils::srShowListingMeta();
        if($show_listing_meta !== true) {
            $list_date = '';
            $date_modified_markup = '';
            $tax_data = '';
            $tax_year = '';
            $tax_annual_amount = '';
        }

        if( get_option('sr_show_listing_remarks') ) {
            $show_remarks = false;
        } else {
            $show_remarks = true;
            $remarks = $listing->remarks;
            $remarks_markup = <<<HTML
            <div class="sr-remarks-details">
              <p>$remarks</p>
            </div>
HTML;
        }

        if( get_option('sr_show_leadcapture') ) {
            $contact_text = 'Contact us about this listing';
            $cf_listing = $listing_address . ' ( MLS #' . $listing_mlsid . ' )';
            $contact_markup = SimplyRetsApiHelper::srContactFormMarkup($cf_listing);
        } else {
            $contact_text = '';
            $contact_markup = '';
        }


        /**
         * Check for ListHub Analytics
         */
        if( get_option( 'sr_listhub_analytics' ) ) {
            $lh_analytics = SimplyRetsApiHelper::srListhubAnalytics();
            if( get_option( 'sr_listhub_analytics_id' ) ) {
                $metrics_id = get_option( 'sr_listhub_analytics_id' );
                $lh_send_details = SimplyRetsApiHelper::srListhubSendDetails(
                    $metrics_id
                    , true
                    , $listing_mlsid
                    , $postal_code
                );
                $lh_analytics .= $lh_send_details;
            }
        } else {
            $lh_analytics = '';
        }

        ///////////////////////////////////////////////////////

        $show_contact_info = SrUtils::showAgentContact();

        // agent data
        $listing_agent_id    = $listing->agent->id;
        $listing_agent_name  = $listing->agent->firstName . ' ' . $listing->agent->lastName;

        $listing_agent_email;
        if($show_contact_info) {
            $listing_agent_email = $listing->agent->contact->email;
        } else {
            $listing_agent_email = '';
        }

        // agent email is available
        $agent_email = trim($listing_agent_email);
        if(!empty($agent_email)) {
            $listing_agent_name = "<a href='mailto:$listing_agent_email'>$listing_agent_name</a>";
        }
        //agent name is not available - use their id
        $agent_name = trim($listing_agent_name);
        if(empty($agent_name)) {
            $listing_agent_name = $listing_agent_id;
        }

        $agent = SimplyRetsApiHelper::srDetailsTable($listing_agent_name, "Listing Agent");

        $listing_agent_phone = $listing->agent->contact->office;
        $agent_phone = SimplyRetsApiHelper::srDetailsTable($listing_agent_phone, "Listing Agent Phone");


        // Office
        $listing_office = $listing->office->name;
        $office = SimplyRetsApiHelper::srDetailsTable($listing_office, "Listing Office");
        $listing_office_phone = $listing->office->contact->office;
        $officePhone = SimplyRetsApiHelper::srDetailsTable($listing_office_phone, "Listing Office Phone");

        $listing_office_email = $listing->office->contact->email;
        $officeEmail = SimplyRetsApiHelper::srDetailsTable($listing_office_email, "Listing Office Email");

        /* If show_contact_info is false, stub these fields */
        if(!$show_contact_info) {
            $agent_phone = '';
            $officePhone = '';
            $officeEmail = '';
        }


        $compliance_markup = SrUtils::mkListingSummaryCompliance($listing_office);


        $galleria_theme = plugins_url('assets/galleria/themes/classic/galleria.classic.min.js', __FILE__);

        // Build details link for map marker
        $link = SrUtils::buildDetailsLink(
            $listing,
            !empty($vendor) ? array("sr_vendor" => $vendor) : array()
        );

        $addrFull = $address . ', ' . $city . ' ' . $zip;

        if( $listing_lat  && $listing_longitude ) {
            /**
             * Google Map for single listing
             **************************************************/
            $map       = SrSearchMap::mapWithDefaults();
            $marker    = SrSearchMap::markerWithDefaults();
            $iw        = SrSearchMap::infoWindowWithDefaults();
            $mapHelper = SrSearchMap::srMapHelper();
            $iwCont    = SrSearchMap::infoWindowMarkup(
                $link,
                $main_photo,
                $address,
                $listing_USD,
                $listing_bedrooms,
                $listing_bathsFull,
                $listing_mls_status,
                $listing_mlsid,
                $listing_type,
                $area,
                $listing_style,
                $compliance_markup
            );
            $iw->setContent($iwCont);
            $marker->setPosition($listing_lat, $listing_longitude, true);
            $map->setCenter($listing_lat, $listing_longitude, true);
            $marker->setInfoWindow($iw);
            $map->addMarker($marker);
            $map->setAutoZoom(false);
            $map->setMapOption('zoom', 12);
            $mapM = $mapHelper->render($map);
            $mapMarkup = <<<HTML
                <hr>
                <div id="details-map">
                  <h3>Map View</h3>
                  $mapM
                </div>
HTML;
            $mapLink = <<<HTML
              <span style="float:left;">
                <a href="#details-map">
                  View on map
                </a>
              </span>
HTML;
        } else {
            $mapMarkup = '';
            $mapLink = '';
        }
        /************************************************/


        // listing markup
        $cont .= <<<HTML
          <div class="sr-details" style="text-align:left;">
            <p class="sr-details-links" style="clear:both;">
              $mapLink
              $more_photos
              <span id="sr-listing-contact">
                <a href="#sr-contact-form">$contact_text</a>
              </span>
            </p>
            $gallery_markup
            <script>
              if(document.getElementById('sr-fancy-gallery')) {
                  Galleria.loadTheme('$galleria_theme');
                  Galleria.configure({
                      height: 500,
                      width:  "90%",
                      showinfo: false,
                      dummy: "$dummy",
                      lightbox: true,
                      imageCrop: false,
                      imageMargin: 0,
                      fullscreenDoubleTap: true
                  });
                  Galleria.run('.sr-gallery');
              }
            </script>
            <div class="sr-primary-details">
              <div class="sr-detail" id="sr-primary-details-beds">
                <h3>$listing_bedrooms <small>Beds</small></h3>
              </div>
              <div class="sr-detail" id="sr-primary-details-baths">
                <h3>$primary_baths<small> Baths</small></h3>
              </div>
              <div class="sr-detail" id="sr-primary-details-size">
                <h3>$area <small class="sr-listing-area-sqft">SqFt</small></h3>
              </div>
              <div class="sr-detail" id="sr-primary-details-status">
                <h3>$listing_mls_status</h3>
              </div>
            </div>
            $remarks_markup
            <table style="width:100%;">
              <thead>
                <tr>
                  <th colspan="3"><h5>Property Details</h5></th></tr></thead>
              <tbody>
                $price
                $bedrooms
                $bathsFull
                $bathsHalf
                $bathsTotal
                $style
                $lotsize_markup

                $lotsizearea_markup
                $lotsizeareaunits_markup
                $acres_markup

                $stories
                $interiorFeatures
                $exteriorFeatures
                $yearBuilt
                $fireplaces
                $subdivision
                $view
                $roof
                $water
                $heating
                $foundation
                $accessibility
                $lot_description
                $laundry_features
                $pool
                $parking_description
                $parking_spaces
                $garage_spaces
                $association_name
                $association_fee
                $association_amenities
                $additional_rooms
                $roomsMarkup
              </tbody>
              $geo_table_header
                $geo_directions
                $geo_county
                $geo_latitude
                $geo_longitude
                $geo_market_area
              </tbody>
              <thead>
                <tr>
                  <th colspan="3"><h5>Address Information</h5></th></tr></thead>
              <tbody>
                $address
                $unit
                $postal_code
                $city
                $cross_street
                $state
                $country
              </tbody>
              <thead>
                <tr>
                  <th colspan="3"><h5>Listing Information</h5></th></tr></thead>
              <tbody>
                $office
                $officePhone
                $officeEmail
                $agent
                $agent_phone
                $terms
                $virtual_tour
              </tbody>
              $school_data
              <thead>
                <tr>
                  <th colspan="3"><h5>Mls Information</h5></th></tr></thead>
              <tbody>
                $days_on_market
                $mls_status
                $list_date
                $date_modified_markup
                $tax_data
                $tax_year
                $tax_annual_amount
                $mls_area
                $mlsid
              </tbody>
            </table>
            $mapMarkup
            <script>$lh_analytics</script>
          </div>
HTML;
        $cont .= SimplyRetsApiHelper::srContactFormDeliver();
        $cont .= $contact_markup;

        // Add disclaimer to the bottom of the page
        $disclaimer = SrUtils::mkDisclaimerText($last_update);
        $cont .= "<br/>{$disclaimer}";

        return $cont;
    }


    public static function resultDataColumnMarkup($val, $name, $reverse=false) {
        if( $val == "" ) {
            $val = "";
        } else {
            if($reverse == false) {
                $val = "<li>$val $name</li>";
            }
            else {
                $val = "<li>$name $val</li>";
            }
        }
        return $val;
    }


    public static function srResidentialResultsGenerator( $response, $settings ) {
        $br                = "<br>";
        $cont              = "";
        $pagination        = $response['pagination'];   // get pagination links out of response
        $lastUpdate        = $response['lastUpdate'];   // get lastUpdate time out of response
        $response          = $response['response'];     // get listing data out of response
        $map_position      = get_option('sr_search_map_position', 'list_only');
        $show_listing_meta = SrUtils::srShowListingMeta();
        $pag               = SrUtils::buildPaginationLinks( $pagination );
        $prev_link         = $pag['prev'];
        $next_link         = $pag['next'];

        $vendor       = isset($settings['vendor'])   ? $settings['vendor']   : '';
        $map_setting  = isset($settings['show_map']) ? $settings['show_map'] : '';

        /** Allow override of "map_position" admin setting on a per short-code basis */
        $map_position = isset($settings['map_position']) ? $settings['map_position'] : $map_position;

        if(empty($vendor)) {
            $vendor = get_query_var('sr_vendor', '');
        }

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if($response == NULL
           || array_key_exists("errors", $response)
           || array_key_exists("error", $response)
        ) {
            $err = SrMessages::noResultsMsg((array)$response);
            return $err;
        }

        $response_size = sizeof($response);
        if(!array_key_exists("0", $response)) {
            $response = array($response);
        }


        $map       = SrSearchMap::mapWithDefaults();
        $mapHelper = SrSearchMap::srMapHelper();
        $map->setAutoZoom(true);
        $markerCount = 0;

        foreach( $response as $listing ) {
            $listing_uid        = $listing->mlsId;
            $mlsid              = $listing->listingId;
            $listing_price      = $listing->listPrice;
            $remarks            = $listing->remarks;
            $city               = $listing->address->city;
            $county             = $listing->geo->county;
            $address            = $listing->address->full;
            $zip                = $listing->address->postalCode;
            $listing_agent_id   = $listing->agent->id;
            $listing_agent_name = $listing->agent->firstName . ' ' . $listing->agent->lastName;
            $lng                = $listing->geo->lng;
            $lat                = $listing->geo->lat;
            $propType           = $listing->property->type;
            $bedrooms           = $listing->property->bedrooms;
            $bathsFull          = $listing->property->bathsFull;
            $bathsHalf          = $listing->property->bathsHalf;
            $bathsTotal         = $listing->property->bathrooms;
            $area               = $listing->property->area; // might be empty
            $lotSize            = $listing->property->lotSize; // might be empty
            $subdivision        = $listing->property->subdivision;
            $style              = $listing->property->style;
            $yearBuilt          = $listing->property->yearBuilt;

            /**
             * Listing status to show. This may return a statusText.
             */
            $mls_status = SrListing::listingStatus($listing);

            $addrFull = $address . ', ' . $city . ' ' . $zip;
            $listing_USD = $listing_price == "" ? "" : '$' . number_format( $listing_price );

            if( $bedrooms == null || $bedrooms == "" ) {
                $bedrooms = 0;
            }
            if( $bathsFull == null || $bathsFull == "" ) {
                $bathsFull = 0;
            }
            if( $bathsHalf == null || $bathsHalf == "" ) {
                $bathsHalf = 0;
            }
            if( !$area == 0 ) {
                $area = number_format( $area );
            }

            // listing photos
            $listingPhotos = $listing->photos;
            if( empty( $listingPhotos ) ) {
                $listingPhotos[0] = plugins_url( 'assets/img/defprop.jpg', __FILE__ );
            }
            $main_photo = trim($listingPhotos[0]);
            $main_photo = str_replace("\\", "", $main_photo);

            // listing link to details
            $link = SrUtils::buildDetailsLink(
                $listing,
                !empty($vendor) ? array("sr_vendor" => $vendor) : array()
            );


            /**
             * Show 'Listing Courtesy of ...' if setting is enabled
             */
            $listing_office = $listing->office->name;
            $compliance_markup = SrUtils::mkListingSummaryCompliance($listing_office);


            /************************************************
             * Make our map marker for this listing
             */
            if( $lat && $lng ) {
                $marker = SrSearchMap::markerWithDefaults();
                $iw     = SrSearchMap::infoWindowWithDefaults();
                $iwCont = SrSearchMap::infoWindowMarkup(
                    $link,
                    $main_photo,
                    $address,
                    $listing_USD,
                    $bedrooms,
                    $bathsFull,
                    $mls_status,
                    $mlsid,
                    $propType,
                    $area,
                    $style,
                    $compliance_markup
                );
                $iw->setContent($iwCont);
                $marker->setPosition($lat, $lng, true);
                $marker->setInfoWindow($iw);
                $map->addMarker($marker);
                $markerCount = $markerCount + 1;
            }
            /************************************************/

            /*
             * Variables that contain markup for sr-data-column
             * If the field is empty, they'll be hidden
             * TODO: Create a ranking system 1 - 10 to smartly replace missing values
             */
            $bedsMarkup  = SimplyRetsApiHelper::resultDataColumnMarkup($bedrooms, 'Bedrooms');
            $areaMarkup  = SimplyRetsApiHelper::resultDataColumnMarkup(
                $area, '<span class="sr-listing-area-sqft">SqFt</span>'
            );
            $yearMarkup  = SimplyRetsApiHelper::resultDataColumnMarkup($yearBuilt, 'Built in', true);
            $cityMarkup  = SimplyRetsApiHelper::resultDataColumnMarkup($city, 'Located in', true);
            $mlsidMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($mlsid, 'MLS #:', true);

            if( $area == 0 ) {
                $areaMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($bathsHalf, 'Half Baths', false);
                if( $areaMarkup == 0 ) {
                    $areaMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($county, "County", false);
                }
            }

            if( $yearBuilt == 0 ) {
                $yearMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($subdivision, "");
            }


            /**
             * Get the 'best' number for the total baths.
             * Prioritize 'bathrooms' (eg, total baths) over
             * bathsFull, and only fallback to bathsFull if bathrooms
             * is not available.
             */
            $bathsMarkup;
            if(is_numeric($bathsTotal)) {
                $total_baths = $bathsTotal + 0; // strips extraneous decimals
                $bathsMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($total_baths, 'Bath');
            } else {
                $bathsMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($bathsFull, 'Full Baths');
            }


            // append markup for this listing to the content
            $resultsMarkup .= <<<HTML
              <hr>
              <div class="sr-listing">
                <a href="$link">
                  <div class="sr-photo" style="background-image:url('$main_photo');">
                  </div>
                </a>
                <div class="sr-primary-data">
                  <a href="$link">
                    <h4>$address
                    <span class="sr-price"><i>$listing_USD</i></span></h4>
                  </a>
                </div>
                <div class="sr-secondary-data">
                  <ul class="sr-data-column">
                    $cityMarkup
                    $yearMarkup
                    $mlsidMarkup
                  </ul>
                  <ul class="sr-data-column">
                    $bedsMarkup
                    $bathsMarkup
                    $areaMarkup
                  </ul>
                </div>
                <div style="clear:both;">
                  <div style="text-align:right;display:block">
                    <span style="position:absolute;left:0">
                      <a href="$link">More details</a>
                    </span>
                    $compliance_markup
                  </div>
                </div>
              </div>
HTML;

        }

        $markerCount > 0 ? $mapMarkup = $mapHelper->render($map) : $mapMarkup = '';

        if( $map_setting == 'false' ) {
            $mapMarkup = '';
        }

        if( $map_position == 'list_only' )
        {
            $cont .= $resultsMarkup;
        }
        elseif( $map_position == 'map_only' )
        {
            $cont .= $mapMarkup;
        }
        elseif( $map_position == 'map_above' )
        {
            $cont .= $mapMarkup;
            $cont .= $resultsMarkup;
        }
        elseif( $map_position == 'map_below' )
        {
            $cont .= $resultsMarkup;
            $cont .= '<hr>';
            $cont .= $mapMarkup;
        }
        else
        {
            $cont .= $resultsMarkup;
        }

        $disclaimer_text = SrUtils::mkDisclaimerText($lastUpdate);

        $cont .= "<hr><p class='sr-pagination'>$prev_link $next_link</p>";
        $cont .= "<br>{$disclaimer_text}";

        return $cont;

    }


    public static function srWidgetListingGenerator( $response, $settings ) {
        $br   = "<br>";
        $cont = "";

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        $response = $response['response'];
        $response_size = sizeof( $response );

        if($response == NULL
           || array_key_exists( "error", $response )
           || array_key_exists( "errors", $response )) {

            $err = SrMessages::noResultsMsg($response);
            return $err;
        }

        if( array_key_exists( "error", $response ) ) {
            $error = $response['error'];
            $response_markup = "<hr><p>{$error}</p>";
            return $response_markup;
        }

        if( !array_key_exists("0", $response ) ) {
            $response = array( $response );
        }

        if( $response_size < 1 ) {
            $response = array( $response );
        }

        foreach ( $response as $listing ) {
            $listing_uid = $listing->mlsId;
            $listing_remarks  = $listing->remarks;

            // widget details
            $bedrooms = $listing->property->bedrooms;
            if( $bedrooms == null || $bedrooms == "" ) {
                $bedrooms = 0;
            }

            $bathsFull   = $listing->property->bathsFull;
            if( $bathsFull == null || $bathsFull == "" ) {
                $bathsFull = 0;
            }

            $mls_status = SrListing::listingStatus($listing);

            $listing_price = $listing->listPrice;
            $listing_USD   = '$' . number_format( $listing_price );

            // widget title
            $address = $listing->address->full;

            // widget photo
            $listingPhotos = $listing->photos;
            if( empty( $listingPhotos ) ) {
                $listingPhotos[0] = plugins_url( 'assets/img/defprop.jpg', __FILE__ );
            }
            $main_photo = $listingPhotos[0];
            $main_photo = str_replace("\\", "", $main_photo);


            $vendor = isset($settings['vendor']) ? $settings['vendor'] : '';
            // create link to listing
            $link = SrUtils::buildDetailsLink(
                $listing,
                !empty($vendor) ? array("sr_vendor" => $vendor) : array()
            );

            // append markup for this listing to the content
            $cont .= <<<HTML
              <div class="sr-listing-wdgt">
                <a href="$link">
                  <h5>$address
                    <small> - $listing_USD </small>
                  </h5>
                </a>
                <a href="$link">
                  <img src="$main_photo" width="100%" alt="$address">
                </a>
                <div class="sr-listing-wdgt-primary">
                  <div id="sr-listing-wdgt-details">
                    <span>$bedrooms Bed | $bathsFull Bath | $mls_status </span>
                  </div>
                  <hr>
                  <div id="sr-listing-wdgt-remarks">
                    <p>$listing_remarks</p>
                  </div>
                </div>
                <div id="sr-listing-wdgt-btn">
                  <a href="$link">
                    <button class="button btn">
                      More about this listing
                    </button>
                  </a>
                </div>
              </div>
HTML;

        }
        return $cont;
    }


    public static function srContactFormMarkup($listing) {
        $markup .= '<hr>';
        $markup .= '<div id="sr-contact-form">';
        $markup .= '<h3>Contact us about this listing</h3>';
        $markup .= '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
        $markup .= '<p>';
        $markup .= '<input type="hidden" name="sr-cf-listing" value="' . $listing . '" />';
        $markup .= 'Your Name (required) <br/>';
        $markup .= '<input type="text" name="sr-cf-name" value="'
            . ( isset( $_POST["sr-cf-name"] ) ? esc_attr( $_POST["sr-cf-name"] ) : '' ) . '" size="40" />';
        $markup .= '</p>';
        $markup .= '<p>';
        $markup .= 'Your Email (required) <br/>';
        $markup .= '<input type="email" name="sr-cf-email" value="'
            . ( isset( $_POST["sr-cf-email"] ) ? esc_attr( $_POST["sr-cf-email"] ) : '' ) . '" size="40" />';
        $markup .= '</p>';
        $markup .= '<p>';
        $markup .= 'Subject (required) <br/>';
        $markup .= '<input type="text" name="sr-cf-subject" value="'
            . ( isset( $_POST["sr-cf-subject"] ) ? esc_attr( $_POST["sr-cf-subject"] ) : '' ) . '" size="40" />';
        $markup .= '</p>';
        $markup .= '<p>';
        $markup .= 'Your Message (required) <br/>';
        $markup .= '<textarea rows="10" cols="35" name="sr-cf-message">'
            . ( isset( $_POST["sr-cf-message"] ) ? esc_attr( $_POST["sr-cf-message"] ) : '' ) . '</textarea>';
        $markup .= '</p>';
        $markup .= '<p><input class="btn button btn-submit" type="submit" name="sr-cf-submitted" value="Send"></p>';
        $markup .= '</form>';
        $markup .= '</div>';

        return $markup;

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


    public static function srListingSliderGenerator( $response, $settings ) {
        $listings = $response['response'];
        $inner;

        $last_update = $response['lastUpdate'];
        $disclaimer = SrUtils::mkDisclaimerText($last_update);

        if(!empty($settings['random']) && $settings['random'] === "true") {
            shuffle($listings);
        }

        foreach($listings as $l) {
            $uid     = $l->mlsId;
            $address = $l->address->full;
            $price   = $l->listPrice;
            $photos  = $l->photos;
            $beds    = $l->property->bedrooms;
            $baths   = $l->property->bathsFull;
            $area    = $l->property->area;

            $priceUSD = '$' . number_format( $price );

            // create link to listing
            $vendor = isset($settings['vendor']) ? $settings['vendor'] : '';
            $link = SrUtils::buildDetailsLink(
                $l,
                !empty($vendor) ? array("sr_vendor" => $vendor) : array()
            );

            if( $area == 0 ) {
                $area = 'na';
            } else {
                $area = number_format( $area );
            }

            if( empty( $photos ) ) {
                $photo = plugins_url( 'assets/img/defprop.jpg', __FILE__ );
            } else {
                $photo = trim($photos[0]);
                $photo = str_replace("\\", "", $photo);
            }

            /**
             * Show listing brokerage, if applicable
             */
            $listing_office  = $l->office->name;
            $compliance_markup = SrUtils::mkListingSummaryCompliance($listing_office);

            $inner .= <<<HTML
                <div class="sr-listing-slider-item">
                  <a href="$link">
                    <div class="sr-listing-slider-item-img" style="background-image: url('$photo')"></div>
                  </a>
                  <a href="$link">
                    <h4 class="sr-listing-slider-item-address">$address <small>$priceUSD</small></h4>
                  </a>
                  <p class="sr-listing-slider-item-specs">$beds bed / $baths bath / $area SqFt</p>
                  <p class="sr-listing-slider-item-specs">$compliance_markup</p>
                </div>
HTML;
        }

        $content = <<<HTML

            <div>
              <div id="simplyrets-listings-slider" class="sr-listing-carousel">
                $inner
              </div>
              <br/>
              $disclaimer
            </div>
HTML;

        return $content;
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
        $test       = $t;
        $mlsid      = $mlsid;
        $zipcode    = $zip;

        $lh_send_details = "lh('init', {provider: '$metrics_id', test: $test}); "
            . "lh('submit', 'DETAIL_PAGE_VIEWED', {mlsn: '$mlsid', zip: '$zipcode'});";

        return $lh_send_details;

    }
}
