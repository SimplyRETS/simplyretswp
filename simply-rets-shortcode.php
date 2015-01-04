<?php

/*
 *
 * simple-rets-api-helper.php - Copyright (C) Reichert Brothers 2014
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
*/

/* Code starts here */

class SimplyRetsShortcodes {

    // [retsd_residential] for all residential listings
    function retsd_residential_shortcode() {
        global $wp_query;
        ob_start();
    
        if ( isset($wp_query->query_vars['listing_id']) && $wp_query->query_vars['listing_id'] != '' ) {
            $listing_id = get_query_var( 'listing_id' );

            echo '<strong>we captured a single listing query for property '; echo $listing_id; echo '</strong><br><br>';
        ?>  <!-- shortcode template here -->
            <div id="residential-properties">
              <h2>RetsD Residential Listing</h2>
              <?php retsd_residential_single( $listing_id ); ?>
            </div>
        <?php

        } else {

            echo '<strong>This is not a search for a single listing</strong><br><br>';
    
            $listing_params = array();
            $listings_content = SimpleRetsApiHelper::retrieveRetsListings( $listing_params );
            return $listings_content;

        }

        // print_r( $wp_query->query_vars ); // returns an array of all the query variables in that request
        return ob_get_clean();
    }


    // [retsd_openhouses] for all residential listings
    function retsd_openhouses_shortcode() {
        ob_start();
    
        ?> <!-- shortcode template here -->
        <div id="openhouses">
          <h2>RetsD Open Houses</h2>
          <?php retsd_openhouses(); ?>
        </div>
        <?php
    
        return ob_get_clean();
    }

    
    // [retsd_search_form] to display a form for search filtering
    function retsd_search_form_shortcode() {
        ob_start();
    
        ?>
        <div id="retsd-search-form">
          <h2>RetsD Search Form</h2>
        </div>
        <?php
    
        return ob_get_clean();
    }
}