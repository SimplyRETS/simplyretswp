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
    var price = listing.listPrice          || "Unknown";
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
       '        <span><strong>Price:</strong>' + price + '</span>' +
       '        <br>' +
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
var srMapSendRequest = function(points, params) {

    for (var p in params) {
        if(params[p] === null || params[p] === undefined || params[p] === "") {
            delete params[p];
        }
    }

    var pointsQ = $_.param(points);
    var paramsQ = $_.param(params);

    var query = pointsQ + "&" + paramsQ;

    var req = $_.ajax({
        type: 'post',
        url: sr_ajaxUrl, // defined in <head>
        data: {
            action: 'update_int_map_data', // server controller
            data: pointsQ,
            params: query
        },
    });

    return req;
}

var srMapHandleRequest = function(data) {

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

var replaceListingMarkup = function(markup) {

    var root = $_('.sr-int-map-list-view');
    root.html(markup);

}


var getSearchFormValues = function() {

    var keyword  = $_('.sr-int-map-search-wrapper #sr-search-keywords > input[type="text"]').val(),
        ptype    = $_('.sr-int-map-search-wrapper #sr-search-ptype select').val(),
        minprice = $_('.sr-int-map-search-wrapper #sr-search-minprice input').val(),
        maxprice = $_('.sr-int-map-search-wrapper #sr-search-maxprice input').val(),
        minbeds  = $_('.sr-int-map-search-wrapper #sr-search-minbeds input').val(),
        maxbeds  = $_('.sr-int-map-search-wrapper #sr-search-maxbeds input').val(),
        minbaths = $_('.sr-int-map-search-wrapper #sr-search-minbaths input').val(),
        maxbaths = $_('.sr-int-map-search-wrapper #sr-search-maxbaths input').val(),
        sort     = $_('.sr-int-map-search-wrapper .sr-sort-wrapper select').val();

    return {
        q:        keyword,
        type:     ptype,
        sort:     sort,
        minprice: minprice,
        maxprice: maxprice,
        minbeds:  minbeds,
        maxbeds:  maxbeds,
        minbaths: minbaths,
        maxbaths: maxbaths,
    }
}


/********************************/


/********************************/

var initIntMap = function() {

    var map      = new L.Map('sr-int-map');
    var bounds   = [];
    var markers  = [];
    var listings = [];
    var polygon  = null;
    var popup    = null;
    var loadMsg  = "Loading...";

    var SMap =  L.Class.extend({

        makeRequest: srMapSendRequest,

        addTileLayer: function() {

            L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="http://openstreetmap.org">OpenStreetMap</a> contributors',
                maxZoom: 18
            }).addTo(map);

        },

        handleRequest: function(data) {

            bounds   = [];
            listings = [];

            var response = srMapHandleRequest(data);
            var ls       = response.listings.length > 0 ? response.listings : [];
            var mks      = makeMapMarkers(ls);

            bounds   = mks.bounds;
            markers  = mks.markers;
            listings = ls;

            if(polygon != null && popup != null) polygon.closePopup();

            placeMapMarkers(map, markers);
            map.fitBounds(bounds);
            replaceListingMarkup(data.markup);

        },

        handleFormSubmit: function(e) {
            e.preventDefault();

            $_.each (markers, function (i, m) { map.removeLayer(m) });

            var params = getSearchFormValues();

            markers = [];

            polygon.bindPopup(loadMsg);
            polygon.openPopup();

            var points = $_.map(polygon.getLatLngs(), function(o) {
                return {
                    name: "points",
                    value: o.lat + "," + o.lng
                }
            });

            return {
                query:  params,
                points: points
            }

        },

        handleDraw: function(e) {

            var query = getSearchFormValues();

            $_.each (markers, function (i, m) { map.removeLayer(m) });
            if(polygon != null) map.removeLayer(polygon);

            map.addLayer(e.layer);

            polygon = e.layer;
            markers = [];
            popup   = loadMsg;

            e.layer.bindPopup(loadMsg);
            e.layer.openPopup();

            var latLngs = $_.map(e.layer.getLatLngs(), function(o) {
                return {
                    name: "points",
                    value: o.lat + "," + o.lng
                }
            });

            return {
                latLngs: latLngs,
                query: query
            }

        },

        addLayer: function(layer) {
            map.addLayer(layer);
        },

        addControl: function(control) {
            map.addControl(control);
        },

        on: function(action, callback) {
            map.on(action, callback);
        },

        setView: function(z) {
            map.setView(new L.LatLng(41.850033, -87.6500523), z);
        }

    });


    // Make new map
    var SrMap = new SMap('sr-int-map');

    // Initial layers/markers
    SrMap.addTileLayer();
    SrMap.setView(2);
    SrMap.on('load', SrMap.makeRequest([], {}).done(SrMap.handleRequest));

    // make controls
    var drawnItems = new L.FeatureGroup();
    var drawCtrl   = new L.Control.Draw({
        edit: { featureGroup: drawnItems },
        draw: {
            circle: false,
            marker: false,
            polyline: false
        }
    });

    // add controls to map
    SrMap.addControl(drawCtrl);
    SrMap.addLayer(drawnItems);

    // Run when a new polygon is drawn
    SrMap.on('draw:created', function(e) {

        var params = SrMap.handleDraw(e),
            points = params.latLngs,
            query  = params.query;

        SrMap.makeRequest(points, query).done(SrMap.handleRequest);

    });

    // When the search form is submitted, rerun query
    $_('.sr-int-map-search-wrapper form input.submit').on('click', function(e) {

        var params = SrMap.handleFormSubmit(e),
            points = params.points,
            query  = params.query;

        SrMap.makeRequest(points, query).done(SrMap.handleRequest);

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
