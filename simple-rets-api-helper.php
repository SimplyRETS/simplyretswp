<?php

/*
 *
 * simple-rets-api-helper.php - Copyright (C) Reichert Brothers 2014
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
*/

/* Code starts here */

class simpleRetsApiHelper {

    public static function retrieveRetsListings( $params ) {
        $request_url = simpleRetsApiHelper::retsdRequestUrlBuilder( $params );

        $content = 'get your listings here: ' . $request_url;

        return $content;
    }

    public static function retsdRequestUrlBuilder( $params ) {
        $base_url = 'http://54.187.230.155/properties/';
        $property_type = 'res/';
        $request_url = $base_url . $property_type;

        return $request_url;
    }

}