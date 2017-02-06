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
        $vendors = get_option('sr_adv_search_meta_vendors', array());
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
        $listing_address = $listing->address->full;

        if($prettify && $custom_permalink_struct === "pretty_extra") {

            $listing_city = $listing->address->city;
            $listing_state = $listing->address->state;
            $listing_zip = $listing->address->postalCode;


            $url .= "/listings/$listing_city/$listing_state/$listing_zip/$listing_address/$listing_id";

            if(!empty($query)) {
                $url .= "?" . $query;
            }

        } elseif($prettify && $custom_permalink_struct === "pretty") {

            $url .= "/listings/$listing_id/$listing_address";

            if(!empty($query)) {
                $url .= "?" . $query;
            }

        } else {

            $url .= "?sr-listings=sr-single"
                 .  "&listing_id=$listing_id"
                 .  "&listing_title=$listing_address";

            if(!empty($query)) {
                $url .= "&" . $query;
            }

        }

        $url = str_replace(' ', '+', $url);
        $url = str_replace('#', '%23', $url);

        return $url;
    }

    public static function buildPaginationLinks( $pagination ) {
        $pag = array(
            'prev' => '',
            'next' => ''
        );
        $siteUrl = get_home_url() . '/?sr-listings=sr-search&';

        if( $pagination['prev'] !== null && !empty($pagination['prev'] ) ) {
            $previous = $pagination['prev'];
            $prev = str_replace( 'https://api.simplyrets.com/properties?', $siteUrl, $previous );
            $prev_link = "<a href='{$prev}'>Prev</a>";
            $pag['prev'] = $prev_link;
        }

        if( $pagination['next'] !== null && !empty($pagination['next'] ) ) {
            $nextLink = $pagination['next'];
            $next = str_replace( 'https://api.simplyrets.com/properties?', $siteUrl, $nextLink );
            $next_link = "| <a href='{$next}'>Next</a>";
            $pag['next'] = $next_link;
        }

        return $pag;
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
        $arr = array();
        # split on outer delimiter
        $pairs = explode('&', $str);
        foreach ($pairs as $i) {

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


    public static function ordinalSuffix($number) {
        $ends = array('th','st','nd','rd','th','th','th','th','th','th');
        if ((($number % 100) >= 11) && (($number%100) <= 13)) {
            return $number. 'th';
        } else {
            return $number. $ends[$number % 10];
        }
    }


    public static function mkListingSummaryCompliance($listing_office) {

        $office_on_thumbnails = get_option('sr_office_on_thumbnails', false);
        $idx_img_on_thumbnails = get_option('sr_thumbnail_idx_image', false);

        $listing_office_markup  = "";
        $listing_idx_img_markup = "";

        if (!empty($listing_office) && !empty($office_on_thumbnails)) {
            $listing_office_markup = "Listing broker: {$listing_office}";
        }

        if ($idx_img_on_thumbnails !== false && !empty($idx_img_on_thumbnails)) {
            $listing_idx_img_markup = "<img src=\"{$idx_img_on_thumbnails}\"/>";
        }


        // Add a line break if both fields are enabled
        if (!empty($listing_office_markup) && !empty($listing_idx_img_markup)) {

            return "{$listing_office_markup}<br/>{$listing_idx_img_markup}";

        } else {

            return "{$listing_office_markup} {$listing_idx_img_markup}";

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

}


class SrListing {

    /**
     * Return a 'display-ready' status for a listing. Checks the
     * sr_show_mls_status_text option and returns either the
     * statusText or status for the listing.
     */
    public static function listingStatus($listing) {
        $useStatusText = get_option('sr_show_mls_status_text', false);
        return $useStatusText ? $listing->mls->statusText : $listing->mls->status;
    }
}


class SrMessages {

    public static function noResultsMsg($response) {

        $response = (array)$response;
        if($response['message']) {
            return (
                '<br><p><strong>'
                . $response['message']
                . '</br></p></strong>'
            );
        }

        $noResultsMsg = "<br><p><strong>There are 0 listings that match this search. "
                         . "Please try to broaden your search criteria or feel free to try again later.</p></strong>";
        return $noResultsMsg;
    }

}



class SrViews {

    public static function listDateResults( $date ) {
        $markup = <<<HTML
            <li>
                <span>Listed on $date</span>
            </li>
HTML;

        return $markup;

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
