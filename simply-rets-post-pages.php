<?php

/*
 *
 * simply-rets-post-pages.php - Copyright (C) Reichert Brothers 2014
 * This file provides the logic for the simply-rets custom post type pages.
 *
*/


/* Code starts here */
add_action( 'init',                  array( 'SimplyRetsCustomPostPages', 'srRegisterPostType' ) );
add_filter( 'comments_template',     array( 'SimplyRetsCustomPostPages', 'srClearComments' ) );
add_filter( 'single_template',       array( 'SimplyRetsCustomPostPages', 'srLoadPostTemplate' ) );
add_filter( 'the_content',           array( 'SimplyRetsCustomPostPages', 'srPostDefaultContent' ) );
add_filter( 'the_posts',             array( 'SimplyRetsCustomPostPages', 'srCreateDynamicPost' ) );
add_action( 'add_meta_boxes',        array( 'SimplyRetsCustomPostPages', 'postFilterMetaBox' ) );
add_action( 'add_meta_boxes',        array( 'SimplyRetsCustomPostPages', 'postTemplateMetaBox' ) );
add_action( 'save_post',             array( 'SimplyRetsCustomPostPages', 'postFilterMetaBoxSave' ) );
add_action( 'save_post',             array( 'SimplyRetsCustomPostPages', 'postTemplateMetaBoxSave' ) );
add_action( 'admin_init',            array( 'SimplyRetsCustomPostPages', 'postFilterMetaBoxCss' ) );
add_action( 'admin_enqueue_scripts', array( 'SimplyRetsCustomPostPages', 'postFilterMetaBoxJs' ) );
// ^TODO: load css/js only on sr-listings post type pages when admin
//  and move these into a constructor


class SimplyRetsCustomPostPages {

    public static function srActivate() {
        SimplyRetsCustomPostPages::srRegisterPostType();
        flush_rewrite_rules();
    }

    public static function srDeactivate() {
        flush_rewrite_rules();
    }

    // Create our Custom Post Type
    public static function srRegisterPostType() {
        $labels = array(
            'name'          => __( 'Simply Rets' ),
            'singular_name' => __( 'Simply Rets Page' ),
            'add_new_item'  => __( 'New Simply Rets Page' ),
            'edit_item'     => __( 'Edit Simply Rets Page' ),
            'new_item'      => __( 'New Simply Rets Page' ),
            'view_item'     => __( 'View Simply Rets Page' ),
            'all_items'     => __( 'All Simply Rets Pages' ),
            'search_items'  => __( 'Search Simply Rets Pages' ),
        );
        $args = array(
            'public'          => true,
            'has_archive'     => false,
            'labels'          => $labels,
            'description'     => 'Simply Rets property listings pages',
            'query_var'       => true,
            'menu_positions'  => '15',
            'capability_type' => 'page',
            'hierarchical'    => true,
            'taxonomies'      => array(),
            'supports'        => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
            'rewrite'         => true
        );
        register_post_type( 'sr-listings', $args );
    }

    public static function srQueryVarsInit( $vars ) {
        global $wp_query;
        $vars[] = "listing_id";
        $vars[] = "listing_title";
        $vars[] = "listing_price";
        // sr prefixes are for the search form
        $vars[] = "sr_minprice";
        $vars[] = "sr_maxprice";
        $vars[] = "sr_minbeds";
        $vars[] = "sr_maxbeds";
        $vars[] = "sr_minbaths";
        $vars[] = "sr_maxbaths";
        $vars[] = "sr_keywords";
        $vars[] = "sr_ptype";
        $vars[] = "sr-listings";
        return $vars;
    }

    public static function postFilterMetaBox() {
        add_meta_box(
            'sr-meta-box-filter'
            , __( 'Filter Results on This Page', 'sr-textdomain')
            , array('SimplyRetsCustomPostPages', 'postFilterMetaBoxMarkup')
            , 'sr-listings'
            , 'normal'
            , 'high'
        );
    }

    public static function postTemplateMetaBox() {
        add_meta_box(
             'sr-template-meta-box'
             , __('Page Template', 'sr-textdomain')
             , array( 'SimplyRetsCustomPostPages', 'postTemplateMetaBoxMarkup' )
             , 'sr-listings'
             , 'side'
             , 'core'
        );
    }

    public static function postFilterMetaBoxJs() {
        wp_register_script( 'simply-rets-admin-js'
                            , plugins_url( 'js/simply-rets-admin.js', __FILE__ )
                            , array( 'jquery' )
        );
        wp_enqueue_script( 'simply-rets-admin-js' );
    }

    public static function postFilterMetaBoxCss() {
        wp_register_style( 'simply-rets-admin-css', plugins_url( 'css/simply-rets-admin.css', __FILE__ ) );
        wp_enqueue_style( 'simply-rets-admin-css' );

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
        $listing_type_filter  = "";

        $sr_filters = get_post_meta( $post->ID, 'sr_filters', true);

        // TODO: Once all the query parameters are finalized, we can generate
        // most of the markup below.
        ?>
        <div class="current-filters">
            <span class="filter-add">
              <?php _e( 'Add new Filter' ); ?>
            </span>
            <select name="sr-filter-select" id="sr-filter-select">
                <option> -- Select a Filter --                   </option>
                <option val="minprice-option">  Minimum Price     </option>
                <option val="maxprice-option">  Maximum Price     </option>
                <option val="minbeds-option">   Minimum Beds      </option>
                <option val="maxbeds-option">   Maximum Beds      </option>
                <option val="minbaths-option">  Minimum Bathrooms </option>
                <option val="maxbaths-option">  Maximum Bathrooms </option>
                <option val="agentid-option">   Listing Agent     </option>
                <option val="type-option">      Listing Type      </option>
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
            <input id="minbeds" type="text" name="sr_filters[minbeds]"
              value="<?php print_r( $min_bed_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Max Bed Filter -->
          <div class="sr-filter-input" id="sr-max-bed-span">
            <label for="sr-max-bed-input">
              Maximum Bedrooms:
            </label>
            <input id="maxbeds" type="text" name="sr_filters[maxbeds]"
              value="<?php print_r( $max_bed_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Min Baths Filter -->
          <div class="sr-filter-input" id="sr-min-bath-span">
            <label for="sr-min-bath-input">
              Minimum Bathrooms:
            </label>
            <input id="minbaths" type="text" name="sr_filters[minbaths]"
              value="<?php print_r( $min_bath_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Max Baths Filter -->
          <div class="sr-filter-input" id="sr-max-bath-span">
            <label for="sr-max-bath-input">
              Maximum Bathrooms:
            </label>
            <input id="maxbaths" type="text" name="sr_filters[maxbaths]"
              value="<?php print_r( $max_bath_filisting_typelter ); ?>"/>
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

          <!-- Listing Type Filter -->
          <div class="sr-filter-input" id="sr-listing-type-span">
            <label for="sr-listing-type-input">
              Property Type:
            </label>
            <input id="type" type="text" name="sr_filters[type]"
              value="<?php print_r( $listing_type_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

        </div>
        <?php

        // echo '<br>Current filters: <br>'; print_r( $sr_filters );
        // echo '<br>';
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
        }
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

        $box_label = '<label class="sr-filter-meta-box" for="sr_page_template">Page Template</label><br />';
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


    // TODO: not sure if this is entirely necessary...at one time it was
    function srClearComments() {
        return;
        global $post;
        if ( !( is_singular() && ( have_comments() || 'open' == $post->comment_status ) ) ) {
            return;
        }
        if ( $post->post_type == 'sr-listings') {
            return dirname(__FILE__) . '/simply-rets-comments-template.php';
        }
    }


    public static function srLoadPostTemplate() {
        $query_object = get_queried_object();
        $sr_post_type = 'sr-listings';
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

    public static function srPostDefaultContent( $content ) {
        require_once( plugin_dir_path(__FILE__) . 'simply-rets-api-helper.php' );
        $post_type = get_post_type();
        $page_name = get_query_var( 'sr-listings' );

        $sr_post_type = 'sr-listings';
        $br = '<br>';

        if ( $page_name == 'sr-single' ) {
            $listing_id = get_query_var( 'listing_id' );
            $content .= SimplyRetsApiHelper::retrieveListingDetails( $listing_id );
            return $content;
        }

        if ( $page_name == 'sr-search' ) {
            $minbeds   = get_query_var( 'sr_minbeds',   '' );
            $maxbeds   = get_query_var( 'sr_maxbeds',   '' );
            $minbaths  = get_query_var( 'sr_minbaths',  '' );
            $maxbaths  = get_query_var( 'sr_maxbaths',  '' );
            $minprice = get_query_var( 'sr_minprice', '' );
            $maxprice = get_query_var( 'sr_maxprice', '' );
            // TODO: make sure api helper supports these
            $keywords = get_query_var( 'sr_keywords', '' );
            $type     = get_query_var( 'sr_ptype', '' );

            // these should correlate with what the api expects as filters
            $listing_params = array(
                "type"     => $type,
                "minbeds"   => $minbeds,
                "maxbeds"   => $maxbeds,
                "minbaths"  => $minbaths,
                "maxbaths"  => $maxbaths,
                "minprice" => $minprice,
                "maxprice" => $maxprice
            );

            $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $listing_params );
            $content .= do_shortcode( '[sr_search_form]' );
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
            // foreach ( $listing_params as $key=>$value ) {
            //     echo 'param: ' . $key . ' value: ' . $value . $br;
            // }
            // the simply rets api helper takes care of retrieving, parsing, and generating
            // the markup for the listings to be shown on this page based off of the sr_filters
            // saved for this post
            $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $listing_params );
            $content = $content . $listings_content;

            return $content;
        }
        return $content;
    }



    public static function srCreateDynamicPost( $posts ) {

        // if we catch a singlelisting query, create a new post on the fly
        global $wp_query;
        if( $wp_query->query['sr-listings'] == "sr-single" ) {
            $post_id    = get_query_var( 'listing_id' );
            $post_addr  = get_query_var( 'listing_title', 'none' );
            $post_price = get_query_var( 'listing_price', '' );

            $listing_USD = '$' . number_format( $post_price );
            $title_normalize = "background-color:transparent;padding:0px;";
            $post_title = "{$post_addr} - <span style='{$title_normalize}'><i>{$listing_USD}</i></span>";

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
                "post_type"      => "sr-listings"
            );

            $posts = array( $post );
            return $posts;
        }
        // if we catch a search results query, create a new post on the fly
        if( $wp_query->query['sr-listings'] == "sr-search" ) {
            $post_id = get_query_var( 'sr_minprice', '9998' );

            $post = (object)array(
                "ID"             => $post_id,
                "comment_count"  => 0,
                "comment_status" => "closed",
                "ping_status"    => "closed",
                "post_author"    => 1,
                "post_name"      => "Search Listings",
                "post_date"      => date("c"),
                "post_date_gmt"  => gmdate("c"),
                "post_parent"    => 0,
                "post_status"    => "publish",
                "post_title"     => "Search Results",
                "post_type"      => "sr-listings"
            );

		    $posts = array( $post );
            return $posts;
        }

        return $posts;
    }

}
?>
