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
        $api_parameters_json = wp_json_encode($api_parameters);

        // Delete attributes that are API parameters
        $markup_settings = array_diff_key($atts, $api_parameters);
        $markup_settings_json = wp_json_encode($markup_settings);

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

            $search_form =
                  '<div class="sr-int-map-search-wrapper">'
                . '  <div id="sr-search-wrapper">'
                . '    <h3>Search Listings</h3>'
                . '    <form method="get" class="sr-search sr-map-search-form">'
                . '      <input type="hidden" name="sr-listings" value="sr-search">'
                . '      <div class="sr-minmax-filters">'
                . '        <div class="sr-search-field" id="sr-search-keywords">'
                . '          <input name="sr_keywords"'
                . '                 type="text"'
                . '                 placeholder="Subdivision, Zipcode, ' . $MLS_text . ' Area, ' . $MLS_text . ' Number, or Market Area"'
                . '          />'
                . '        </div>'
                . '        <div class="sr-search-field" id="sr-search-ptype">'
                . '          <select name="sr_ptype">'
                . '            <option value="">Property Type</option>'
                .              $type_options
                . '          </select>'
                . '        </div>'
                . '      </div>'
                . '      <div class="sr-minmax-filters">'
                . '        <div class="sr-search-field" id="sr-search-minprice">'
                . '          <input name="sr_minprice" step="1000" min="0" type="number" placeholder="Min Price.." />'
                . '        </div>'
                . '        <div class="sr-search-field" id="sr-search-maxprice">'
                . '          <input name="sr_maxprice" step="1000" min="0" type="number" placeholder="Max Price.." />'
                . '        </div>'
                . '        <div class="sr-search-field" id="sr-search-minbeds">'
                . '          <input name="sr_minbeds" min="0" type="number" placeholder="Min Beds.." />'
                . '        </div>'
                . '        <div class="sr-search-field" id="sr-search-maxbeds">'
                . '          <input name="sr_maxbeds" min="0" type="number" placeholder="Max Beds.." />'
                . '        </div>'
                . '        <div class="sr-search-field" id="sr-search-minbaths">'
                . '          <input name="sr_minbaths" min="0" type="number" placeholder="Min Baths.." />'
                . '        </div>'
                . '        <div class="sr-search-field" id="sr-search-maxbaths">'
                . '          <input name="sr_maxbaths" min="0" type="number" placeholder="Max Baths.." />'
                . '        </div>'
                . '      </div>'
                . '      <input type="hidden" name="sr_vendor"  value="' . $vendor . '" />'
                . '      <input type="hidden" name="sr_brokers" value="' . $brokers . '" />'
                . '      <input type="hidden" name="sr_agent"   value="' . $agent . '" />'
                . '      <input type="hidden" name="sr_idx"     value="' . $idx . '" />'
                . '      <input type="hidden" name="limit"      value="' . $limit . '" />'
                . '      <div>'
                . '          <input class="submit button btn" type="submit" value="Search Properties">'
                . '          <div class="sr-sort-wrapper">'
                . '              <label for="sr_sort">Sort by: </label>'
                . '              <select class="select" name="sr_sort">'
                . '                  <option value="">Sort Options</option>'
                . '                  <option value="-modified"> Recently modified</option>'
                . '                  <option value="-listprice"> Price - High to Low</option>'
                . '                  <option value="listprice"> Price - Low to High</option>'
                . '                  <option value="-listdate"> List Date - New to Old</option>'
                . '                  <option value="listdate"> List date - Old to New</option>'
                . '              </select>'
                . '          </div>'
                . '      </div>'
                . '      <p style="margin-bottom:5px">'
                . '        <span><small><i>'
                . '          To make a search, set your parameters'
                . '          above and/or draw a section on the map.'
                . '        </i></small></span>'
                . '      </p>'
                . '    </form>'
                . '  </div>'
                . '</div>';
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
    public static function parseShortcodeAttributes($atts, $setting_atts = array(), $explode_values = TRUE) {
        $attributes = array("params" => array(), "settings" => $setting_atts);

        if (!$atts) {
            return $attributes;
        }

        foreach ($atts as $param=>$value_) {

            // 1. Escape values for use in HTML attributes
            // 2. Ensure "&" is not HTML encoded (https://stackoverflow.com/a/20078112/3464723)
            $value = str_replace("&amp;", "&", esc_attr($value_));
            $name = SrShortcodes::attributeNameToParameter($param);

            // Parse settings, don't add them to the API query
            if (array_key_exists($param, $setting_atts)) {
                $attributes["settings"][$param] = $value;
            }

            // By default, attributes with multiple values separated by
            // a semicolon are split into an array. To byass this, set
            // explode_values is set to false.
            if ($explode_values == TRUE) {
                $values = explode(";", $value);
                foreach($values as $idx=>$val) {
                    $values[$idx] = trim($val);
                }

                $attributes["params"][$name] = count($values) > 1 ? $values : $value;
            } else {
                $attributes["params"][$name] = $value;
            }
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
    public static function sr_search_form_shortcode($atts) {
        ob_start();
        $home_url = get_home_url();
        $singleVendor = SrUtils::isSingleVendor();
        $MLS_text = SrUtils::mkMLSText();
        $availableVendors = get_option('sr_adv_search_meta_vendors', array());

        if( !is_array($atts) ) { $atts = array(); }

        // Properly escape and sanitize all values that are being
        // printed into an HTML attribute. See:
        // https://developer.wordpress.org/apis/security/escaping/
        $escaped_attributes = SrShortcodes::parseShortcodeAttributes(
            $atts,
            array(),
            false
        );

        $attributes = $escaped_attributes["params"];

        /** Configuration Parameters (shortcode attributes) */
        $vendor  = isset($attributes['vendor'])  ? $attributes['vendor']  : '';
        $brokers = isset($attributes['brokers']) ? $attributes['brokers'] : '';
        $agent   = isset($attributes['agent'])   ? $attributes['agent']   : '';
        $water   = isset($attributes['water'])   ? $attributes['water']   : '';
        $idx     = isset($attributes['idx'])   ? $attributes['idx']       : '';
        $limit   = isset($attributes['limit'])   ? $attributes['limit']   : '';
        $subtype = isset($attributes['subtype']) ? $attributes['subtype'] : '';
        $subTypeText = isset($attributes['subtypetext']) ? $attributes['subtypetext'] : '';
        $counties = isset($attributes['counties']) ? $attributes['counties'] : '';
        $postalCodes = isset($attributes['postalCodes']) ? $attributes['postalCodes'] : '';
        $neighborhoods = isset($attributes['neighborhoods']) ? $attributes['neighborhoods'] : '';
        $cities = isset($attributes['cities']) ? $attributes['cities'] : '';
        $state = isset($attributes['state']) ? $attributes['state'] : '';
        $specialListingConditions = isset($attributes['speciallistingconditions']) ? $attributes['speciallistingconditions'] : '';
        $ownership = isset($attributes['ownership']) ? $attributes['ownership'] : '';
        $salesAgent = isset($attributes['salesagent']) ? $attributes['salesagent'] : '';
        $areaMinor = isset($attributes['areaMinor']) ? $attributes['areaMinor'] : '';
        $exteriorFeatures = isset($attributes['exteriorFeatures']) ? $attributes['exteriorFeatures'] : '';
        $lotDescription = isset($attributes['lotDescription']) ? $attributes['lotDescription'] : '';

        $config_type = isset($attributes['type']) ? $attributes['type']   : '';
        if($config_type === '') {
            $config_type = isset($_GET['sr_ptype']) ? $_GET['sr_ptype'] : '';
        }

        if(empty($vendor) && $singleVendor === true && !empty($availableVendors)) {
            $vendor = $availableVendors[0];
        }

        /** Settings */
        $grid_view = isset($attributes["grid_view"]) ? $attributes["grid_view"] : FALSE;
        $show_map = isset($attributes["show_map"]) ? $attributes["show_map"] : "true";

        /** User Facing Parameters */
        $minbeds    = array_key_exists('minbeds',  $attributes) ? $attributes['minbeds']  : '';
        $maxbeds    = array_key_exists('maxbeds',  $attributes) ? $attributes['maxbeds']  : '';
        $minbaths   = array_key_exists('minbaths', $attributes) ? $attributes['minbaths'] : '';
        $maxbaths   = array_key_exists('maxbaths', $attributes) ? $attributes['maxbaths'] : '';
        $minprice   = array_key_exists('minprice', $attributes) ? $attributes['minprice'] : '';
        $maxprice   = array_key_exists('maxprice', $attributes) ? $attributes['maxprice'] : '';
        $keywords   = array_key_exists('q',        $attributes) ? $attributes['q']        : '';
        $sort       = array_key_exists('sort',     $attributes) ? $attributes['sort']     : '';

        /** Advanced Search Parameters */
        $adv_status = array_key_exists('status',   $attributes) ? $attributes['status']   : '';
        $lotsize    = array_key_exists('lotsize',  $attributes) ? $attributes['lotsize']  : '';
        $area       = array_key_exists('area',     $attributes) ? $attributes['area']     : '';
        $adv_features      = isset($_GET['sr_features']) ? $_GET['sr_features'] : array();
        $adv_neighborhoods = isset($_GET['sr_neighborhoods']) ? $_GET['sr_neighborhoods']     : array();

        // Get the initial values for `cities`. If a query parameter
        // is set, use-that, otherwise check for a 'cities' attribute
        // on the [sr_search_form] short-code
        $adv_cities = isset($_GET['sr_cities']) ? $_GET['sr_cities'] : array();
        if (empty($adv_cities) && array_key_exists('cities', $attributes)) {
            $adv_cities = explode(";", $attributes['cities']);
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

        $q_placeholder = "Subdivision, Zipcode, "
                       . $MLS_text . " area, " . $MLS_text . " #, etc";

        if(array_key_exists('advanced', $attributes) && ($attributes['advanced'] == 'true' || $attributes['advanced'] == 'True')) {
            ?>

            <div class="sr-adv-search-wrap">
              <form method="get" class="sr-search" action="<?php echo esc_url($home_url); ?>">
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
                               placeholder="<?php echo esc_attr($q_placeholder); ?>"
                               value="<?php echo esc_attr($keywords); ?>" />
                      </div>

                      <div class="sr-search-field" id="sr-search-ptype">
                        <select name="sr_ptype">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput
                            echo $default_type_option;
                            // phpcs:ignore WordPress.Security.EscapeOutput
                            echo $type_options;
                            ?>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-col2 sr-adv-search-price">
                      <label><strong>Price Range</strong></label>
                      <input step="1000" min="0" type="number" name="sr_minprice" placeholder="10000" value="<?php echo esc_attr($minprice); ?>"/>
                      <input step="1000" min="0" type="number" name="sr_maxprice" placeholder="1000000" value="<?php echo esc_attr($maxprice); ?>"/>
                    </div>

                    <div class="sr-adv-search-col4" id="sr-adv-minbeds">
                      <label for="sr_minbeds" id="sr-adv-minbeds-label">
                          <strong>Bedrooms</strong>
                      </label>
                      <select name="sr_minbeds" id="sr-adv-minbeds-select">
                        <option value="<?php echo esc_attr($minbeds); ?>">
                          <?php echo esc_html($minbeds); ?>+
                        </option>
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
                        <option value="<?php echo esc_attr($minbaths); ?>">
                          <?php echo esc_attr($minbaths); ?>+
                        </option>
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
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $status_options;
                        ?>
                      </select>
                    </div>
                    <div class="sr-adv-search-col4" id="sr-adv-lotsize">
                      <label for="sr_lotsize"><strong>Lot Size</strong></label>
                      <input type="number" name="sr_lotsize" placeholder="3500" value="<?php echo esc_attr($lotsize); ?>"/>
                    </div>
                    <div class="sr-adv-search-col4" id="sr-adv-area">
                      <label for="sr_area"><strong>Area (SqFt)</strong></label>
                      <input type="number" name="sr_area" value="<?php echo esc_attr($area); ?>" placeholder="1500" />
                    </div>
                  </div>


                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-col2" id="sr-adv-cities">
                      <label><strong>Cities</strong></label>
                      <select name='sr_cities[]' multiple>
                      <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $city_options;
                      ?>
                      </select>
                    </div>

                    <div class="sr-adv-search-col2" id="sr-adv-neighborhoods">
                      <label><strong>Locations</strong></label>
                      <select name="sr_neighborhoods[]" multiple>
                      <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $location_options;
                      ?>
                      </select>
                    </div>
                  </div>

                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-amenities-wrapper">
                      <label><strong>Features</strong></label>
                      <div class="sr-adv-search-amenities-wrapper-inner">
                      <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $features_options;
                      ?>
                      </div>
                    </div>
                  </div>

                </div>

                <input type="hidden" name="water" value="<?php echo esc_attr($water); ?>"  />
                <input type="hidden" name="sr_idx" value="<?php echo esc_attr($idx); ?>"  />
                <input type="hidden" name="sr_vendor" value="<?php echo esc_attr($vendor); ?>"  />
                <input type="hidden" name="sr_brokers" value="<?php echo esc_attr($brokers); ?>" />
                <input type="hidden" name="sr_agent" value="<?php echo esc_attr($agent); ?>" />
                <input type="hidden" name="sr_subtype" value="<?php echo esc_attr($subtype); ?>" />
                <input type="hidden" name="sr_subTypeText" value="<?php echo esc_attr($subTypeText); ?>" />
                <input type="hidden" name="sr_counties" value="<?php echo esc_attr($counties); ?>" />
                <input type="hidden" name="limit" value="<?php echo esc_attr($limit); ?>" />
                <input type="hidden" name="sr_postalCodes" value="<?php echo esc_attr($postalCodes); ?>" />
                <input
                    type="hidden"
                    name="sr_specialListingConditions"
                    value="<?php echo esc_attr($specialListingConditions); ?>"
                />
                <input type="hidden" name="sr_areaMinor" value="<?php echo esc_attr($areaMinor); ?>" />
                <input type="hidden" name="sr_ownership" value="<?php echo esc_attr($ownership); ?>" />
                <input type="hidden" name="sr_salesagent" value="<?php echo esc_attr($salesAgent); ?>" />
                <input type="hidden" name="sr_exteriorFeatures" value="<?php echo esc_attr($exteriorFeatures); ?>" />
                <input type="hidden" name="sr_lotDescription" value="<?php echo esc_attr($lotDescription); ?>" />
                <input type="hidden" name="grid_view" value="<?php echo esc_attr($grid_view); ?>" />
                <input type="hidden" name="show_map" value="<?php echo esc_attr($show_map); ?>" />

                <div>
                    <button class="btn button submit btn-submit" style="display:inline-block;">Search</button>
                    <div class="sr-sort-wrapper">
                        <label for="sr_sort">Sort by: </label>
                        <select name="sr_sort">
                            <option value="-modified" <?php  echo esc_attr($sort_price_mod); ?>> Recently modified</option>
                            <option value="-listprice" <?php echo esc_attr($sort_price_hl); ?>> Price - High to Low</option>
                            <option value="listprice" <?php echo esc_attr($sort_price_lh); ?>> Price - Low to High</option>
                            <option value="-listdate" <?php echo esc_attr($sort_date_hl); ?> > List Date - New to Old</option>
                            <option value="listdate" <?php echo esc_attr($sort_date_lh); ?> > List date - Old to New</option>
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
          <form method="get" class="sr-search" action="<?php echo esc_url($home_url); ?>">
            <input type="hidden" name="sr-listings" value="sr-search">

            <div class="sr-minmax-filters">
              <div class="sr-search-field" id="sr-search-keywords">
                <input name="sr_keywords"
                       type="text"
                       placeholder="<?php echo esc_attr($q_placeholder); ?>"
                       value="<?php echo esc_attr($keywords); ?>" />
              </div>

              <div class="sr-search-field" id="sr-search-ptype">
                <select name="sr_ptype">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput
                    echo $default_type_option;
                    // phpcs:ignore WordPress.Security.EscapeOutput
                    echo $type_options;
                    ?>
                </select>
              </div>
            </div>

            <div class="sr-minmax-filters">
              <div class="sr-search-field" id="sr-search-minprice">
                <input name="sr_minprice" step="1000" min="0" type="number" value="<?php echo esc_attr($minprice); ?>" placeholder="Min Price.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxprice">
                <input name="sr_maxprice" step="1000" min="0" type="number" value="<?php echo esc_attr($maxprice); ?>" placeholder="Max Price.." />
              </div>

              <div class="sr-search-field" id="sr-search-minbeds">
                <input name="sr_minbeds" min="0" type="number" value="<?php echo esc_attr($minbeds); ?>" placeholder="Min Beds.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxbeds">
                <input name="sr_maxbeds" min="0" type="number" value="<?php echo esc_attr($maxbeds); ?>" placeholder="Max Beds.." />
              </div>

              <div class="sr-search-field" id="sr-search-minbaths">
                <input name="sr_minbaths" min="0" type="number" value="<?php echo esc_attr($minbaths); ?>" placeholder="Min Baths.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxbaths">
                <input name="sr_maxbaths" min="0" type="number" value="<?php echo esc_attr($maxbaths); ?>" placeholder="Max Baths.." />
              </div>
            </div>

            <div>
                <input class="submit button btn" type="submit" value="Search Properties">

                <div class="sr-sort-wrapper">
                    <label for="sr_sort">Sort by: </label>
                    <select class="select" name="sr_sort">
                        <option value="-modified"  <?php echo esc_attr($sort_price_mod); ?>> Recently modified</option>
                        <option value="-listprice" <?php echo esc_attr($sort_price_hl); ?>> Price - High to Low</option>
                        <option value="listprice"  <?php echo esc_attr($sort_price_lh); ?>> Price - Low to High</option>
                        <option value="-listdate"  <?php echo esc_attr($sort_date_hl); ?> > List Date - New to Old</option>
                        <option value="listdate"   <?php echo esc_attr($sort_date_lh); ?> > List date - Old to New</option>
                    </select>
                </div>
            </div>

            <input type="hidden" name="water" value="<?php echo esc_attr($water); ?>"  />
            <input type="hidden" name="sr_idx" value="<?php echo esc_attr($idx); ?>"  />
            <input type="hidden" name="sr_vendor" value="<?php echo esc_attr($vendor); ?>"  />
            <input type="hidden" name="sr_brokers" value="<?php echo esc_attr($brokers); ?>" />
            <input type="hidden" name="sr_agent" value="<?php echo esc_attr($agent); ?>" />
            <input type="hidden" name="sr_subtype" value="<?php echo esc_attr($subtype); ?>" />
            <input type="hidden" name="sr_subTypeText" value="<?php echo esc_attr($subTypeText); ?>" />
            <input type="hidden" name="sr_counties" value="<?php echo esc_attr($counties); ?>" />
            <input type="hidden" name="sr_postalCodes" value="<?php echo esc_attr($postalCodes); ?>" />
            <input type="hidden" name="sr_neighborhoods" value="<?php echo esc_attr($neighborhoods); ?>" />
            <input type="hidden" name="sr_cities" value="<?php echo esc_attr($cities); ?>" />
            <input type="hidden" name="sr_state" value="<?php echo esc_attr($state); ?>" />
            <input type="hidden" name="limit" value="<?php echo esc_attr($limit); ?>" />
            <input type="hidden" name="status" value="<?php echo esc_attr($adv_status); ?>" />
            <input type="hidden" name="grid_view" value="<?php echo esc_attr($grid_view); ?>" />
            <input type="hidden" name="show_map" value="<?php echo esc_attr($show_map); ?>" />
            <input
                type="hidden"
                name="sr_specialListingConditions"
                value="<?php echo esc_attr($specialListingConditions); ?>"
            />
            <input type="hidden" name="sr_areaMinor" value="<?php echo esc_attr($areaMinor); ?>" />
            <input type="hidden" name="sr_ownership" value="<?php echo esc_attr($ownership); ?>" />
            <input type="hidden" name="sr_salesagent" value="<?php echo esc_attr($salesAgent); ?>" />
            <input type="hidden" name="sr_exteriorFeatures" value="<?php echo esc_attr($exteriorFeatures); ?>" />
            <input type="hidden" name="sr_lotDescription" value="<?php echo esc_attr($lotDescription); ?>" />

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
        $def_params = array("limit" => "12");
        $def_settings = array("vendor" => "", "random" => "false");
        $def_atts = array_merge($def_params, is_array($atts) ? $atts : array());

        $data = SrShortcodes::parseShortcodeAttributes($def_atts, $def_settings);

        return SimplyRetsApiHelper::retrieveListingsSlider(
            $data["params"],
            $data["settings"]
        );
    }
}
