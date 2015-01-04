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



    public static function srRequestUrlBuilder( $params ) {
        // $base_url = 'http://localhost:3001/properties';

        $base_url = 'http://mojo:mojo@54.187.230.155/properties';
        $authid   = get_option( 'sr_api_name' );
        $authkey  = get_option( 'sr_api_key' );
        $prop_type = 'res';
        // ^only residentials for now

        // build enough of the url to authorize api
        $filters_query = http_build_query( array_filter( $params ) );

        $request_url = "{$base_url}?{$filters_query}";
        // ^TODO: Remove debug

        return $request_url;
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

            // append markup for this listing to the content
            $cont .= '<hr>';
            $cont .= $listing_id . $br;
            $cont .= '<div class="sr-listing">';
            $cont .= '  <div class="sr-photo">';
            $cont .= '    <a href="#">';
            $cont .= '      <img src="' . $listingPhotos[0] . '">';
            $cont .= '    </a>';
            $cont .= '  </div>';
            $cont .= '  <div class="sr-primary-data">';
            $cont .= '    <a href="#">';
            $cont .= '      <h4>' .$street_number . ' '. $street_name . '  ';
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
            $cont .= '</div>';
        }


    $cont .= '  </div>';
    $cont .= '</div>';

    return $cont;
    }

}

// $fireplaces  = $listing->residentialPropertyFireplaces;
// $heating     = $listing->residentialPropertyHeating;

// geographic data
// $geo_county     = $listing->residentialPropertyListing->listingGeographicData->geographicDataCounty;
// $geo_directions = $listing->residentialPropertyListing->listingGeographicData->geographicDataDirections;
// $geo_latitude   = $listing->residentialPropertyListing->listingGeographicData->geographicDataLatitude;
// $geo_longitude  = $listing->residentialPropertyListing->listingGeographicData->geographicDataLongitude;

// mls information
// $mls_status  = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationStatus;
// $mls_area    = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationArea;
// $mls_serving = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationServingName;
// $days_on_market = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationDaysOnMarket;

// $roof        = $listing->residentialPropertyRoof;
// $stories     = $listing->residentialPropertyStories;
// $style       = $listing->residentialPropertyStyle;

// listing meta information
// $disclaimer       = $listing->residentialPropertyListing->listingDisclaimer;
// $listing_id       = $listing->residentialPropertyListing->listingId;
// $listing_modified = $listing->residentialPropertyListing->listingModificationTimestamp; // TODO: format date
// $listing_parcel   = $listing->residentialPropertyListing->listingParcel; // probably don't need this
// $real_account     = $listing->residentialPropertyListing->listingRealAccount; // probably don't need this
// $sales_data       = $listing->residentialPropertyListing->listingSalesData; //probably empty
// $school_data      = $listing->residentialPropertyListing->listingSchoolData;
// $tax_data         = $listing->residentialPropertyListing->listingTaxData;

// $interiorFeatures = $listing->residentialPropertyInteriorFeatures;
// $exteriorFeatures = $listing->residentialPropertyExteriorFeatures;

// $listing_office   = $listing->residentialPropertyListing->listingData->listingDataOffice;
// $listing_remarks  = $listing->residentialPropertyListing->listingData->listingDataRemarks;
// $showing_instructions = $listing->residentialPropertyListing->listingData->listingDataShowingInstructions;

// $country       = $listing->residentialPropertyListing->listingStreetAddress->streetAddressCountry;
// $postal_code   = $listing->residentialPropertyListing->listingStreetAddress->streetAddressPostalCode;