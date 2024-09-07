<?php

/*
 *
 * simply-rets-api-helper.php - Copyright (C) 2014-2024 SimplyRETS
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
*/

/* Code starts here */

add_action('init', array('SrShortcodes', 'sr_residential_btn') );


class SrShortcodes {


    /**
     * Short code kitchen sink button registration
     */
    public static function sr_residential_btn() {
        if ( current_user_can('edit_posts') && current_user_can('edit_pages') ) {
            add_filter('mce_external_plugins', array('SrShortcodes', 'sr_res_add_plugin') );
            add_filter('mce_buttons', array('SrShortcodes', 'sr_register_res_button') );
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
     * [sr_map_search] - we return HTML with a special element that
     * the client attaches to to render a searchable map. This is
     * different from the other short-codes in that mostly everything
     * after this point is handled by the client.
     */
    public static function sr_int_map_search($atts) {
        if(!is_array($atts)) $atts = array();

        /** Private Parameters (shortcode attributes) */
        $vendor   = isset($atts['vendor'])  ? $atts['vendor']  : '';
        $brokers  = isset($atts['brokers']) ? $atts['brokers'] : '';
        $agent    = isset($atts['agent'])   ? $atts['agent']   : '';
        $limit    = isset($atts['limit'])   ? $atts['limit'] : '25';
        $idx      = isset($atts['idx'])     ? $atts['idx'] : '';
        $type_att = isset($atts['type'])    ? $atts['type'] : '';

        $content     = "";
        $search_form = "";
        $gmaps_key   = get_option('sr_google_api_key', '');
        $idx_img     = get_option('sr_thumbnail_idx_image');
        $office_on_thumbnails = get_option('sr_office_on_thumbnails', false);
        $agent_on_thumbnails = get_option('sr_agent_on_thumbnails', false);
        $force_image_https = get_option('sr_listing_force_image_https', false);

        $markup_settings = array(
            "list_view" => false,
            "search_form" => false,
            "grid_view" => false
        );

        // Delete attributes that aren't API parameters
        $api_parameters = array_diff_key($atts, $markup_settings);
        $api_parameters_json = json_encode($api_parameters);

        // Delete attributes that are API parameters
        $markup_settings = array_diff_key($atts, $api_parameters);
        $markup_settings_json = json_encode($markup_settings);

        $map_markup  = "<div id='sr-map-search'
                             data-api-key='{$gmaps_key}'
                             data-idx-img='{$idx_img}'
                             data-office-on-thumbnails='{$office_on_thumbnails}'
                             data-agent-on-thumbnails='{$agent_on_thumbnails}'
                             data-force-image-https='{$force_image_https}'
                             data-limit='{$limit}'
                             data-default-settings='{$markup_settings_json}'
                             data-default-parameters='{$api_parameters_json}'
                             data-vendor='{$vendor}'></div>";

        $list_markup = isset($atts["list_view"]) || isset($atts["grid_view"])
                     ? "<div class=\"sr-map-search-list-view\"></div>"
                     : "";

        $MLS_text = SrUtils::mkMLSText();

        if(!empty($atts['search_form'])) {

            $single_vendor = SrUtils::isSingleVendor();
            $allVendors    = get_option('sr_adv_search_meta_vendors', array());
            $vendor        = (empty($vendor) && $single_vendor == true && !empty($allVendors[0]))
                           ? $allVendors[0]
                           : $vendor;
            $prop_types    = get_option("sr_adv_search_meta_types_$vendor"
                                        , array("Residential", "Condominium", "Rental"));

            // Split types like CommercialLease into two words
            foreach($prop_types as $key=>$t) {
                $prop_types[$key] = implode(" ", preg_split('/(?<=\\w)(?=[A-Z])/', $t));
            }

            $type_options = "";
            foreach($prop_types as $key=>$type) {
                if( $type == $type_att) {
                    $type_options .= "<option value='$type' selected>$type</option>";
                } else {
                    $type_options .= "<option value='$type'>$type</option>";
                }
            }

            $search_form = <<<HTML
                <div class="sr-int-map-search-wrapper">
                  <div id="sr-search-wrapper">
                    <h3>Search Listings</h3>
                    <form method="get" class="sr-search sr-map-search-form">
                      <input type="hidden" name="sr-listings" value="sr-search">

                      <div class="sr-minmax-filters">
                        <div class="sr-search-field" id="sr-search-keywords">
                          <input name="sr_keywords"
                                 type="text"
                                 placeholder="Subdivision, Zipcode, $MLS_text Area, $MLS_text Number, or Market Area"
                          />
                        </div>

                        <div class="sr-search-field" id="sr-search-ptype">
                          <select name="sr_ptype">
                            <option value="">Property Type</option>
                            $type_options;
                          </select>
                        </div>
                      </div>

                      <div class="sr-minmax-filters">
                        <div class="sr-search-field" id="sr-search-minprice">
                          <input name="sr_minprice" step="1000" min="0" type="number" placeholder="Min Price.." />
                        </div>
                        <div class="sr-search-field" id="sr-search-maxprice">
                          <input name="sr_maxprice" step="1000" min="0" type="number" placeholder="Max Price.." />
                        </div>

                        <div class="sr-search-field" id="sr-search-minbeds">
                          <input name="sr_minbeds" min="0" type="number" placeholder="Min Beds.." />
                        </div>
                        <div class="sr-search-field" id="sr-search-maxbeds">
                          <input name="sr_maxbeds" min="0" type="number" placeholder="Max Beds.." />
                        </div>

                        <div class="sr-search-field" id="sr-search-minbaths">
                          <input name="sr_minbaths" min="0" type="number" placeholder="Min Baths.." />
                        </div>
                        <div class="sr-search-field" id="sr-search-maxbaths">
                          <input name="sr_maxbaths" min="0" type="number" placeholder="Max Baths.." />
                        </div>
                      </div>

                      <input type="hidden" name="sr_vendor"  value="$vendor" />
                      <input type="hidden" name="sr_brokers" value="$brokers" />
                      <input type="hidden" name="sr_agent"   value="$agent" />
                      <input type="hidden" name="sr_idx"     value="$idx" />
                      <input type="hidden" name="limit"      value="$limit" />

                      <div>
                          <input class="submit button btn" type="submit" value="Search Properties">

                          <div class="sr-sort-wrapper">
                              <label for="sr_sort">Sort by: </label>
                              <select class="select" name="sr_sort">
                                  <option value="">Sort Options</option>
                                  <option value="-modified"> Recently modified</option>
                                  <option value="-listprice"> Price - High to Low</option>
                                  <option value="listprice"> Price - Low to High</option>
                                  <option value="-listdate"> List Date - New to Old</option>
                                  <option value="listdate"> List date - Old to New</option>
                              </select>
                          </div>
                      </div>
                      <p style="margin-bottom:5px">
                        <span><small><i>
                          To make a search, set your parameters
                          above and/or draw a section on the map.
                        </i></small></span>
                      </p>
                    </form>
                  </div>
                </div>
HTML;

        }

        $content .= $search_form;
        $content .= $map_markup;
        $content .= $list_markup;

        return $content;

    }

    /**
     * WordPress downcases all attribute names. This function will
     * take a downcased parameter and convert it to the SimplyRETS
     * parameter name.
     */
    public static function attributeNameToParameter($name) {
        $fixes = array(
            "exteriorfeatures" => "exteriorFeatures",
            "lotdescription" => "lotDescription",
            "postalcodes" => "postalCodes",
            "mingaragespaces" => "minGarageSpaces",
            "maxgaragespaces" => "maxGarageSpaces",
            "salesagent" => "salesAgent",
            "subtypetext" => "subTypeText",
            "speciallistingconditions" => "specialListingConditions",
            "areaminor" => "areaMinor"
        );

        return array_key_exists($name, $fixes) ? $fixes[$name] : $name;
    }

    /**
     * Take an array of short-code attributes and parse them. Returns:
     *   - params: an array of API search parameters
     *   - settings: a key/value of settings (non-search attributes)
     */
    public static function parseShortcodeAttributes($atts, $setting_atts = array()) {
        $attributes = array("params" => array(), "settings" => $setting_atts);

        if (!$atts) {
            return $attributes;
        }

        foreach ($atts as $param=>$value_) {
            // Ensure "&" is not HTML encoded
            // https://stackoverflow.com/a/20078112/3464723
            $value = str_replace("&amp;", "&", $value_);
            $name = SrShortcodes::attributeNameToParameter($param);

            // Parse settings, don't add them to the API query
            if (array_key_exists($param, $setting_atts)) {
                $attributes["settings"][$param] = $value;
            }

            $values = explode(";", $value);
            foreach($values as $idx=>$val) {
                $values[$idx] = trim($val);
            }

            $attributes["params"][$name] = count($values) > 1 ? $values : $value;
        }

        return $attributes;
    }

    public static function sr_openhouses_shortcode($atts = array()) {
        $data = SrShortcodes::parseShortcodeAttributes($atts);

        return SimplyRetsApiHelper::retrieveOpenHousesResults(
            $data["params"],
            $data["settings"]
        );
    }


    /**
     * [sr_residential] - Residential Listings Shortcode
     *
     * Show all residential listings with the ability to filter by mlsid
     * to show a single listing.
     * ie, [sr_residential mlsid="12345"]
     */
    public static function sr_residential_shortcode($atts = array ()) {
        $setting_atts = array(
            "map_position" => get_option('sr_search_map_position', 'map_above'),
            "grid_view" => false,
            "show_map" => true,
            "vendor" => "",
            "limit" => 20
        );

        $data = SrShortcodes::parseShortcodeAttributes($atts, $setting_atts);

        // Use /properties/:id if `mlsid` parameter is used
        if(!empty($atts['mlsid'])) {
            $qs = "/{$atts['mlsid']}"
                . !empty($atts['vendor']) ? "&vendor={$atts['vendor']}" : "";

            return SimplyRetsApiHelper::retrieveRetsListings($qs, $data["settings"]);
        }

        return SimplyRetsApiHelper::retrieveRetsListings(
            $data["params"],
            $data["settings"]
        );
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
        $singleVendor = SrUtils::isSingleVendor();
        $MLS_text = SrUtils::mkMLSText();

        if( !is_array($atts) ) {
            $atts = array();
        }

        $availableVendors = get_option('sr_adv_search_meta_vendors', array());

        /** Configuration Parameters (shortcode attributes) */
        $vendor  = isset($atts['vendor'])  ? $atts['vendor']  : '';
        $brokers = isset($atts['brokers']) ? $atts['brokers'] : '';
        $agent   = isset($atts['agent'])   ? $atts['agent']   : '';
        $water   = isset($atts['water'])   ? $atts['water']   : '';
        $idx     = isset($atts['idx'])   ? $atts['idx']       : '';
        $limit   = isset($atts['limit'])   ? $atts['limit']   : '';
        $config_type = isset($atts['type']) ? $atts['type']   : '';
        $subtype = isset($atts['subtype']) ? $atts['subtype'] : '';
        $subTypeText = isset($atts['subtypetext']) ? $atts['subtypetext'] : '';
        $counties = isset($atts['counties']) ? $atts['counties'] : '';
        $postalCodes = isset($atts['postalcodes']) ? $atts['postalcodes'] : '';
        $neighborhoods = isset($atts['neighborhoods']) ? $atts['neighborhoods'] : '';
        $cities = isset($atts['cities']) ? $atts['cities'] : '';
        $state = isset($atts['state']) ? $atts['state'] : '';
        $specialListingConditions = isset($atts['speciallistingconditions']) ? $atts['speciallistingconditions'] : '';
        $areaMinor = isset($atts['areaminor']) ? $atts['areaminor'] : '';
        $ownership = isset($atts['ownership']) ? $atts['ownership'] : '';
        $salesAgent = isset($atts['salesagent']) ? $atts['salesagent'] : '';

        if($config_type === '') {
            $config_type = isset($_GET['sr_ptype']) ? $_GET['sr_ptype'] : '';
        }
        if(empty($vendor) && $singleVendor === true && !empty($availableVendors)) {
            $vendor = $availableVendors[0];
        }

        /** Settings */
        $grid_view = isset($atts["grid_view"]) ? $atts["grid_view"] : FALSE;
        $show_map = isset($atts["show_map"]) ? $atts["show_map"] : "true";

        /** User Facing Parameters */
        $minbeds    = array_key_exists('minbeds',  $atts) ? $atts['minbeds']  : '';
        $maxbeds    = array_key_exists('maxbeds',  $atts) ? $atts['maxbeds']  : '';
        $minbaths   = array_key_exists('minbaths', $atts) ? $atts['minbaths'] : '';
        $maxbaths   = array_key_exists('maxbaths', $atts) ? $atts['maxbaths'] : '';
        $minprice   = array_key_exists('minprice', $atts) ? $atts['minprice'] : '';
        $maxprice   = array_key_exists('maxprice', $atts) ? $atts['maxprice'] : '';
        $keywords   = array_key_exists('q',        $atts) ? $atts['q']        : '';
        $sort       = array_key_exists('sort',     $atts) ? $atts['sort']     : '';
        /** Advanced Search Parameters */
        $adv_status = array_key_exists('status',   $atts) ? $atts['status']   : '';
        $lotsize    = array_key_exists('lotsize',  $atts) ? $atts['lotsize']  : '';
        $area       = array_key_exists('area',     $atts) ? $atts['area']     : '';
        $adv_features      = isset($_GET['sr_features']) ? $_GET['sr_features'] : array();
        $adv_neighborhoods = isset($_GET['sr_neighborhoods']) ? $_GET['sr_neighborhoods']     : array();

        /*
         * Get the initial values for `cities`. If a query parameter
           is set, use-that, otherwise check for a 'cities' attribute
           on the [sr_search_form] short-code
         */
        $adv_cities = isset($_GET['sr_cities']) ? $_GET['sr_cities'] : array();
        if (empty($adv_cities) && array_key_exists('cities', $atts)) {
            $adv_cities = explode(";", $atts['cities']);
        }

        $sort_price_mod = ($sort == "-modified") ? "selected" : '';
        $sort_price_hl = ($sort == "-listprice") ? "selected" : '';
        $sort_price_lh = ($sort == "listprice")  ? "selected" : '';
        $sort_date_hl  = ($sort == "-listdate")  ? "selected" : '';
        $sort_date_lh  = ($sort == "listdate")   ? "selected" : '';

        /**
         * Advanced Search Form.
         * Used by [sr_search_form advanced='true']
         *
         * We populate the options used in the form by the meta data received from retsd daily.
         *
         * price range, *city, *neighborhood (location), * type (condo, townhome, residential),
         * *amenities (int/ext), *status (active, pending, sold), area.
         */
        $type_options             = '';
        $status_options           = "";
        $available_property_types = get_option("sr_adv_search_meta_types_$vendor", array());
        $default_type_option      = '<option value="">Property Type</option>';

        if( empty( $available_property_types ) ) {
            $available_property_types = array("Residential", "Condominium", "Rental" );
        }

        // Split types like CommercialLease into two words
        foreach($available_property_types as $key=>$t) {
            $available_property_types[$key] = implode(
                " ",
                preg_split('/(?<=\\w)(?=[A-Z])/', $t)
            );
        }

        if((is_array($config_type) == TRUE) && isset($_GET['sr_ptype'])) {
            $type_string = join(';', $config_type);
            $default_type_option = "<option value='$type_string' selected>Property Type</option>";
            foreach($available_property_types as $key=>$value) {
                $type_options .= "<option value='$value' />$value</option>";
            }
        } elseif(strpos($config_type, ";") !== FALSE) {
            $default_type_option = "<option value='$config_type' selected>Property Type</option>";
            foreach($available_property_types as $key=>$value) {
                $type_options .= "<option value='$value' />$value</option>";
            }
        } else {
            foreach($available_property_types as $key=>$value) {
                if( $value == $config_type ) {
                    $type_options .= "<option value='$value' selected />$value</option>";
                } else {
                    $type_options .= "<option value='$value' />$value</option>";
                }
            }
        }

        $city_options = "";
        $adv_search_cities = get_option("sr_adv_search_meta_city_$vendor", array());
        sort($adv_search_cities);
        foreach( (array)$adv_search_cities as $key=>$city ) {
            $checked = in_array($city, (array)$adv_cities) ? 'selected="selected"' : '';
            $city_options .= "<option value='$city' $checked>$city</option>";
        }

        $adv_search_status = get_option("sr_adv_search_meta_status_$vendor", array());
        foreach( (array)$adv_search_status as $key=>$status) {
            if( $status == $adv_status ) {
                $status_options .= "<option value='$status' selected />$status</option>";
            } else {
                $status_options .= "<option value='$status' />$status</option>";
            }
        }

        $location_options = "";
        $adv_search_neighborhoods= get_option("sr_adv_search_meta_neighborhoods_$vendor", array());
        sort( $adv_search_neighborhoods );
        foreach( (array)$adv_search_neighborhoods as $key=>$neighborhood) {
            $checked = in_array($neighborhood, (array)$adv_neighborhoods) ? 'selected="selected"' : '';
            $location_options .= "<option value='$neighborhood' $checked>$neighborhood</option>";
        }


        $features_options = "";
        $adv_search_features = get_option("sr_adv_search_meta_features_$vendor", array());
        sort( $adv_search_features );
        foreach( (array)$adv_search_features as $key=>$feature) {
            $checked = in_array($feature, (array)$adv_features) ? 'checked="checked"' : '';
            $features_options .= "<li class='sr-adv-search-option'>"
                 ."<label><input name='sr_features[]' type='checkbox' value='$feature' $checked />$feature</label></li>";
        }

        if(array_key_exists('advanced', $atts) && ($atts['advanced'] == 'true' || $atts['advanced'] == 'True')) {
            ?>

            <div class="sr-adv-search-wrap">
              <form method="get" class="sr-search" action="<?php echo $home_url; ?>">
                <input type="hidden" name="sr-listings" value="sr-search">
                <input type="hidden" name="advanced" value="true">
                <h3>Advanced Listings Search</h3>
                <div class="sr-adv-search-minmax sr-adv-search-part">

                  <div class="sr-adv-search-col1">
                    <!-- Keyword / Property Type -->
                    <div class="sr-minmax-filters">
                      <div class="sr-search-field" id="sr-search-keywords">
                        <input name="sr_keywords"
                               type="text"
                               placeholder="Subdivision, Zipcode, <?php echo $MLS_text ?> Area, <?php echo $MLS_text ?> Number, or Market Area"
                               value="<?php echo $keywords ?>" />
                      </div>

                      <div class="sr-search-field" id="sr-search-ptype">
                        <select name="sr_ptype">
                          <?php echo $default_type_option; ?>
                          <?php echo $type_options; ?>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-col2 sr-adv-search-price">
                      <label><strong>Price Range</strong></label>
                      <input step="1000" min="0" type="number" name="sr_minprice" placeholder="10000" value="<?php echo $minprice; ?>"/>
                      <input step="1000" min="0" type="number" name="sr_maxprice" placeholder="1000000" value="<?php echo $maxprice; ?>"/>
                    </div>

                    <div class="sr-adv-search-col4" id="sr-adv-minbeds">
                      <label for="sr_minbeds" id="sr-adv-minbeds-label">
                          <strong>Bedrooms</strong>
                      </label>
                      <select name="sr_minbeds" id="sr-adv-minbeds-select">
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

                    <div class="sr-adv-search-col4" id="sr-adv-minbaths">
                      <label for="sr_minbaths" id="sr-adv-minbaths-label">
                          <strong>Bathrooms</strong>
                      </label>
                      <select name="sr_minbaths" id="sr-adv-minbaths-select">
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
                    <div class="sr-adv-search-col2" id="sr-adv-status">
                      <label for="status" id="sr-adv-status-label">
                          <strong>Status</strong>
                      </label>
                      <select name="status" id="sr-adv-status-select">
                        <option value="">All</option>
                        <?php echo $status_options; ?>
                      </select>
                    </div>
                    <div class="sr-adv-search-col4" id="sr-adv-lotsize">
                      <label for="sr_lotsize"><strong>Lot Size</strong></label>
                      <input type="number" name="sr_lotsize" placeholder="3500" value="<?php echo $lotsize; ?>"/>
                    </div>
                    <div class="sr-adv-search-col4" id="sr-adv-area">
                      <label for="sr_area"><strong>Area (SqFt)</strong></label>
                      <input type="number" name="sr_area" value="<?php echo $area; ?>" placeholder="1500" />
                    </div>
                  </div>


                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-col2" id="sr-adv-cities">
                      <label><strong>Cities</strong></label>
                      <select name='sr_cities[]' multiple>
                        <?php echo $city_options ?>
                      </select>
                    </div>

                    <div class="sr-adv-search-col2" id="sr-adv-neighborhoods">
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

                <input type="hidden" name="water"   value="<?php echo $water; ?>"  />
                <input type="hidden" name="sr_idx"   value="<?php echo $idx; ?>"  />
                <input type="hidden" name="sr_vendor"  value="<?php echo $vendor; ?>"  />
                <input type="hidden" name="sr_brokers" value="<?php echo $brokers; ?>" />
                <input type="hidden" name="sr_agent"   value="<?php echo $agent; ?>" />
                <input type="hidden" name="sr_subtype" value="<?php echo $subtype; ?>" />
                <input type="hidden" name="sr_subTypeText" value="<?php echo $subTypeText; ?>" />
                <input type="hidden" name="sr_counties" value="<?php echo $counties; ?>" />
                <input type="hidden" name="limit"      value="<?php echo $limit; ?>" />
                <input type="hidden" name="sr_postalCodes" value="<?php echo $postalCodes; ?>" />
                <input
                    type="hidden"
                    name="sr_specialListingConditions"
                    value="<?php echo $specialListingConditions; ?>"
                />
                <input type="hidden" name="sr_areaMinor" value="<?php echo $areaMinor; ?>" />
                <input type="hidden" name="sr_ownership" value="<?php echo $ownership; ?>" />
                <input type="hidden" name="sr_salesagent" value="<?php echo $salesAgent; ?>" />
                <input type="hidden" name="grid_view" value="<?php echo $grid_view; ?>" />
                <input type="hidden" name="show_map" value="<?php echo $show_map; ?>" />

                <div>
                    <button class="btn button submit btn-submit" style="display:inline-block;">Search</button>
                    <div class="sr-sort-wrapper">
                        <label for="sr_sort">Sort by: </label>
                        <select name="sr_sort">
                            <option value="-modified" <?php echo $sort_price_mod ?>> Recently modified</option>
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
                       placeholder="Subdivision, Zipcode, <?php echo $MLS_text ?> Area, <?php echo $MLS_text ?> Number, or Market Area"
                       value="<?php echo $keywords ?>" />
              </div>

              <div class="sr-search-field" id="sr-search-ptype">
                <select name="sr_ptype">
                  <?php echo $default_type_option; ?>
                  <?php echo $type_options; ?>
                </select>
              </div>
            </div>

            <div class="sr-minmax-filters">
              <div class="sr-search-field" id="sr-search-minprice">
                <input name="sr_minprice" step="1000" min="0" type="number" value="<?php echo $minprice; ?>" placeholder="Min Price.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxprice">
                <input name="sr_maxprice" step="1000" min="0" type="number" value="<?php echo $maxprice; ?>" placeholder="Max Price.." />
              </div>

              <div class="sr-search-field" id="sr-search-minbeds">
                <input name="sr_minbeds" min="0" type="number" value="<?php echo $minbeds; ?>" placeholder="Min Beds.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxbeds">
                <input name="sr_maxbeds" min="0" type="number" value="<?php echo $maxbeds; ?>" placeholder="Max Beds.." />
              </div>

              <div class="sr-search-field" id="sr-search-minbaths">
                <input name="sr_minbaths" min="0" type="number" value="<?php echo $minbaths; ?>" placeholder="Min Baths.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxbaths">
                <input name="sr_maxbaths" min="0" type="number" value="<?php echo $maxbaths; ?>" placeholder="Max Baths.." />
              </div>
            </div>

            <div>
                <input class="submit button btn" type="submit" value="Search Properties">

                <div class="sr-sort-wrapper">
                    <label for="sr_sort">Sort by: </label>
                    <select class="select" name="sr_sort">
                        <option value="-modified" <?php echo $sort_price_mod ?>> Recently modified</option>
                        <option value="-listprice" <?php echo $sort_price_hl ?>> Price - High to Low</option>
                        <option value="listprice"  <?php echo $sort_price_lh ?>> Price - Low to High</option>
                        <option value="-listdate"  <?php echo $sort_date_hl ?> > List Date - New to Old</option>
                        <option value="listdate"   <?php echo $sort_date_lh ?> > List date - Old to New</option>
                    </select>
                </div>
            </div>

            <input type="hidden" name="water"   value="<?php echo $water; ?>"  />
            <input type="hidden" name="sr_idx"   value="<?php echo $idx; ?>"  />
            <input type="hidden" name="sr_vendor"  value="<?php echo $vendor; ?>"  />
            <input type="hidden" name="sr_brokers" value="<?php echo $brokers; ?>" />
            <input type="hidden" name="sr_agent"   value="<?php echo $agent; ?>" />
            <input type="hidden" name="sr_subtype" value="<?php echo $subtype; ?>" />
            <input type="hidden" name="sr_subTypeText" value="<?php echo $subTypeText; ?>" />
            <input type="hidden" name="sr_counties" value="<?php echo $counties; ?>" />
            <input type="hidden" name="sr_postalCodes" value="<?php echo $postalCodes; ?>" />
            <input type="hidden" name="sr_neighborhoods" value="<?php echo $neighborhoods; ?>" />
            <input type="hidden" name="sr_cities" value="<?php echo $cities; ?>" />
            <input type="hidden" name="sr_state" value="<?php echo $state; ?>" />
            <input type="hidden" name="limit"      value="<?php echo $limit; ?>" />
            <input type="hidden" name="status"     value="<?php echo $adv_status; ?>" />
            <input type="hidden" name="grid_view" value="<?php echo $grid_view; ?>" />
            <input type="hidden" name="show_map" value="<?php echo $show_map; ?>" />
            <input
                type="hidden"
                name="sr_specialListingConditions"
                value="<?php echo $specialListingConditions; ?>"
            />
            <input type="hidden" name="sr_areaMinor" value="<?php echo $areaMinor; ?>" />
            <input type="hidden" name="sr_ownership" value="<?php echo $ownership; ?>" />
            <input type="hidden" name="sr_salesagent" value="<?php echo $salesAgent; ?>" />

          </form>
        </div>
        <?php

        return ob_get_clean();
    }


    /**
     * TODO: sr_listings_slider should support attributes that can
     * take multiple values (eg, postalCodes, counties). #32
     */
    public static function sr_listing_slider_shortcode($atts = array()) {
        ob_start();

        $def_params = array("limit" => "12");
        $def_settings = array("random" => "false");
        $def_atts = array_merge($def_params, is_array($atts) ? $atts : array());

        $data = SrShortcodes::parseShortcodeAttributes($def_atts, $def_settings);

        echo SimplyRetsApiHelper::retrieveListingsSlider(
            $data["params"], $data["settings"]
        );

        return ob_get_clean();
    }

}
