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

function admin_page() {
    global $wpdb;
    ?>
    <div class="wrap">
      <h2>RetsD Admin Settings</h2>
      <hr>
      <form method="post" action="options.php">
      </form>
    </div>
    <?php
}

add_action('admin_menu', 'add_to_admin_menu');



// Residential Properties Shortocde
//
// use this short code [rests_residential] on any page to get
// all listings on that page.
add_shortcode('test', 'retsd_residential_shortcode');

function retsd_residential_shortcode() {
    ?>
    <p>
      <?php retsd_residential(); ?>
    </p>
    <?php
}



// RetsD API Wrappers
//
// retsd_residential to get all residential listings
function retsd_residential() {
    $response = wp_remote_retrieve_body( wp_remote_get( 'http://54.187.230.155/properties/res' ) );
    echo $response;
    //var_dump(json_decode($response));
}


// initialize any javascript we need here
function init_js() {
    wp_enqueue_script('retsd', plugins_url('/retsd.js',__FILE__) );
}

add_action('wp_head', 'init_js');
