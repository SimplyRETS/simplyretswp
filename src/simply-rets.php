<?php
/*
Plugin Name: SimplyRETS
Plugin URI: https://simplyrets.com
Description: Show your Real Estate listings on your Wordpress site. SimplyRETS provides a very simple set up and full control over your listings.
Author: SimplyRETS
Version: 2.9.8
License: GNU General Public License v3 or later

Copyright (c) SimplyRETS 2014 - 2015

*/

/* Code starts here */

$plugin = plugin_basename(__FILE__);
$php_version = phpversion();

require __DIR__.'/vendor/autoload.php';

require_once( plugin_dir_path(__FILE__) . 'simply-rets-utils.php' );
require_once( plugin_dir_path(__FILE__) . 'simply-rets-post-pages.php' );
require_once( plugin_dir_path(__FILE__) . 'simply-rets-openhouses.php' );
require_once( plugin_dir_path(__FILE__) . 'simply-rets-api-helper.php' );
require_once( plugin_dir_path(__FILE__) . 'simply-rets-shortcode.php' );
require_once( plugin_dir_path(__FILE__) . 'simply-rets-widgets.php' );
require_once( plugin_dir_path(__FILE__) . 'simply-rets-maps.php' );


if ( is_admin() ) {
    require_once( plugin_dir_path(__FILE__) . 'simply-rets-admin.php' );
    add_action( 'admin_init', array( 'SrAdminSettings', 'register_admin_settings' ) );
    add_action( 'admin_menu', array( 'SrAdminSettings', 'add_to_admin_menu' ) );
}

add_shortcode( 'sr_residential',     array( 'SrShortcodes', 'sr_residential_shortcode' ) );
add_shortcode( 'sr_listings',        array( 'SrShortcodes', 'sr_residential_shortcode' ) );
add_shortcode( 'sr_openhouses',      array( 'SrShortcodes', 'sr_openhouses_shortcode' ) );
add_shortcode( 'sr_search_form',     array( 'SrShortcodes', 'sr_search_form_shortcode' ) );
add_shortcode( 'sr_listings_slider', array( 'SrShortcodes', 'sr_listing_slider_shortcode' ) );
add_shortcode( 'sr_map_search',      array( 'SrShortcodes', 'sr_int_map_search' ) );

add_action( 'widgets_init', 'srRegisterWidgets' );
add_action( 'wp_enqueue_scripts', array( 'SimplyRetsApiHelper', 'simplyRetsClientCss' ) );
add_action( 'wp_enqueue_scripts', array( 'SimplyRetsApiHelper', 'simplyRetsClientJs' ) );
add_filter( 'query_vars', array( 'SimplyRetsCustomPostPages', 'srQueryVarsInit' ) );
add_filter( "plugin_action_links_{$plugin}", array( 'SimplyRetsCustomPostPages', 'srPluginSettingsLink' ) );

register_activation_hook( __FILE__,   array('SimplyRetsCustomPostPages', 'srActivate' ) );
register_deactivation_hook( __FILE__, array('SimplyRetsCustomPostPages', 'srDeactivate' ) );
