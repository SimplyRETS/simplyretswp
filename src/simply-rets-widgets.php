<?php

/*
 *
 * simply-rets-widgets.php - Copyright (C) 2014-2015 SimplyRETS
 * This file provides the logic for the simply-rets sidebar widgets.
 *
*/

/*
 * To add new widgets, extend the WP_Widget class  with a constructor,
 * update, form, and widget function.
 * To activate new widgets, simply add a line to register the widget
 * in the srRegisterWidgets function - the rest is already initialized.
*/


/* Code starts here */
function srRegisterWidgets() {
    register_widget('srFeaturedListingWidget');
    register_widget('srAgentListingWidget');
    register_widget('srRandomListingWidget');
    register_widget('srSearchFormWidget');
}


class SrWidgetHelper {

    /*
     * Create an API query string from $params : {array}.
     * Specifically, this adds all Basic statuses to the default query
     * since it is not configurable from the widget settings. Ie,
     * ActiveUnderContract. We could also use the
     * `sr_adv_search_meta_status_` data from the DB to use all
     * statuses this user has access to.
     */
    public static function mkApiQueryString($params) {
        $qs = "?status=Active"
            . "&status=Pending"
            . "&status=ActiveUnderContract";

        foreach((array)$params as $key=>$value) {
            $qs .= "&{$key}={$value}";
        }

        return $qs;
    }
}


class srFeaturedListingWidget extends WP_Widget {

    /** constructor */
    function __construct() {
        parent::__construct(false, "SimplyRETS Featured Listing");
    }

    /** save widget --  @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['mlsid'] = strip_tags($new_instance['mlsid']);
        $instance['vendor'] = strip_tags($new_instance['vendor']);
        return $instance;
    }

    /** admin widget form --  @see WP_Widget::form */
    function form($instance) {
        $default_options = array(
            "title" => "Featured listing",
            "mlsid" => "",
            "vendor" => "",
        );

        $widget = wp_parse_args((array)$instance, $default_options);

        $singleVendor = SrUtils::isSingleVendor();
        $MLS_text = SrUtils::mkMLSText();

        $title  = esc_attr($widget['title']);
        $mlsid  = esc_attr($widget['mlsid']);
        $vendor = esc_attr($widget['vendor']);

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">
                <?php _e('Title:'); ?>
            </label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   type="text"
                   value="<?php echo $title; ?>"
            />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('mlsid'); ?>">
                <?php _e('Listing ' . $MLS_text . ' Id:'); ?>
            </label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id('mlsid'); ?>"
                   name="<?php echo $this->get_field_name('mlsid'); ?>"
                   type="text"
                   value="<?php echo $mlsid; ?>"
            />
        </p>
        <?php if(!$singleVendor) { ?>
            <p>
                <label for="<?php echo $this->get_field_id('vendor'); ?>">
                    <?php _e('Vendor:'); ?>
                </label>
                <input class="widefat" id="<?php echo $this->get_field_id('vendor'); ?>"
                       name="<?php echo $this->get_field_name('vendor'); ?>"
                       type="text"
                       value="<?php echo $vendor; ?>"
                />
            </p>
        <?php }
    }

    /** front end widget render -- @see WP_Widget::widget */
    function widget( $args, $instance ) {
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $mlsid = $instance['mlsid'];
        $vendor = $instance['vendor'];

        $cont = $before_widget;
        // populate title
        if( $title ) {
            $cont .= $before_title . $title . $after_title;
        } else {
            $cont .= $before_title . $after_title;
        }

        $settings = array(
            'vendor' => $vendor
        );

        // populate content
        if( $mlsid ) {
            $qs = SrWidgetHelper::mkApiQueryString(
                array("q" => $mlsid, "vendor" => $vendor)
            );

            $cont .= SimplyRetsApiHelper::retrieveWidgetListing($qs, $settings);
        } else {
            $cont .= "No listing found";
        }

        $cont .= $after_widget;
        echo $cont;
    }

}

class srAgentListingWidget extends WP_Widget {

    /** constructor */
    function __construct() {
        parent::__construct(false, "SimplyRETS Agents Listings");
    }


    /** save widget --  @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['agent'] = strip_tags($new_instance['agent']);
        $instance['limit'] = strip_tags($new_instance['limit']);
        $instance['vendor'] = strip_tags($new_instance['vendor']);
        return $instance;
    }

    /** admin widget form --  @see WP_Widget::form */
    function form($instance) {
        $default_options = array(
            "title" => "My listings",
            "limit" => "5",
            "agent" => "",
            "vendor" => "",
        );

        $widget = wp_parse_args((array)$instance, $default_options);

        $singleVendor = SrUtils::isSingleVendor();
        $MLS_text = SrUtils::mkMLSText();

        $title = esc_attr($widget['title']);
        $agent = esc_attr($widget['agent']);
        $limit = esc_attr($widget['limit']);
        $vendor = esc_attr($widget['vendor']);

        ?>
        <p>
          <label for="<?php echo $this->get_field_id('title'); ?>">
                <?php _e('Title:'); ?>
          </label>
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                         name="<?php echo $this->get_field_name('title'); ?>"
                         type="text"
                         value="<?php echo $title; ?>" />
        </p>

        <p>
          <label for="<?php echo $this->get_field_id('agent'); ?>">
                <?php _e('Agent ' . $MLS_text . ' Id:'); ?>
          </label>
          <input class="widefat"
                         id="<?php echo $this->get_field_id('agent'); ?>"
                         name="<?php echo $this->get_field_name('agent'); ?>"
                         type="text"
                         value="<?php echo $agent; ?>" />
        </p>

        <p>
          <label for="<?php echo $this->get_field_id('limit'); ?>">
                <?php _e('Amount of listings to show:'); ?>
          </label>
          <input class="widefat"
                         id="<?php echo $this->get_field_id('limit'); ?>"
                         name="<?php echo $this->get_field_name('limit'); ?>"
                         type="text"
                         value="<?php echo $limit; ?>" />
        </p>
        <?php if(!$singleVendor) { ?>
            <p>
                <label for="<?php echo $this->get_field_id('vendor'); ?>">
                    <?php _e('Vendor:'); ?>
                </label>
                <input class="widefat" id="<?php echo $this->get_field_id('vendor'); ?>"
                       name="<?php echo $this->get_field_name('vendor'); ?>"
                       type="text"
                       value="<?php echo $vendor; ?>"
                />
            </p>
        <?php }
    }

    /** front end widget render -- @see WP_Widget::widget */
    function widget( $args, $instance ) {
       extract( $args );
       $title  = apply_filters('widget_title', $instance['title']);
       $agent  = $instance['agent'];
       $limit  = $instance['limit'];
       $vendor = $instance['vendor'];

       $cont = $before_widget;
       // populate title
       if( $title ) {
           $cont .= $before_title . $title . $after_title;
       } else {
           $cont .= $before_title . $after_title;
       }

       $settings = array(
           'vendor' => $vendor
       );

       // populate content
       if( $agent && $limit ) {
           $qs = SrWidgetHelper::mkApiQueryString(
               array(
                   "agent" => $agent,
                   "limit" => $limit,
                   "vendor" => $vendor
               )
           );

           $cont .= SimplyRetsApiHelper::retrieveWidgetListing($qs, $settings);
       } else {
           $cont .= "No listing found";
       }

       $cont .= $after_widget;
       echo $cont;
    }

}

class srRandomListingWidget extends WP_Widget {

    /** constructor */
    function __construct() {
        parent::__construct(false, "SimplyRETS Random Listing");
    }

    /** save widget --  @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title']  = strip_tags($new_instance['title']);
        $instance['mlsids'] = strip_tags($new_instance['mlsids']);
        $instance['vendor'] = strip_tags($new_instance['vendor']);
        return $instance;
    }

    /** admin widget form --  @see WP_Widget::form */
    function form( $instance ) {
        $default_options = array(
            "title" => "Random listing",
            "mlsids" => "",
            "vendor" => "",
        );

        $widget = wp_parse_args((array)$instance, $default_options);

        $singleVendor = SrUtils::isSingleVendor();
        $MLS_text = SrUtils::mkMLSText();

        $title  = esc_attr($widget['title']);
        $mlsids = esc_attr($widget['mlsids']);
        $vendor = esc_attr($widget['vendor']);

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">
                <?php _e('Title:'); ?>
            </label>
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                 name="<?php echo $this->get_field_name('title'); ?>"
                 type="text"
                 value="<?php echo $title; ?>"
            />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('mlsids'); ?>">
                <?php _e($MLS_text . ' Id\'s (comma separated):'); ?>
            </label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id('mlsids'); ?>"
                   name="<?php echo $this->get_field_name('mlsids'); ?>"
                   type="text"
                   value="<?php echo $mlsids; ?>"
            />
        </p>
        <?php if(!$singleVendor) { ?>
            <p>
                <label for="<?php echo $this->get_field_id('vendor'); ?>">
                    <?php _e('Vendor:'); ?>
                </label>
                <input class="widefat" id="<?php echo $this->get_field_id('vendor'); ?>"
                       name="<?php echo $this->get_field_name('vendor'); ?>"
                       type="text"
                       value="<?php echo $vendor; ?>"
                />
            </p>
        <?php }
    }

    /** front end widget render -- @see WP_Widget::widget */
    function widget( $args, $instance ) {
        extract( $args );

        $vendor = apply_filters('widget_title', $instance['vendor']);
        $title  = apply_filters('widget_title', $instance['title']);
        $mlsids = $instance['mlsids'];
        $mlsids_arr = explode( ',', $mlsids );

        $mlsid = trim($mlsids_arr[array_rand($mlsids_arr)]);

        $cont = $before_widget;

        // populate title
        if( $title ) {
            $cont .= $before_title . $title . $after_title;
        } else {
            $cont .= $before_title . $after_title;
        }

        $settings = array(
            'vendor' => $vendor
        );

        // populate content
        if( $mlsid ) {
            $qs = SrWidgetHelper::mkApiQueryString(
                array("q" => $mlsid, "vendor" => $vendor)
            );

            $cont .= SimplyRetsApiHelper::retrieveWidgetListing($qs, $settings);
        } else {
            $cont .= "No listing found";
        }

        $cont .= $after_widget;
        echo $cont;
    }
}


class srSearchFormWidget extends WP_Widget {

    /** constructor */
    function __construct() {
        parent::__construct(false, "SimplyRETS Search Widget");
    }

    /** save widget --  @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title']  = strip_tags($new_instance['title']);
        $instance['vendor'] = strip_tags($new_instance['vendor']);
        return $instance;
    }

    /** admin widget form --  @see WP_Widget::form */
    function form($instance) {
        $default_options = array(
            "title" => "Search listings",
            "vendor" => "",
        );

        $widget = wp_parse_args((array)$instance, $default_options);

        $singleVendor = SrUtils::isSingleVendor();
        $title  = esc_attr($widget['title']);
        $vendor = esc_attr($widget['vendor']);

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">
                <?php _e('Title:'); ?>
            </label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   type="text"
                   value="<?php echo $title; ?>" />
        </p>
        <?php if(!$singleVendor) { ?>
            <p>
                <label for="<?php echo $this->get_field_id('vendor'); ?>">
                    <?php _e('Vendor:'); ?>
                </label>
                <input class="widefat" id="<?php echo $this->get_field_id('vendor'); ?>"
                       name="<?php echo $this->get_field_name('vendor'); ?>"
                       type="text"
                       value="<?php echo $vendor; ?>"
                />
            </p>
        <?php }
    }

    /** front end widget render -- @see WP_Widget::widget */
    function widget( $args, $instance ) {
        extract( $args );
        $title  = apply_filters('widget_title', $instance['title']);
        $vendor = apply_filters('widget_vendor', $instance['vendor']);

        $cont = $before_widget;

        // populate title
        if( $title ) {
            $cont .= $before_title . $title . $after_title;
        } else {
            $cont .= $before_title . $after_title;
        }

        // Create property type dropdown options
        $singleVendor = SrUtils::isSingleVendor();
        $availableVendors = get_option('sr_adv_search_meta_vendors', array());
        $ven = isset($vendor) ? $vendor  : '';
        if(empty($ven) && $singleVendor === true) {
            $ven = $availableVendors[0];
        }

        $current_type = empty($_GET['sr_ptype']) ? '' : $_GET['sr_ptype'];

        $adv_search_types = get_option("sr_adv_search_meta_types_$ven",
                                       array("Residential", "Condominium", "Rental" ));

        $type_options = '';
        foreach( (array)$adv_search_types as $key=>$type) {
            if( $type == $current_type) {
                $type_options .= "<option value='$type' selected />$type</option>";
            } else {
                $type_options .= "<option value='$type' />$type</option>";
            }
        }


        $home_url = get_home_url();
        $search_form_markup = <<<HTML
          <div class="sr-search-widget">
            <form method="get" class="sr-search" action="$home_url">
              <input type="hidden" name="sr-listings" value="sr-search">

              <div class="sr-search-field" id="sr-search-keywords">
                <input name="sr_keywords" type="text" placeholder="Subdivision, Zipcode, or Keywords" />
              </div>

              <div class="sr-search-field" id="sr-search-ptype">
                <select name="sr_ptype">
                  <option value="">Property Type</option>
                  $type_options
                </select>
              </div>

              <div class="sr-search-widget-filters">
                <div class="sr-search-widget-field" id="sr-search-minprice">
                  <input name="sr_minprice" step="1000" min="0" type="number" placeholder="Min Price.." />
                </div>
                <div class="sr-search-widget-field" id="sr-search-maxprice">
                  <input name="sr_maxprice" step="1000" min="0" type="number" placeholder="Max Price.." />
                </div>

                <div class="sr-search-widget-field" id="sr-search-minbeds">
                  <input name="sr_minbeds" min="0" type="number" placeholder="Min Beds.." />
                </div>
                <div class="sr-search-widget-field" id="sr-search-maxbeds">
                  <input name="sr_maxbeds" min="0" type="number" placeholder="Max Beds.." />
                </div>

                <div class="sr-search-widget-field" id="sr-search-minbaths">
                  <input name="sr_minbaths" min="0" type="number" placeholder="Min Baths.." />
                </div>
                <div class="sr-search-widget-field" id="sr-search-maxbaths">
                  <input name="sr_maxbaths" min="0" type="number" placeholder="Max Baths.." />
                </div>
              </div>

              <input type="hidden" name="sr_vendor" value="$vendor" >

              <input class="submit button btn" type="submit" value="Search Properties">

            </form>
          </div>
HTML;

        // populate content
        $cont .= $search_form_markup;

        $cont .= $after_widget;
        echo $cont;

    }

}
