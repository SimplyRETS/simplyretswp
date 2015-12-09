/**
 *
 * simply-rets-client.js - Copyright (c) 2014-2015 SimplyRETS
 *
 * This file provides the client side javascript for the
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


var makeMapMarkers = function(map, listings) {
    // if(!listings || listings.length < 1) return [];

    var markers = [];
    var windows = [];
    var bounds  = new google.maps.LatLngBounds();

    $_.each(listings, function(idx, listing) {

        var lat = listing.geo.lat,
            lng = listing.geo.lng;

        if(lat && lng) {

            var bound  = new google.maps.LatLng(listing.geo.lat, listing.geo.lng);

            var popup  = genMarkerPopup(listing);
            var window = new google.maps.InfoWindow({
                content: popup
            });

            var marker = new google.maps.Marker({
                position: bound,
                map: map,
                title: listing.address.full
            });

            marker.addListener('click', function() {
                window.open(map, marker);
            });

            bounds.extend(bound);
            markers.push(marker);
            windows.push(window);
        }

    });

    return {
        bounds:   bounds,
        markers:  markers,
        windowsL: windows
    }

}


var replaceListingMarkup = function(markup) {
    var root = $_('.sr-map-search-list-view');
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


/**
 * Our Map Class
 * Holds some state for working with the map:
 */
function Map() {

    this.element   = 'sr-map-search';
    this.bounds    = [];
    this.markers   = [];
    this.listings  = [];
    this.polygon   = null;
    this.rectangle = null;
    this.popup     = null;
    this.loadMsg   = "Loading...";
    this.loaded    = false;
    this.options   = { zoom: 8 }

    this.map = new google.maps.Map(document.getElementById('sr-map-search'), this.options);

    return this;
}


/**
 * Map prototype methods
 */


/** `rec`: google.maps.OverlayType === RECTANGLE */
Map.prototype.getRectanglePoints = function(rec) {

    var b  = rec.getBounds();
    var nE = [ b.getNorthEast().lat(), b.getNorthEast().lng() ];
    var nW = [ b.getNorthEast().lat(), b.getSouthWest().lng() ];
    var sE = [ b.getSouthWest().lat(), b.getNorthEast().lng() ];
    var sW = [ b.getSouthWest().lat(), b.getSouthWest().lng() ];

    var latLngs = $_.map([nE, nW, sE, sW], function(o) {
        return {
            name: "points",
            value: o[0] + "," + o[1]
        }
    });

    return latLngs;

}

Map.prototype.getPolygonPoints = function(polygon) {

    var paths  = polygon.getPaths();
    var points = [];

    for (var p = 0; p < paths.getLength(); p++) {
        var path = paths.getAt(p);
        for (var i = 0; i < path.getLength(); i++) {
            points.push([ path.getAt(i).lat(), path.getAt(i).lng() ]);
        }
    }

    var latLngs = $_.map(points, function(o) {
        return {
            name: "points",
            value: o[0] + "," + o[1]
        }
    });

    return latLngs;

}


Map.prototype.addEventListener = function(source, event, fn) {
    return google.maps.event.addListener(source, event, fn);
}

Map.prototype.searchFormValues = function() {
    return getSearchFormValues();
};

Map.prototype.clearMarkers = function() {
    if(this.markers.length > 0)
        this.setMapOnMarkers(null);
}

Map.prototype.clearPolygon = function() {
    if(this.polygon !== null)
        this.setMapOnPolygon(null);
}


Map.prototype.handlePolygonDraw = function(that, overlay) {

    that.clearMarkers();
    that.clearPolygon();

    var pts   = this.getPolygonPoints(overlay);
    var query = that.searchFormValues();

    that.shape   = 'polygon';
    that.polygon = overlay;
    that.markers = [];

    return {
        points: pts,
        query: query
    }
}


Map.prototype.handleRectangleDraw = function(that, overlay) {

    that.clearMarkers();
    that.clearPolygon();

    var pts   = that.getRectanglePoints(overlay);
    var query = that.searchFormValues();

    // $_.each (markers, function (i, m) { map.removeLayer(m) });
    // if(polygon != null) map.removeLayer(polygon);
    // map.addLayer(e.layer);

    this.shape   = "rectangle";
    this.polygon = overlay;
    this.markers = [];

    return {
        points: pts,
        query: query
    }
}


Map.prototype.handleFormSubmit = function(e) {
    e.preventDefault();

    this.clearMarkers();

    var params = this.searchFormValues();
    var points = this.shape === "rectangle"
               ? this.getRectanglePoints(this.polygon)
               : this.getPolygonPoints(this.polygon);

    return {
        query:  params,
        points: points
    }

}


Map.prototype.setMapOnMarkers = function(map) {

    for(var i = 0; i < this.markers.length; i++) {
        this.markers[i].setMap(map);
    }

    return true;
}


Map.prototype.setMapOnPolygon = function(map) {

    this.polygon.setMap(map);

    return true;
}


Map.prototype.handleRequest = function(that, data) {

    that.setMapOnMarkers(null);
    // that.polygon !== null ? that.polygon.setMap(null) : true;

    that.bounds   = [];
    that.listings = [];

    var listings = data.result.response.length > 0
                 ? data.result.response
                 : [];
    var markers  = makeMapMarkers(that.map, listings); // {markers:_,windows:_,bounds:_}

    that.bounds   = markers.bounds;
    that.markers  = markers.markers;
    that.listings = listings;

    that.map.fitBounds(that.bounds);
    replaceListingMarkup(data.markup);

}

Map.prototype.sendRequest = function(points, params) {

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

Map.prototype.setDrawingManager = function() {

    var that = this;

    // Enable the drawing tools toolbar
    var drawingManager = new google.maps.drawing.DrawingManager({
        map: this.map,
        drawingControl: true,
        drawingControlOptions: {
            position: google.maps.ControlPosition.TOP_CENTER,
            drawingModes: [
                google.maps.drawing.OverlayType.POLYGON,
                google.maps.drawing.OverlayType.RECTANGLE
            ]
        },
        // markerOptions: { icon: 'custom/icon/here.png' },
        rectangleOptions: {
            fillOpacity: 0.1,
            fillColor: 'green',
            strokeColor: 'green'
        },
        polygonOptions: {
            // editable: true
            fillOpacity: 0.1,
            fillColor: 'green',
            strokeColor: 'green'
        },
    });

    this.addEventListener(drawingManager, 'rectanglecomplete', function(overlay) {
        var q = that.handleRectangleDraw(that, overlay);

        that.sendRequest(q.points, q.query).done(function(data) {
            that.handleRequest(that, data);
        });

    });

    this.addEventListener(drawingManager, 'polygoncomplete', function(overlay) {
        var q = that.handlePolygonDraw(that, overlay);

        that.sendRequest(q.points, q.query).done(function(data) {
            that.handleRequest(that, data);
        });

    });

    return drawingManager;

};


Map.prototype.initEventListeners = function() {

    var that = this;

    // fetch initial listings when map is loaded
    this.addEventListener(this.map, 'idle', function() {
        if(!that.loaded) {
            that.loaded = true;
            that.sendRequest([], {}).done(function(data) {
                that.handleRequest(that, data);
            });
        }
    });


    // Watch the search form for submission
    $_('.sr-int-map-search-wrapper form input.submit').on('click', function(e) {
        var params = that.handleFormSubmit(e),
            points = params.points,
            query  = params.query;

        that.sendRequest(points, query).done(function(data) {
            that.handleRequest(that, data);
        });

    });

    return;

};


var startMap = function() {

    var map = new Map();
    map.setDrawingManager();
    map.initEventListeners();
    map.initSearchFormEventHandler();

}


$_(document).ready(function() {

    classicGalleryToggle();
    classicGalleryTextToggle();
    advSearchFormToggler();
    listingSliderCarousel();

    if(document.getElementById('sr-map-search')) {
        startMap();
    }

});
