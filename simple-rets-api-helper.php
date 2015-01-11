<?php

/*
 *
 * simple-rets-api-helper.php - Copyright (C) Reichert Brothers 2014
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
 *
*/

/* Code starts here */

class SimpleRetsApiHelper {



    public static function retrieveRetsListings( $params ) {
        $request_url      = SimpleRetsApiHelper::srRequestUrlBuilder( $params );
        $request_response = SimpleRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimpleRetsApiHelper::srResidentialResultsGenerator( $request_response );

        return $response_markup;
    }


    public static function retrieveListingDetails( $listing_id ) {
        $request_url      = SimpleRetsApiHelper::srRequestUrlBuilder( $listing_id );
        $request_response = SimpleRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimpleRetsApiHelper::srResidentialDetailsGenerator( $request_response );

        return $response_markup;
    }


    public static function srRequestUrlBuilder( $params ) {
        // $base_url = 'http://localhost:3001/properties';
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

        return $response_array;
    }



    public static function simpleRetsClientCss() {
        wp_register_style( 'simple-rets-client-css', plugins_url( '/rets/css/simple-rets-client.css' ) );
        wp_enqueue_style( 'simple-rets-client-css' );
    }

    // generate markup for a SINGLE listing's details page
    public static function srResidentialDetailsGenerator( $listing ) {
        $br = "<br>";
        $cont = "";

        // echo '<pre><code>';
        // var_dump( $listing );
        // echo '</pre></code>';
        // Amenities
        $bedrooms         = $listing->property->bedrooms;
        $bathsFull        = $listing->property->bathsfull;
        $interiorFeatures = $listing->property->interiorfeatures;
        $style            = $listing->property->style;
        $heating          = $listing->property->heating;
        $stories          = $listing->property->stories;
        $exteriorFeatures = $listing->property->exteriorfeatures;
        $yearBuilt        = $listing->property->yearbuild;
        $lotSize          = $listing->property->lotsize; // might be empty
        $fireplaces       = $listing->property->fireplaces;
        $subdivision      = $listing->property->subdivision;
        $roof             = $listing->property->roof;

        // geographic data
        $geo_directions = $listing->geo->directions;
        $geo_longitude  = $listing->geo->lng;
        $geo_latitude   = $listing->geo->lat;
        $geo_county     = $listing->geo->county;

        // photos data
        $photos = $listing->photos;

        // listing meta information
        $listing_modified = $listing->modified; // TODO: format date
        $listing_parcel   = $listing->parcel; // probably don't need this
        $school_data      = $listing->school;
        $disclaimer       = $listing->disclaimer;
        $tax_data         = $listing->tax;
        $listing_uid      = $listing->mlsid;
        $sales_data       = $listing->sales; //probably empty
        $real_account     = $listing->realaccount; // probably don't need this

        // street address info
        $postal_code   = $listing->address->postalcode;
        $country       = $listing->address->country;
        $address       = $listing->address->address;
        $city          = $listing->address->city;

        // Listing Data
        $showing_instructions = $listing->showinginstructions;
        $listing_office   = $listing->office;
        $listing_agent    = $listing->agent->id;
        $list_date        = $listing->date;
        $listing_price    = $listing->price;
        $listing_remarks  = $listing->remarks;

        // mls information
        $mls_status     = $listing->mlsinfo->status;
        $mls_area       = $listing->mlsinfo->area;
        $mls_serving    = $listing->mlsinfo->servingname;
        $days_on_market = $listing->mlsinfo->daysonmarket;

        $pcount = count( $photos );
        $photo_counter = 0;
        foreach( $photos as $photo ) {
            $photo_markup .= "<input class=\"sr-slider-input\" type=\"radio\" name=\"slide_switch\" id=\"id$photo_counter\" value=\"$photo\"/>";
            $photo_markup .= "<label for='id$photo_counter'>";
            $photo_markup .= "  <img src='$photo' width='100'>";
            $photo_markup .= "</label>";
            $photo_counter++;
        }

        $main_photo = $photos[0];
        $cont .= <<<HTML
          <div class="sr-details" style="text-align:left;">
            <div class="slider">
              <img class="sr-slider-img-act" src="$photos[0]">
              $photo_markup
            </div>
            <p style="clear:both;">
              <span id="sr-toggle-gallery">See more photos</span>
            </p>
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
                  <td>Listing Parcel</td>
                  <td>$listing_parcel</td></tr>
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
                <tr>
                  <td>Sales Data</td>
                  <td>$sales_data</td></tr>
                <tr>
                  <td>Real Account Data</td>
                  <td>$real_account</td></tr>
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
                <tr>
                  <td>Mls Service Name</td>
                  <td>$mls_serving</td></tr>
              </tbody>
            </table>
          </div>
HTML;

        return $cont;
    }


    // generate markup for a listings results page
    public static function srResidentialResultsGenerator( $response ) {
        $br = "<br>";
        $cont = "";
        $response_size = sizeof( $response );

        // echo '<pre><code>';
        // var_dump( $response );
        // echo '</pre></code>';

        if( $response_size <= 1 ) {
            $response = array( $response );
        }

        foreach ( $response as $listing ) {
            // id
            $listing_uid      = $listing->mlsid;

            // Amenities
            $bedrooms    = $listing->property->bedrooms;
            $bathsFull   = $listing->property->bathsfull;
            $lotSize     = $listing->property->lotsize; // might be empty
            $subdivision = $listing->property->subdivision;
            $yearBuilt   = $listing->property->yearbuild;

            // listing data
            $listing_agent    = $listing->agent->id;

            $listing_price    = $listing->price;
            $list_date        = $listing->date;

            // street address info
            $city    = $listing->address->city;
            $address = $listing->address->address;

            // listing photos
            $listingPhotos = $listing->photos;
            if( empty( $listingPhotos ) ) {
                $listingPhotos[0] = 'http://placehold.it/250x175.jpg';
            }
            $main_photo = $listingPhotos[0];

            // append markup for this listing to the content
            $cont .= <<<HTML
              <hr>
              <div class="sr-listing">
                <a href="/?retsd-listings=sr-single&listing_id=$listing_uid&listing_title=$address">
                  <div class="sr-photo" style="background-image:url($main_photo);">
                  </div>
                </a>
                <div class="sr-primary-data">
                  <a href="/?retsd-listings=sr-single&listing_id=$listing_uid&listing_title=$address">
                    <h4>$address
                    <span id="sr-price">$ $listing_price</span></h4>
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
                  <a href="/?retsd-listings=sr-single&listing_id=$listing_uid&listing_title=$address">More details</a>
                </div>
              </div>
HTML;
        }

    return $cont;
    }

}