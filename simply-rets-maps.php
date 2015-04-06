<?php

/*
 *
 * simply-rets-maps.php - Copyright (C) 2014-2015 SimplyRETS
 * This file provides the logic for the simply-rets custom post type pages.
 *
*/


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
        $map->setAsync(true);
        $map->setHtmlContainerId('sr_map_canvas');
        $map->setStylesheetOptions(array(
            'width' => '100%',
            'height' =>  '550px'
        ));
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
        $baths,
        $status,
        $mlsid,
        $propType,
        $area,
        $style
    ) {
        $markup = <<<HTML
            <div class="sr-iw-inner">
              <h4 class="sr-iw-addr">$address<small> $price</small></h4>
              <div class="sr-iw-inner__img">
                <a href='$link'>
                  <img id="sr-iw-inner__img-img" src='$photo'>
                </a>
              </div>
              <div class="sr-iw-inner__primary">
                <p>$beds Bed | $baths Bath | $status </p>
              </div>
              <hr>
              <div class="sr-iw-inner__secondary">
                <p><strong>MLS #:</strong> $mlsid</p>
                <p><strong>Area:</strong> $area SqFt</p>
                <p><strong>Property Type:</strong> $propType</p>
                <p><strong>Property Style:</strong> $style</p>
              </div>
              <hr>
              <div class="sr-iw-inner__view-details">
                <a href='$link' class='sr-iw-inner__details-link'>View Details</a>
              </div>
            </div>
HTML;

        return $markup;

    }

}