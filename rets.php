<?php
/*
 * Plugin Name: Simply Rets
 * Description: A Wordpress plugin for Reichert Brothers Retsd server.
 * Copyright (c) Reichert Brothers 2014
*/

/* Code starts here */



// Initialize admin panel pages and settings for admin only users
if ( is_admin() ) {
    require_once( plugin_dir_path(__FILE__) . 'simple-rets-admin.php' );
    add_action( 'admin_init', 'register_admin_settings' );
    add_action( 'admin_menu', 'add_to_admin_menu' );
}
// initialize custom post type
require_once( plugin_dir_path(__FILE__) . 'simple-rets-post-pages.php' );


// a filter to remove comments from property pages
// TODO - set title and other meta fields on client side pages because some themes
// use incorrect data if not explicitly set.
function remove_retsd_comments() {
    global $post;
    if ( !( is_singular() && ( have_comments() || 'open' == $post->comment_status ) ) ) {
        return;
    }
    if ( $post->post_type == 'retsd-listings') {
        return dirname(__FILE__) . '/comments-template.php';
    }
}
add_filter( 'comments_template', 'remove_retsd_comments' );

// Custom Query variables we'll use to load the correct template and retrieve
// data from RetsD
function add_query_vars_filter( $vars ){
    global $wp_query;
    $vars[] = "listing_id";
    return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );



// Shortcodes
//
// [retsd_residential] for all residential listings
function retsd_residential_shortcode() {
    global $wp_query;
    ob_start();

    if ( isset($wp_query->query_vars['listing_id']) && $wp_query->query_vars['listing_id'] != '' ) {
        // ^ listing_id has to be set AND not empty to get redirected to a single listing template

        $listing_id = get_query_var( 'listing_id' );
        echo '<strong>we captured a single listing query for property '; echo $listing_id; echo '</strong><br><br>';

        ?> <!-- shortcode template here -->
        <div id="residential-properties">
          <h2>RetsD Residential Listing</h2>
          <?php retsd_residential_single( $listing_id ); ?>
        </div>

    <?php
    } else {
        echo '<strong>This is not a search for a single listing</strong><br><br>';

        ?> <!-- shortcode template here -->
        <div id="residential-properties">
          <h2>RetsD Residential Listings</h2>
          <?php retsd_residential(); ?>
        </div>
        <?php

    }

    // print_r( $wp_query->query_vars ); // returns an array of all the query variables in that request
    return ob_get_clean();
}
add_shortcode('retsd_residential', 'retsd_residential_shortcode');

// [retsd_openhouses] for all open houses
function retsd_openhouses_shortcode() {
    ob_start();

    ?> <!-- shortcode template here -->
    <div id="openhouses">
      <h2>RetsD Open Houses</h2>
      <?php retsd_openhouses(); ?>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('retsd_openhouses', 'retsd_openhouses_shortcode');

// [retsd_search_form] to display a form for search filtering
function retsd_search_form_shortcode() {
    ob_start();

    ?>
    <div id="retsd-search-form">
      <h2>RetsD Search Form</h2>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('retsd_search_form', 'retsd_search_form_shortcode');


// RetsD API Wrappers
//
// retsd_residential to get all residential listings
function retsd_residential_single($listing_id) {

    $listing_id = $listing_id;
    $retsd_url = 'http://54.187.230.155/properties/res/' . $listing_id;

    $response = wp_remote_retrieve_body( wp_remote_get( $retsd_url ) );
    $response_json = json_decode( $response );
    $listing = $response_json;
    // ^ decodes response into an array of objects

    // TODO: create requests all for all fields when the API is stable
    // mls information
    $mls_status  = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationStatus;
    $mls_area    = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationArea;
    $mls_serving = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationServingName;

    // listing information
    $listing_modified = $listing->residentialPropertyListing->listingModificationTimestamp; // TODO: format date
    $listing_office   = $listing->residentialPropertyListing->{"listingData'"}->listingDataOffice;
    $listing_agent    = $listing->residentialPropertyListing->{"listingData'"}->listingDataAgent;
    $listing_date     = $listing->residentialPropertyListing->{"listingData'"}->listingDataListDate;
    $listing_price    = $listing->residentialPropertyListing->{"listingData'"}->listingDataListPrice;
    $listing_remarks  = $listing->residentialPropertyListing->{"listingData'"}->listingDataRemarks;

    $listing_uid      = $listing->residentialPropertyListing->listingId;
    // Amenities
    $beds  = "{$listing->residentialPropertyBedrooms}";
    $baths = "{$listing->residentialPropertyBathsFull}";

    echo <<<HTML
      <h4>Listing Id: <a href="/?retsd-listings=search&listing_id={$listing_uid}">{$listing_uid}</a></h4>
HTML;

    echo '<p>Status: ';       echo $mls_status;  echo '</p>';
    echo '<p>Mls Area: ';     echo $mls_area;    echo '</p>';
    echo '<p>Serving Name: '; echo $mls_serving; echo '</p>';

    echo '<p>Listing Modified: '; echo $listing_modified; echo '</p>';
    echo '<p>Listing Office: ';   echo $listing_office;   echo '</p>';
    echo '<p>Listing Agent: ';    echo $listing_agent;    echo '</p>';
    echo '<p>Listing Date: ';     echo $listing_date;     echo '</p>';
    echo '<p>Listing Price: ';    echo $listing_price;    echo '</p>';
    echo '<p>Listing Remarks: ';  echo $listing_remarks;  echo '</p>';

    echo '<p>Beds: ';  echo $beds;  echo '</p>';
    echo '<p>Baths: '; echo $baths; echo '</p>';

    echo '<pre><code>'; print_r( $response_json ); echo '</pre></code>';
}

function retsd_residential() {

    $response = wp_remote_retrieve_body( wp_remote_get( 'http://54.187.230.155/properties/res' ) );
    $response_json = json_decode( $response );
    // ^ decodes response into an array of objects

    // TODO: create requests all for all fields when the API is stable
    foreach ( $response_json as $listing ) {
        // mls information
        $mls_status  = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationStatus;
        $mls_area    = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationArea;
        $mls_serving = $listing->residentialPropertyListing->listingMlsInformation->mlsInformationServingName;

        // listing information
        $listing_modified = $listing->residentialPropertyListing->listingModificationTimestamp; // TODO: format date
        $listing_office   = $listing->residentialPropertyListing->{"listingData'"}->listingDataOffice;
        $listing_agent    = $listing->residentialPropertyListing->{"listingData'"}->listingDataAgent;
        $listing_date     = $listing->residentialPropertyListing->{"listingData'"}->listingDataListDate;
        $listing_price    = $listing->residentialPropertyListing->{"listingData'"}->listingDataListPrice;
        $listing_remarks  = $listing->residentialPropertyListing->{"listingData'"}->listingDataRemarks;

        $listing_uid      = $listing->residentialPropertyListing->listingId;

        // Amenities
        $beds  = "{$listing->residentialPropertyBedrooms}";
        $baths = "{$listing->residentialPropertyBathsFull}";


        echo '<div>';

        $listing_link = add_query_arg( 'listing_id', $listing_uid );
        echo $listing_link;
        echo <<<HTML
          <h4>Listing Id: <a href="/?retsd-listings=search&listing_id={$listing_uid}">{$listing_uid}</a></h4>
HTML;

        echo '<p>Status: '; echo $mls_status; echo '</p>';
        echo '<p>Mls Area: '; echo $mls_area; echo '</p>';
        echo '<p>Serving Name: '; echo $mls_serving; echo '</p>';

        echo '<p>Listing Modified: '; echo $listing_modified; echo '</p>';
        echo '<p>Listing Office: '; echo $listing_office; echo '</p>';
        echo '<p>Listing Agent: '; echo $listing_agent; echo '</p>';
        echo '<p>Listing Date: '; echo $listing_date; echo '</p>';
        echo '<p>Listing Price: '; echo $listing_price; echo '</p>';
        echo '<p>Listing Remarks: '; echo $listing_remarks; echo '</p>';

        echo '<p>Beds: '; echo $beds; echo '</p>';
        echo '<p>Baths: '; echo $baths; echo '</p>';

        echo '</div>';
        echo '<hr>';
    }

    echo '<pre><code>'; print_r( $response_json[0] ); echo '</pre></code>';

    ?>
    <script type="text/javascript">
        var residentialProperties = <?php echo $response ?>;
        console.log(residentialProperties);
        for (var i = 0; i < residentialProperties.length; i++) {
            var property = residentialProperties[i];
            console.log(property);
        }

    </script>
    <?php
}

function retsd_openhouses() {
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


// initialize any javascript and css files we need here
require_once( plugin_dir_path(__FILE__) . 'simple-rets-api-helper.php' );
add_action( 'wp_enqueue_scripts', array( 'SimpleRetsApiHelper', 'simpleRetsClientCss' ) );

function init_js() {
    wp_enqueue_script('retsd', plugins_url('/js/retsd.js',__FILE__) );
}
add_action('wp_head', 'init_js');
