<?php

/*
 *
 * simple-rets-api-helper.php - Copyright (C) Reichert Brothers 2014
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
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

        // Amenities
        $bedrooms         = $listing->residentialPropertyBedrooms;
        $bathsFull        = $listing->residentialPropertyBathsFull;
        $interiorFeatures = $listing->residentialPropertyInteriorFeatures;
        $style            = $listing->residentialPropertyStyle;
        $heating          = $listing->residentialPropertyHeating;
        $stories          = $listing->residentialPropertyStories;
        $exteriorFeatures = $listing->residentialPropertyExteriorFeatures;
        $yearBuilt        = $listing->residentialPropertyYearBuild;
        $lotSize          = $listing->residentialPropertyLotSize; // might be empty
        $fireplaces       = $listing->residentialPropertyFireplaces;
        $subdivision      = $listing->residentialPropertySubdivision;
        $roof             = $listing->residentialPropertyRoof;

        // geographic data
        $geo_directions = $listing->residentialPropertyListing->listingGeographicData->geographicDataDirections;
        $geo_longitude  = $listing->residentialPropertyListing->listingGeographicData->geographicDataLongitude;
        $geo_latitude   = $listing->residentialPropertyListing->listingGeographicData->geographicDataLatitude;
        $geo_county     = $listing->residentialPropertyListing->listingGeographicData->geographicDataCounty;

        // photos data
        $photos = $listing->residentialPropertyListing->listingPhotos;

        // listing meta information
        $listing_modified = $listing->residentialPropertyListing->listingModificationTimestamp; // TODO: format date
        $listing_parcel   = $listing->residentialPropertyListing->listingParcel; // probably don't need this
        $school_data      = $listing->residentialPropertyListing->listingSchoolData;
        $disclaimer       = $listing->residentialPropertyListing->listingDisclaimer;
        $tax_data         = $listing->residentialPropertyListing->listingTaxData;
        $listing_id       = $listing->residentialPropertyListing->listingId;
        $sales_data       = $listing->residentialPropertyListing->listingSalesData; //probably empty
        $real_account     = $listing->residentialPropertyListing->listingRealAccount; // probably don't need this

        // street address info
        $postal_code   = $listing->residentialPropertyListing->listingStreetAddress->streetAddressPostalCode;
        $country       = $listing->residentialPropertyListing->listingStreetAddress->streetAddressCountry;
        $street_number = $listing->residentialPropertyListing->listingStreetAddress->streetAddressStreetNumber;
        $city          = $listing->residentialPropertyListing->listingStreetAddress->streetAddressCity;
        $street_name   = $listing->residentialPropertyListing->listingStreetAddress->streetAddressStreetName;

        // Listing Data
        $showing_instructions = $listing->residentialPropertyListing->listingData->listingDataShowingInstructions;
        $listing_office   = $listing->residentialPropertyListing->listingData->listingDataOffice;
        $listing_agent    = $listing->residentialPropertyListing->listingData->listingDataAgent;
        $list_date        = $listing->residentialPropertyListing->listingData->listingDataListDate;
        $listing_price    = $listing->residentialPropertyListing->listingData->listingDataListPrice;
        $listing_remarks  = $listing->residentialPropertyListing->listingData->listingDataRemarks;

        // mls information
        $mls_status  = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationStatus;
        $mls_area    = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationArea;
        $mls_serving = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationServingName;
        $days_on_market = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationDaysOnMarket;

        $main_photo = $photos[0];
        $cont .= <<<HTML
          <div class="sr-details" style="text-align:left;">
            <div class="sr-details-photos">
              <img src="$main_photo" width="100%">
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
                  <td>$listing_id</td></tr>
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
                  <td>Street Nummber</td>
                  <td>$street_number</td></tr>
                <tr>
                  <td>Streen Name</td>
                  <td>$street_name</td></tr>
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

        $cont .= '<div class="sr-results">';
        $cont .= '  <div class="sr-listings">';
        foreach ( $response as $listing ) {
            // id
            $listing_uid      = $listing->residentialPropertyListing->listingId;

            // Amenities
            $bedrooms    = $listing->residentialPropertyBedrooms;
            $bathsFull   = $listing->residentialPropertyBathsFull;
            $lotSize     = $listing->residentialPropertyLotSize; // might be empty
            $subdivision = $listing->residentialPropertySubdivision;
            $yearBuilt   = $listing->residentialPropertyYearBuild;
            // listing data
            $listing_agent    = $listing->residentialPropertyListing->listingData->listingDataAgent;
            $list_date        = $listing->residentialPropertyListing->listingData->listingDataListDate;
            $listing_price    = $listing->residentialPropertyListing->listingData->listingDataListPrice;

            // street address info
            $city          = $listing->residentialPropertyListing->listingStreetAddress->streetAddressCity;
            $street_name   = $listing->residentialPropertyListing->listingStreetAddress->streetAddressStreetName;
            $street_number = $listing->residentialPropertyListing->listingStreetAddress->streetAddressStreetNumber;

            // listing photos
            $listingPhotos = $listing->residentialPropertyListing->listingPhotos;
            if( empty( $listingPhotos ) ) {
                $listingPhotos[0] = 'http://placehold.it/350x350.jpg';
            }

            $address = $street_name . ' ' . $street_number;
            // append markup for this listing to the content
            $cont .= '<hr>';
            $cont .= $listing_uid . $br;
            $cont .= '<div class="sr-listing">';
            $cont .= '  <div class="sr-photo">';
            $cont .= '    <a href="#">';
            $cont .= '      <img src="' . $listingPhotos[0] . '">';
            $cont .= '    </a>';
            $cont .= '  </div>';
            $cont .= '  <div class="sr-primary-data">';
            $cont .= '    <a href="#">';
            $cont .= '      <h4>' . $address;
            $cont .= '      <span id="sr-price">$' . $listing_price . '</span></h4>';
            $cont .= '    </a>';
            $cont .= '  </div>';
            $cont .= '  <div class="sr-secondary-data">';
            $cont .= '    <ul class="sr-data-column">';
            $cont .= '      <li>';
            $cont .= '        <span>' . $bedrooms . ' Bedrooms</span>';
            $cont .= '      </li>';
            $cont .= '      <li>';
            $cont .= '        <span>' . $bathsFull . ' Full Baths</span>';
            $cont .= '      </li>';
            $cont .= '      <li>';
            $cont .= '        <span>' . $lotSize . ' Sq Ft</span>';
            $cont .= '      </li>';
            $cont .= '      <li>';
            $cont .= '        <span>Built in ' . $yearBuilt . '</span>';
            $cont .= '      </li>';
            $cont .= '    </ul>';
            $cont .= '    <ul class="sr-data-column">';
            $cont .= '      <li>';
            $cont .= '        <span>In the ' . $subdivision . ' Subdivision</span>';
            $cont .= '      </li>';
            $cont .= '      <li>';
            $cont .= '        <span>The City of ' . $city . '</span>';
            $cont .= '      </li>';
            $cont .= '      <li>';
            $cont .= '        <span>Listed by ' . $listing_agent . '</span>';
            $cont .= '      </li>';
            $cont .= '      <li>';
            $cont .= '        <span>Listed on {$list_date}</span>';
            $cont .= '      </li>';
            $cont .= '    </ul>';
            $cont .= '  </div>';
            $cont .= '  <div>';
            $cont .= "    <a href=\"/?retsd-listings=sr-single&listing_id={$listing_uid}&listing_title={$address}\">More details</a>";
            $cont .= '  </div>';
            $cont .= '</div>';
        }


    $cont .= '  </div>';
    $cont .= '</div>';

    return $cont;
    }

}