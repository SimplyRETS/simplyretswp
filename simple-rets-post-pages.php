<?php

/*
 *
 * simple-rets-post-pages.php - Copyright (C) Reichert Brothers 2014
 * This file provides the logic for the simple-rets custom post type pages.
 *
*/


/* Code starts here */
add_action( 'init', array( 'simpleRetsCustomPostPages', 'simpleRetsPostType' ) );

add_filter( 'single_template', array( 'simpleRetsCustomPostPages', 'loadSimpleRetsPostTemplate' ) );
add_filter( 'the_content', array( 'simpleRetsCustomPostPages', 'simpleRetsDefaultContent' ) );

add_filter( 'the_posts', array( 'simpleRetsCustomPostPages', 'srListingDetailsPost' ) );

add_action( 'add_meta_boxes', array( 'simpleRetsCustomPostPages', 'postFilterMetaBox' ) );
add_action( 'add_meta_boxes', array( 'simpleRetsCustomPostPages', 'postTemplateMetaBox' ) );


add_action( 'save_post', array( 'simpleRetsCustomPostPages', 'postFilterMetaBoxSave' ) );
add_action( 'save_post', array( 'simpleRetsCustomPostPages', 'postTemplateMetaBoxSave' ) );


add_action( 'admin_enqueue_scripts', array( 'simpleRetsCustomPostPages', 'postFilterMetaBoxJs' ) );
add_action( 'admin_init', array( 'simpleRetsCustomPostPages', 'postFilterMetaBoxCss' ) );
// ^TODO: load css/js only on retsd-listings post type pages when admin


class simpleRetsCustomPostPages {

    // Create our Custom Post Type
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
            'supports'        => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
            'rewrite'         => true
        );
        register_post_type( 'retsd-listings', $args );
    }

    public static function postFilterMetaBox() {
        add_meta_box(
            'sr-meta-box-filter'
            , __( 'Filter Results on This Page', 'sr-textdomain')
            , array('simpleRetsCustomPostPages', 'postFilterMetaBoxMarkup')
            , 'retsd-listings'
            , 'normal'
            , 'high'
        );
    }

    public static function postTemplateMetaBox() {
        add_meta_box(
             'sr-template-meta-box'
             , __('Page Template', 'sr-textdomain')
             , array( 'simpleRetsCustomPostPages', 'postTemplateMetaBoxMarkup' )
             , 'retsd-listings'
             , 'side'
             , 'core'
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
        $min_bath_filter  = "";
        $max_bath_filter  = "";
        $agent_id_filter  = "";

        $sr_filters = get_post_meta( $post->ID, 'sr_filters', true);

        // TODO: Once all the query parameters are finalized, we can generate
        // most of the markup below.
        ?>
        <div class="current-filters">
            <span class="filter-add">
              <?php _e( 'Add new Filter' ); ?>
            </span>
            <select name="sr-filter-select" id="sr-filter-select">
                <option> -- Select a Filter --                    </option>
                <option val="minprice-option">  Minimum Price     </option>
                <option val="maxprice-option"> Maximum Price     </option>
                <option val="minbed-option">    Minimum Beds      </option>
                <option val="maxbed-option">    Maximum Beds      </option>
                <option val="minbath-option">   Minimum Bathrooms </option>
                <option val="maxbath-option">   Maximum Bathrooms </option>
                <option val="agentid-option">   Listing Agent     </option>
            </select>
            <hr>
        </div>

        <div class="sr-meta-inner">

          <!-- Min Price Filter -->
          <div class="sr-filter-input" id="sr-min-price-span">
            <label for="sr-min-price-input">
              <?php _e( 'Minimum Price', 'sr-textdomain' ) ?>
            </label>
            <input id="minprice" type="text" name="sr_filters[minprice]"
              value="<?php print_r( $min_price_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Max Price Filter -->
          <div class="sr-filter-input" id="sr-max-price-span">
            <label for="sr-max-price-input">
              Maximum Price:
            </label>
            <input id="maxprice" type="text" name="sr_filters[maxprice]"
              value="<?php print_r( $max_price_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Min Bed Filter -->
          <div class="sr-filter-input" id="sr-min-bed-span">
            <label for="sr-min-bed-input">
              Minimum Bedrooms:
            </label>
            <input id="minbed" type="text" name="sr_filters[minbed]"
              value="<?php print_r( $min_bed_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Max Bed Filter -->
          <div class="sr-filter-input" id="sr-max-bed-span">
            <label for="sr-max-bed-input">
              Maximum Bedrooms:
            </label>
            <input id="maxbed" type="text" name="sr_filters[maxbed]"
              value="<?php print_r( $max_bed_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Min Baths Filter -->
          <div class="sr-filter-input" id="sr-min-bath-span">
            <label for="sr-min-bath-input">
              Minimum Bathrooms:
            </label>
            <input id="minbath" type="text" name="sr_filters[minbath]"
              value="<?php print_r( $min_bath_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Max Baths Filter -->
          <div class="sr-filter-input" id="sr-max-bath-span">
            <label for="sr-max-bath-input">
              Maximum Bathrooms:
            </label>
            <input id="maxbath" type="text" name="sr_filters[maxbath]"
              value="<?php print_r( $max_bath_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Agent ID Filter -->
          <div class="sr-filter-input" id="sr-listing-agent-span">
            <label for="sr-listing-agent-input">
              Listing Agent MLS Id:
            </label>
            <input id="agentid" type="text" name="sr_filters[agentid]"
              value="<?php print_r( $agent_id_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

        </div>
        <?php

        echo '<br>Current filters: <br>'; print_r( $sr_filters );
        echo '<br>';
        // ^TODO: Remove degbug

        // on page load, if there are any filters already saved, load them,
        // show the input field, and remove the option from the dropdown
        foreach( $sr_filters as $key=>$val ) {
            if ( $val != '' ) {
                ?>
                <script>
                    var filterArea = jQuery('.current-filters');
                    var key = jQuery(<?php print_r( $key ); ?>);
                    var val = <?php echo json_encode( $val ); ?>;
                    var parent = key.parent();

                    key.val(val); // set value to $key
                    console.log(key.val());
                    filterArea.append(parent); //append div to filters area
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
            return;
        }

        $sr_filters = $_POST['sr_filters'];
        update_post_meta( $post_id, 'sr_filters', $sr_filters );
    }

    public static function postTemplateMetaBoxMarkup( $post ) {
        wp_nonce_field( basename(__FILE__), 'sr_template_meta_nonce' );

        $current_template = get_post_meta( $post->ID, 'sr_page_template', true);
        $template_options = get_page_templates();

        $box_label = '<label class="sr-filter-meta-box" for="sr_page_template">Page Template</label>';
        $box_select = '<select name="sr_page_template" id="sr-page-template-select">';
        $box_default_option = '<option value="">Default Template</option>';
        $box_option = '';

        echo $box_label;

        foreach (  $template_options as $name=>$file ) {
            if ( $current_template == $file ) {
                $box_option .= '<option value="' . $file . '" selected="selected">' . $name . '</option>';
            } else {
                $box_option .= '<option value="' . $file . '">' . $name . '</option>';
            }
        }

        echo $box_select;
        echo $box_default_option;
        echo $box_option;
        echo '</select>';
    }

    public static function postTemplateMetaBoxSave( $post_id ) {
        $current_nonce = $_POST['sr_template_meta_nonce'];
        $is_autosaving = wp_is_post_autosave( $post_id );
        $is_revision   = wp_is_post_revision( $post_id );
        $valid_nonce   = ( isset( $current_nonce ) && wp_verify_nonce( $current_nonce, basename( __FILE__ ) ) ) ? 'true' : 'false';

        if ( $is_autosaving || $is_revision || !$valid_nonce ) {
            return;
        }

        $sr_page_template = $_POST['sr_page_template'];
        update_post_meta( $post_id, 'sr_page_template', $sr_page_template );
    }

    public static function loadSimpleRetsPostTemplate() {
        $query_object = get_queried_object();
        $sr_post_type = 'retsd-listings';
        $page_template = get_post_meta( $query_object->ID, 'sr_page_template', true );



        $default_templates    = array();
        $default_templates[]  = "single-{$query_object->post_type}-{$query_object->post_name}.php";
        $default_templates[]  = "single-{$query_object->post_type}.php";
        $default_templates[]  = "page.php";

        // only apply our template to our CPT pages
        if ( $query_object->post_type == $sr_post_type ) {
            if ( !empty( $page_template ) ) {
                $default_templates = $page_template;
            }
        }

        $new_template = locate_template( $default_templates, false );
        return $new_template;
    }

    public static function simpleRetsDefaultContent( $content, $post ) {
        require_once( plugin_dir_path(__FILE__) . 'simple-rets-api-helper.php' );
        $post_type = get_post_type();
        $page_name = get_query_var( 'retsd-listings' );

        $sr_post_type = 'retsd-listings';
        $br = '<br>';

        if ( $page_name == 'sr-single' ) {
            $listing_id = get_query_var( 'listing_id' );
            $content .= SimpleRetsApiHelper::retrieveListingDetails( $listing_id );
            return $content;
        }

        if ( $page_name == 'sr-search' ) {
            $minbed   = get_query_var( 'sr_minbed',   '' );
            $maxbed   = get_query_var( 'sr_maxbed',   '' );
            $minbath  = get_query_var( 'sr_minbath',  '' );
            $maxbath  = get_query_var( 'sr_maxbath',  '' );
            $minprice = get_query_var( 'sr_minprice', '' );
            $maxprice = get_query_var( 'sr_maxprice', '' );
            // TODO: make sure api helper supports these
            $keywords = get_query_var( 'sr_keywords', '');
            $type     = get_query_var( 'sr_ptype', '');
            echo $keywords;
            echo $type;

            $listing_params = array(
                "minbed"   => $minbed,
                "maxbed"   => $maxbed,
                "minbath"  => $minbath,
                "maxbath"  => $maxbath,
                "minprice" => $minprice,
                "maxprice" => $maxprice
            );

            $listings_content = SimpleRetsApiHelper::retrieveRetsListings( $listing_params );
            $content .= print_r( $listing_params );
            $content .= $listings_content;

            return $content;
        }

        if( $post_type == $sr_post_type ) {
            $query_object = get_queried_object();
            $listing_params = get_post_meta( $query_object->ID, 'sr_filters', true );

            if ( empty($listing_params) ) {
                return $content;
            }

            // debug for viewing the search filters saved from the custom post page
            //foreach ( $listing_params as $key=>$value ) {
            //    $filters = 'param: ' . $key . ' value: ' . $value . $br . $filters;
            //}
            // the simple rets api helper takes care of retrieving, parsing, and generating
            // the markup for the listings to be shown on this page based off of the sr_filters
            // saved for this post
            $listings_content = SimpleRetsApiHelper::retrieveRetsListings( $listing_params );
            $content = $content . $listings_content;

            return $content;
        }
        return $content;
    }



    public static function srListingDetailsPost( $posts ) {


        // if we catch a singlelisting query, create a new post on the fly
        global $wp_query;
        if( $wp_query->query['retsd-listings'] == "sr-single" ) {
            $post_id    = get_query_var( 'listing_id' );
            $post_addr = get_query_var( 'listing_title', 'none' );
            $post_price = get_query_var( 'listing_price', '' );

            $listing_USD = '$' . number_format( $post_price );
            $title_normalize = "background-color:transparent;padding:0px;";
            $post_title = "{$post_addr} - <span style='{$title_normalize}'>{$listing_USD}</span>";

            $post = (object)array(
                "ID"             => $post_id,
                "comment_count"  => 0,
                "comment_status" => "closed",
                "ping_status"    => "closed",
                "post_author"    => 1,
                "post_name"      => $post_id,
                "post_date"      => date("c"),
                "post_date_gmt"  => gmdate("c"),
                "post_parent"    => 0,
                "post_status"    => "publish",
                "post_title"     => $post_title,
                "post_type"      => "retsd-listings"
            );

            $posts = array( $post );
            return $posts;
        }
        // if we catch a search results query, create a new post on the fly
        if( $wp_query->query['retsd-listings'] == "sr-search" ) {
            $post_id    = get_query_var( 'sr_minprice' );
            $post_title = get_query_var( 'sr_minprice', 'none' );

            $post = (object)array(
                "ID"              => $post_id,
                "comment_count"   => 0,
                "comment_status"  => "closed",
                "ping_status"     => "closed",
                "post_author"     => 1,
                "post_name"       => "Search Listings",
                "post_date"       => date("c"),
                "post_date_gmt"   => gmdate("c"),
                "post_parent"     => 0,
                "post_status"     => "publish",
                "post_title"      => "Listings",
                "post_type"       => "retsd-listings"
            );

		    $posts = array( $post );
            return $posts;
        }

        return $posts;
    }

}
?>
