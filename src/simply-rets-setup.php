<?php

/*
 *
 * simply-rets-setup.php - Copyright (C) 2014-2024 SimplyRETS
 * This file provides the initial setup and routing logic.
 *
*/

class SimplyRetsSetup {

    public static function srActivate() {
        SimplyRetsSetup::srRegisterPostType();
        SimplyRetsApiClient::srUpdateAdvSearchOptions();

        wp_schedule_event(
            get_option('sr_adv_search_meta_timestamp'),
            'daily',
            'sr_update_adv_search_options_action'
        );

        add_option('sr_api_name', 'simplyrets');
        add_option('sr_api_key', 'simplyrets');
        add_option('sr_listing_gallery', 'fancy');
        add_option('sr_show_leadcapture', true);
        add_option('sr_search_map_position', 'map_above');

        add_option('sr_permalink_structure', 'pretty');

        add_option('sr_show_admin_message', 1);
        add_option('sr_demo_page_created', false);

        add_option('sr_office_on_thumbnails', false);
        add_option('sr_agent_on_thumbnails', false);
        add_option('sr_thumbnail_idx_image', '');
        add_option('sr_agent_office_above_the_fold', false);
        add_option('sr_show_mls_trademark_symbol', false);

        flush_rewrite_rules();
    }

    public static function srDeactivate() {
        delete_option('sr_show_admin_message');
        delete_option('sr_demo_page_created');
        flush_rewrite_rules();
    }

    public static function srInitRewriteRules() {
        $rules = get_option('rewrite_rules');

        add_rewrite_tag('%listings%', '([^&]+)');

        // 'pretty_extra' permalinks
        add_rewrite_rule(
            'listings/([^&]+)/([^&]+)/([^&]+)/([^&]+)/([^&]+)/?$',
            'index.php?sr-listings=sr-single&sr_city=$matches[1]&sr_state=$matches[2]&sr_zip=$matches[3]&listing_id=$matches[5]&listing_title=$matches[4]',
            'top'
        );

        // 'pretty' permalinks
        add_rewrite_rule(
            'listings/(.*)/(.*)?$',
            'index.php?sr-listings=sr-single&listing_id=$matches[1]&listing_title=$matches[2]',
            'top'
        );

        if (!isset($rules['%listings%'])) {
            flush_rewrite_rules();
        }

        return;
    }

    public static function srAddRewriteRules($incoming) {
        $rules = array(
            'listings/([^&]+)/([^&]+)/([^&]+)/([^&]+)/([^&]+)/?$' => 'index.php?sr-listings=sr-single&sr_city=$matches[1]&sr_state=$matches[2]&sr_zip=$matches[3]&listing_title=$matches[4]&listing_id=$matches[5]',
            'listings/(.*)/(.*)?$' => 'index.php?sr-listings=sr-single&listing_id=$matches[1]&listing_title=$matches[2]'
        );

        return $incoming + $rules;
    }

    // Create our Custom Post Type
    public static function srRegisterPostType() {
        $labels = array(
            'name'          => 'SimplyRETS',
            'singular_name' => 'SimplyRETS Page',
            'add_new_item'  => 'New SimplyRETS Page',
            'edit_item'     => 'Edit SimplyRETS Page',
            'new_item'      => 'New SimplyRETS Page',
            'view_item'     => 'View SimplyRETS Page',
            'all_items'     => 'All SimplyRETS Pages',
            'search_items'  => 'Search SimplyRETS Pages',
        );
        $args = array(
            'public'          => true,
            'has_archive'     => false,
            'labels'          => $labels,
            'description'     => 'SimplyRETS property listings pages',
            'query_var'       => true,
            'menu_positions'  => '15',
            'capability_type' => 'page',
            'hierarchical'    => true,
            'taxonomies'      => array(),
            'supports'        => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'rewrite'         => true,
            'show_in_rest'    => true,
        );
        register_post_type('sr-listings', $args);
    }

    public static function srQueryVarsInit($vars) {
        global $wp_query;
        $vars[] = "listing_id";
        $vars[] = "listing_title";
        $vars[] = "listing_price";
        $vars[] = "limit";
        $vars[] = "offset";
        $vars[] = "advanced";
        $vars[] = "status";
        // sr prefixes are for the search form
        $vars[] = "sr_minprice";
        $vars[] = "sr_maxprice";
        $vars[] = "sr_minbeds";
        $vars[] = "sr_maxbeds";
        $vars[] = "sr_minbaths";
        $vars[] = "sr_maxbaths";
        $vars[] = "sr_minyear";
        $vars[] = "sr_maxyear";
        $vars[] = "sr_q";
        $vars[] = "sr_keywords";
        $vars[] = "sr_type";
        $vars[] = "sr_ptype";
        $vars[] = "sr_subtype";
        $vars[] = "sr_subTypeText";
        $vars[] = "sr_specialListingConditions";
        $vars[] = "sr_areaMinor";
        $vars[] = "sr_ownership";
        $vars[] = "sr_salesAgent";
        $vars[] = "sr_agent";
        $vars[] = "sr_brokers";
        $vars[] = "sr_sort";
        $vars[] = "sr_idx";
        $vars[] = "sr_style";
        $vars[] = "sr_exteriorFeatures";
        $vars[] = "sr_lotDescription";
        $vars[] = "water";
        // post type
        $vars[] = "sr-listings";
        // advanced search form parameters
        $vars[] = "sr_lotsize";
        $vars[] = "sr_area";
        $vars[] = "sr_cities";
        $vars[] = "sr_state";
        $vars[] = "sr_neighborhoods";
        $vars[] = "sr_amenities";
        $vars[] = "sr_features";
        $vars[] = "sr_counties";
        // multi-mls
        $vars[] = "vendor";
        $vars[] = "sr_vendor";
        // settings
        $vars[] = "sr_map_position";
        $vars[] = "show_map";
        $vars[] = "grid_view";
        return $vars;
    }
}
