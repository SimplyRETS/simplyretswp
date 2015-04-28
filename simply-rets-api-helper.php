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

    public static function retrieveWidgetListing( $listing_id ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $listing_id );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsApiHelper::srWidgetListingGenerator( $request_response );

        return $response_markup;
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

        $ua_string     = "SimplyRETSWP/1.4.0 Wordpress/{$wp_version} PHP/{$php_version}";
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
        $options_url = SimplyRetsApiHelper::srRequestUrlBuilder( array() );
        $options     = SimplyRetsApiHelper::srApiOptionsRequest( $options_url );

        $defaultArray   = array();
        $defaultTypes   = array("Residential", "Condominium", "Rental");
        $defaultExpires = time();

        $types = $options->fields->type;
        !isset( $types ) || empty( $types )
            ? $types = $defaultTypes
            : $types = $options->fields->type;

        $expires = $options->expires;
        !isset( $expires ) || empty( $expires )
            ? $expires = $defaultExpires
            : $expires = $options->expires;

        $status = $options->fields->status;
        !isset( $status ) || empty( $status )
            ? $status = $defaultArray
            : $status = $options->fields->status;

        $counties = $options->fields->counties;
        !isset( $counties ) || empty( $counties )
            ? $counties = $defaultArray
            : $counties = $options->fields->counties;

        $cities = $options->fields->cities;
        !isset( $cities ) || empty( $cities )
            ? $cities = $defaultArray
            : $cities = $options->fields->cities;

        $features = $options->fields->features;
        !isset( $features ) || empty( $features )
            ? $features = $defaultArray
            : $features = $options->fields->features;

        $neighborhoods = $options->fields->neighborhoods;
        !isset( $neighborhoods ) || empty( $neighborhoods )
            ? $neighborhoods = $defaultArray
            : $neighborhoods = $options->fields->neighborhoods;

        update_option( 'sr_adv_search_meta_timestamp', $expires );
        update_option( 'sr_adv_search_meta_status', $status );
        update_option( 'sr_adv_search_meta_types', $types );
        update_option( 'sr_adv_search_meta_county', $counties );
        update_option( 'sr_adv_search_meta_city', $cities );
        update_option( 'sr_adv_search_meta_features', $features );
        update_option( 'sr_adv_search_meta_neighborhoods', $neighborhoods );
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

        $ua_string     = "SimplyRETSWP/1.4.0 Wordpress/{$wp_version} PHP/{$php_version}";
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

            // decode the reponse body
            $response_array = json_decode( $body );

            $srResponse = array();
            $srResponse['pagination'] = $pag_links;
            $srResponse['response'] = $response_array;;

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
            return $response_array;
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


    public static function srPaginationParser( $linkHeader ) {
        // get link val from header
        $pag_links = array();
        $name = 'Link';
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
            parse_str( $link_parts['query'], $output );
            if( !empty( $output ) ) {
                foreach( $output as $query=>$parameter) {
                    if( $query == 'type' ) {
                        $output['sr_p' . $query] = $output[$query];
                        unset( $output[$query] );
                    }
                    if( $query !== 'offset' && $query !== 'limit' && $query !== 'type' ) {
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
        wp_register_style( 'simply-rets-client-css', plugins_url( 'assets/css/simply-rets-client.css', __FILE__ ) );
        wp_enqueue_style( 'simply-rets-client-css' );
    }

    public static function simplyRetsClientJs() {
        wp_register_script( 'simply-rets-client-js',
                            plugins_url( 'assets/js/simply-rets-client.js', __FILE__ ),
                            array('jquery')
        );
        wp_enqueue_script( 'simply-rets-client-js' );

        wp_register_script( 'simply-rets-galleria-js',
                            plugins_url( 'assets/galleria/galleria-1.4.2.min.js', __FILE__ ),
                            array('jquery')
        );
        wp_enqueue_script( 'simply-rets-galleria-js' );
    }


    /**
     * Run fields through this function before rendering them on single listing
     * pages to hide fields that are null.
     */
    public static function srDetailsTable($val, $name) {
        if( $val == "" ) {
            $val = "";
        } else {
            $val = <<<HTML
                <tr>
                  <td>$name</td>
                  <td>$val</td>
HTML;
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
        $contact_page = get_option( 'sr_contact_page' );

        $listing = $listing['response'];
        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if( $listing == NULL ) {
            $err = SrMessages::noResultsMsg();
            return $err;
        }
        if( array_key_exists( "error", $listing ) || array_key_exists( "errors", $listing ) ) {
            $err = SrMessages::noResultsMsg();
            return $err;
        }

        // internal unique id
        $listing_uid = $listing->mlsId;

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
        // mls area
        $listing_mlsarea = $listing->mls->area;
        $mls_area = SimplyRetsApiHelper::srDetailsTable($listing_mlsarea, "MLS Area");
        // tax data
        $listing_taxdata = $listing->tax->id;
        $tax_data = SimplyRetsApiHelper::srDetailsTable($listing_taxdata, "Tax ID");
        // school zone data
        $listing_schooldata = $listing->school->district;
        $school_data = SimplyRetsApiHelper::srDetailsTable($listing_schooldata, "School Zone");
        // roof
        $listing_roof = $listing->property->roof;
        $roof = SimplyRetsApiHelper::srDetailsTable($listing_roof, "Roof");
        // style
        $listing_style = $listing->property->style;
        $style = SimplyRetsApiHelper::srDetailsTable($listing_style, "Property Style");
        // subdivision
        // TODO: Check if neighborhood
        $listing_subdivision = $listing->property->subdivision;
        $subdivision = SimplyRetsApiHelper::srDetailsTable($listing_subdivision, "Subdivision");
        // unit
        $listing_unit = $listing->address->unit;
        $unit = SimplyRetsApiHelper::srDetailsTable($listing_unit, "Unit");
        // mls information
        $listing_mls_status     = $listing->mls->status;
        $mls_status = SimplyRetsApiHelper::srDetailsTable($listing_mls_status, "MLS Status");
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
        // listing meta information
        $listing_disclaimer  = $listing->disclaimer;
        $disclaimer = SimplyRetsApiHelper::srDetailsTable($listing_disclaimer, "Disclaimer");
        // listing date
        $list_date = $listing->listDate;
        $list_date_formatted = date("M j, Y", strtotime($list_date));
        $list_date_formatted_markup = SimplyRetsApiHelper::srDetailsTable($list_date_formatted, "Listing Date");
        // listing date modified
        $listing_modified = $listing->modified; // TODO: format date
        $date_modified    = date("M j, Y", strtotime($listing_modified));
        $date_modified_markup = SimplyRetsApiHelper::srDetailsTable($date_modified, "Listing Last Modified");

        // street address info
        $postal_code   = $listing->address->postalCode;
        $country       = $listing->address->country;
        $address       = $listing->address->full;
        $city          = $listing->address->city;
        // Listing Data
        $listing_office   = $listing->office->name;
        $listing_price    = $listing->listPrice;
        $listing_USD      = '$' . number_format( $listing_price );


        // lot size
        $lotSize = $listing->property->lotSize;
        if( $lotSize == 0 ) {
            $lot_sqft = 'n/a';
        } else {
            $lot_sqft = $lotSize;
        }
        // area
        $area = $listing->property->area;
        if( $area == 0 ) {
            $area = 'n/a';
        } else {
            $area = number_format( $area );
        }
        // bed/baths
        if( $listing_bedrooms == null || $listing_bedrooms == "" ) {
            $listing_bedrooms = 0;
        }
        if( $listing_bathsFull == null || $listing_bathsFull == "" ) {
            $listing_bathsFull = 0;
        }


        // photo gallery
        $photos         = $listing->photos;
        $photo_gallery  = SimplyRetsApiHelper::srDetailsGallery( $photos );
        $gallery_markup = $photo_gallery['markup'];
        $more_photos    = $photo_gallery['more'];
        $dummy          = plugins_url( 'assets/img/defprop.jpg', __FILE__ );
        !empty($photos) ? $main_photo = $photos[0] : $main_photo = $dummy;

        // geographic data
        $geo_directions = $listing->geo->directions;
        if( !$geo_directions == "" ) {
            $geo_directions = <<<HTML
              <thead>
                <tr>
                  <th colspan="2"><h5>Geographical Data</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Directions</td>
                  <td>$geo_directions</td></tr>
HTML;
        }

        // list date and listing last modified
        $show_listing_meta = SrUtils::srShowListingMeta();
        $list_date_markup = '';
        $listing_meta_markup = '';
        if( $show_listing_meta == true ) {

            $listing_days_on_market = $listing->mls->daysOnMarket;
            $days_on_market = SimplyRetsApiHelper::srDetailsTable($listing_days_on_market, "Days on Market" );

            $listing_meta_markup = <<<HTML
              <thead>
                <tr>
                  <th colspan="2"><h5>Listing Meta Data</h5></th></tr></thead>
              <tbody>
                $list_date_formatted_markup
                $date_modified_markup
                $school_data
                $tax_data
              </tbody>
HTML;

        }

        $remarks_markup = '';
        $remarks_table  = '';
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
            $remarks_table = SimplyRetsApiHelper::srDetailsTable($remarks, "Remarks" );
        }

        if( get_option('sr_show_leadcapture') ) {
            $contact_text = 'Contact us about this listing';
            $cf_listing = $address . ' ( MLS #' . $listing_mlsid . ' )';
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

        // agent data
        $listing_agent_id    = $listing->agent->id;
        $listing_agent_name  = $listing->agent->firstName;
        $listing_agent_email = $listing->agent->contact->email;
        if( !$listing_agent_email == "" ) {
            $listing_agent_name = "<a href='mailto:$listing_agent_email'>$listing_agent_name</a>";
        }

        $galleria_theme = plugins_url('assets/galleria/themes/classic/galleria.classic.min.js', __FILE__);

        $link = get_home_url() . $_SERVER['REQUEST_URI'];
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
                $listing_style
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
                <h3>$listing_bathsFull <small>Baths</small></h3>
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
                  <th colspan="2"><h5>Listing Details</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Price</td>
                  <td>$listing_USD</td></tr>
                $bedrooms
                $bathsFull
                $bathsHalf
                $style
                <tr>
                  <td>Lot Size</td>
                  <td>$lot_sqft <span class="sr-listing-lotsize-sqft">SqFt</span></td></tr>
                $stories
                $interiorFeatures
                $exteriorFeatures
                $yearBuilt
                $fireplaces
                $subdivision
                $roof
                $heating
              </tbody>
                $geo_directions
                $geo_county
                $geo_latitude
                $geo_longitude
              </tbody>
              <thead>
                <tr>
                  <th colspan="2"><h5>Address Information</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Address</td>
                  <td>$address</td></tr>
                $unit
                <tr>
                  <td>Postal Code</td>
                  <td>$postal_code</td></tr>
                <tr>
                  <td>City</td>
                  <td>$city</td></tr>
                <tr>
                  <td>Country</td>
                  <td>$country</td></tr>
              </tbody>
              <thead>
                <tr>
                  <th colspan="2"><h5>Listing Information</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Listing Office</td>
                  <td>$listing_office</td></tr>
                <tr>
                  <td>Listing Agent</td>
                  <td>$listing_agent_name</td></tr>
              </tbody>
              $listing_meta_markup
              <thead>
                <tr>
                  <th colspan="2"><h5>Mls Information</h5></th></tr></thead>
              <tbody>
                $days_on_market
                $mls_status
                $mls_area
                $mlsid
                $disclaimer
              </tbody>
            </table>
            $mapMarkup
            <script>$lh_analytics</script>
          </div>
HTML;
        $cont .= SimplyRetsApiHelper::srContactFormDeliver();
        $cont .= $contact_markup;
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
        $pagination        = $response['pagination'];
        $response          = $response['response'];
        $map_position      = get_option('sr_search_map_position', 'list_only');
        $show_listing_meta = SrUtils::srShowListingMeta();
        $pag               = SrUtils::buildPaginationLinks( $pagination );
        $prev_link         = $pag['prev'];
        $next_link         = $pag['next'];

        isset( $settings['show_map'] ) ? $map_setting = $settings['show_map'] : $map_setting = '';

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if( $response == NULL || array_key_exists( "errors", $response ) ) {
            $err = SrMessages::noResultsMsg();
            return $err;
        }

        $response_size = sizeof( $response );
        if( !array_key_exists( "0", $response ) ) {
            $response = array( $response );
        }


        $map       = SrSearchMap::mapWithDefaults();
        $mapHelper = SrSearchMap::srMapHelper();
        $map->setAutoZoom(true);
        $markerCount = 0;

        foreach( $response as $listing ) {
            $listing_uid        = $listing->mlsId;
            $mlsid              = $listing->listingId;
            $listing_price      = $listing->listPrice;
            $list_date          = $listing->listDate;
            $remarks            = $listing->remarks;
            $city               = $listing->address->city;
            $county             = $listing->geo->county;
            $address            = $listing->address->full;
            $zip                = $listing->address->postalCode;
            $listing_agent_id   = $listing->agent->id;
            $listing_agent_name = $listing->agent->firstName;
            $lng                = $listing->geo->lng;
            $lat                = $listing->geo->lat;
            $mls_status         = $listing->mls->status;
            $propType           = $listing->property->type;
            $bedrooms           = $listing->property->bedrooms;
            $bathsFull          = $listing->property->bathsFull;
            $bathsHalf          = $listing->property->bathsHalf;
            $area               = $listing->property->area; // might be empty
            $lotSize            = $listing->property->lotSize; // might be empty
            $subdivision        = $listing->property->subdivision;
            $style              = $listing->property->style;
            $yearBuilt          = $listing->property->yearBuilt;

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
            // show listing date if setting is on
            if( $show_listing_meta == true ) {
                $list_date_formatted = date("M j, Y", strtotime($list_date));
                $list_date_markup = SrViews::listDateResults( $list_date_formatted );
            }

            // listing photos
            $listingPhotos = $listing->photos;
            if( empty( $listingPhotos ) ) {
                $listingPhotos[0] = plugins_url( 'assets/img/defprop.jpg', __FILE__ );
            }
            $main_photo = trim($listingPhotos[0]);

            $listing_link = get_home_url() .
                "/?sr-listings=sr-single&listing_id=$listing_uid&listing_price=$listing_price&listing_title=$address";
            $link = str_replace( ' ', '%20', $listing_link );
            $link = str_replace( '#', '%23', $link );


            /************************************************
             * Make our map marker for this listing
             */
            if( $lat  && $lng ) {
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
                    $style
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
            $bathsMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($bathsFull, 'Full Baths');
            $areaMarkup  = SimplyRetsApiHelper::resultDataColumnMarkup($area, '<span class="sr-listing-area-sqft">SqFt</span>');
            $yearMarkup  = SimplyRetsApiHelper::resultDataColumnMarkup($yearBuilt, 'Built in', true);
            $cityMarkup  = SimplyRetsApiHelper::resultDataColumnMarkup($city, 'The City of', true);
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
                    <span id="sr-price"><i>$listing_USD</i></span></h4>
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
                  <a href="$link">More details</a>
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

        $cont .= "<hr><p class='sr-pagination'>$prev_link $next_link</p>";
        $cont .= "<br><p><small><i>This information is believed to be accurate, but without any warranty.</i></small></p>";
        return $cont;

    }


    public static function srWidgetListingGenerator( $response ) {
        $br = "<br>";
        $cont = "";

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        $response = $response['response'];
        $response_size = sizeof( $response );

        if( $response == NULL || array_key_exists( "errors", $response ) ) {
            $err = SrMessages::noResultsMsg();
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
            // widget details
            $bedrooms = $listing->property->bedrooms;
            if( $bedrooms == null || $bedrooms == "" ) {
                $bedrooms = 0;
            }
            $bathsFull   = $listing->property->bathsFull;
            if( $bathsFull == null || $bathsFull == "" ) {
                $bathsFull = 0;
            }
            $mls_status    = $listing->mls->status;
            $listing_remarks  = $listing->remarks;
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

            // create link to listing
            $listing_link = get_home_url()
                . "/?sr-listings=sr-single&listing_id=$listing_uid&listing_price=$listing_price&listing_title=$address";
            $link = str_replace( ' ', '%20', $listing_link );
            $link = str_replace( '#', '%23', $link );

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
        $markup .= '<input type="text" name="sr-cf-name" pattern="[a-zA-Z0-9 ]+" value="'
            . ( isset( $_POST["sr-cf-name"] ) ? esc_attr( $_POST["sr-cf-name"] ) : '' ) . '" size="40" />';
        $markup .= '</p>';
        $markup .= '<p>';
        $markup .= 'Your Email (required) <br/>';
        $markup .= '<input type="email" name="sr-cf-email" value="'
            . ( isset( $_POST["sr-cf-email"] ) ? esc_attr( $_POST["sr-cf-email"] ) : '' ) . '" size="40" />';
        $markup .= '</p>';
        $markup .= '<p>';
        $markup .= 'Subject (required) <br/>';
        $markup .= '<input type="text" name="sr-cf-subject" pattern="[a-zA-Z ]+" value="'
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
            $message = esc_textarea( $_POST["sr-cf-message"] ) . ' - ' . $listing;

            // get the blog administrator's email address
            $to = get_option( 'admin_email' );

            $headers = "From: $name <$email>" . "\r\n";

            // If email has been process for sending, display a success message
            if ( wp_mail( $to, $subject, $message, $headers ) ) {
                echo '<div></div>';
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
        $test       = $t;
        $mlsid      = $mlsid;
        $zipcode    = $zip;

        $lh_send_details = "lh('init', {provider: '$metrics_id', test: $test}); "
            . "lh('submit', 'DETAIL_PAGE_VIEWED', {mlsn: '$mlsid', zip: '$zipcode'});";

        return $lh_send_details;

    }
}
