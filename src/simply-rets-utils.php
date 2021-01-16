<?php

/*
 *
 * simply-rets-utils.php - Copyright (C) 2014-2015 SimplyRETS
 * This file provides general utilities for the SimplyRETS plugin.
 *
*/


/* Code starts here */
class SrUtils {


    public static function isSingleVendor() {
        $vendors = (array)get_option('sr_adv_search_meta_vendors', array());

        if(count($vendors) > 1) {
            return false;
        }

        return true;
    }


    public static function srShowListingMeta() {

        if( get_option('sr_show_listingmeta') ) {
            $show_listing_meta = false;
        } else {
            $show_listing_meta = true;
        }

        return $show_listing_meta;
    }

    /**
     * The naming for the database option is backwards.
     * If it's 'checked', we _don't_ show data.
     */
    public static function showAgentContact() {

        if( get_option('sr_show_agent_contact') ) {
            $show = false;
        } else {
            $show = true;
        }

        return $show;
    }

    /**
     * Normalize a property type abbreviation into the full text.
     */
    public static function normalizePropertyType($type) {
        $normalized_type = null;

        switch($type) {
            case "RES":
                $normalized_type = "Residential";
                break;

            case "CND":
                $normalized_type = "Condominium";
                break;

            case "RNT":
                $normalized_type = "Rental";
                break;

            case "CRE":
                $normalized_type = "Commercial";
                break;

            case "LND":
                $normalized_type = "Land";
                break;

            case "MLF":
                $normalized_type = "MultiFamily";
                break;

            case "FRM":
                $normalized_type = "Farm";
                break;

            case "MBL":
                $normalized_type = "Mobile Home";
                break;

        }

        return $normalized_type;
    }

    /**
     * Remove trailing "County" (or "county") from a county name so it
     * can be shown within our details builder, or in other use-cases
     * where "County" may be appended manually.
     */
    public static function normalizeCountyText($county) {
        $county_text = str_replace(" County", "", $county);
        $county_text = str_replace(" county", "", $county_text);
        return $county_text;
    }

    /**
     * Render a listings full address with state and postalCode
     * <street address>, <city>, <state> <zip>
     */
    public static function buildFullAddressString($listing) {

        $idxAddress = $listing->internetAddressDisplay;
        $address = $idxAddress === false
                 ? $idxAddressReplacement = get_option(
                     "sr_idx_address_display_text",
                     "Undisclosed address"
                 ) : $listing->address->full;

        $city = $listing->address->city;
        $state = $listing->address->state;
        $zip = $listing->address->postalCode;

        // A listing might have a null address if a flag like "Display
        // address" is set to false. This just removes the comma in
        // these cases, but the rest of the address remains the same.
        $comma = $address ? ', ' : '';

        return $address . $comma . $city . ', ' . $state . ' ' . $zip;
    }

    /**
     * Encode specific characters in a string to work in a URL. For
     * example, a `/` character cannot be normally encoded as %2F
     * because Apache url decodes the string and treats it as a path
     * separator. See: https://stackoverflow.com/a/3235361/3464723
     */
    public static function encodeStringForUrl($str) {
        return str_replace("/", "_", $str);
    }

    // Decode a string encoded with `encodeStringForUrl`
    public static function decodeStringForUrl($str) {
        return str_replace("_", "/", $str);
    }

    /**
     * Builds a link to a listings' details page. Used in search results.
     */
    public static function buildDetailsLink($listing, $params = array()) {

        $permalink_struct = get_option('permalink_structure', '');
        $custom_permalink_struct = get_option('sr_permalink_structure', '');

        // Are pretty permalinks enabled?
        $prettify = true;
        $prettify = $custom_permalink_struct === "pretty"       ? true  : $prettify;
        $prettify = $custom_permalink_struct === "pretty_extra" ? true  : $prettify;
        $prettify = $custom_permalink_struct === "query_string" ? false : $prettify;
        $prettify = $permalink_struct === ""                    ? false : $prettify;

        // Build a query string
        $_query = http_build_query($params);
        $query = !empty($_query) ? $_query : "";

        // Base of the URL we're building
        $url = get_home_url();

        // Listing details
        $listing_id = $listing->mlsId;
        $listing_city = $listing->address->city;
        $listing_state = $listing->address->state;
        $listing_zip = $listing->address->postalCode;

        $listing_address = $listing->address->full;
        $listing_address_full = SrUtils::encodeStringForUrl(
            SrUtils::buildFullAddressString($listing)
        );

        if($prettify && $custom_permalink_struct === "pretty_extra") {

            $url .= "/listings/$listing_city/$listing_state/$listing_zip/$listing_address_full/$listing_id";

            if(!empty($query)) {
                $url .= "?" . $query;
            }

        } elseif($prettify && $custom_permalink_struct === "pretty") {

            $url .= "/listings/$listing_id/$listing_address_full";

            if(!empty($query)) {
                $url .= "?" . $query;
            }

        } else {

            $url .= "?sr-listings=sr-single"
                 .  "&listing_id=$listing_id"
                 .  "&listing_title=$listing_address_full";

            if(!empty($query)) {
                $url .= "&" . $query;
            }

        }

        // URL encode special characters
        $url = str_replace(' ', '+', $url);
        $url = str_replace('#', '%23', $url);
        $url = str_replace(',', '%2C', $url);

        return $url;
    }

    public static function buildPaginationLinks( $pagination ) {
        $prevPagination = $pagination["prev"];
        $nextPagination = $pagination["next"];
        $destination = $prevPagination && strpos($prevPagination, "/openhouses") ||
                       $nextPagination && strpos($nextPagination, "/openhouses")
                     ? "sr-openhouses"
                     : "sr-search";

        $siteUrl = get_home_url() . "/?sr-listings={$destination}&";
        $paginationLinks = array('prev' => '', 'next' => '');
        $apiUrls = array(
            "https://api.simplyrets.com/properties?",
            "https://api.simplyrets.com/openhouses?",
        );


        if($prevPagination !== null && !empty($prevPagination)) {
            $prev = str_replace($apiUrls, $siteUrl, $prevPagination);
            $prev_link = "<a href='{$prev}'>Prev</a>";
            $paginationLinks['prev'] = $prev_link;
        }

        if($nextPagination !== null && !empty($nextPagination)) {
            $next = str_replace($apiUrls, $siteUrl, $nextPagination);
            $maybe_pipe = $prevPagination && !empty($prevPagination) ? "|" : "";
            $next_link = "{$maybe_pipe} <a href='{$next}'>Next</a>";
            $paginationLinks['next'] = $next_link;
        }

        return $paginationLinks;
    }


    /**
     * Use this instead of builting parse_str
     * proper_parse_str will sanely handle duplicate
     * keys in the query. id ?foo=1&foo2
     *
     * @param $str - a query string
     * @result $arr - the query string in array form
     */
    public static function proper_parse_str($str) {

        if (empty($str)) {
            return array();
        }

        $arr = array();
        $pairs = explode('&', $str);

        foreach ($pairs as $i) {

            if (empty($i)) {
                continue;
            }

            list($name,$value) = explode('=', $i, 2);

            if( isset($arr[$name]) ) {

                if( is_array($arr[$name]) ) {
                    $arr[$name][] = $value;
                }
                else {
                    $arr[$name] = array($arr[$name], $value);
                }
            }
            else {
                $arr[$name] = $value;
            }
        }
        return $arr;
    }

    /**
     * Build a query string from an array of parameters. NOTE: This
     * function REMOVES array indexes ([0]) from parameters names that
     * are specified multiple times. For example:
     *
     * http_build_query: q[0]=first&q[1]=second
     * proper_build_query: q=first&q=second
     */
    public static function proper_build_query($params = array()) {
        $array_indice_regex = "/%5B(?:[0-9]|[1-9][0-9]+)%5D=/";
        $query_str = http_build_query($params);
        return preg_replace($array_indice_regex, "=", $query_str);
    }

    public static function ordinalSuffix($number) {
        $ends = array('th','st','nd','rd','th','th','th','th','th','th');
        if ((($number % 100) >= 11) && (($number%100) <= 13)) {
            return $number. 'th';
        } else {
            return $number. $ends[$number % 10];
        }
    }


    /**
     * Create markup for showing various MLS compliance information
     * based on users current admin settings.
     */
    public static function mkListingSummaryCompliance($listing_office, $listing_agent) {

        /** Get current settings */
        $office_on_thumbnails = get_option('sr_office_on_thumbnails', false);
        $agent_on_thumbnails = get_option('sr_agent_on_thumbnails', false);
        $idx_img_on_thumbnails = get_option('sr_thumbnail_idx_image', false);

        /** Helpers if agent or office CAN and SHOULD be shown */
        $show_agent = !empty($agent_on_thumbnails) && trim($listing_agent) !== "";
        $show_office = !empty($office_on_thumbnails) && trim($listing_office) !== "";

        /** Initial markup */
        $listing_by = "";
        $listing_idx_img_markup = "";

        /**
         * Create a "Listing by" string that shows some combination of
         * listing agent and/or office depending on current settings.
         */
        if ($show_office || $show_agent) {
            $listing_by = "Listing by ";

            if ($show_agent) {
                $listing_by .= $listing_agent;
            }

            if ($show_office) {
                if ($show_agent) {
                    $listing_by .= ", {$listing_office}";
                } else {
                    $listing_by .= $listing_office;
                }
            }
        }

        /**
         * Create an <img> element if IDX image is available and
         * setting is enabled.
         */
        if (!empty($idx_img_on_thumbnails) && !empty($idx_img_on_thumbnails)) {
            $listing_idx_img_markup = "<img src=\"{$idx_img_on_thumbnails}\"/>";
        }


        // Add a line break if both fields are enabled
        if (!empty($listing_by) && !empty($listing_idx_img_markup)) {
            return "<span class='sr-listing-summary-compliance'>"
                . "{$listing_by}<br/>{$listing_idx_img_markup}"
                . "</span>";
        } else {
            return "<span class='sr-listing-summary-compliance'>"
                . "{$listing_by} {$listing_idx_img_markup}"
                . "</span>";
        }

    }


    /**
     * Generate disclaimer text shown with short-code listings.  If
     * the user has provided a custom disclaimer in their settings
     * page use that, otherwise use the SimplyRETS default.
     */
    public static function mkDisclaimerText($lastUpdate) {
        $custom_disclaimer = get_option('sr_custom_disclaimer', false);

        if ($custom_disclaimer) {

            // Splice lastUpdate date into custom disclaimer
            $built_disclaimer = str_replace('{lastUpdate}', $lastUpdate, $custom_disclaimer);

            return html_entity_decode($built_disclaimer);

        } else {

            return "This information is believed to be accurate, but without any warranty.";

        }
    }

    /**
     * Created the "Listing by" markup if
     * sr_agent_office_above_the_fold is enabled. This also handles
     * showing the correct info when only the agent name, or only the
     * office name is available.
     */
    public static function mkAgentOfficeAboveTheFold($agent, $office) {

        // Initialize variables
        $listing_by;

        // Ensure we have all the info we need
        $agentOfficeAboveTheFoldEnabled = get_option(
            'sr_agent_office_above_the_fold',
            false
        );

        if ($agentOfficeAboveTheFoldEnabled) {

            if (!empty($agent) AND !empty($office)) {

                /**
                 * Agent and office are available, show both of them
                 */
                $listing_by .= "Listing by: ";
                $listing_by .= "<strong>$agent</strong>, ";
                $listing_by .= "<strong>$office</strong>";
                return "<p>$listing_by</p>";

            } elseif (empty($agent) AND !empty($office)) {

                /**
                 * Only office name is available, show that
                 */
                $listing_by = "Listing by: <strong>$office</strong>";
                return "<p>$listing_by</p>";

            } elseif (!empty($agent) AND empty($office)) {

                /**
                 * Only agent name is available, show that
                 */
                $listing_by = "Listing by: <strong>$agent</strong>";
                return "<p>$listing_by</p>";

            } else {
                return "";
            }
        }

        return "";
    }

    /**
     * Return the text "MLS". If the 'sr_show_mls_trademark_symbol'
     * admin option is enabled, the trademark symbol is returned with
     * the text: MLS®
     */
    public static function mkMLSText() {
        $td = get_option('sr_show_mls_trademark_symbol', false);

        if (empty($td)) {
            return "MLS";
        } else {
            return "MLS®";
        }
    }

}


class SrListing {

    public static $default_photo =
        "https://s3-us-west-2.amazonaws.com/simplyrets/trial/properties/defprop.jpg";

    public static function normalizeListingPhotoUrl($url) {
        $force_https = get_option("sr_listing_force_image_https", false);

        if ($force_https) {
            return str_replace("http://", "https://", $url);
        } else {
            return $url;
        }
    }

    public static function mainPhotoOrDefault($listing) {
        $photos = $listing->photos;

        if (empty($photos)) {
            return SrListing::$default_photo;
        } else {
            $main_photo = str_replace("//", "", trim($photos[0]));
            return SrListing::normalizeListingPhotoUrl($photos[0]);
        }
    }

    /**
     * Return a 'display-ready' status for a listing. Checks the
     * sr_show_mls_status_text option and returns either the
     * statusText or status for the listing.
     */
    public static function listingStatus($listing) {
        $useStatusText = get_option('sr_show_mls_status_text', false);
        return $useStatusText ? $listing->mls->statusText : $listing->mls->status;
    }

    /**
     * Return a 'display-ready' number of bathrooms for a
     * listing. Checks for `.property.bathrooms` first, and then
     * `.property.bathsFull`, and pluralizes the "bath(s)" text.
     */
    public static function getBathroomsDisplay(
        $bathrooms,
        $bathsFull = 0,
        $small_text = false
    ) {
        if (is_numeric($bathrooms)) {
            $s = $bathrooms > 1 ? "s" : "";
            $e = $small_text ? "<small>Bath$s</small>" : "Bath$s";

            return "$bathrooms $e";
        }

        $s = $bathsFull > 1 ? "s" : "";
        $b = $bathsFull > 0 ? $bathsFull : "n/a";
        $e = $small_text ? "<small>Full bath$s</small>" : "Full bath$s";

        return "$b Full bath$s";
    }
}


class SrMessages {

    public static function noResultsMsg($response) {

        $response = (array)$response;
        if(array_key_exists("message", $response)) {
            return (
                '<br><p><strong>'
                . $response['message']
                . '</br></p></strong>'
            );
        }

        /** Use custom message from admin settings, if set. */
        $customMsg = get_option('sr_custom_no_results_message');
        if ($customMsg !== false && $customMsg !== "") {
            return $customMsg;
        }

        $noResultsMsg = "<br><p><strong>There are 0 listings that match this search. "
                         . "Please try to broaden your search criteria or feel free to try again later.</p></strong>";
        return $noResultsMsg;
    }

}


/**
 * Top level 'pollyfill' for 'http_parse_headers'
 *
 * Taken from PHP implementation:
 * http://php.net/manual/it/function.http-parse-headers.php
 */
if (!function_exists('http_parse_headers')) {
    function http_parse_headers ($raw_headers) {
        $headers = array(); // $headers = [];

        foreach (explode("\n", $raw_headers) as $i => $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                if(!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim($h[1]);
                } else if(is_array($headers[$h[0]])) {
                    $tmp = array_merge($headers[$h[0]],array(trim($h[1])));
                    $headers[$h[0]] = $tmp;
                } else {
                    $tmp = array_merge(array($headers[$h[0]]),array(trim($h[1])));
                    $headers[$h[0]] = $tmp;
                }
            }
        }

        return $headers;
    }
}
