<?php

/*
 *
 * simply-rets-api-helper.php - Copyright (C) Reichert Brothers 2014
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
*/

/* Code starts here */

class SimplyRetsShortcodes {

    // [sr_residential] for all residential listings
    function sr_residential_shortcode( $atts ) {
        global $wp_query;
        ob_start();


        if( !empty($atts['mlsid']) ) {
            $mlsid = $atts['mlsid'];
            $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $mlsid );
            return $listings_content;
        }

        $listing_params = array();
        $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $listing_params );
        return $listings_content;

    }


    // [sr_openhouses] for all residential listings
    // this is pulling condos and obviously needs to be pulling open houses
    function sr_openhouses_shortcode() {
        $listing_params = array(
            "type" => "cnd"
        );
        $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $listing_params );
        $listings_content = "Sorry we could not find any open houses that match your search.";
        return $listings_content;
    }


    // [sr_search_form] to display a form for search filtering
    function sr_search_form_shortcode() {
        ob_start();
        $home_url = get_home_url();

        ?>
        <div id="sr-search-wrapper">
          <h3>Search Listings</h3>
          <form method="get" class="sr-search" action="<?php echo $home_url; ?>">
            <input type="hidden" name="sr-listings" value="sr-search">

            <div class="sr-minmax-filters">
              <div class="sr-search-field" id="sr-search-keywords">
                <input name="sr_keywords" type="text" placeholder="Subdivision, Zipcode, MLS Area, MLS Number, or Market Area" />
              </div>

              <div class="sr-search-field" id="sr-search-ptype">
                <select name="sr_ptype">
                  <option value="">Property Type</option>
                  <option value="res">Residential</option>
                  <option value="cnd">Condo</option>
                  <option value="rnt">Rental</option>
                </select>
              </div>
            </div>

            <div class="sr-minmax-filters">
              <div class="sr-search-field" id="sr-search-minprice">
                <input name="sr_minprice" type="number" placeholder="Min Price.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxprice">
                <input name="sr_maxprice" type="number" placeholder="Max Price.." />
              </div>

              <div class="sr-search-field" id="sr-search-minbeds">
                <input name="sr_minbeds" type="number" placeholder="Min Beds.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxbeds">
                <input name="sr_maxbeds" type="number" placeholder="Max Beds.." />
              </div>

              <div class="sr-search-field" id="sr-search-minbaths">
                <input name="sr_minbaths" type="number" placeholder="Min Baths.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxbaths">
                <input name="sr_maxbaths" type="number" placeholder="Max Baths.." />
              </div>
            </div>

            <input class="submit button btn" type="submit" value="Search Properties">

          </form>
        </div>
        <?php

        return ob_get_clean();
    }
}
