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