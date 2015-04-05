<?php

/*
 *
 * simply-rets-utils.php - Copyright (C) 2014-2015 SimplyRETS
 * This file provides general utilities for the SimplyRETS plugin.
 *
*/


/* Code starts here */
class SrUtils {

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