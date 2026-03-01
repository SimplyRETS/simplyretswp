<?php
/*
 * simply-rets-query-parser.php - Copyright (C) 2014-2024 SimplyRETS
 * This file provides the query parsing logic for SimplyRETS pages.
 */

class SimplyRetsQueryParser {

    /**
     * When loading a sr-listings page, this function will parse a GET
     * (search) parameter, and return the original values, formatting
     * query string, and an array of the values.
     */
    public static function parseGetParameter($name, $key, $params) {
        $param = isset($_GET[$name]) ? $_GET[$name] : "";
        $param_arr = is_array($param) ? $param : explode(";", $param);
        $param_att = is_array($param) ? implode(";", $param) : $param;
        $param_str = "";

        if (is_array($param_arr) && !empty($param_arr)) {
            foreach ((array)$param_arr as $idx => $val) {
                if (!empty($val)) {
                    $final = trim($val);
                    $param_str .= "&{$key}={$final}";
                }
            }
        }

        return array(
            "param" => $param,
            "query" => $param_str,
            "att" => $param_att
        );
    }

    public static function parseSearchParams() {
        $minbeds  = get_query_var('sr_minbeds',  '');
        $maxbeds  = get_query_var('sr_maxbeds',  '');
        $minyear  = get_query_var('sr_minyear',  '');
        $maxyear  = get_query_var('sr_maxyear',  '');
        $minbaths = get_query_var('sr_minbaths', '');
        $maxbaths = get_query_var('sr_maxbaths', '');
        $minprice = get_query_var('sr_minprice', '');
        $maxprice = get_query_var('sr_maxprice', '');
        $water    = get_query_var('water', '');
        /** Pagination */
        $limit    = get_query_var('limit', '');
        $offset   = get_query_var('offset', '');
        /** Advanced Search */
        $advanced  = get_query_var('advanced', '');
        $status    = get_query_var('status', '');
        $lotsize   = get_query_var('sr_lotsize', '');
        $area      = get_query_var('sr_area', '');
        $sort      = get_query_var('sr_sort', '');
        $idx       = get_query_var('sr_idx', '');
        /** multi mls */
        $vendor    = get_query_var('sr_vendor', '');
        /** Settings */
        $grid_view = get_query_var("grid_view", false);
        $show_map = get_query_var("show_map", true);
        $map_position = get_query_var(
            'sr_map_position',
            get_option('sr_search_map_position')
        );

        /**
         * Format the 'type' parameter.
         * Note that the 'type' might come in as an Array or a
         * String.  For strings, we split on ";" to support
         * multiple property types only if the string is not
         * empty.  Arrays are concated into multiple type=
         * parameters.
         */
        $p_types = isset($_GET['sr_ptype']) ? $_GET['sr_ptype'] : '';
        $ptypes_string = '';
        if (!is_array($p_types) && !empty($p_types)) {
            if (strpos($p_types, ";") !== FALSE) {
                $p_types = explode(';', $p_types);
            } else {
                $ptypes_string = "&type=$p_types";
            }
        }
        if (is_array($p_types) && !empty($p_types)) {
            foreach ((array)$p_types as $key => $ptype) {
                $final = trim($ptype);
                $ptypes_string .= "&type=$final";
            }
        }


        /**
         * Format the 'status' parameter.
         * Note that the 'status' might come in as an Array or a
         * String.  For strings, we split on ";" to support
         * multiple statuses only if the string is not empty.
         * Arrays are concated into multiple status= parameters.
         *
         * NOTE: it is important to not send an empty status
         * parameter, for example "status=" to the API, as it will
         * interpret it as Active, whereas _no_ status parameter
         * is Active and Pending.
         */
        $statuses = isset($_GET['sr_status']) ? $_GET['sr_status'] : $status;
        $statuses_string = '';
        $statuses_attribute = '';

        if (!is_array($statuses) && !empty($statuses)) {
            if (strpos($statuses, ";") !== FALSE) {
                $statuses = explode(';', $statuses);
            } else {
                $statuses_string = "&status=$statuses";
            }

            $statuses_attribute = $statuses;
        }

        if (is_array($statuses) && !empty($statuses)) {
            foreach ((array)$statuses as $key => $stat) {
                $final = trim($stat);
                $statuses_string .= "&status=$final";
            }

            $statuses_attribute = implode(";", $statuses);
        }

        /**
         * The loops below check if the short-code has multiple
         * values for any query parameter. Eg, multiple cities.
         * Since they support multiple, we do the following for
         * each:
         *
         *
         * - Split string on ';' delimeter (which returns a single
         *   item array if there is none)
         *
         * - Make each array item into a query (eg, &status=Closed)
         *
         * - Concat them together (eg,&status=Active&status=Closed)
         */

        $features = isset($_GET['sr_features']) ? $_GET['sr_features'] : '';
        $features_string = "";
        if (!empty($features)) {
            foreach ((array)$features as $key => $feature) {
                $features_string .= "&features=$feature";
            }
        }

        $amenities = isset($_GET['sr_amenities']) ? $_GET['sr_amenities'] : '';
        $amenities_string = "";
        if (!empty($amenities)) {
            foreach ((array)$amenities as $key => $amenity) {
                $amenities_string .= "&amenities=$amenity";
            }
        }

        /** Parse multiple `style` from short-code attributes */
        $styleData = SimplyRetsQueryParser::parseGetParameter(
            "sr_style",
            "style",
            $_GET
        );

        $style_att = $styleData["att"];
        $style_query = $styleData["query"];

        /** Parse multiple brokers from short-code parameter */
        $brokersData = SimplyRetsQueryParser::parseGetParameter(
            "sr_brokers",
            "brokers",
            $_GET
        );

        $brokers_att = $brokersData["att"];
        $brokers_query = $brokersData["query"];

        /** Parse multiple agent from short-code parameter */
        $agentData = SimplyRetsQueryParser::parseGetParameter(
            "sr_agent",
            "agent",
            $_GET
        );

        $agent_att = $agentData["att"];
        $agent_query = $agentData["query"];

        /** Parse multiple postalCodes from short-code parameter */
        $postalCodesData = SimplyRetsQueryParser::parseGetParameter(
            "sr_postalCodes",
            "postalCodes",
            $_GET
        );

        $postalCodes_att = $postalCodesData["att"];
        $postalCodes_query = $postalCodesData["query"];

        /** Parse multiple subtypes from short-code parameter */
        $subtypeData = SimplyRetsQueryParser::parseGetParameter(
            "sr_subtype",
            "subtype",
            $_GET
        );

        $subtype_att = $subtypeData["att"];
        $subtype_query = $subtypeData["query"];

        /** Parse multiple subtypes from short-code parameter */
        $subTypeTextData = SimplyRetsQueryParser::parseGetParameter(
            "sr_subTypeText",
            "subTypeText",
            $_GET
        );

        $subTypeText_att = $subTypeTextData["att"];
        $subTypeText_query = $subTypeTextData["query"];

        /** Parse multiple subtypes from short-code parameter */
        $specialListingConditionsData = SimplyRetsQueryParser::parseGetParameter(
            "sr_specialListingConditions",
            "specialListingConditions",
            $_GET
        );

        $specialListingConditions_att = $specialListingConditionsData["att"];
        $specialListingConditions_query = $specialListingConditionsData["query"];

        /** Parse areaMinor from short-code parameter */
        $areaMinorData = SimplyRetsQueryParser::parseGetParameter(
            "sr_areaMinor",
            "areaMinor",
            $_GET
        );

        $areaMinor_att = $areaMinorData["att"];
        $areaMinor_query = $areaMinorData["query"];

        /** Parse multiple ownership's from short-code parameter */
        $ownershipData = SimplyRetsQueryParser::parseGetParameter(
            "sr_ownership",
            "ownership",
            $_GET
        );

        $ownership_att = $ownershipData["att"];
        $ownership_query = $ownershipData["query"];

        /** Parse multiple salesAgent's from short-code parameter */
        $salesAgentData = SimplyRetsQueryParser::parseGetParameter(
            "sr_salesAgent",
            "salesAgent",
            $_GET
        );

        $salesAgent_att = $salesAgentData["att"];
        $salesAgent_query = $salesAgentData["query"];

        /** Parse multiple cities from short-code parameter */
        $citiesData = SimplyRetsQueryParser::parseGetParameter(
            "sr_cities",
            "cities",
            $_GET
        );

        $cities_att = $citiesData["att"];
        $cities_query = $citiesData["query"];

        /** Parse multiple state from short-code parameter */
        $stateData = SimplyRetsQueryParser::parseGetParameter(
            "sr_state",
            "state",
            $_GET
        );

        $state_att = $stateData["att"];
        $state_query = $stateData["query"];

        /** Parse multiple counties from short-code parameter */
        $countiesData = SimplyRetsQueryParser::parseGetParameter(
            "sr_counties",
            "counties",
            $_GET
        );

        $counties_att = $countiesData["att"];
        $counties_query = $countiesData["query"];

        /** Parse multiple neighborhoods from short-code parameter */
        $neighborhoodsData = SimplyRetsQueryParser::parseGetParameter(
            "sr_neighborhoods",
            "neighborhoods",
            $_GET
        );

        $neighborhoods_att = $neighborhoodsData["att"];
        $neighborhoods_query = $neighborhoodsData["query"];

        /** Parse multiple exteriorFeatures from short-code parameter */
        $exteriorFeaturesData = SimplyRetsQueryParser::parseGetParameter(
            "sr_exteriorFeatures",
            "exteriorFeatures",
            $_GET
        );

        $exteriorFeatures_att = $exteriorFeaturesData["att"];
        $exteriorFeatures_query = $exteriorFeaturesData["query"];

        /** Parse multiple lotDescription filters from short-code parameter */
        $lotDescriptionData = SimplyRetsQueryParser::parseGetParameter(
            "sr_lotDescription",
            "lotDescription",
            $_GET
        );

        $lotDescription_att = $lotDescriptionData["att"];
        $lotDescription_query = $lotDescriptionData["query"];

        /**
         * If `sr_q` is set, the user clicked a pagination link
         * (next/prev), and `sr_q` will possibly be an array of
         * values. Those translate to multiple `q` params in the
         * API request.
         */
        $q_string = '';

        $kws = isset($_GET['sr_q']) ? $_GET['sr_q'] : '';
        if (!empty($kws)) {
            foreach ((array)$kws as $key => $kw) {
                $q_string .= "&q=$kw";
            }
        }

        /**
         * If `sr_keywords` is set, the user submitted a search
         * form. This will always be a string, but it may contain
         * multiple searches separated by a ';'. If so, split and
         * translate them to multiple `q` parameters in the API
         * request.
         */
        $sfq = isset($_GET['sr_keywords']) ? $_GET['sr_keywords'] : '';
        if (!empty($sfq)) {
            $splitkw = explode(';', $sfq);
            if (!empty($splitkw)) {
                foreach ($splitkw as $key => $value) {
                    $trimmedkw = trim($value);
                    $q_string .= "&q=$trimmedkw";
                }
            }
        }

        /**
         * Make a new array with all query parameters.
         *
         * Note: We're only using params that weren't transformed
         * above.
         */
        $listing_params = array(
            "minbeds"   => $minbeds,
            "maxbeds"   => $maxbeds,
            "minbaths"  => $minbaths,
            "maxbaths"  => $maxbaths,
            "minprice"  => $minprice,
            "maxprice"  => $maxprice,
            "minyear"   => $minyear,
            "maxyear"   => $maxyear,
            "water"     => $water,
            "idx"       => $idx,

            /** Pagination */
            "limit"     => $limit,
            "offset"    => $offset,

            /** Advanced Search */
            "lotsize"   => $lotsize,
            "area"      => $area,
            "sort"      => $sort,

            /** Multi MLS */
            "vendor"    => $vendor,

            /**
             * Settings that need to be propogated through to
             * pagination. It's a bit awkward because these aren't
             * SimplyRETS query parameters, but it's the easiest
             * way to get them back on the other side right now.
             */
            "grid_view" => $grid_view,
            "show_map" => $show_map,
            "map_position" => $map_position
        );

        $settings = array(
            "limit" => $limit,
            "map_position" => $map_position,
            "show_map" => $show_map,
            "grid_view" => $grid_view
        );

        /**
         * Create a string that will be passed to the `q`
         * parameter in [sr_search_form q="STRING"].
         *
         * If `?sr_q` parameter has a value use/format that,
         * else check for `?sr_keywords`
         */
        $sr_q = get_query_var('sr_q');
        $sr_kw = get_query_var('sr_keywords');
        $kw_string = "";
        if (is_string($sr_q) && $sr_q != "") {
            $kw_string = $sr_q;
        } elseif (is_array($sr_q)) {
            $kw_string = implode("; ", $sr_q);
        } else {
            $kw_string = $sr_kw;
        }

        $next_atts = $listing_params + array(
            "q" => $kw_string,
            "status" => $statuses_attribute,
            "advanced" => $advanced == "true" ? "true" : "false",
            "subtype" => $subtype_att,
            "subTypeText" => $subTypeText_att,
            "specialListingConditions" => $specialListingConditions_att,
            "areaMinor" => $areaMinor_att,
            "ownership" => $ownership_att,
            "salesAgent" => $salesAgent_att,
            "agent" => $agent_att,
            "brokers" => $brokers_att,
            "style" => $style_att,
            "postalCodes" => $postalCodes_att,
            "cities" => $cities_att,
            "state" => $state_att,
            "counties" => $counties_att,
            "neighborhoods" => $neighborhoods_att,
            "exteriorFeatures" => $exteriorFeatures_att,
            "lotDescription" => $lotDescription_att
        );

        // Create a string of attributes to put on the
        // [sr_search_form] short-code.
        $filters_string = '';
        foreach ($next_atts as $param => $att) {
            if (!$att == '') {
                $filters_string .= ' ' . $param . '=\'' . $att . '\'';
            }
        }

        // Final API query string
        $qs = '?'
            . http_build_query(array_filter($listing_params))
            . $agent_query
            . $brokers_query
            . $style_query
            . $features_string
            . $cities_query
            . $state_query
            . $counties_query
            . $neighborhoods_query
            . $postalCodes_query
            . $ptypes_string
            . $subtype_query
            . $subTypeText_query
            . $specialListingConditions_query
            . $areaMinor_query
            . $ownership_query
            . $salesAgent_query
            . $statuses_string
            . $amenities_string
            . $exteriorFeatures_query
            . $lotDescription_query
            . $q_string;

        $qs = str_replace(' ', '%20', $qs);

        return array(
            'filters_string' => $filters_string,
            'qs' => $qs,
            'settings' => $settings
        );
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

        foreach ($atts as $param => $value_) {

            // 1. Escape values for use in HTML attributes
            // 2. Ensure "&" is not HTML encoded (https://stackoverflow.com/a/20078112/3464723)
            $value = str_replace("&amp;", "&", esc_attr($value_));
            $name = SimplyRetsQueryParser::attributeNameToParameter($param);

            // Parse settings, don't add them to the API query
            if (array_key_exists($param, $setting_atts)) {
                $attributes["settings"][$param] = $value;
            }

            // By default, attributes with multiple values separated by
            // a semicolon are split into an array. To byass this, set
            // explode_values is set to false.
            if ($explode_values == TRUE) {
                $values = explode(";", $value);
                foreach ($values as $idx => $val) {
                    $values[$idx] = trim($val);
                }

                $attributes["params"][$name] = count($values) > 1 ? $values : $value;
            } else {
                $attributes["params"][$name] = $value;
            }
        }

        return $attributes;
    }
}
