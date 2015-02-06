<?php
/*
Plugin Name: SimplyRETS
Plugin URI: https://wordpress.org/extend/plugins/simply-rets/
Description: Show your Real Estate listings on your Wordpress site. SimplyRETS provides a very simple set up and full control over your listings.
Author: Cody Reichert - Reichert Brothers
Version: 1.0.3
License: GNU General Public License v3 or later
Copyright (c) Reichert Brothers 2014

*/

/* Code starts here */

require_once( plugin_dir_path(__FILE__) . 'simply-rets-post-pages.php' );
require_once( plugin_dir_path(__FILE__) . 'simply-rets-api-helper.php' );
require_once( plugin_dir_path(__FILE__) . 'simply-rets-shortcode.php' );
require_once( plugin_dir_path(__FILE__) . 'simply-rets-widgets.php' );

if ( is_admin() ) {
    require_once( plugin_dir_path(__FILE__) . 'simply-rets-admin.php' );
    add_action( 'admin_init', array( 'SrAdminSettings', 'register_admin_settings' ) );
    add_action( 'admin_menu', array( 'SrAdminSettings', 'add_to_admin_menu' ) );
}

add_shortcode( 'sr_residential', array( 'SimplyRetsShortcodes', 'sr_residential_shortcode' ) );
add_shortcode( 'sr_openhouses',  array( 'SimplyRetsShortcodes', 'sr_openhouses_shortcode' ) );
add_shortcode( 'sr_search_form', array( 'SimplyRetsShortcodes', 'sr_search_form_shortcode' ) );

add_action( 'widgets_init', 'srRegisterWidgets' );
add_action( 'wp_enqueue_scripts', array( 'SimplyRetsApiHelper', 'simplyRetsClientCss' ) );
add_action( 'wp_enqueue_scripts', array( 'SimplyRetsApiHelper', 'simplyRetsClientJs' ) );
add_filter( 'query_vars', array( 'SimplyRetsCustomPostPages', 'srQueryVarsInit' ) );

register_activation_hook( __FILE__,   array('SimplyRetsCustomPostPages', 'srActivate' ) );
register_deactivation_hook( __FILE__, array('SimplyRetsCustomPostPages', 'srDeactivate' ) );