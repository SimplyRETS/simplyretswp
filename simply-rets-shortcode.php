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
    function sr_search_form_shortcode( $atts ) {
        ob_start();
        $home_url = get_home_url();

        $minbeds  = array_key_exists('minbeds',  $atts) ? $atts['minbeds']  : '';
        $maxbeds  = array_key_exists('maxbeds',  $atts) ? $atts['maxbeds']  : '';
        $minbaths = array_key_exists('minbaths', $atts) ? $atts['minbaths'] : '';
        $maxbaths = array_key_exists('maxbaths', $atts) ? $atts['maxbaths'] : '';
        $minprice = array_key_exists('minprice', $atts) ? $atts['minprice'] : '';
        $maxprice = array_key_exists('maxprice', $atts) ? $atts['maxprice'] : '';
        $keywords = array_key_exists('q',        $atts) ? $atts['q']        : '';
        $type     = array_key_exists('type',     $atts) ? $atts['type']     : '';

        if( !$type == "" ) {
            $type_res = ($type == "res") ? "selected" : '';
            $type_cnd = ($type == "cnd") ? "selected" : '';
            $type_rnt = ($type == "rnt") ? "selected" : '';
        }

        ?>
        <div id="sr-search-wrapper">
          <h3>Search Listings</h3>
          <form method="get" class="sr-search" action="<?php echo $home_url; ?>">
            <input type="hidden" name="sr-listings" value="sr-search">

            <div class="sr-minmax-filters">
              <div class="sr-search-field" id="sr-search-keywords">
                <input name="sr_keywords"
                       type="text"
                       placeholder="Subdivision, Zipcode, MLS Area, MLS Number, or Market Area"
                       value="<?php echo $keywords ?>" />
              </div>

              <div class="sr-search-field" id="sr-search-ptype">
                <select name="sr_ptype">
                  <option value="">Property Type</option>
                  <option <?php echo $type_res; ?> value="res">Residential</option>
                  <option <?php echo $type_cnd; ?> value="cnd">Condo</option>
                  <option <?php echo $type_rnt; ?> value="rnt">Rental</option>
                </select>
              </div>
            </div>

            <div class="sr-minmax-filters">
              <div class="sr-search-field" id="sr-search-minprice">
                <input name="sr_minprice" type="number" value="<?php echo $minprice; ?>" placeholder="Min Price.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxprice">
                <input name="sr_maxprice" type="number" value="<?php echo $maxprice; ?>" placeholder="Max Price.." />
              </div>

              <div class="sr-search-field" id="sr-search-minbeds">
                <input name="sr_minbeds" type="number" value="<?php echo $minbeds; ?>" placeholder="Min Beds.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxbeds">
                <input name="sr_maxbeds" type="number" value="<?php echo $maxbeds; ?>" placeholder="Max Beds.." />
              </div>

              <div class="sr-search-field" id="sr-search-minbaths">
                <input name="sr_minbaths" type="number" value="<?php echo $minbaths; ?>" placeholder="Min Baths.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxbaths">
                <input name="sr_maxbaths" type="number" value="<?php echo $maxbaths; ?>" placeholder="Max Baths.." />
              </div>
            </div>

            <input class="submit button btn" type="submit" value="Search Properties">

          </form>
        </div>
        <?php

        return ob_get_clean();
    }
}
