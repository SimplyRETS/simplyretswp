<?php

/*
 *
 * This file provides the logic for the simple-rets custom post type pages.
 *
*/

/* Code starts here */
add_action( 'init', array( 'simpleRetsCustomPostPages', 'simpleRetsPostType' ) );
add_action( 'add_meta_boxes', array( 'simpleRetsCustomPostPages', 'postFilterMetaBox' ) );
add_action( 'save_post', array( 'simpleRetsCustomPostPages', 'postFilterMetaBoxSave' ) );

add_action( 'admin_enqueue_scripts', array( 'simpleRetsCustomPostPages', 'postFilterMetaBoxJs' ) );
add_action( 'admin_init', array( 'simpleRetsCustomPostPages', 'postFilterMetaBoxCss' ) );
// ^TODO: This should be conditioned to only load on our retsd-listings pages so it's not on
// every page of the admin panel. We are using an 'sr-' prefix on all of our elements for
// extra safety.


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
             'sr_meta_box_filter'
             , __( 'Filter Results on This Page', 'sr-textdomain')
           , array('simpleRetsCustomPostPages', 'postFilterMetaBoxMarkup')
           , 'retsd-listings'
           , 'normal'
           , 'high'
        );
    }

    public static function postFilterMetaBoxJs() {
        wp_register_script( 'simple-rets-admin-js', plugins_url( '/rets/js/simple-rets-admin.js' ), array( 'jquery' ) );
        wp_enqueue_script( 'simple-rets-admin-js' );
    }

    public static function postFilterMetaBoxCss() {
        wp_register_style( 'simple-rets-admin-css', plugins_url( '/rets/css/simple-rets-admin.css' ) );
        wp_enqueue_style( 'simple-rets-admin-css' );

    }

    public static function postFilterMetaBoxMarkup( $post ) {
        wp_nonce_field( basename(__FILE__), 'sr_meta_box_nonce' );
        $sr_page_meta = get_post_meta( $post->ID );
        $sr_filter_val = $sr_page_meta['sr-filter-val'];

        echo 'Current filter array: '; print_r( $sr_filter_val );
        // ^TODO: Remove this debug
        ?>
        <div id="sr-post-meta-box">
          <h4>The markup for the Meta Box goes here.</h4>
          <label for="sr-filter-val"><?php _e( 'Example Filter Input', 'sr-textdomain' ) ?></label>
          <input name="sr-filter-val" type="text" id="sr-filter-val"
                 value="<?php if ( isset( $sr_filter_val ) ) echo $sr_filter_val[0]; ?>" />
        </div>
        <?php

    }

    public static function postFilterMetaBoxSave( $post_id ) {
        $current_nonce = $_POST['sr_meta_box_nonce'];
        $is_autosaving = wp_is_post_autosave( $post_id );
        $is_revision   = wp_is_post_revision( $post_id );
        $valid_nonce   = ( isset( $current_nonce ) && wp_verify_nonce( $current_nonce, basename( __FILE__ ) ) ) ? 'true' : 'false';

        if( $is_autosaving || $is_revision || !$valid_nonce ) {
            die('save post meta failed');
            // ^ TODO: make this just a return statement in production. We're dying right now to get a good
            //         error message
        }

        // if there is text in our input box, nab it and save it
        if( isset( $_POST['sr-filter-val'] ) ) {
            update_post_meta( $post_id, 'sr-filter-val', sanitize_text_field( $_POST['sr-filter-val'] ) );
        }

    }

}
