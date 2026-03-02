<?php

/*
 *
 * simply-rets-post-pages.php - Copyright (C) 2014-2024 SimplyRETS
 * This file provides the logic for the simply-rets custom post type pages.
 *
*/


/* Code starts here */
add_action('init',                  array('SimplyRetsSetup', 'srInitRewriteRules'));
add_action('init',                  array('SimplyRetsSetup', 'srRegisterPostType'));
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
add_action('sr_update_adv_search_meta_action', array('SimplyRetsApiClient', 'srUpdateAdvSearchOptions'));

add_filter("rewrite_rules_array", array("SimplyRetsSetup", "srAddRewriteRules"));

class SimplyRetsCustomPostPages {


    public static function onActivationNotice() {
        $sr_dismiss_admin_msg_nonce_action = 'sr_dismiss_admin_msg_nonce_action';
        $sr_dismiss_admin_msg_nonce_field = 'sr_dismiss_admin_msg_nonce_field';

        $notice = '<div id="setting-error-settings_updated" class="updated settings-error notice">'
            . '<p>'
            . '  <span>'
            . '    <form id="admin-msg" method="post" action="options-general.php?page=simplyrets-admin.php">'
            . '      <input type="hidden" name="sr_create_demo_page" value="1" />'
            . '      <strong>SimplyRETS: </strong>'
            . '      <button class="sr-admin-msg-btn" type="submit">Click here</button> to set up a demo page!'
            . '    </form>'
            . '  </span>'
            . '  <span style="float:right">'
            . '    <form id="admin-dismiss" method="post" action="options-general.php?page=simplyrets-admin.php">'
            .        wp_nonce_field($sr_dismiss_admin_msg_nonce_action, $sr_dismiss_admin_msg_nonce_field)
            . '      <input type="hidden" name="sr_dismiss_admin_msg" value="1" />'
            . '      <button class="sr-admin-msg-btn" type="submit">Dismiss</button>'
            . '    </form>'
            . '  </span>'
            . '</p>'
            . '</div>';

        return $notice;
    }

    public static function srPluginSettingsLink($links) {
        $settings_link = '<a href="'
            . admin_url('options-general.php?page=simplyrets-admin.php')
            . '">Settings</a>';

        array_unshift($links, $settings_link);
        return $links;
    }


    public static function postFilterMetaBox() {
        add_meta_box(
            'sr-meta-box-filter',
            'Filter Results on This Page',
            array('SimplyRetsCustomPostPages', 'postFilterMetaBoxMarkup'),
            'sr-listings',
            'normal',
            'high'
        );
    }

    public static function postTemplateMetaBox() {
        add_meta_box(
            'sr-template-meta-box',
            'Page Template',
            array('SimplyRetsCustomPostPages', 'postTemplateMetaBoxMarkup'),
            'sr-listings',
            'side',
            'core'
        );
    }

    public static function postFilterMetaBoxJs() {
        wp_register_script(
            'simply-rets-admin-js',
            plugins_url('assets/js/simply-rets-admin.js', __FILE__),
            array('jquery'),
            SIMPLYRETSWP_VERSION,
            array("in_footer" => false)
        );
        wp_enqueue_script('simply-rets-admin-js');
    }

    public static function postFilterMetaBoxCss() {
        wp_register_style(
            'simply-rets-admin-css',
            plugins_url('assets/css/simply-rets-admin.css', __FILE__),
            array(),
            SIMPLYRETSWP_VERSION
        );
        wp_enqueue_style('simply-rets-admin-css');
    }

    public static function postFilterMetaBoxMarkup($post) {
        wp_nonce_field(basename(__FILE__), 'sr_meta_box_nonce');
        $min_price_filter = "";
        $max_price_filter = "";
        $min_bed_filter   = "";
        $max_bed_filter   = "";
        $min_bath_filter  = "";
        $max_bath_filter  = "";
        $agent_id_filter  = "";
        $listing_type_filter  = "";
        $limit_filter     = "";

        $sr_filters = get_post_meta($post->ID, 'sr_filters', true);

        // TODO: Once all the query parameters are finalized, we can generate
        // most of the markup below.
?>
        <div class="current-filters">
            <span class="filter-add">
                Add new filter
            </span>
            <select name="sr-filter-select" id="sr-filter-select">
                <option> -- Select a Filter -- </option>
                <option val="minprice-option"> Minimum Price </option>
                <option val="maxprice-option"> Maximum Price </option>
                <option val="minbeds-option"> Minimum Beds </option>
                <option val="maxbeds-option"> Maximum Beds </option>
                <option val="minbaths-option"> Minimum Bathrooms </option>
                <option val="maxbaths-option"> Maximum Bathrooms </option>
                <option val="agentid-option"> Listing Agent </option>
                <option val="type-option"> Listing Type </option>
                <option val="limit-option"> Amount of listings </option>
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
                    value="<?php echo esc_attr($min_price_filter); ?>" />
                <span class="sr-remove-filter">Remove Filter</span>
            </div>

            <!-- Max Price Filter -->
            <div class="sr-filter-input" id="sr-max-price-span">
                <label for="sr-max-price-input">
                    Maximum Price:
                </label>
                <input id="maxprice" type="number" name="sr_filters[maxprice]"
                    value="<?php echo esc_attr($max_price_filter); ?>" />
                <span class="sr-remove-filter">Remove Filter</span>
            </div>

            <!-- Min Bed Filter -->
            <div class="sr-filter-input" id="sr-min-bed-span">
                <label for="sr-min-bed-input">
                    Minimum Bedrooms:
                </label>
                <input id="minbeds" type="number" name="sr_filters[minbeds]"
                    value="<?php echo esc_attr($min_bed_filter); ?>" />
                <span class="sr-remove-filter">Remove Filter</span>
            </div>

            <!-- Max Bed Filter -->
            <div class="sr-filter-input" id="sr-max-bed-span">
                <label for="sr-max-bed-input">
                    Maximum Bedrooms:
                </label>
                <input id="maxbeds" type="number" name="sr_filters[maxbeds]"
                    value="<?php echo esc_attr($max_bed_filter); ?>" />
                <span class="sr-remove-filter">Remove Filter</span>
            </div>

            <!-- Min Baths Filter -->
            <div class="sr-filter-input" id="sr-min-bath-span">
                <label for="sr-min-bath-input">
                    Minimum Bathrooms:
                </label>
                <input id="minbaths" type="number" name="sr_filters[minbaths]"
                    value="<?php echo esc_attr($min_bath_filter); ?>" />
                <span class="sr-remove-filter">Remove Filter</span>
            </div>

            <!-- Max Baths Filter -->
            <div class="sr-filter-input" id="sr-max-bath-span">
                <label for="sr-max-bath-input">
                    Maximum Bathrooms:
                </label>
                <input id="maxbaths" type="number" name="sr_filters[maxbaths]"
                    value="<?php echo esc_attr($max_bath_filter); ?>" />
                <span class="sr-remove-filter">Remove Filter</span>
            </div>

            <!-- Agent ID Filter -->
            <div class="sr-filter-input" id="sr-listing-agent-span">
                <label for="sr-listing-agent-input">
                    Listing Agent ID:
                </label>
                <input id="agent" type="number" name="sr_filters[agent]"
                    value="<?php echo esc_attr($agent_id_filter); ?>" />
                <span class="sr-remove-filter">Remove Filter</span>
            </div>

            <!-- Listing Type Filter -->
            <div class="sr-filter-input" id="sr-listing-type-span">
                <label for="sr-listing-type-input">
                    Property Type:
                </label>
                <input id="type" type="text" name="sr_filters[type]"
                    value="<?php echo esc_attr($listing_type_filter); ?>" />
                <span class="sr-remove-filter">Remove Filter</span>
            </div>

            <!-- Response Limit Filter -->
            <div class="sr-filter-input" id="sr-limit-span">
                <label for="sr-limit-input">
                    Amount of listings to show:
                </label>
                <input id="limit" type="text" name="sr_filters[limit]"
                    value="<?php echo esc_attr($limit_filter); ?>" />
                <span class="sr-remove-filter">Remove Filter</span>
            </div>

        </div>
        <?php

        // on page load, if there are any filters already saved, load them,
        // show the input field, and remove the option from the dropdown
        if (!is_array($sr_filters)) {
            $sr_filters = array();
        }
        foreach ($sr_filters as $key => $val) {
            if ($val != '') {
        ?>
                <script>
                    var filterArea = jQuery('.current-filters');
                    var key = jQuery(<?php echo esc_js($key); ?>);
                    var val = <?php echo wp_json_encode($val); ?>;
                    var parent = key.parent();

                    key.val(val); // set value to $key
                    filterArea.append(parent); //append div to filters area
                    parent.show(); //display: block the div since it has a value
                </script>
<?php
            }
        }
    }

    public static function postFilterMetaBoxSave($post_id) {
        if (isset($_POST['sr_meta_box_nonce'])) {
            $current_nonce = $_POST['sr_meta_box_nonce'];
        } else {
            $current_nonce = NULL;
        }

        $is_autosaving = wp_is_post_autosave($post_id);
        $is_revision   = wp_is_post_revision($post_id);
        $valid_nonce   = (isset($current_nonce) && wp_verify_nonce($current_nonce, basename(__FILE__))) ? 'true' : 'false';

        if ($is_autosaving || $is_revision || !$valid_nonce) {
            return;
        }

        if (isset($_POST['sr_filters'])) {
            $sr_filters = sanitize_text_field(wp_unslash($_POST['sr_filters']));
            return update_post_meta($post_id, 'sr_filters', $sr_filters);
        }
    }

    public static function postTemplateMetaBoxMarkup($post) {
        wp_nonce_field(basename(__FILE__), 'sr_template_meta_nonce');

        $current_template = get_post_meta($post->ID, 'sr_page_template', true);
        $template_options = get_page_templates();

        $box_label = '<label class="sr-filter-meta-box" for="sr_page_template">Page Template</label><br />';
        $box_select = '<select name="sr_page_template" id="sr-page-template-select">';
        $box_default_option = '<option value="">Default Template</option>';
        $box_option = '';

        echo esc_html($box_label);

        foreach ($template_options as $name => $file) {
            if ($current_template == $file) {
                $box_option .= '<option value="' . $file . '" selected="selected">' . $name . '</option>';
            } else {
                $box_option .= '<option value="' . $file . '">' . $name . '</option>';
            }
        }

        echo esc_html($box_select);
        echo esc_html($box_default_option);
        echo esc_html($box_option);
        echo '</select>';
    }

    public static function postTemplateMetaBoxSave($post_id) {
        if (isset($_POST['sr_template_meta_nonce'])) {
            $current_nonce = $_POST['sr_template_meta_nonce'];
        } else {
            $current_nonce = NULL;
        }

        $is_autosaving = wp_is_post_autosave($post_id);
        $is_revision   = wp_is_post_revision($post_id);
        $valid_nonce   = (isset($current_nonce) && wp_verify_nonce($current_nonce, basename(__FILE__))) ? 'true' : 'false';

        if ($is_autosaving || $is_revision || !$valid_nonce) {
            return;
        }

        if (isset($_POST['sr_page_template'])) {
            $sr_page_template = sanitize_text_field(wp_unslash($_POST['sr_page_template']));
            return update_post_meta($post_id, 'sr_page_template', $sr_page_template);
        }
    }


    // TODO: not sure if this is entirely necessary...at one time it was
    public static function srClearComments() {
        global $post;
        if (!(is_singular() && (have_comments() || 'open' == $post->comment_status))) {
            return;
        }
        if ($post->post_type == 'sr-listings') {
            return dirname(__FILE__) . '/simply-rets-comments-template.php';
        }
        return;
    }


    public static function srLoadPostTemplate($single) {
        $query_object = get_queried_object();
        $sr_post_type = 'sr-listings';

        /**
         * If current theme is a Block Theme, return original arg. The
         * 'Single' block template will be used by default, or the user
         * can create a custom template for 'Single: SimplyRETS Page'
         * in the Block Theme Editor settings.
         */
        if (function_exists("wp_is_block_theme") && wp_is_block_theme()) {
            return $single;
        }

        // If this isn't a SimplyRETS page, return default template
        if ($query_object->post_type !== $sr_post_type) {
            return $single;
        }

        // The user can use a custom template if the file name is:
        // single-sr-listings.php
        $default_templates    = array();
        $default_templates[]  = "single-{$query_object->post_type}.php";
        $default_templates[]  = "page.php";

        // If the user is using a "SimplyRETS Page", they may select a
        // specific template from the current theme.
        $page_template = get_post_meta($query_object->ID, 'sr_page_template', true);
        if (!empty($page_template)) {
            $default_templates = $page_template;
        }

        // Resolve path to the template
        $new_template = locate_template($default_templates, false);

        return $new_template;
    }

    public static function srPostDefaultContent($content) {
        require_once(plugin_dir_path(__FILE__) . 'simply-rets-api-client.php');
        require_once(plugin_dir_path(__FILE__) . 'simply-rets-api-helper.php');
        $post_type = get_post_type();
        $page_name = get_query_var('sr-listings');
        $sr_post_type = 'sr-listings';

        // Single listing page
        if (get_query_var('listing_id') != NULL and get_query_var('listing_title') != NULL) {

            $listing_id = get_query_var('listing_id');
            $vendor     = get_query_var('sr_vendor', '');

            $add_rooms  = get_option('sr_additional_rooms') ? 'rooms' : '';

            $params = http_build_query(
                array(
                    "vendor" => $vendor,
                    "include" => $add_rooms
                )
            );

            $resource = "/{$listing_id}?{$params}&include=compliance";
            $content .= SimplyRetsApiHelper::retrieveListingDetails($resource);
            return $content;
        }

        // Open houses page
        if ($page_name === "sr-openhouses") {
            global $wp_query;

            $parameters = $wp_query->query;
            $searchParameters = array_intersect_key(
                $parameters,
                array_flip(
                    array_merge(
                        preg_grep('/sr_.*/', array_keys($parameters)),
                        preg_grep('/(limit|offset)/', array_keys($parameters))
                    )
                )
            );

            $shortcodeAttributes = array_combine(
                preg_replace("/sr_/", "", array_keys($searchParameters)),
                array_values($searchParameters)
            );

            $nextAttributes = "";
            foreach ($shortcodeAttributes as $name => $value) {
                $nextValue = is_array($value) ? implode("; ", $value) : $value;
                $nextAttributes .= $name . '="' . $nextValue . '" ';
            }

            return do_shortcode("[sr_openhouses $nextAttributes]");
        }

        // Search results page
        if ($page_name == 'sr-search') {
            $parsed_data = SimplyRetsQueryParser::parseSearchParams();

            $listings_content = SimplyRetsApiHelper::retrieveRetsListings($parsed_data['qs'], $parsed_data['settings']);
            $content .= do_shortcode("[sr_search_form " . $parsed_data['filters_string'] . "]");
            $content .= $listings_content;

            return $content;
        }

        // SimplyRETS Page (deprecated)
        if ($post_type == $sr_post_type) {
            $query_object = get_queried_object();
            $listing_params = get_post_meta($query_object->ID, 'sr_filters', true);

            if (empty($listing_params)) {
                return $content;
            }

            // debug for viewing the search filters saved from the custom post page
            // foreach ( $listing_params as $key=>$value ) {
            //     echo 'param: ' . $key . ' value: ' . $value . $br;
            // }
            // the simply rets api helper takes care of retrieving, parsing, and generating
            // the markup for the listings to be shown on this page based off of the sr_filters
            // saved for this post
            $listings_content = SimplyRetsApiHelper::retrieveRetsListings($listing_params);
            $content = $content . $listings_content;

            return $content;
        }

        return $content;
    }



    public static function srCreateDynamicPost($posts) {

        // if we catch a singlelisting query, create a new post on the fly
        global $wp_query;
        $wpq = $wp_query->query;

        if ((!empty($wpq['sr-listings']) and $wpq['sr-listings'] == 'sr-search') and
            array_key_exists("listing_id", $wp_query->query_vars) and
            array_key_exists("listing_title", $wp_query->query_vars) or
            (!empty($wpq['sr-listings']) and $wpq['sr-listings'] == "sr-single")
        ) {

            $post_id    = urldecode(get_query_var('listing_id', ''));
            $post_price = urldecode(get_query_var('listing_price', ''));
            $post_addr  = SrUtils::decodeStringForUrl(
                urldecode(get_query_var('listing_title', ''))
            );

            $listing_USD = $post_price == '' ? '' : '$' . number_format($post_price);
            $title_normalize = "background-color:transparent;padding:0px;";
            $post_title = "{$post_addr} "
                . "<span style='{$title_normalize}'>"
                . "  <small>"
                . "    <i> {$listing_USD}</i>"
                . "  </small>"
                . "</span>";

            $post = (object)array(
                "comment_count"  => 0,
                "comment_status" => "closed",
                "ping_status"    => "closed",
                "post_author"    => 1,
                "post_name"      => $post_id,
                "post_date"      => gmdate("c"),
                "post_date_gmt"  => gmdate("c"),
                "post_parent"    => 0,
                "post_status"    => "publish",
                "post_title"     => $post_title,
                "post_type"      => "sr-listings"
            );

            return $posts + array($post);
        }

        // if we catch a search results query, create a new post on the fly
        if (!empty($wpq['sr-listings']) and $wpq['sr-listings'] == "sr-search") {

            $post = (object)array(
                "comment_count"  => 0,
                "comment_status" => "closed",
                "ping_status"    => "closed",
                "post_author"    => 1,
                "post_name"      => "Search Listings",
                "post_date"      => gmdate("c"),
                "post_date_gmt"  => gmdate("c"),
                "post_parent"    => 0,
                "post_status"    => "publish",
                "post_title"     => "Search Results",
                "post_type"      => "sr-listings"
            );

            return $posts + array($post);
        }

        if (!empty($wpq['sr-listings']) and $wpq['sr-listings'] == "sr-openhouses") {

            $post = (object)array(
                "comment_count"  => 0,
                "comment_status" => "closed",
                "ping_status"    => "closed",
                "post_author"    => 1,
                "post_name"      => "Open houses search results",
                "post_date"      => gmdate("c"),
                "post_date_gmt"  => gmdate("c"),
                "post_parent"    => 0,
                "post_status"    => "publish",
                "post_title"     => "Open houses search results",
                "post_type"      => "sr-listings"
            );

            return $posts + array($post);
        }

        return $posts;
    }
}
