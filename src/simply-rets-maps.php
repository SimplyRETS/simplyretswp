<?php

/*
 *
 * simply-rets-maps.php - Copyright (C) 2014-2024 SimplyRETS
 * This file provides the logic for the simply-rets custom post type pages.
 *
*/


add_action('wp_head',
           array('SrSearchMap', 'defineAjaxUrl'));

add_action('wp_ajax_nopriv_update_int_map_data',
           array('SrSearchMap', 'update_int_map_data'));

add_action('wp_ajax_update_int_map_data',
           array('SrSearchMap', 'update_int_map_data'));


/* Code starts here */
use Ivory\GoogleMap\Map,
    Ivory\GoogleMap\Helper\MapHelper,
    Ivory\GoogleMap\MapTypeId,
    Ivory\GoogleMap\Overlays\Animation,
    Ivory\GoogleMap\Overlays\Marker,
    Ivory\GoogleMap\Overlays\InfoWindow,
    Ivory\HttpAdapter\CurlHttpAdapter;


class SrSearchMap {

    public static function mapWithDefaults() {
        $map = new Map();

        // Generate an ident for the map so you can render mutliple
        // maps on the same page. This isn't the most stable solution,
        // but we don't have unique identifiers about the current
        // short-code (and even if we did, it's very possible someone
        // might want to show two of the same short-codes on the same
        // page.
        $ident = wp_rand();
        $map->setHtmlContainerId("{$ident}");

        // Don't use async so that you can render multiple maps on the
        // same page. When `async` is true, each map (each short-code)
        // fetches it's own Google Maps script, causing conflict
        // errors if there are multiple on the same page. Async does
        // not have this problem.
        $map->setAsync(false);

        $map->setStylesheetOptions(array(
            'width' => '100%',
            'height' =>  '550px'
        ));

        // Set API key if user has added one.
        $apik = get_option('sr_google_api_key');
        if (!empty($apik)) {
            $map->setApiKey($apik);
        }

        return $map;
    }

    public static function markerWithDefaults() {
        $marker = new Marker();
        $marker->setPrefixJavascriptVariable('marker_');
        $marker->setOptions(array(
            'clickable' => true
        ));
        return $marker;
    }


    public static function infoWindowWithDefaults() {
        $iw = new InfoWindow();
        $iw->setAutoClose(true);
        $iw->setPrefixJavascriptVariable('info_window_');
        $iw->setOpenEvent('click');
        return $iw;
    }

    public static function srMapHelper() {
        return new MapHelper();
    }

    public static function infoWindowMarkup(
        $link,
        $photo,
        $address,
        $price,
        $beds,
        $baths_display,
        $status,
        $mlsid,
        $propType,
        $area,
        $style,
        $compliance_markup
    ) {

        $MLS_text = SrUtils::mkMLSText();

        ob_start();
        ?>
        <div class="sr-iw-inner">
            <h4 class="sr-iw-addr">
                <?php echo esc_html($address); ?>
                <small> - <?php echo esc_html($price); ?></small>
            </h4>
            <div class="sr-iw-inner__img">
                <a href="<?php echo esc_url($link); ?>">
                    <?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                    <img id="sr-iw-inner__img-img" src="<?php echo esc_url($photo); ?>">
                </a>
            </div>
            <div class="sr-iw-inner__primary">
                <p>
                    <?php echo esc_html($beds) . "bed | "; ?>
                    <?php echo esc_html($baths_display) . " | "; ?>
                    <?php echo esc_html($status); ?>
                </p>
            </div>
            <hr>
            <div class="sr-iw-inner__secondary">
                <p>
                    <strong>
                        <?php echo esc_html($MLS_text) . " #: "; ?>
                    </strong>
                    <?php echo esc_html($mlsid); ?>
                </p>
                <p>
                    <strong>Area: </strong>
                    <?php echo esc_html($area) . "sqft"; ?>
                </p>
                <p>
                    <strong>Property Type: </strong>
                    <?php echo esc_html($propType); ?>
                </p>
                <p>
                    <strong>Property Style: </strong>
                    <?php echo esc_html($style); ?>
                </p>
                <p>
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo html_entity_decode(esc_html($compliance_markup));
                    ?>
                </p>
            </div>
            <hr>
            <div class="sr-iw-inner__view-details">
                <a
                    href="<?php echo esc_url($link); ?>"
                    class='sr-iw-inner__details-link'>
                    View Details
                </a>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }


    public static function defineAjaxUrl() {
        ?>
        <script>
            var sr_ajaxUrl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
        </script>
        <?php
    }


    /**
     * This is the handler for API requests made from the
     * sr_map_search short-code. The client makes an AJAX request, we
     * take the `parameters` (a query string) and pass it to the API
     * request directly.  We return a glob of HTML that is then
     * rendered on the client.
     */
    public static function update_int_map_data() {

        // Ensure we only capture SimplyRETS requests
        if(array_key_exists('action', $_POST)
           && $_POST['action'] === "update_int_map_data") {

            $permalink_struct = get_option('sr_permalink_structure', false);
            $showStatusText = get_option('sr_show_mls_status_text', false);
            $showMlsTrademark = get_option('sr_show_mls_trademark_symbol', false);
            $site_root = get_site_url();
            $vendor = array_key_exists("vendor", $_POST) ? $_POST["vendor"] : "";

            header("Content-Type: application/json");


            $settings_ = $_POST['settings'];
            $def_settings = array("show_map" => "false", "vendor" => $vendor);
            $settings = array_merge($settings_, $def_settings);

            $req = SimplyRetsApiHelper::makeApiRequest($_POST['parameters']);
            $con = SimplyRetsApiHelper::srResidentialResultsGenerator($req, $settings);

            $response = array(
                "result" => $req,
                "markup" => $con,
                "post"   => $_POST,
                "permalink_structure" => $permalink_struct,
                "show_mls_status_text" => $showStatusText,
                "show_mls_trademark_symbol" => $showMlsTrademark,
                "site_root" => $site_root
            );


            wp_send_json($response);

        }

        return;
    }

    /**
     * Returns true if a listing has lat/lng - false otherwise.
     */
    public static function mappable($arr) {
        $lat = $arr->geo->lat;
        $lng = $arr->geo->lng;

        if (empty($lat) OR empty($lng)) {
            return false;
        }

        return true;
    }

    public static function filter_mappable($arr) {
        return array_filter($arr, 'SrSearchMap::mappable');
    }

    /**
     * Given a list of listings, return the number of unique lat/lng
     * pairs.
     */
    public static function uniqGeos($arr) {
        $tmp_geos = array();

        foreach($arr as $a) {
            $tmp_geos[$a->geo->lat . $a->geo->lng] = array(
                $a->geo->lat,
                $a->geo->lng
            );
        }

        return array_values($tmp_geos);
    }
}
