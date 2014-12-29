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
        $response_markup  = SimpleRetsApiHelper::srResidentialGenerator( $request_response );


        $br = '<br>';
        $content = 'request_url: ' . $request_url . $br;
        $content .= 'request_response: ' . $request_response . $br;
        $content = $content . 'request_markup: ' . $response_markup . $br;

        return $content;

    }

    // takes parameters from the loaded post and creates the url we need to call
    // the remote retsd api.
    public static function srRequestUrlBuilder( $params ) {
        $base_url = 'http://54.187.230.155/properties';
        // $base_url = 'http://localhost:3001/properties';
        $authid   = get_option( 'sr_api_name' );
        $authkey  = get_option( 'sr_api_key' );
        $prop_type = 'res';
        // ^only residentials for now

        // build enough of the url to authorize api
        $auth_url = "{$base_url}?authid={$authid}&authkey={$authkey}&type={$prop_type}";

        // run params through this filter to remove elements with empty values
        $filters = array_filter( $params );
        $filters_query = http_build_query( $filters );
        $request_url = "{$auth_url}&{$filters_query}";

        return $request_url;
    }

    public static function srApiRequest( $url ) {
        $request = wp_remote_retrieve_body( wp_remote_get( $url ) );
        $response_json = json_decode( $request );

        $response = $response_json;

        return $response;
    }

    public static function srResidentialGenerator( $response ) {
        ?>
        <script>
        console.log( <?php print_r ( json_encode( $response ) ); ?> );
        </script>
        <?php

        $br = "<br>";
        foreach ( $response as $listing ) {
            // id
            $listing_uid      = $listing->residentialPropertyListing->listingId;
            echo "<h4>Listing Id: <a href=\"/?retsd-listings=search&listing_id={$listing_uid}\">{$listing_uid}</a></h4>";

            // Amenities
            $bedrooms    = $listing->residentialPropertyBedrooms;
            $bathsFull   = $listing->residentialPropertyBathsFull;
            $fireplaces  = $listing->residentialPropertyFireplaces;
            $heating     = $listing->residentialPropertyHeating;
            $lotSize     = $listing->residentialPropertyLotSize; // might be null
            $roof        = $listing->residentialPropertyRoof;
            $stories     = $listing->residentialPropertyStories;
            $style       = $listing->residentialPropertyStyle;
            $subdivision = $listing->residentialPropertySubdivision;
            $yearBuilt   = $listing->residentialPropertyYearBuild;
            $interiorFeatures = $listing->residentialPropertyInteriorFeatures;
            $exteriorFeatures = $listing->residentialPropertyExteriorFeatures;
            echo "<strong>Amenities</strong>"             . $br;
            echo "Beds: {$bedrooms}"                      . $br;
            echo "Bathrooms: {$bathsFull}"                . $br;
            echo "Fireplaces: {$fireplaces}"              . $br;
            echo "Heating: {$heating}"                    . $br;
            echo "Lot Size: {$lotSize}"                   . $br;
            echo "Roof: {$roof}"                          . $br;
            echo "Stories: {$stories}"                    . $br;
            echo "Style: {$style}"                        . $br;
            echo "Subdivision: {$subdivision}"            . $br;
            echo "Year Built: {$yearBuilt}"               . $br;
            echo "Interior Features: {$interiorFeatures}" . $br;
            echo "Exterior Features: {$exteriorFeatures}" . $br;

            // listing meta information
            $disclaimer       = $listing->residentialPropertyListing->listingDisclaimer;
            $listing_id       = $listing->residentialPropertyListing->listingId;
            $listing_modified = $listing->residentialPropertyListing->listingModificationTimestamp; // TODO: format date
            $listing_parcel   = $listing->residentialPropertyListing->listingParcel; // probably don't need this
            $real_account     = $listing->residentialPropertyListing->listingRealAccount; // probably don't need this
            $sales_data       = $listing->residentialPropertyListing->listingSalesData; //probably empty
            $school_data      = $listing->residentialPropertyListing->listingSchoolData;
            $tax_data         = $listing->residentialPropertyListing->listingTaxData;
            echo "<strong>Meta Info</strong>"            . $br;
            echo "Disclaimer: {$disclaimer}"             . $br;
            echo "Listing Id: {$listing_id}"             . $br;
            echo "Listing Modified: {$listing_modified}" . $br;
            echo "Listing Parcel: {$listing_parcel}"     . $br;
            echo "Listing Real Account: {$real_account}" . $br;
            echo "Listing Sales Data: {$sales_data}"     . $br;
            echo "Listing School Data: {$school_data}"   . $br;
            echo "Listing Tax Data: {$tax_data}"         . $br;

            // listing data
            $listing_agent    = $listing->residentialPropertyListing->listingData->listingDataAgent;
            $list_date        = $listing->residentialPropertyListing->listingData->listingDataListDate;
            $listing_price    = $listing->residentialPropertyListing->listingData->listingDataListPrice;
            $listing_office   = $listing->residentialPropertyListing->listingData->listingDataOffice;
            $listing_remarks  = $listing->residentialPropertyListing->listingData->listingDataRemarks;
            $showing_instructions = $listing->residentialPropertyListing->listingData->listingDataShowingInstructions;
            echo "<strong>Listing Data</strong>"                 . $br;
            echo "Listing Agent: {$listing_agent}"               . $br;
            echo "List Date: {$list_date}"                       . $br;
            echo "Listing Price: {$listing_price}"               . $br;
            echo "Listing Office: {$listing_office}"             . $br;
            echo "Listing Remarks: {$listing_remarks}"           . $br;
            echo "Showing Instructions: {$showing_instructions}" . $br;

            // geographic data
            $geo_county     = $listing->residentialPropertyListing->listingGeographicData->geographicDataCounty;
            $geo_directions = $listing->residentialPropertyListing->listingGeographicData->geographicDataDirections;
            $geo_latitude   = $listing->residentialPropertyListing->listingGeographicData->geographicDataLatitude;
            $geo_longitude  = $listing->residentialPropertyListing->listingGeographicData->geographicDataLongitude;
            echo "<strong>Geographic Info</strong>" . $br;
            echo "County: {$geo_county}"            . $br;
            echo "Directions: {$geo_directions}"        . $br;
            echo "Latitude: {$geo_latitude}"          . $br;
            echo "Longitude: {$geo_longitude}"         . $br;

            // mls information
            $mls_status  = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationStatus;
            $mls_area    = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationArea;
            $mls_serving = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationServingName;
            $days_on_market = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationDaysOnMarket;
            echo "<strong>Mls Info</strong>"         . $br;
            echo "Mls Area: {$mls_area}"             . $br;
            echo "Days on Market: {$days_on_market}" . $br;
            echo "Serving Name: {$mls_serving}"      . $br;
            echo "Mls Status: {$mls_status}"         . $br;

            // street address info
            $city          = $listing->residentialPropertyListing->listingStreetAddress->streetAddressCity;
            $country       = $listing->residentialPropertyListing->listingStreetAddress->streetAddressCountry;
            $postal_code   = $listing->residentialPropertyListing->listingStreetAddress->streetAddressPostalCode;
            $street_name   = $listing->residentialPropertyListing->listingStreetAddress->streetAddressStreetName;
            $street_number = $listing->residentialPropertyListing->listingStreetAddress->streetAddressStreetNumber;
            echo "<strong>Street Address</strong>" . $br;
            echo "City: {$city}"                   . $br;
            echo "County: {$country}"              . $br;
            echo "Postal Code: {$postal_code}"     . $br;
            echo "Street Name: {$street_name}"     . $br;
            echo "Street Number: {$street_number}" . $br;

            // listing photos
            $listingPhotos = $listing->residentialPropertyListing->listingPhotos;
            echo "<strong>Listing Photos</strong>" . $br;

            if( empty( $listingPhotos ) ) {
                echo 'no photos for this listing';
            } else {
                echo $listingPhotos[0] . $br;
                echo '<img src="' . $listingPhotos[0] . '" width="100%">' . $br;
            }

            echo '<hr>';
        }

        $cont = "here is your property listings markup: ";
        $cont .= $response;

        return $cont;
    }

}
