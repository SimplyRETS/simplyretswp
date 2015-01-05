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

        $cont .= "<div class='sr-details'>";
        $cont .= "  <h4>Single Listing Details</h4>";
        $cont .= "  <p>Beds: {$bedrooms}</p>";
        $cont .= "  <p>Baths: {$bathsFull}</p>";
        $cont .= "  <p>Interior: {$interiorFeatures}</p>";
        $cont .= "  <p>Stlye: {$style}</p>";
        $cont .= "  <p>Heating: {$heating}</p>";
        $cont .= "  <p>Stories: {$stories}</p>";
        $cont .= "  <p>Exterior: {$exteriorFeatures}</p>";
        $cont .= "  <p>Year built: {$yearBuilt}</p>";
        $cont .= "  <p>Lost Size: {$lotSize}</p>";
        $cont .= "  <p>Fireplaces: {$fireplaces}</p>";
        $cont .= "  <p>Subdivision: {$subdivision}</p>";
        $cont .= "  <p>Roof: {$roof}</p>";

        $cont .= "  <h4>Geographical</h4>";
        $cont .= "  <p>Directions: {$geo_directions}</p>";
        $cont .= "  <p>County: {$geo_county}</p>";
        $cont .= "  <p>Latitude: {$geo_latitude}</p>";
        $cont .= "  <p>Longitude: {$geo_longitude}</p>";

        $cont .= "  <h4>Photos</h4>";
        $cont .= "  <p>Photos: {$photos}</p>";

        $cont .= "  <h4>Listing Meta Data</h4>";
        $cont .= "  <p>Listing modified: {$listing_modified}</p>";
        $cont .= "  <p>Listing Parcel: {$listing_parcel}</p>";
        $cont .= "  <p>School Data: {$school_data}</p>";
        $cont .= "  <p>Disclaimer: {$disclaimer}</p>";
        $cont .= "  <p>Tax Data: {$tax_data}</p>";
        $cont .= "  <p>Listing Id: {$listing_id}</p>";
        $cont .= "  <p>Sales Data: {$sales_data}</p>";
        $cont .= "  <p>Real Account: {$real_account}</p>";

        $cont .= "  <h4>Street Address Info</h4>";
        $cont .= "  <p>Postal Code: {$postal_code}</p>";
        $cont .= "  <p>Country: {$country}</p>";
        $cont .= "  <p>Street Number: {$street_number}</p>";
        $cont .= "  <p>City: {$city}</p>";
        $cont .= "  <p>Street Name: {$street_name}</p>";

        $cont .= "  <h4>Listing Data</h4>";
        $cont .= "  <p>Showing Instructions: {$showing_instructions}</p>";
        $cont .= "  <p>Listing Office: {$listing_office}</p>";
        $cont .= "  <p>Listing Agent: {$listing_agent}</p>";
        $cont .= "  <p>List Date: {$listing_price}</p>";
        $cont .= "  <p>Remarks: {$listing_remarks}</p>";

        $cont .= "  <h4>Mls Information</h4>";
        $cont .= "  <p>Days on Market: {$days_on_market}</p>";
        $cont .= "  <p>Mls Status: {$mls_status}</p>";
        $cont .= "  <p>Mls Area: {$mls_area}</p>";
        $cont .= "  <p>Service Name: {$mls_serving}</p>";

        $cont .= "</div>";

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