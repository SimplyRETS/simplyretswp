<?php

/*
 * simply-rets-api-helper.php - Copyright (C) 2014-2024 SimplyRETS, Inc.
 *
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
*/

/* Code starts here */

class SimplyRetsApiHelper {

    public static function retrieveRetsListings($params, $settings = NULL) {
        $request_url      = SimplyRetsApiClient::srRequestUrlBuilder($params);
        $request_response = SimplyRetsApiClient::srApiRequest($request_url);
        $response_markup  = SimplyRetsRenderer::srResidentialResultsGenerator($request_response, $settings);

        return $response_markup;
    }

    public static function retrieveOpenHousesResults($params, $settings = NULL) {
        $api_url = SimplyRetsApiClient::srRequestUrlBuilder($params, "openhouses");
        $api_response = SimplyRetsApiClient::srApiRequest($api_url);

        return SimplyRetsOpenHouses::openHousesSearchResults($api_response, $settings);
    }

    public static function retrieveListingDetails($listing_id) {
        $request_url      = SimplyRetsApiClient::srRequestUrlBuilder($listing_id, "properties", true);
        $request_response = SimplyRetsApiClient::srApiRequest($request_url);
        $response_markup  = SimplyRetsRenderer::srResidentialDetailsGenerator($request_response);

        return $response_markup;
    }

    public static function retrieveWidgetListing($listing_id, $settings = NULL) {
        $request_url      = SimplyRetsApiClient::srRequestUrlBuilder($listing_id);
        $request_response = SimplyRetsApiClient::srApiRequest($request_url);
        $response_markup  = SimplyRetsRenderer::srWidgetListingGenerator($request_response, $settings);

        return $response_markup;
    }

    public static function retrieveListingsSlider($params, $settings = NULL) {
        $request_url      = SimplyRetsApiClient::srRequestUrlBuilder($params);
        $request_response = SimplyRetsApiClient::srApiRequest($request_url);
        $response_markup  = SimplyRetsRenderer::srListingSliderGenerator($request_response, $settings);

        return $response_markup;
    }
}
