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
        $request_url = SimpleRetsApiHelper::srRequestUrlBuilder( $params );
        $request_response = SimpleRetsApiHelper::srApiRequest( $request_url );
        $response_markup = SimpleRetsApiHelper::srResidentialGenerator( $request_response );


        $br = '<br>';
        $content = 'get your listings here: ' . $request_url . $br;
        $content = $content . 'here are your listings: ' . $request_response . $br;
        $content = $request_response;

        return $content;
    }

    // takes parameters from the loaded post and creates the url we need to call
    // the remote retsd api.
    public static function srRequestUrlBuilder( $params ) {
        // $base_url = 'http://54.187.230.155/properties/';
        $base_url = 'http://localhost:3001/properties?authid=mojo&authkey=mojo&type=res';

        // run params through this filter to remove elements with empty values
        $filters = array_filter( $params );

        $filters_query = http_build_query( $filters );
        $request_url = $base_url . $property_type . $start_query . $filters_query . '<br>';

        return $base_url;
    }

    public static function srApiRequest( $url ) {
        $request = wp_remote_retrieve_body( wp_remote_get( $url ) );
        $response_json = json_decode( $request );

        $response = $response_json;

        return $response;
    }

    public static function srResidentialGenerator( $response ) {
        echo 'we\'ll parse the property info here <br><br>';
    }

}