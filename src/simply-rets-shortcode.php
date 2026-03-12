<?php

/*
 *
 * simply-rets-api-helper.php - Copyright (C) 2014-2024 SimplyRETS
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
*/
if (! defined('ABSPATH')) {
    exit;
}

/* Code starts here */

add_action('init', array('SrShortcodes', 'sr_residential_btn'));


class SrShortcodes {


    /**
     * Short code kitchen sink button registration
     */
    public static function sr_residential_btn() {
        if (current_user_can('edit_posts') && current_user_can('edit_pages')) {
            add_filter('mce_external_plugins', array('SrShortcodes', 'sr_res_add_plugin'));
            add_filter('mce_buttons', array('SrShortcodes', 'sr_register_res_button'));
        }
    }

    public static function sr_register_res_button($buttons) {
        array_push($buttons, "simplyRets");
        return $buttons;
    }

    public static function sr_res_add_plugin($plugin_array) {
        $plugin_array['simplyRets'] = plugins_url('assets/js/simply-rets-shortcodes.js', __FILE__);
        return $plugin_array;
    }


    /**
     * [sr_map_search] - we return HTML with a special element that
     * the client attaches to to render a searchable map. This is
     * different from the other short-codes in that mostly everything
     * after this point is handled by the client.
     */
    public static function sr_int_map_search($atts) {
        if (!is_array($atts)) $atts = array();
        return SimplyRetsRenderer::renderMapSearchForm($atts);
    }

    public static function sr_openhouses_shortcode($atts = array()) {
        $data = SimplyRetsQueryParser::parseShortcodeAttributes($atts);

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
    public static function sr_residential_shortcode($atts = array()) {
        $setting_atts = array(
            "map_position" => get_option('sr_search_map_position', 'map_above'),
            "grid_view" => false,
            "show_map" => true,
            "vendor" => "",
            "limit" => 20
        );

        $data = SimplyRetsQueryParser::parseShortcodeAttributes($atts, $setting_atts);

        // Use /properties/:id if `mlsid` parameter is used
        if (!empty($atts['mlsid'])) {
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
        if (!is_array($atts)) $atts = array();
        return SimplyRetsRenderer::renderSearchForm($atts);
    }


    /**
     * TODO: sr_listings_slider should support attributes that can
     * take multiple values (eg, postalCodes, counties). #32
     */
    public static function sr_listing_slider_shortcode($atts = array()) {
        $def_params = array("limit" => "12");
        $def_settings = array("vendor" => "", "random" => "false");
        $def_atts = array_merge($def_params, is_array($atts) ? $atts : array());

        $data = SimplyRetsQueryParser::parseShortcodeAttributes($def_atts, $def_settings);

        return SimplyRetsApiHelper::retrieveListingsSlider(
            $data["params"],
            $data["settings"]
        );
    }
}
