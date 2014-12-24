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
        $min_price_filter = "";
        $max_price_filter = "";
        $min_bed_filter   = "";
        $max_bed_filter   = "";
        $agent_id_filter  = "";

        $sr_filters = get_post_meta( $post->ID, 'sr_filters', true);

        ?>
        <div class="current-filters">
            <span class="filter-add">
              <?php _e( 'Add new Filter' ); ?>
            </span>
            <select name="sr-filter-select" id="sr-filter-select">
                <option> -- Select a Filter -- </option>
                <option val="minPrice-option"> Minimum Price  </option>
                <option val="maxPrice-option"> Maximum Price  </option>
                <option val="minBed-option">   Minimum Beds   </option>
                <option val="maxBed-option">   Maximum Beds   </option>
                <option val="agentId-option">  Listing Agent  </option>
            </select>
        </div>

        <br>
        <div class="sr-meta-inner">
          <br>

          <div class="sr-filter-input" id="sr-min-price-span">
            <label for="sr-min-price-input">
              <?php _e( 'Minimum Price', 'sr-textdomain' ) ?>
            </label>
            <input id="minPrice" type="text" name="sr_filters[minPrice]" value="<?php print_r( $min_price_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <div class="sr-filter-input" id="sr-max-price-span">
            <label for="sr-max-price-input">
              Maximum Price:
            </label>
            <input id="maxPrice" type="text" name="sr_filters[maxPrice]" value="<?php print_r( $max_price_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <div class="sr-filter-input" id="sr-min-bed-span">
            <label for="sr-min-bed-input">
              Minimum Bedrooms:
            </label>
            <input id="minBed" type="text" name="sr_filters[minBed]" value="<?php print_r( $min_bed_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <div class="sr-filter-input" id="sr-max-bed-span">
            <label for="sr-max-bed-input">
              Maximum Bedrooms:
            </label>
            <input id="maxBed" type="text" name="sr_filters[maxBed]" value="<?php print_r( $max_bed_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <div class="sr-filter-input" id="sr-listing-agent-span">
            <label for="sr-listing-agent-input">
              Listing Agent MLS Id:
            </label>
            <input id="agentId" type="text" name="sr_filters[agentId]" value="<?php print_r( $agent_id_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <span id="filter-here"></span>

          <script>
          jQuery(document).ready(function() {
          });
          </script>
        </div>
        <?php
        echo '<hr>Current filters: <br>'; print_r( $sr_filters );
        echo '<br>';
        foreach( $sr_filters as $key=>$val ) {
            if ( $val != '' ) {
                ?>
                <script>
                    var filterSelectBox = jQuery('#sr-filter-select');
                    var key = jQuery(<?php print_r( $key ); ?>);
                    var val = <?php print_r( $val ); ?>;
                    var parent = key.parent();

                    key.val(val); // set value to $key
                    console.log(key.val());
                    filterSelectBox.after(parent); //append div to filters area
                    parent.show(); //display: block the div since it has a value

                </script>
                <?php
            }
        };
    }

    public static function postFilterMetaBoxSave( $post_id ) {
        $current_nonce = $_POST['sr_meta_box_nonce'];
        $is_autosaving = wp_is_post_autosave( $post_id );
        $is_revision   = wp_is_post_revision( $post_id );
        $valid_nonce   = ( isset( $current_nonce ) && wp_verify_nonce( $current_nonce, basename( __FILE__ ) ) ) ? 'true' : 'false';

        if ( $is_autosaving || $is_revision || !$valid_nonce ) {
            die('save post meta failed');
            // ^ TODO: make this just a return statement in production. We're dying right now to get a good
            //         error message
        }

        $sr_filters = $_POST['sr_filters'];
        update_post_meta( $post_id, 'sr_filters', $sr_filters );
    }
}
?>