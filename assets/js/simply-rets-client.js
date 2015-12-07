/**
 *
 * simply-rets-client.js - Copyright (c) 2014-2015 SimplyRETS
 *
 * This file provides the (minimal) client side javascript for the
 * SimplyRETS Wordpress plugin.
 *
 * License: GPLv3 (http://www.gnu.org/licenses/gpl.html)
 *
**/


var $_ = jQuery; // reassign jQuery


/* Single Listing Details Image Slider (Classic) */
var classicGalleryToggle = function() {

    $_(".sr-slider-input").click(function() {

        var imgSrc = $_("input[name='slide_switch']:checked").val();
        var imgAct = $_(".sr-slider-img-act");

        imgAct.fadeOut("fast", function() {

            imgAct.attr('src', imgSrc);
            imgAct.fadeIn("fast");

        });

    });

};


/* Open/Close Listing Image Gallery (Classic) */
var classicGalleryTextToggle = function() {

    $_('#sr-toggle-gallery').click(function() {
        $_('.sr-slider label').toggle(100);
        if($_(this).text() == 'Hide photos') {
            $_(this).text('Show more photos');
        } else {
            $_(this).text('Hide photos');
        }
    });

};


/** Hide 'Amenities' in advanced search form when "Land" is selected */
var advSearchFormToggler = function() {

    if($_('#sr-search-ptype select').val() == 'Land') {
        $_('.sr-adv-search-amenities-wrapper').hide();
    }

    $_('#sr-search-ptype select').change(function() {
        if($_(this).val() == 'Land') {
            $_('.sr-adv-search-amenities-wrapper').hide();
            $_('input[name="sr_features[]"]').each(function() {
                $_(this).attr('checked', false);
            });
        } else {
            $_('.sr-adv-search-amenities-wrapper').show();
        }
    });

};



/** [sr_listings_slider] default number of items */
var listingSliderCarousel = function() {

    $_("#simplyrets-listings-slider").owlCarousel({
        items: 4
    });

}


var genMarkerPopup = function(listing) {

    var stat  = listing.mls.status         || "Active";
    var baths = listing.property.bathsFull || "n/a";
    var beds  = listing.property.bedrooms  || "n/a";
    var style = listing.property.style     || "Res" ;
    var type  = listing.property.type      || "Res";
    var sqft  = listing.property.area      || "n/a";
    var mlnum = listing.listingId          || "n/a";
    var addr  = listing.address.full       || "Unknown";
    var photo = listing.photos.length > 1
              ? listing.photos[0]
              : 'assets/img/defprop.jpg';
    var link  = window.location.href +
                "/?sr-listings=sr-single" +
                "&listing_id=" + listing.mlsId +
                "&sr_vendor=" +
                "&listing_price=" + listing.listPrice +
                "&listing_title=" + listing.address.full;

    var markup = '' +
       '<div class="sr-iw-inner">' +
       '    <h4 class="sr-iw-addr">' + addr + '</h4>' +
       '    <div class="sr-iw-inner__img">' +
       '        <a href="' + link + '">' +
       '            <img id="sr-iw-inner__img-img" src="' + photo + '" style="max-width:100%">' +
       '        </a>' +
       '    </div>' +
       '    <div class="sr-iw-inner__primary">' +
       '        <p>' + beds + ' Beds | ' + baths + ' Baths | ' + stat + '</p>' +
       '    </div>' +
       '    <hr>' +
       '    <div class="sr-iw-inner__secondary">' +
       '        <span><strong>MLS #:</strong>' + mlnum + '</span>' +
       '        <br>' +
       '        <span><strong>Area:</strong>' + sqft + '</span>' +
       '        <br>' +
       '        <span><strong>Property Type:</strong>' + type + '</span>' +
       '        <br>' +
       '        <span><strong>Property Style:</strong>' + style + '</span>' +
       '    </div>' +
       '    <hr>' +
       '    <div class="sr-iw-inner__view-details">' +
       '       <a href="' + link + '">View Details</a>' +
       '    </div>' +
       '    </div>' +
       '</div>';
    
    return markup;

}


/**
 * Send request with points
 */
var srMapSendRequest = function(points) {

    var req = $_.ajax({
        type: 'post',
        url: sr_ajaxUrl, // defined in <head>
        data: {
            action: 'update_int_map_data', // server controller
            data: points,
            params: $_.param(points)
        },
    });

    return req;
}

var srMapHandleRequest = function(data) {

    console.log(data);

    var listings = data.result.response.length > 0
                 ? data.result.response
                 : [];

    return {
        data: data,
        listings: listings
    }

}


var makeMapMarkers = function(listings) {
    // if(!listings || listings.length < 1) return [];

    var bounds  = [];
    var markers = [];

    $_.each(listings, function(idx, listing) {

        var lat = listing.geo.lat,
            lng = listing.geo.lng;

        if(lat && lng) {
            var bound  = new L.LatLng(listing.geo.lat, listing.geo.lng);
            var marker = new L.Marker(bound);
            var popup  = genMarkerPopup(listing);

            marker.bindPopup(popup);

            bounds.push(bound);
            markers.push(marker);
        }

    });

    return {
        bounds:   bounds,
        markers:  markers,
        listings: listings
    }

}


var placeMapMarkers = function(map, markers) {
    // if(!markers || markers.length < 1) return [];

    $_.each(markers, function (idx, marker) {
        map.addLayer(marker);
    });

}

var initIntMap = function() {

    /*
     * Interactive Map Search JS
     */
    var map = new L.Map('sr-int-map');
    var center = new L.LatLng(41.850033, -87.6500523);

    var mapBounds   = [];
    var mapMarkers  = [];
    var mapListings = [];
    var mapPolygon  = null;

    L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="http://openstreetmap.org">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);


    map.on('load', function() {
        srMapSendRequest([]).done(function(data) {

            var response = srMapHandleRequest(data);
            var markers  = makeMapMarkers(response.listings);

            mapBounds   = markers.bounds;
            mapMarkers  = markers.markers;
            mapListings = markers.listings;

            placeMapMarkers(map, markers.markers);
            map.fitBounds(markers.bounds);

        });
    });

    map.setView(center, 2);

    /***********************************************************/

    var drawnItems = new L.FeatureGroup();
    var drawCtrl   = new L.Control.Draw({
        edit: { featureGroup: drawnItems },
        draw: {
            circle: false,
            marker: false,
            polyline: false
        }
    });

    map.addControl(drawCtrl);
    map.addLayer(drawnItems);

    /***********************************************************/

    // load initial listings after render
    map.on('draw:created', function(e) {

        $_.each (mapMarkers, function (i, m) { map.removeLayer(m) });
        if (mapPolygon != null) map.removeLayer (mapPolygon);

        mapPolygon  = null;
        mapBounds   = [];
        mapMarkers  = [];
        mapListings = [];

        var latLngs = $_.map(e.layer.getLatLngs(), function(o) {
            return {
                name: "points",
                value: o.lat + "," + o.lng
            }
        });

        srMapSendRequest(latLngs).done(function(data) {

            var response = srMapHandleRequest(data);
            var markers  = makeMapMarkers(response.listings);

            console.log(markers.markers.length);

            mapBounds   = markers.bounds;
            mapMarkers  = markers.markers;
            mapListings = markers.listings;
            mapPolygon  = e.layer;

            console.log(mapBounds);

            placeMapMarkers(map, markers.markers);
            map.addLayer(e.layer);
            map.fitBounds(markers.bounds);

        });

    });

    return;
}


$_(document).ready(function() {

    classicGalleryToggle();
    classicGalleryTextToggle();
    advSearchFormToggler();
    listingSliderCarousel();


    if($_('#sr-int-map').length) {
        initIntMap();
    }
});
