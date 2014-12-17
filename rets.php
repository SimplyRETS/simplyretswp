<?php
/*
Plugin Name: WP-Rets
Description: A Wordpress plugin for Reichert Brothers Retsd server.
Copyright (c) Reichert Brothers 2014
*/

/* Code starts here */



// Custom Post Type Taxonomy
function retsd_custom_post_type() {
    $labels = array(
        'name'          => __( 'Rets Pages' ),
        'singular_name' => __( 'Rets Page' ),
        'add_new_item'  => __( 'New Rets Page' ),
        'edit_item'     => __( 'Edit Rets Page' ),
        'new_item'      => __( 'New Rets Page' ),
        'view_item'     => __( 'View Rets Page' ),
        'all_items'     => __( 'All Rets Pages' ),
        'search_items'  => __( 'Search Rets Pages' ),
    );
    $args = array(
        'labels'          => $labels,
        'description'     => 'SimplyRets property listings pages',
        'public'          => true,
        'has_archive'     => false,
        'menu_positions'  => '15',
        'capability_type' => 'page',
        'supports'        => array( 'title', 'editor', 'thumbnail' ),
    );
    register_post_type( 'retsd-listings', $args );
}
add_action( 'init', 'retsd_custom_post_type' );

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





// Admin Panel Settings Page
function add_to_admin_menu() {
    add_options_page('RetsD Settings', 'RetsD', 'manage_options', 'rets-admin.php', 'admin_page');
}

function register_admin_settings() {
    register_setting('rets_admin_settings', 'api_name');
    register_setting('rets_admin_settings', 'api_key');
}

function admin_page() {
    global $wpdb;
    ?>
    <div class="wrap">
      <h2>RetsD Admin Settings</h2>
      <hr>

      <form method="post" action="options.php">
        <?php settings_fields( 'rets_admin_settings'); ?>
        <?php do_settings_sections( 'rets_admin_settings'); ?>

        <!-- api username -->
        <strong>Api Username: </strong>
        <input type="text" name="api_name" value="<?php echo esc_attr( get_option('api_name') ); ?>" />
        <span>(current: <?php echo esc_attr( get_option('api_name') ); ?>)</span>
        <br>
        <br>

        <!-- api password -->
        <strong>Api Key: </strong>
        <input type="text" name="api_key" value="<?php echo esc_attr( get_option('api_key') ); ?>" />
        <span>(current: <?php echo esc_attr( get_option('api_key') ); ?>)</span>
        <?php submit_button(); ?>
      </form>

    </div>
    <?php
}

add_action('admin_init', 'register_admin_settings');
add_action('admin_menu', 'add_to_admin_menu');



// Shortcodes
//
// [retsd_residential] for all residential listings
function retsd_residential_shortcode() {
    ?>
    <div id="residential-properties">
      <h2>RetsD Residential Listings</h2>
      <?php retsd_residential(); ?>
    </div>
    <?php
}
add_shortcode('retsd_residential', 'retsd_residential_shortcode');

// [retsd_openhouses] for all open houses
function retsd_openhouses_shortcode() {
    ?>
    <div id="openhouses">
      <h2>RetsD Open Houses</h2>
      <?php retsd_openhouses(); ?>
    </div>
    <?php
}
add_shortcode('retsd_openhouses', 'retsd_openhouses_shortcode');

// [retsd_search_form] to display a form for search filtering
function retsd_search_form_shortcode() {
    ?>
    <div id="retsd-search-form">
      <h2>RetsD Search Form</h2>
    </div>
    <?php
}
add_shortcode('retsd_search_form', 'retsd_search_form_shortcode');


// RetsD API Wrappers
//
// retsd_residential to get all residential listings
function retsd_residential() {
    $response = wp_remote_retrieve_body( wp_remote_get( 'http://54.187.230.155/properties/res' ) );
    $response_json = json_decode( $response );
    // ^ decodes response into an array of objects

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

        // Amenities
        $beds  = "{$listing->residentialPropertyBedrooms}";
        $baths = "{$listing->residentialPropertyBathsFull}";


        echo '<div>';

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
        var residentialProperties = <?php echo $response ?>
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


// initialize any javascript we need here
function init_js() {
    wp_enqueue_script('retsd', plugins_url('/js/retsd.js',__FILE__) );
}

add_action('wp_head', 'init_js');
