<?php
/*
Plugin Name: WP-Rets
Description: A Wordpress plugin for Reichert Brothers Retsd server.
Copyright (c) Reichert Brothers 2014
*/

/* Code starts here */



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
        <strong>Api Key:</strong>
        <input type="text" name="api_key" value="<?php echo esc_attr( get_option('api_key') ); ?>" />
        <span>(current: <?php echo esc_attr( get_option('api_key') ); ?>)</span>
        <?php submit_button(); ?>
      </form>

    </div>
    <?php
}

add_action('admin_init', 'register_admin_settings');
add_action('admin_menu', 'add_to_admin_menu');



// Residential Properties Shortcode
//
// use this short code [rests_residential] on any page to get
// all listings on that page.
add_shortcode('retsd_residential', 'retsd_residential_shortcode');

function retsd_residential_shortcode() {
    ?>
    <div id="residential-properties">
      <?php retsd_residential(); ?>
    </div>
    <?php
}



// RetsD API Wrappers
//
// retsd_residential to get all residential listings
function retsd_residential() {
    $response = wp_remote_retrieve_body( wp_remote_get( 'http://54.187.230.155/properties/res' ) );
    $response_json = json_decode( $response );
    // ^ decodes response into an array of objects

    echo '<p>Status: '; print_r( $response_json[0]
                                     ->residentialPropertyListing
                                     ->listingMlsInformation
                                     ->mlsInformationStatus
                                   ); echo '</p>';
    echo '<p>Beds: '; print_r( $response_json[0]->residentialPropertyBedrooms ); echo '</p>';
    echo '<pre><code>'; print_r( $response_json[0] ); echo '</pre></code>';

    ?>
    <script type="text/javascript">
        var residentialProperties = <?php echo $response ?>

        console.log(residentialProperties);
        for (var i = 0; i < residentialProperties.length; i++) {
            var property = residentialProperties[i];
            var div = document.getElementById('residential-properties')
            console.log(property);
            div.innerHTML = div.innerHTML + '<br>' + property + '<br>';
        }

    </script>
    <hr>
    <?php
}


// initialize any javascript we need here
function init_js() {
    wp_enqueue_script('retsd', plugins_url('/js/retsd.js',__FILE__) );
}

add_action('wp_head', 'init_js');
