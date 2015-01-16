<?php
/*
 * Plugin Name: Simply Rets
 * Description: A Wordpress plugin for Reichert Brothers Retsd server.
 * Copyright (c) Reichert Brothers 2014
*/

/* Code starts here */
require_once( plugin_dir_path(__FILE__) . 'simply-rets-post-pages.php' );
require_once( plugin_dir_path(__FILE__) . 'simply-rets-shortcode.php' );
require_once( plugin_dir_path(__FILE__) . 'simply-rets-widgets.php' );


if ( is_admin() ) {
    require_once( plugin_dir_path(__FILE__) . 'simply-rets-admin.php' );
    add_action( 'admin_init', array( 'SrAdminSettings', 'register_admin_settings' ) );
    add_action( 'admin_menu', array( 'SrAdminSettings', 'add_to_admin_menu' ) );
}


// initialize simply rets shortcodes
add_shortcode('sr_residential', array( 'SimplyRetsShortcodes', 'sr_residential_shortcode') );
add_shortcode('sr_openhouses',  array( 'SimplyRetsShortcodes', 'sr_openhouses_shortcode')  );
add_shortcode('sr_search_form', array( 'SimplyRetsShortcodes', 'sr_search_form_shortcode') );


// initialize simply rets shortcodes
add_action( 'widgets_init', 'srRegisterWidgets' );
function srRegisterWidgets() {
    register_widget('sr_listing_widget');
}


// Custom Query variables we'll use to load the correct template and retrieve
// data from RetsD
function add_query_vars_filter( $vars ){
    global $wp_query;
    $vars[] = "listing_id";
    $vars[] = "listing_title";
    $vars[] = "listing_price";
    // sr prefixes are for the search form
    $vars[] = "sr_minprice";
    $vars[] = "sr_maxprice";
    $vars[] = "sr_minbed";
    $vars[] = "sr_maxbed";
    $vars[] = "sr_minbath";
    $vars[] = "sr_maxbath";
    $vars[] = "sr_keywords";
    $vars[] = "sr_ptype";
    $vars[] = "retsd-listings";
    return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );


// initialize any javascript and css files we need here
require_once( plugin_dir_path(__FILE__) . 'simply-rets-api-helper.php' );
add_action( 'wp_enqueue_scripts', array( 'SimplyRetsApiHelper', 'simplyRetsClientCss' ) );

function init_js() {
    wp_enqueue_script('retsd', plugins_url('/js/retsd.js',__FILE__) );
}
add_action('wp_head', 'init_js');

function sr_openhouses() {
    $response = wp_remote_retrieve_body( wp_remote_get( 'http://54.187.230.155/openhouse' ) );
    $response_json = json_decode( $response );
    // ^ decodes response into an array of objects

    foreach ( $response_json as $openhouse ) {

        $start_date   = $openhouse->openHouseFromDate;
        $end_date     = $openhouse->openHouseToDate;
        $input_date   = $openhouse->openHouseInputDate;
        $uid          = $openhouse->openHouseUid;
        $input_id     = $openhouse->openHouseInputId;
        $showing_type = $openhouse->openHouseType;
        $refreshments = $openhouse->openHouseRefreshements;
        $description  = $openhouse->openHouseDescription;

        echo '<div>';

        echo '<p>Start Date: '; echo $start_date; echo '</p>';
        echo '<p>End Date: '; echo $end_date; echo '</p>';
        echo '<p>Listing Date: '; echo $input_date; echo '</p>';
        echo '<p>Uid: '; echo $uid; echo '</p>';
        echo '<p>Input Id: '; echo $input_id; echo '</p>';
        echo '<p>Showing Type: '; echo $showing_type; echo '</p>';
        echo '<p>Refreshments: '; echo $refreshments; echo '</p>';
        echo '<p>Description: '; echo $description; echo '</p>';

        echo '</div>';
        echo '<hr>';
    }

    echo '<pre><code>'; print_r( $response_json[0] ); echo '</pre></code>';
}
