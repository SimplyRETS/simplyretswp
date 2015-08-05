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
        if(count($vendors) > 1)
            return false;
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