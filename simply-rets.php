<?php
/*
 * Plugin Name: Simply Rets
 * Description: A Wordpress plugin for Reichert Brothers Retsd server.
 * Copyright (c) Reichert Brothers 2014
 *
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

add_action( 'wp_head', 'init_js' );
add_action( 'widgets_init', 'srRegisterWidgets' );
add_action( 'wp_enqueue_scripts', array( 'SimplyRetsApiHelper', 'simplyRetsClientCss' ) );
add_filter( 'query_vars', array( 'SimplyRetsCustomPostPages', 'srQueryVarsInit' ) );

register_activation_hook( __FILE__, 'srFlushRewriteRules' );
register_deactivation_hook( __FILE__, 'srFlushRewriteRules' );

function srFlushRewriteRules() {
    flush_rewrite_rules();
}

function init_js() {
    wp_enqueue_script('simply-rets-js', plugins_url('/js/simply-rets.js',__FILE__) );
}