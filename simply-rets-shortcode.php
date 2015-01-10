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
        <div id="sr-search-wrapper">
          <h4>Search Listings</h3>
          <form method="get" class="sr-search" action="<?php echo $home_url; ?>">
            <input type="hidden" name="retsd-listings" value="sr-search">

            <div class="sr-search-field" id="sr-search-keywords">
              <input name="sr_minprice" type="text" placeholder="Keywords" />
            </div>

            <div class="sr-search-field" id="sr-search-ptype">
              <select name="sr_ptype">
                <option value="">-- Property Type --</option>
                <option value="res">Residential</option>
                <option value="cnd">Condo</option>
                <option value="rnt">Rental</option>
              </select>
            </div>

            <div class="sr-search-field" id="sr-search-minprice">
              <input name="sr_minprice" type="text" placeholder="Min Price.." />
            </div>
            <div class="sr-search-field" id="sr-search-maxprice">
              <input name="sr_maxprice" type="text" placeholder="Max Price.." />
            </div>

            <div class="sr-search-field" id="sr-search-minbed">
              <input name="sr_minbed" type="text" placeholder="Min Beds.." />
            </div>
            <div class="sr-search-field" id="sr-search-maxbed">
              <input name="sr_maxbed" type="text" placeholder="Max Beds.." />
            </div>

            <div class="sr-search-field" id="sr-search-minbath">
              <input name="sr_minbath" type="text" placeholder="Min Baths.." />
            </div>
            <div class="sr-search-field" id="sr-search-maxbath">
              <input name="sr_maxbath" type="text" placeholder="Max Baths.." />
            </div>

            <br>
            <input class="submit real-btn" type="submit" value="Seach Properties">

          </form>
        </div>
        <?php
    
        return ob_get_clean();
    }
}
