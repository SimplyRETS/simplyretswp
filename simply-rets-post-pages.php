<?php

/*
 *
 * simply-rets-post-pages.php - Copyright (C) 2014-2015 SimplyRETS
 * This file provides the logic for the simply-rets custom post type pages.
 *
*/


/* Code starts here */
add_action('init',                  array('SimplyRetsCustomPostPages', 'srInitRewriteRules'));
add_action('init',                  array('SimplyRetsCustomPostPages', 'srRegisterPostType'));
add_filter('comments_template',     array('SimplyRetsCustomPostPages', 'srClearComments'));
add_filter('single_template',       array('SimplyRetsCustomPostPages', 'srLoadPostTemplate'));
add_filter('the_content',           array('SimplyRetsCustomPostPages', 'srPostDefaultContent'));
add_filter('the_posts',             array('SimplyRetsCustomPostPages', 'srCreateDynamicPost'));
add_action('add_meta_boxes',        array('SimplyRetsCustomPostPages', 'postFilterMetaBox'));
add_action('add_meta_boxes',        array('SimplyRetsCustomPostPages', 'postTemplateMetaBox'));
add_action('save_post',             array('SimplyRetsCustomPostPages', 'postFilterMetaBoxSave'));
add_action('save_post',             array('SimplyRetsCustomPostPages', 'postTemplateMetaBoxSave'));
add_action('admin_init',            array('SimplyRetsCustomPostPages', 'postFilterMetaBoxCss'));
add_action('admin_enqueue_scripts', array('SimplyRetsCustomPostPages', 'postFilterMetaBoxJs'));
// ^TODO: load css/js only on sr-listings post type pages when admin
//  and move these into a constructor
add_action('sr_update_adv_search_meta_action', array('SimplyRetsApiHelper', 'srUpdateAdvSearchOptions'));

add_filter("rewrite_rules_array", array("SimplyRetsCustomPostPages", "srAddRewriteRules"));

class SimplyRetsCustomPostPages {

    public static function srActivate() {
        SimplyRetsCustomPostPages::srRegisterPostType();
        SimplyRetsApiHelper::srUpdateAdvSearchOptions();

        wp_schedule_event(
            get_option('sr_adv_search_meta_timestamp')
            , 'daily'
            , 'sr_update_adv_search_options_action'
        );

        add_option( 'sr_api_name', 'simplyrets' );
        add_option( 'sr_api_key', 'simplyrets' );
        add_option( 'sr_listing_gallery', 'fancy' );
        add_option( 'sr_show_leadcapture', true );
        add_option( 'sr_search_map_position', 'map_above');

        add_option( 'sr_permalink_structure', 'pretty' );

        add_option('sr_show_admin_message', 1);
        add_option('sr_demo_page_created', false);

        add_option('sr_office_on_thumbnails', false);
        add_option('sr_thumbnail_idx_image', '');

        flush_rewrite_rules();
    }

    public static function srDeactivate() {
        delete_option('sr_show_admin_message');
        delete_option('sr_demo_page_created');
        flush_rewrite_rules();
    }

    public static function srInitRewriteRules() {
        $rules = get_option('rewrite_rules');

        add_rewrite_tag('%listings%', '([^&]+)');

        // 'pretty_extra' permalinks
        add_rewrite_rule(
            'listings/([^&]+)/([^&]+)/([^&]+)/([^&]+)/([^&]+)/?$',
            'index.php?sr-listings=sr-single&sr_city=$matches[1]&sr_state=$matches[2]&sr_zip=$matches[3]&listing_id=$matches[5]&listing_title=$matches[4]',
            'top'
        );

        // 'pretty' permalinks
        add_rewrite_rule(
            'listings/(.*)/(.*)?$',
            'index.php?sr-listings=sr-single&listing_id=$matches[1]&listing_title=$matches[2]',
            'top'
        );

        if(!isset($rules['%listings%'])) {
            flush_rewrite_rules();
        }

        return;
    }

    public static function srAddRewriteRules($incoming) {
		$rules = array(
            'listings/([^&]+)/([^&]+)/([^&]+)/([^&]+)/([^&]+)/?$'
            => 'index.php?sr-listings=sr-single&sr_city=$matches[1]&sr_state=$matches[2]&sr_zip=$matches[3]&listing_title=$matches[4]&listing_id=$matches[5]',
			"listings/(.*)/(.*)?$"
                => "index.php?sr-listings=sr-single&listing_id=$matches[1]&listing_title=$matches[2]"
		);
        return $incoming + $rules;
    }

    public static function onActivationNotice () {
        return (
            '<div id="setting-error-settings_updated" class="updated settings-error notice">'.
            '<p>' .
            '  <span>'.
            '    <form id="admin-msg" method="post" action="options-general.php?page=simplyrets-admin.php">' .
            '      <input type="hidden" name="sr_create_demo_page" value="1" />' .
            '      <strong>SimplyRETS: </strong>' .
            '      <button class="sr-admin-msg-btn" type="submit">Click here</button>' .
            '      to set up a demo page!' .
            '    </form>' .
            '  </span>' .
            '  <span style="float:right">' .
            '    <form id="admin-dismiss" method="post" action="options-general.php?page=simplyrets-admin.php">' .
            '      <input type="hidden" name="sr_dismiss_admin_msg" value="1" />' .
            '      <button class="sr-admin-msg-btn" type="submit">Dismiss</button>' .
            '    </form>' .
            '  </span>' .
            '</p>' .
            '</div>'
        );
    }

    public static function srPluginSettingsLink( $links ) {
        $settings_link =
            '<a href="' . admin_url( 'options-general.php?page=simplyrets-admin.php' ) . '">'
            . __( 'Settings', 'SimplyRETS' )
            . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    // Create our Custom Post Type
    public static function srRegisterPostType() {
        $labels = array(
            'name'          => __( 'SimplyRETS' ),
            'singular_name' => __( 'SimplyRETS Page' ),
            'add_new_item'  => __( 'New SimplyRETS Page' ),
            'edit_item'     => __( 'Edit SimplyRETS Page' ),
            'new_item'      => __( 'New SimplyRETS Page' ),
            'view_item'     => __( 'View SimplyRETS Page' ),
            'all_items'     => __( 'All SimplyRETS Pages' ),
            'search_items'  => __( 'Search SimplyRETS Pages' ),
        );
        $args = array(
            'public'          => true,
            'has_archive'     => false,
            'labels'          => $labels,
            'description'     => 'SimplyRETS property listings pages',
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
        $vars[] = "limit";
        $vars[] = "offset";
        $vars[] = "advanced";
        $vars[] = "status";
        // sr prefixes are for the search form
        $vars[] = "sr_minprice";
        $vars[] = "sr_maxprice";
        $vars[] = "sr_minbeds";
        $vars[] = "sr_maxbeds";
        $vars[] = "sr_minbaths";
        $vars[] = "sr_maxbaths";
        $vars[] = "sr_q";
        $vars[] = "sr_keywords";
        $vars[] = "sr_type";
        $vars[] = "sr_ptype";
        $vars[] = "sr_agent";
        $vars[] = "sr_brokers";
        $vars[] = "sr_sort";
        $vars[] = "water";
        // post type
        $vars[] = "sr-listings";
        // advanced search form parameters
        $vars[] = "sr_lotsize";
        $vars[] = "sr_area";
        $vars[] = "sr_cities";
        $vars[] = "sr_neighborhoods";
        $vars[] = "sr_amenities";
        $vars[] = "sr_features";
        // multi-mls
        $vars[] = "vendor";
        $vars[] = "sr_vendor";
        // settings
        $vars[] = "sr_map_position";
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
                            , plugins_url( 'assets/js/simply-rets-admin.js', __FILE__ )
                            , array( 'jquery' )
        );
        wp_enqueue_script( 'simply-rets-admin-js' );
    }

    public static function postFilterMetaBoxCss() {
        wp_register_style( 'simply-rets-admin-css', plugins_url( 'assets/css/simply-rets-admin.css', __FILE__ ) );
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
        $limit_filter     = "";

        $sr_filters = get_post_meta( $post->ID, 'sr_filters', true);

        // TODO: Once all the query parameters are finalized, we can generate
        // most of the markup below.
        ?>
        <div class="current-filters">
            <span class="filter-add">
              <?php _e( 'Add new Filter' ); ?>
            </span>
            <select name="sr-filter-select" id="sr-filter-select">
                <option> -- Select a Filter -- </option>
                <option val="minprice-option">  Minimum Price      </option>
                <option val="maxprice-option">  Maximum Price      </option>
                <option val="minbeds-option">   Minimum Beds       </option>
                <option val="maxbeds-option">   Maximum Beds       </option>
                <option val="minbaths-option">  Minimum Bathrooms  </option>
                <option val="maxbaths-option">  Maximum Bathrooms  </option>
                <option val="agentid-option">   Listing Agent      </option>
                <option val="type-option">      Listing Type       </option>
                <option val="limit-option">     Amount of listings </option>
            </select>
            <hr>
        </div>

        <div class="sr-meta-inner">

          <!-- Min Price Filter -->
          <div class="sr-filter-input" id="sr-min-price-span">
            <label for="sr-min-price-input">
              Minimum Price:
            </label>
            <input id="minprice" type="number" name="sr_filters[minprice]"
              value="<?php print_r( $min_price_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Max Price Filter -->
          <div class="sr-filter-input" id="sr-max-price-span">
            <label for="sr-max-price-input">
              Maximum Price:
            </label>
            <input id="maxprice" type="number" name="sr_filters[maxprice]"
              value="<?php print_r( $max_price_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Min Bed Filter -->
          <div class="sr-filter-input" id="sr-min-bed-span">
            <label for="sr-min-bed-input">
              Minimum Bedrooms:
            </label>
            <input id="minbeds" type="number" name="sr_filters[minbeds]"
              value="<?php print_r( $min_bed_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Max Bed Filter -->
          <div class="sr-filter-input" id="sr-max-bed-span">
            <label for="sr-max-bed-input">
              Maximum Bedrooms:
            </label>
            <input id="maxbeds" type="number" name="sr_filters[maxbeds]"
              value="<?php print_r( $max_bed_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Min Baths Filter -->
          <div class="sr-filter-input" id="sr-min-bath-span">
            <label for="sr-min-bath-input">
              Minimum Bathrooms:
            </label>
            <input id="minbaths" type="number" name="sr_filters[minbaths]"
              value="<?php print_r( $min_bath_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Max Baths Filter -->
          <div class="sr-filter-input" id="sr-max-bath-span">
            <label for="sr-max-bath-input">
              Maximum Bathrooms:
            </label>
            <input id="maxbaths" type="number" name="sr_filters[maxbaths]"
              value="<?php print_r( $max_bath_filisting_typelter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

          <!-- Agent ID Filter -->
          <div class="sr-filter-input" id="sr-listing-agent-span">
            <label for="sr-listing-agent-input">
              Listing Agent ID:
            </label>
            <input id="agent" type="number" name="sr_filters[agent]"
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

          <!-- Response Limit Filter -->
          <div class="sr-filter-input" id="sr-limit-span">
            <label for="sr-limit-input">
              Amount of listings to show:
            </label>
            <input id="limit" type="text" name="sr_filters[limit]"
              value="<?php print_r( $limit_filter ); ?>"/>
            <span class="sr-remove-filter">Remove Filter</span>
          </div>

        </div>
        <?php

        // echo '<br>Current filters: <br>'; print_r( $sr_filters );
        // echo '<br>';
        // ^TODO: Remove degbug

        // on page load, if there are any filters already saved, load them,
        // show the input field, and remove the option from the dropdown
        if( !is_array($sr_filters) ) {
            $sr_filters = array();
        }
        foreach( $sr_filters as $key=>$val ) {
            if ( $val != '' ) {
                ?>
                <script>
                    var filterArea = jQuery('.current-filters');
                    var key = jQuery(<?php print_r( $key ); ?>);
                    var val = <?php echo json_encode( $val ); ?>;
                    var parent = key.parent();

                    key.val(val); // set value to $key
                    filterArea.append(parent); //append div to filters area
                    parent.show(); //display: block the div since it has a value

                </script>
                <?php
            }
        }
    }

    public static function postFilterMetaBoxSave( $post_id ) {
        if( isset($_POST['sr_meta_box_nonce']) ) {
            $current_nonce = $_POST['sr_meta_box_nonce'];
        } else {
            $current_nonce = NULL;
        }
        $is_autosaving = wp_is_post_autosave( $post_id );
        $is_revision   = wp_is_post_revision( $post_id );
        $valid_nonce   = ( isset( $current_nonce ) && wp_verify_nonce( $current_nonce, basename( __FILE__ ) ) ) ? 'true' : 'false';

        if ( $is_autosaving || $is_revision || !$valid_nonce ) {
            return;
        }

        if( isset($_POST['sr_filters']) ) {
            $sr_filters = $_POST['sr_filters'];
        } else {
            $sr_filters = NULL;
        }
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
        if( isset($_POST['sr_template_meta_nonce']) ) {
            $current_nonce = $_POST['sr_template_meta_nonce'];
        } else {
            $current_nonce = NULL;
        }
        $is_autosaving = wp_is_post_autosave( $post_id );
        $is_revision   = wp_is_post_revision( $post_id );
        $valid_nonce   = ( isset( $current_nonce ) && wp_verify_nonce( $current_nonce, basename( __FILE__ ) ) ) ? 'true' : 'false';

        if ( $is_autosaving || $is_revision || !$valid_nonce ) {
            return;
        }

        if( isset($_POST['sr_page_template']) ) {
            $sr_page_template = $_POST['sr_page_template'];
        } else {
            $sr_page_template = NULL;
        }
        update_post_meta( $post_id, 'sr_page_template', $sr_page_template );
    }


    // TODO: not sure if this is entirely necessary...at one time it was
    function srClearComments() {
        global $post;
        if ( !( is_singular() && ( have_comments() || 'open' == $post->comment_status ) ) ) {
            return;
        }
        if ( $post->post_type == 'sr-listings') {
            return dirname(__FILE__) . '/simply-rets-comments-template.php';
        }
        return;
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
        $page_name = get_query_var('sr-listings');
        $sr_post_type = 'sr-listings';

        if (get_query_var('listing_id') != NULL AND get_query_var('listing_title') != NULL) {

            $listing_id = get_query_var('listing_id');
            $vendor     = get_query_var('sr_vendor', '');

            $add_rooms  = get_option('sr_additional_rooms') ? 'rooms' : '';

            $params = http_build_query(
                array(
                    "vendor" => $vendor,
                    "include" => $add_rooms
                )
            );

            $resource = "/{$listing_id}?{$params}";
            $content .= SimplyRetsApiHelper::retrieveListingDetails( $resource );
            return $content;
        }

        if ( $page_name == 'sr-search' ) {
            $minbeds  = get_query_var( 'sr_minbeds',  '' );
            $maxbeds  = get_query_var( 'sr_maxbeds',  '' );
            $minbaths = get_query_var( 'sr_minbaths', '' );
            $maxbaths = get_query_var( 'sr_maxbaths', '' );
            $minprice = get_query_var( 'sr_minprice', '' );
            $maxprice = get_query_var( 'sr_maxprice', '' );
            $keywords = get_query_var( 'sr_keywords', '' )
                      . get_query_var( 'sr_q',        '' );
            $brokers  = get_query_var( 'sr_brokers', '' );
            $water    = get_query_var( 'water', '' );
            /** Pagination */
            $limit    = get_query_var( 'limit', '' );
            $offset   = get_query_var( 'offset', '' );
            /** Advanced Search */
            $advanced  = get_query_var( 'advanced', '' );
            $status    = get_query_var( 'status', '' );
            $lotsize   = get_query_var( 'sr_lotsize', '' );
            $area      = get_query_var( 'sr_area', '' );
            $sort      = get_query_var( 'sr_sort', '' );
            /** multi mls */
            $vendor    = get_query_var('sr_vendor', '');

            $map_position = get_query_var('sr_map_position',
                                          get_option('sr_search_map_position'));


            /**
             * Format the 'type' parameter.
             * Note that the 'type' might come in as an Array or a
             * String.  For strings, we split on ";" to support
             * multiple property types only if the string is not
             * empty.  Arrays are concated into multiple type=
             * parameters.
             */
            $p_types = isset($_GET['sr_ptype']) ? $_GET['sr_ptype'] : '';
            $ptypes_string = '';
            if(!is_array($p_types) && !empty($p_types)) {
                if(strpos($p_types, ";") !== FALSE) {
                    $p_types = explode(';', $p_types);
                } else {
                    $ptypes_string = "&type=$p_types";
                }
            }
            if(is_array($p_types) && !empty($p_types)) {
                foreach((array)$p_types as $key => $ptype) {
                    $final = trim($ptype);
                    $ptypes_string .= "&type=$final";
                }
            }


            /**
             * Format the 'status' parameter.
             * Note that the 'status' might come in as an Array or a
             * String.  For strings, we split on ";" to support
             * multiple statuses only if the string is not empty.
             * Arrays are concated into multiple status= parameters.
             *
             * NOTE: it is important to not send an empty status
             * parameter, for example "status=" to the API, as it will
             * interpret it as Active, whereas _no_ status parameter
             * is Active and Pending.
             */
            $statuses = isset($_GET['sr_status']) ? $_GET['sr_status'] : $status;
            $statuses_string = '';

            if(!is_array($statuses) && !empty($statuses)) {
                if(strpos($statuses, ";") !== FALSE) {
                    $statuses = explode(';', $statuses);
                } else {
                    $statuses_string = "&status=$statuses";
                }
            }
            if(is_array($statuses) && !empty($statuses)) {
                foreach((array)$statuses as $key => $stat) {
                    $final = trim($stat);
                    $statuses_string .= "&status=$final";
                }
            }


            /**
             * The loops below check if the short-code has multiple
             * values for any query parameter. Eg, multiple cities.
             * Since they support multiple, we do the following for
             * each:
             *
             *
             * - Split string on ';' delimeter (which returns a single
                 item array if there is none)
             *
             * - Make each array item into a query (eg, &status=Closed)
             *
             * - Concat them together (eg,&status=Active&status=Closed)
             */

            $features = isset($_GET['sr_features']) ? $_GET['sr_features'] : '';
            if(!empty($features)) {
                foreach((array)$features as $key => $feature) {
                    $features_string .= "&features=$feature";
                }
            }

            $cities = isset($_GET['sr_cities']) ? $_GET['sr_cities'] : '';
            if(!empty($cities)) {
                foreach((array)$cities as $key => $city) {
                    $cities_string .= "&cities=$city";
                }
            }

            $counties = isset($_GET['sr_counties']) ? $_GET['sr_counties'] : '';
            if(!empty($counties)) {
                foreach((array)$counties as $key => $county) {
                    $counties_string .= "&counties=$county";
                }
            }

            $agents = isset($_GET['sr_agent']) ? $_GET['sr_agent'] : '';
            if(!empty($agents)) {
                foreach((array)$agents as $key => $agent) {
                    $agents_string .= "&agent=$agent";
                }
            }

            $neighborhoods = isset($_GET['sr_neighborhoods']) ? $_GET['sr_neighborhoods'] : '';
            if(!empty($neighborhoods)) {
                foreach((array)$neighborhoods as $key => $neighborhood) {
                    $neighborhoods_string .= "&neighborhoods=$neighborhood";
                }
            }
            $amenities = isset($_GET['sr_amenities']) ? $_GET['sr_amenities'] : '';
            if(!empty($amenities)) {
                foreach((array)$amenities as $key => $amenity) {
                    $amenities_string .= "&amenities=$amenity";
                }
            }

            /**
             * Make a new array with all query parameters.
             *
             * Note: We're only using params that weren't transformed
             * above.
             */

            $listing_params = array(
                "q"         => $keywords,
                "brokers"   => $brokers,
                "minbeds"   => $minbeds,
                "maxbeds"   => $maxbeds,
                "minbaths"  => $minbaths,
                "maxbaths"  => $maxbaths,
                "minprice"  => $minprice,
                "maxprice"  => $maxprice,
                "water"     => $water,

                /** Pagination */
                "limit"     => $limit,
                "offset"    => $offset,

                /** Advanced Search */
                "lotsize"   => $lotsize,
                "area"      => $area,
                "sort"      => $sort,

                /** Multi MLS */
                "vendor"    => $vendor,

                /**
                   Settings that need to be propogated through to
                   pagination. It's a bit awkward because these aren't
                   SimplyRETS query parameters, but it's the easiest
                   way to get them back on the other side right now.
                */
                "map_position" => $map_position
            );

            $settings = array(
                "map_position" => $map_position
            );

            foreach( $listing_params as $param => $val ) {
                if( !$val == '' ) {
                    $filters_string .= ' ' . $param . '=\'' . $val . '\'';
                }
            }

            /**
             * Make advanced search page with new query
             */
            if( !$advanced || !$advanced == "true" ) {
              $qs = '?'
                  . http_build_query( array_filter( $listing_params ) )
                  . $features_string
                  . $cities_string
                  . $counties_string
                  . $neighborhoods_string
                  . $agents_string
                  . $ptypes_string
                  . $statuses_string
                  . $amenities_string;

              $qs = str_replace(' ', '%20', $qs);
              $listings_content = SimplyRetsApiHelper::retrieveRetsListings($qs, $settings);
              $content .= do_shortcode( "[sr_search_form  $filters_string]");
              $content .= $listings_content;
              return $content;

            /**
             * Make regular search page with new query
             */
            } else {

              $qs = '?';
              $qs .= http_build_query( array_filter( $listing_params ) );
              $qs .= $features_string;
              $qs .= $cities_string;
              $qs .= $counties_string;
              $qs .= $agents_string;
              $qs .= $ptypes_string;
              $qs .= $neighborhoods_string;
              $qs .= $statuses_string;
              $qs .= $amenities_string;

              $qs = str_replace(' ', '%20', $qs);
              $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $qs );

              $content .= do_shortcode( "[sr_search_form  advanced='True' $filters_string]");
              $content .= $listings_content;
              return $content;
            }

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

        if( $wp_query->query['sr-listings'] == 'sr-search'
            AND array_key_exists("listing_id", $wp_query->query_vars)
            AND array_key_exists("listing_title", $wp_query->query_vars)
            OR $wp_query->query['sr-listings'] == "sr-single"
        ) {

            $post_id    = urldecode(get_query_var( 'listing_id', '' ));
            $post_addr  = urldecode(get_query_var( 'listing_title', '' ));
            $post_price = urldecode(get_query_var( 'listing_price', '' ));

            $listing_USD = $post_price == '' ? '' : '$' . number_format( $post_price );
            $title_normalize = "background-color:transparent;padding:0px;";
            $post_title = "{$post_addr} <span style='{$title_normalize}'><small><i> {$listing_USD}</i></small></span>";

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

            return $posts + array($post);
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

            return $posts + array($post);
        }

        return $posts;
    }

}
?>
