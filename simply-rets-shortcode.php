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

        echo '<strong>This is not a search for a single listing</strong><br><br>';
    
        $listing_params = array();
        $listings_content = SimpleRetsApiHelper::retrieveRetsListings( $listing_params );
        return $listings_content;

        // print_r( $wp_query->query_vars ); // returns an array of all the query variables in that request
        return ob_get_clean();
    }


    // [retsd_openhouses] for all residential listings
    function retsd_openhouses_shortcode() {
        ob_start();
    
        ?> <!-- shortcode template here -->
        <div id="openhouses">
          <h2>Simply Rets Open Houses</h2>
          <?php retsd_openhouses(); ?>
        </div>
        <?php
    
        return ob_get_clean();
    }

    
    // [retsd_search_form] to display a form for search filtering
    function retsd_search_form_shortcode() {
        ob_start();
    
        $home_url = get_home_url();

        ?>
        <div id="retsd-search-form">
          <h2>Simply Rets Search</h2>
        </div>
        <form method="get" action="<?php echo $home_url; ?>">

          <input type="hidden" name="retsd-listings" value="sr-search">

          <label for="sr-minprice">Minimum Price</label>
          <input id="sr-minprice" name="sr_minprice" type="text" />
          <label for="sr-maxprice">Maximum Price</label>
          <input id="sr-maxprice" name="sr_maxprice" type="text" />

          <label for="sr-minbed">Minimum Bedrooms</label>
          <input id="sr-minbed" name="sr_minbed" type="text" />
          <label for="sr-maxbed">Maximum Bedrooms</label>
          <input id="sr-maxbed" name="sr_maxbed" type="text" />

          <label for="sr-minbath">Minimum Bathrooms</label>
          <input id="sr-minbath" name="sr_minbath" type="text" />
          <label for="sr-maxbath">Maximum Bathrooms</label>
          <input id="sr-maxbath" name="sr_maxbath" type="text" />


          <br>
          <input class="submit real-btn" type="submit" value="Seach Properties">

        </form>
        <?php
    
        return ob_get_clean();
    }
}
