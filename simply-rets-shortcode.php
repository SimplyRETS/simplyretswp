<?php

/*
 *
 * simply-rets-api-helper.php - Copyright (C) 2014-2015 SimplyRETS
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
*/

/* Code starts here */

add_action('init', array('SimplyRetsShortcodes', 'sr_residential_btn') );


class SimplyRetsShortcodes {


    /**
     * Short code kitchen sink button registration
     */
    public static function sr_residential_btn() {
        if ( current_user_can('edit_posts') && current_user_can('edit_pages') ) {
            add_filter('mce_external_plugins', array('SimplyRetsShortcodes', 'sr_res_add_plugin') );
            add_filter('mce_buttons', array('SimplyRetsShortcodes', 'sr_register_res_button') );
        }
    }

    public static function sr_register_res_button($buttons) {
        array_push($buttons, "simplyRets");
        return $buttons;
    }

    public static function sr_res_add_plugin($plugin_array) {
        $plugin_array['simplyRets'] = plugins_url( 'assets/js/simply-rets-shortcodes.js', __FILE__ );
        return $plugin_array;
    }


    /**
     * [sr_residential] - Residential Listings Shortcode
     *
     * Show all residential listings with the ability to filter by mlsid
     * to show a single listing.
     * ie, [sr_residential mlsid="12345"]
     */
    public function sr_residential_shortcode( $atts ) {
        global $wp_query;

        if( !empty($atts['mlsid']) ) {
            $mlsid = $atts['mlsid'];
            $listing_params = array(
                "q" => $mlsid
            );
            $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $listing_params, $atts );
            return $listings_content;
        }

        if(!is_array($atts)) {
            $listing_params = array();
        } else {
            $listing_params = $atts;
        }

        if( !isset( $listing_params['neighborhoods'] ) && !isset( $listing_params['postalcodes'] ) ) {
            $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $listing_params, $atts );
            return $listings_content;

        } else {
            /**
             * Neighborhoods filter is being used - check for multiple values and build query accordingly
             */
            if( isset( $listing_params['neighborhoods'] ) && !empty( $listing_params['neighborhoods'] ) ) {
                $neighborhoods = explode( ';', $listing_params['neighborhoods'] );
                foreach( $neighborhoods as $key => $neighborhood ) {
                    $neighborhood = trim( $neighborhood );
                    $neighborhoods_string .= "neighborhoods=$neighborhood&";
                }
                $neighborhoods_string = str_replace(' ', '%20', $neighborhoods_string );
            }

            /**
             * Postal Codes filter is being used - check for multiple values and build query accordingly
             */
            if( isset( $listing_params['postalcodes'] ) && !empty( $listing_params['postalcodes'] ) ) {
                $postalcodes = explode( ';', $listing_params['postalcodes'] );
                foreach( $postalcodes as $key => $postalcode  ) {
                    $postalcode = trim( $postalcode );
                    $postalcodes_string .= "postalCodes=$postalcode&";
                }
                $postalcodes_string = str_replace(' ', '%20', $postalcodes_string );
            }

            foreach( $listing_params as $key => $value ) {
                if( $key !== 'postalcodes' && $key !== 'neighborhoods' ) {
                    $params_string .= $key . "=" . $value . "&";
                }
            }

            $qs = '?';
            $qs .= $neighborhoods_string;
            $qs .= $postalcodes_string;
            $qs .= $params_string;

            $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $qs, $atts );
            return $listings_content;
        }


        $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $listing_params, $atts );
        return $listings_content;
    }


    /**
     * Open Houses Shortcode - [sr_openhouses]
     *
     * this is pulling condos and obviously needs to be pulling open houses
     */
    public static function sr_openhouses_shortcode() {
        $listing_params = array(
            "type" => "cnd"
        );
        $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $listing_params );
        $listings_content = "Sorry we could not find any open houses that match your search.";
        return $listings_content;
    }


    /**
     * Search Form Shortcode - [sr_search_form]
     *
     * Can be used to insert a search form into any page or post. The shortcode takes
     * optional parameters to have default searches:
     * ie, [sr_search_form q="city"] or [sr_search_form minprice="500000"]
     */
    public static function sr_search_form_shortcode( $atts ) {
        ob_start();
        $home_url = get_home_url();

        if( !is_array($atts) ) {
            $atts = array();
        }
        $minbeds    = array_key_exists('minbeds',  $atts) ? $atts['minbeds']  : '';
        $maxbeds    = array_key_exists('maxbeds',  $atts) ? $atts['maxbeds']  : '';
        $minbaths   = array_key_exists('minbaths', $atts) ? $atts['minbaths'] : '';
        $maxbaths   = array_key_exists('maxbaths', $atts) ? $atts['maxbaths'] : '';
        $minprice   = array_key_exists('minprice', $atts) ? $atts['minprice'] : '';
        $maxprice   = array_key_exists('maxprice', $atts) ? $atts['maxprice'] : '';
        $keywords   = array_key_exists('q',        $atts) ? $atts['q']        : '';
        $sort       = array_key_exists('sort',     $atts) ? $atts['sort']     : '';
        /** Advanced Search Parameters */
        $type       = array_key_exists('type',     $atts) ? $atts['type']     : '';
        $adv_type   = array_key_exists('type',     $atts) ? $atts['type']     : '';

        $adv_status = array_key_exists('status',   $atts) ? $atts['status']   : '';
        $lotsize    = array_key_exists('lotsize',  $atts) ? $atts['lotsize']  : '';
        $area       = array_key_exists('area',     $atts) ? $atts['area']     : '';

        $adv_features      = isset($_GET['sr_features']) ? $_GET['sr_features'] : array();
        $adv_cities        = isset($_GET['sr_cities']) ? $_GET['sr_cities']     : array();
        $adv_neighborhoods = isset($_GET['sr_neighborhoods']) ? $_GET['sr_neighborhoods']     : array();

        if( !$type == "" ) {
            $type_res = ($type == "res") ? "selected" : '';
            $type_cnd = ($type == "cnd") ? "selected" : '';
            $type_rnt = ($type == "rnt") ? "selected" : '';
        }

        if( !$sort  == "" ) {
            $sort_price_hl = ($sort == "-listprice") ? "selected" : '';
            $sort_price_lh = ($sort == "listprice")  ? "selected" : '';
            $sort_date_hl  = ($sort == "-listdate")  ? "selected" : '';
            $sort_date_lh  = ($sort == "listdate")   ? "selected" : '';
        }

        /**
         * Advanced Search Form.
         * Used by [sr_search_form advanced='true']
         *
         * We populate the options used in the form by the meta data received from retsd daily.
         *
         * price range, *city, *neighborhood (location), * type (condo, townhome, residential),
         * *amenities (int/ext), *status (active, pending, sold), area.
         */
        $adv_search_types = get_option( 'sr_adv_search_meta_types', array() );
        if( empty( $adv_search_types ) ) {
            $adv_search_types = array("Residential", "Condominium", "Rental" );
        }
        foreach( (array)$adv_search_types as $key=>$type) {
            if( $type == $adv_type) {
                $type_options .= "<option value='$type' selected />$type</option>";
            } else {
                $type_options .= "<option value='$type' />$type</option>";
            }
        }

        $adv_search_cities = get_option( 'sr_adv_search_meta_city', array() );
        foreach( (array)$adv_search_cities as $key=>$city ) {
            $checked = in_array($city, $adv_cities) ? 'selected="selected"' : '';
            $city_options .= "<option value='$city' $checked>$city</option>";
        }

        $adv_search_status = get_option( 'sr_adv_search_meta_status', array() );
        foreach( (array)$adv_search_status as $key=>$status) {
            if( $status == $adv_status ) {
                $status_options .= "<option value='$status' selected />$status</option>";
            } else {
                $status_options .= "<option value='$status' />$status</option>";
            }
        }

        $adv_search_neighborhoods= get_option( 'sr_adv_search_meta_neighborhoods', array() );
        foreach( (array)$adv_search_neighborhoods as $key=>$neighborhood) {
            $checked = in_array($neighborhood, $adv_neighborhoods) ? 'selected="selected"' : '';
            $location_options .= "<option value='$neighborhood' $checked>$neighborhood</option>";
        }


        $adv_search_features = get_option( 'sr_adv_search_meta_features', array() );
        foreach( (array)$adv_search_features as $key=>$feature) {
            $checked = in_array($feature, $adv_features) ? 'checked="checked"' : '';
            $features_options .= "<li class='sr-adv-search-option'>"
                 ."<label><input name='sr_features[]' type='checkbox' value='$feature' $checked />$feature</label></li>";
        }

        // currently unused
        // $adv_search_counties = get_option( 'sr_adv_search_meta_county' );
        // foreach( $adv_search_counties as $key=>$county) {
        //     $county_options .= "<option value='$county' />$county</option>";
        // }

        // currently unused
        // $adv_search_amenities = get_option( 'sr_adv_search_option_amenities' );
        // foreach( $adv_search_amenities as $key=>$amenity) {
        //     $amenity_options .= "<li class='sr-adv-search-option'>"
        //         ."<label><input name='sr_features[]' type='checkbox' value='$amenity' />$amenity</label></li>";
        // }

        if( array_key_exists('advanced', $atts) && $atts['advanced'] == 'true' || $atts['advanced'] == 'True' ) {
            ?>

            <div class="sr-adv-search-wrap">
              <form method="get" class="sr-search" action="<?php echo $home_url; ?>">
                <input type="hidden" name="sr-listings" value="sr-search">
                <input type="hidden" name="advanced" value="true">
                <h2>Advanced Listings Search</h2>
                <div class="sr-adv-search-minmax sr-adv-search-part">

                  <div class="sr-adv-search-col1">
                    <!-- Keyword / Property Type -->
                    <div class="sr-minmax-filters">
                      <div class="sr-search-field" id="sr-search-keywords">
                        <input name="sr_keywords"
                               type="text"
                               placeholder="Keywords, MLS Number, or Market Area"
                               value="<?php echo $keywords ?>" />
                      </div>

                      <div class="sr-search-field" id="sr-search-ptype">
                        <select name="sr_ptype">
                          <option value="">Property Type</option>
                          <?php echo $type_options; ?>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-col2 sr-adv-search-price">
                      <label><strong>Price Range</strong></label>
                      <input type="number" name="sr_minprice" placeholder="10000" value="<?php echo $minprice; ?>"/>
                      <input type="number" name="sr_maxprice" placeholder="1000000" value="<?php echo $maxprice; ?>"/>
                    </div>

                    <div class="sr-adv-search-col4">
                      <label for="sr-adv-minprice"><strong>Bedrooms</strong></label>
                      <select name="sr_minbeds" id="sr-adv-minbeds">
                        <option value="<?php echo $minbeds; ?>"><?php echo $minbeds; ?>+</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                        <option value="5">5+</option>
                        <option value="6">6+</option>
                        <option value="7">7+</option>
                        <option value="8">8+</option>
                      </select>
                    </div>
                    <div class="sr-adv-search-col4">
                      <label><strong>Bathrooms</strong></label>
                      <select name="sr_minbaths" id="sr-adv-minbaths">
                        <option value="<?php echo $minbaths; ?>"><?php echo $minbaths; ?>+</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                        <option value="5">5+</option>
                        <option value="6">6+</option>
                        <option value="7">7+</option>
                        <option value="8">8+</option>
                      </select>
                    </div>
                  </div>

                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-col2">
                      <label><strong>Status</strong></label>
                      <select name="status" id="sr-adv-search-status">
                        <option value="">All</option>
                        <?php echo $status_options; ?>
                      </select>
                    </div>
                    <div class="sr-adv-search-col4">
                      <label for="sr-adv-lotsize"><strong>Lot Size</strong></label>
                      <input type="number" name="sr_lotsize" placeholder="3500" value="<?php echo $lotsize; ?>"/>
                    </div>
                    <div class="sr-adv-search-col4">
                      <label><strong>Area (SqFt)</strong></label>
                      <input type="number" name="sr_area" value="<?php echo $area; ?>" placeholder="1500" />
                    </div>
                  </div>


                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-col2">
                      <label><strong>Cities</strong></label>
                      <select name='sr_cities[]' multiple>
                        <?php echo $city_options ?>
                      </select>
                    </div>

                    <div class="sr-adv-search-col2">
                      <label><strong>Locations</strong></label>
                      <select name="sr_neighborhoods[]" multiple>
                        <?php echo $location_options ?>
                      </select>
                    </div>
                  </div>

                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-amenities-wrapper">
                      <label><strong>Features</strong></label>
                      <div class="sr-adv-search-amenities-wrapper-inner">
                        <?php echo $features_options; ?>
                      </div>
                    </div>
                  </div>

                </div>

                <div>
                    <button class="btn button submit btn-submit" style="display:inline-block;">Search</button>
                    <div class="sr-sort-wrapper" style="display:inline-block;float:right;margin-top:10px;">
                        <label for="sr_sort">Sort by: </label>
                        <select name="sr_sort">
                            <option value="-listprice" <?php echo $sort_price_hl ?>> Price - High to Low</option>
                            <option value="listprice"  <?php echo $sort_price_lh ?>> Price - Low to High</option>
                            <option value="-listdate"  <?php echo $sort_date_hl ?> > List Date - New to Old</option>
                            <option value="listdate"   <?php echo $sort_date_lh ?> > List date - Old to New</option>
                        </select>
                    </div>
                </div>
              </form>
            </div>
            <br>

            <?php
            return ob_get_clean();
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
                  <?php echo $type_options; ?>
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

            <div>
                <input class="submit button btn" type="submit" value="Search Properties">

                <div class="sr-sort-wrapper" style="display:inline-block;float:right;margin:10px 25px 0px 0px;">
                    <label for="sr_sort">Sort by: </label>
                    <select class="select" name="sr_sort">
                        <option value="-listprice" <?php echo $sort_price_hl ?>> Price - High to Low</option>
                        <option value="listprice"  <?php echo $sort_price_lh ?>> Price - Low to High</option>
                        <option value="-listdate"  <?php echo $sort_date_hl ?> > List Date - New to Old</option>
                        <option value="listdate"   <?php echo $sort_date_lh ?> > List date - Old to New</option>
                    </select>
                </div>
            </div>

          </form>
        </div>
        <?php

        return ob_get_clean();
    }
}
