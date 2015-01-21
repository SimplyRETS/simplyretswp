<?php

/*
 *
 * simply-rets-api-helper.php - Copyright (C) Reichert Brothers 2014
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
 *
*/

/* Code starts here */

class SimplyRetsApiHelper {



    public static function retrieveRetsListings( $params ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $params );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsApiHelper::srResidentialResultsGenerator( $request_response );

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
     * requst our listings from the Simply Rets API.
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
        $base_url = "http://{$authid}:{$authkey}@54.187.230.155/properties";

        if( is_array( $params ) ) {
            $filters_query = http_build_query( array_filter( $params ) );
            $request_url = "{$base_url}?{$filters_query}";
            return $request_url;

        } else {
            $request_url = $base_url . '/' . $params;
            return $request_url;

        }

    }



    public static function srApiRequest( $url ) {
        $request = file_get_contents($url);
        $response_array = json_decode( $request );

        if( $request === FALSE || empty($response_array) ) {
            $error =
                "Sorry, Simply Rets could not complete this search." .
                "Please double check that your API credentials are valid " .
                "and that the search filters you used are correct. If this " .
                "is a new listing, you may also try back later.";
            $response_err = array(
                "error" => $error
            );
            return  $response_err;
        }

        return $response_array;
    }



    public static function simplyRetsClientCss() {
        wp_register_style( 'simply-rets-client-css', plugins_url( 'css/simply-rets-client.css', __FILE__ ) );
        wp_enqueue_style( 'simply-rets-client-css' );
    }

    public static function simplyRetsClientJs() {
        wp_register_script( 'simply-rets-client-js', plugins_url( 'js/simply-rets-client.js', __FILE__ ) );
        wp_enqueue_script( 'simply-rets-client-js' );
    }

    // generate markup for a single listing's details page
    public static function srResidentialDetailsGenerator( $listing ) {
        $br = "<br>";
        $cont = "";

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if( array_key_exists( "error", $listing ) ) {
            $error = $listing['error'];
            $cont .= "<hr><p>{$error}</p>";
            return $cont;
        }

        // Amenities
        $bedrooms         = $listing->property->bedrooms;
        $bathsFull        = $listing->property->bathsFull;
        $interiorFeatures = $listing->property->interiorFeatures;
        $style            = $listing->property->style;
        $heating          = $listing->property->heating;
        $stories          = $listing->property->stories;
        $exteriorFeatures = $listing->property->exteriorFeatures;
        $yearBuilt        = $listing->property->yearBuilt;
        $lotSize          = $listing->property->lotSize; // might be empty
        $fireplaces       = $listing->property->fireplaces;
        $subdivision      = $listing->property->subdivision;
        $roof             = $listing->property->roof;
        // geographic data
        $geo_directions = $listing->geo->directions;
        $geo_longitude  = $listing->geo->lng;
        $geo_latitude   = $listing->geo->lat;
        $geo_county     = $listing->geo->county;
        // photos data (and set up slideshow markup)
        $photos = $listing->photos;
        if(empty($photos)) {
            $main_photo = 'http://placehold.it/450x375.jpg';
        } else {
            $main_photo = $photos[0];
            $photo_counter = 0;
            foreach( $photos as $photo ) {
                $photo_markup .= "<input class=\"sr-slider-input\" type=\"radio\" name=\"slide_switch\" id=\"id$photo_counter\" value=\"$photo\"/>";
                $photo_markup .= "<label for='id$photo_counter'>";
                $photo_markup .= "  <img src='$photo' width='100'>";
                $photo_markup .= "</label>";
                $photo_counter++;
            }
        }
        // listing meta information
        $listing_modified = $listing->modified; // TODO: format date
        $school_data      = $listing->school->district;
        $disclaimer       = $listing->disclaimer;
        $tax_data         = $listing->tax->id;
        $listing_uid      = $listing->mlsId;
        // street address info
        $postal_code   = $listing->address->postalCode;
        $country       = $listing->address->country;
        $address       = $listing->address->full;
        $city          = $listing->address->city;
        // Listing Data
        $showing_instructions = $listing->showingInstructions;
        $listing_office   = $listing->office->name;
        $listing_agent    = $listing->agent->id;
        $list_date        = $listing->listDate;
        $listing_price    = $listing->listPrice;
        $listing_remarks  = $listing->remarks;
        // mls information
        $mls_status     = $listing->mls->status;
        $mls_area       = $listing->mls->area;
        $days_on_market = $listing->mls->daysOnMarket;

        // listing markup
        $cont .= <<<HTML
          <div class="sr-details" style="text-align:left;">
            <p class="sr-details-links" style="clear:both;">
              <span id="sr-toggle-gallery">See more photos</span> |
              <span id="sr-listing-contact">Contact us about this listing</span>
            </p>
            <div class="slider">
              <img class="sr-slider-img-act" src="$main_photo">
              $photo_markup
            </div>

            <div class="sr-primary-details">
              <div class="sr-detail" id="sr-primary-details-beds">
                <h3>$bedrooms <small>Beds</small></h3>
              </div>
              <div class="sr-detail" id="sr-primary-details-baths">
                <h3>$bathsFull <small>Baths</small></h3>
              </div>
              <div class="sr-detail" id="sr-primary-details-size">
                <h3>2500 <small>SqFt</small></h3>
              </div>
              <div class="sr-detail" id="sr-primary-details-status">
                <h3>$mls_status</h3>
              </div>
            </div>
            <div class="sr-remarks-details">
              <p>$listing_remarks</p>
            </div>

            <table style="width:100%;">
              <thead>
                <tr>
                  <th colspan="2"><h5>Listing Details</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Bedrooms</td>
                  <td>$bedrooms</td></tr>
                <tr>
                  <td>Full Bathrooms</td>
                  <td>$bathsFull</td></tr>
                <tr>
                  <td>Interior Features</td>
                  <td>$interiorFeatures</td></tr>
                <tr>
                  <td>Property Style</td>
                  <td>$style</td></tr>
                <tr>
                  <td>Heating</td>
                  <td>$heating</td></tr>
                <tr>
                  <td>Stories</td>
                  <td>$stories</td></tr>
                <tr>
                  <td>Exterior Features</td>
                  <td>$exteriorFeatures</td></tr>
                <tr>
                  <td>Year Built</td>
                  <td>$yearBuilt</td></tr>
                <tr>
                  <td>Lot Size</td>
                  <td>$lotSize</td></tr>
                <tr>
                  <td>Fireplaces</td>
                  <td>$fireplaces</td></tr>
                <tr>
                  <td>Subdivision</td>
                  <td>$subdivision</td></tr>
                <tr>
                  <td>Roof</td>
                  <td>$roof</td></tr>
              </tbody>
              <thead>
                <tr>
                  <th colspan="2"><h5>Geographical Data</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Directions</td>
                  <td>$geo_directions</td></tr>
                <tr>
                  <td>County</td>
                  <td>$geo_county</td></tr>
                <tr>
                  <td>Latitude</td>
                  <td>$geo_latitude</td></tr>
                <tr>
                  <td>Longitude</td>
                  <td>$geo_longitude</td></tr>
              </tbody>
              <thead>
                <tr>
                  <th colspan="2"><h5>Listing Meta Data</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>List last modified</td>
                  <td>$listing_modified</td></tr>
                <tr>
                  <td>School Data</td>
                  <td>$school_data</td></tr>
                <tr>
                  <td>Disclaimer</td>
                  <td>$disclaimer</td></tr>
                <tr>
                  <td>Tax Data</td>
                  <td>$tax_data</td></tr>
                <tr>
                  <td>Listing Id</td>
                  <td>$listing_uid</td></tr>
              </tbody>
              <thead>
                <tr>
                  <th colspan="2"><h5>Address Information</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Postal Code</td>
                  <td>$postal_code</td></tr>
                <tr>
                  <td>Country Code</td>
                  <td>$country</td></tr>
                <tr>
                  <td>Address</td>
                  <td>$address</td></tr>
                <tr>
                  <td>City</td>
                  <td>$city</td></tr>
              </tbody>
              <thead>
                <tr>
                  <th colspan="2"><h5>Listing Information</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Showing Instructions</td>
                  <td>$showing_instructions</td></tr>
                <tr>
                  <td>Listing Office</td>
                  <td>$listing_office</td></tr>
                <tr>
                  <td>Listing Agent</td>
                  <td>$listing_agent</td></tr>
                <tr>
                  <td>Price</td>
                  <td>$listing_price</td></tr>
                <tr>
                  <td>Remarks</td>
                  <td>$listing_remarks</td></tr>
              </tbody>
              <thead>
                <tr>
                  <th colspan="2"><h5>Mls Information</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Days on Market</td>
                  <td>$days_on_market</td></tr>
                <tr>
                  <td>Mls Status</td>
                  <td>$mls_status</td></tr>
                <tr>
                  <td>Mls Area</td>
                  <td>$mls_area</td></tr>
              </tbody>
            </table>
          </div>
HTML;

        return $cont;
    }


    public static function srResidentialResultsGenerator( $response ) {
        $br = "<br>";
        $cont = "";

        // echo '<pre><code>';
        // var_dump( $response );
        // echo '</pre></code>';

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if( array_key_exists( "error", $response ) ) {
            $error = $response['error'];
            $response_markup = "<hr><p>{$error}</p>";
            return $response_markup;
        }

        $response_size = sizeof( $response );
        if( $response_size <= 1 ) {
            $response = array( $response );
        }

        foreach ( $response as $listing ) {
            // id
            $listing_uid      = $listing->mlsId;
            // Amenities
            $bedrooms    = $listing->property->bedrooms;
            $bathsFull   = $listing->property->bathsFull;
            $lotSize     = $listing->property->lotSize; // might be empty
            $subdivision = $listing->property->subdivision;
            $yearBuilt   = $listing->property->yearBuilt;
            // listing data
            $listing_agent    = $listing->agent->id;
            $listing_price    = $listing->listPrice;
            $list_date        = $listing->listDate;
            $listing_USD = '$' . number_format( $listing_price );
            // street address info
            $city    = $listing->address->city;
            $address = $listing->address->full;
            // listing photos
            $listingPhotos = $listing->photos;
            if( empty( $listingPhotos ) ) {
                $listingPhotos[0] = 'http://placehold.it/250x175.jpg';
            }
            $main_photo = trim($listingPhotos[0]);

            $listing_link = get_home_url() . "/?sr-listings=sr-single&listing_id=$listing_uid&listing_price=$listing_price&listing_title=$address";
            // append markup for this listing to the content
            $cont .= <<<HTML
              <hr>
              <div class="sr-listing">
                <a href="$listing_link">
                  <div class="sr-photo" style="background-image:url('$main_photo');">
                  </div>
                </a>
                <div class="sr-primary-data">
                  <a href="$listing_link">
                    <h4>$address
                    <span id="sr-price"><i>$listing_USD</i></span></h4>
                  </a>
                </div>
                <div class="sr-secondary-data">
                  <ul class="sr-data-column">
                    <li>
                      <span>$bedrooms Bedrooms</span>
                    </li>
                    <li>
                      <span>$bathsFull Full Baths</span>
                    </li>
                    <li>
                      <span>$lotSize Sq Ft</span>
                    </li>
                    <li>
                      <span>Built in $yearBuilt</span>
                    </li>
                  </ul>
                  <ul class="sr-data-column">
                    <li>
                      <span>In the $subdivision Subdivision</span>
                    </li>
                    <li>
                      <span>The City of $city</span>
                    </li>
                    <li>
                      <span>Listed by $listing_agent</span>
                    </li>
                    <li>
                      <span>Listed on $list_date</span>
                    </li>
                  </ul>
                </div>
                <div style="clear:both;">
                  <a href="$listing_link">More details</a>
                </div>
              </div>
HTML;
        }

        $cont .= "<br><p><small><i>This information is believed to be accurate, but without any warranty.</i></small></p>";
        return $cont;
    }


    public static function srWidgetListingGenerator( $response ) {
        $br = "<br>";
        $cont = "";

        // echo '<pre><code>';
        // var_dump( $response );
        // echo '</pre></code>';

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if( array_key_exists( "error", $response ) ) {
            $error = $response['error'];
            $response_markup = "<hr><p>{$error}</p>";
            return $response_markup;
        }

        $response_size = sizeof( $response );
        if( $response_size <= 1 ) {
            $response = array( $response );
        }

        foreach ( $response as $listing ) {
            $listing_uid      = $listing->mlsId;
            // Amenities
            $bedrooms    = $listing->property->bedrooms;
            $bathsFull   = $listing->property->bathsFull;
            $lotSize     = $listing->property->lotSize; // might be empty
            $subdivision = $listing->property->subdivision;
            $yearBuilt   = $listing->property->yearBuilt;
            // listing data
            $listing_agent = $listing->agent->id;
            $listing_price = $listing->listPrice;
            $list_date     = $listing->listDate;
            $listing_USD   = '$' . number_format( $listing_price );
            // street address info
            $city    = $listing->address->city;
            $address = $listing->address->full;
            // listing photos
            $listingPhotos = $listing->photos;
            if( empty( $listingPhotos ) ) {
                $listingPhotos[0] = 'http://placehold.it/250x175.jpg';
            }
            $main_photo = $listingPhotos[0];

            $mls_status    = $listing->mls->status;
            $listing_remarks  = $listing->remarks;
            $listing_link = get_home_url() . "/?sr-listings=sr-single&listing_id=$listing_uid&listing_price=$listing_price&listing_title=$address";
            // append markup for this listing to the content
            $cont .= <<<HTML
              <div class="sr-listing-wdgt">
                <a href="$listing_link">
                  <h5>$address
                    <small> - $listing_USD </small>
                  </h5>
                </a>
                <a href="$listing_link">
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
                  <a href="$listing_link">
                    <button class="button real-btn">
                      More about this listing
                    </button>
                  </a>
                </div>
              </div>
HTML;

        }
        return $cont;
    }

}
