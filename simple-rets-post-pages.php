<?php

/*
 *
 * This file provides the logic for the simple-rets custom post type pages.
 *
*/

/* Code starts here */
add_action( 'init', array( 'simpleRetsCustomPostPages', 'simpleRetsPostType' ) );
add_action( 'add_meta_boxes', array( 'simpleRetsCustomPostPages', 'postFilterMetaBox' ) );


class simpleRetsCustomPostPages {

    // Custom Post Type
    public static function simpleRetsPostType() {
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
            'public'          => true,
            'has_archive'     => false,
            'labels'          => $labels,
            'description'     => 'SimplyRets property listings pages',
            'query_var'       => true,
            'menu_positions'  => '15',
            'capability_type' => 'page',
            'hierarchical'    => true,
            'taxonomies'      => array(),
            'supports'        => array( 'title', 'editor', 'thumbnail' ),
            'rewrite'         => true
        );
        register_post_type( 'retsd-listings', $args );
    }

    public static function postFilterMetaBox() {
        add_meta_box(
             'filter-post-meta-box'
           , 'Filter Results on This Page'
           , array('simpleRetsCustomPostPages', 'postFilterMetaBoxMarkup')
           , 'retsd-listings'
           , 'normal'
           , 'high'
        );
    }

    public static function postFilterMetaBoxMarkup() {
        echo <<<HTML

        <div id="filter-post-meta-box">
          <h4>The markup for the Meta Box goes here.</h4>
          <label>Add a filter: </label>
          <input for="sr-filter" type="text" id="sr-filter-val" />
        </div>

HTML;
    }

}