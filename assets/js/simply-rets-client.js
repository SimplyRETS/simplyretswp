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


var scrollToAnchor = function(aid) {
    var aTag = $_("#"+ aid);
    $_('html,body').animate({scrollTop: aTag.offset().top},'slow');
}


var buildPrettyLink = function(mlsId, address, root) {
    return root
         + "/listings/"
         + mlsId + "/"
         + address
}

var buildUglyLink = function(mlsId, address, root) {
    return root
         + "?sr-listings=sr-single"
         + "&listing_id=" + mlsId
         + "&listing_title=" + address;
}


var genMarkerPopup = function(listing, linkStyle, siteRoot, idxImg, officeOnThumbnails, statusText) {

    var stat  = statusText ? listing.mls.statusText : listing.mls.status;
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
              : 'https://s3-us-west-2.amazonaws.com/simplyrets/trial/properties/defprop.jpg';
    var office = officeOnThumbnails && listing.office.name
               ? listing.office.name
               : ""

    var link = linkStyle === "pretty"
             ? buildPrettyLink(listing.mlsId, listing.address.full, siteRoot)
             : buildUglyLink(listing.mlsId, listing.address.full, siteRoot);

    var markup = '' +
       '<div class="sr-iw-inner">' +
       '  <h4 class="sr-iw-addr">' + addr + '<small> $' + price + '</small></h4>' +
       '  <div class="sr-iw-inner__img">' +
       '    <a href="' + link + '">' +
       '      <img id="sr-iw-inner__img-img" src="' + photo +'">' +
       '    </a>' +
       '  </div>' +
       '  <div class="sr-iw-inner__primary">' +
       '    <p>' + beds + ' Beds | ' + baths + ' Baths | ' + stat + '</p>' +
       '  </div>' +
       '  <hr>' +
       '  <div class="sr-iw-inner__secondary">' +
       '    <p><strong>Price: </strong>$' + price + '</p>' +
       '    <p><strong>MLS #: </strong>' + mlnum + '</p>' +
       '    <p><strong>Area: </strong>' + sqft + '</p>' +
       '    <p><strong>Property Type: </strong>' + type + '</p>' +
       '    <p><strong>Property Style: </strong>' + style + '</p>' +
       '    <p><strong>Listing office: </strong>'+ office + '</p>' +
       '    <img src="' + idxImg + '"/>' +
       '  </div>' +
       '  <hr>' +
       '  <div class="sr-iw-inner__view-details">' +
       '    <a class="sr-iw-inner__details-link" href="' + link + '">View Details</a>' +
       '  </div>' +
       '</div>';

    return markup;

}


var makeMapMarkers = function(map, listings, linkStyle, siteRoot, idxImg, officeOnThumbnails, statusText) {
    // if(!listings || listings.length < 1) return [];

    var markers = [];
    var bounds  = new google.maps.LatLngBounds();

    $_.each(listings, function(idx, listing) {

        var lat = listing.geo.lat,
            lng = listing.geo.lng;

        if(lat && lng) {

            var bound  = new google.maps.LatLng(listing.geo.lat, listing.geo.lng);

            var popup  = genMarkerPopup(listing, linkStyle, siteRoot, idxImg, officeOnThumbnails, statusText);

            var window = new google.maps.InfoWindow({
                content: popup
            });

            var marker = new google.maps.Marker({
                position: bound,
                map: map,
                title: listing.address.full
            });

            marker.addListener('click', function() {
                if(window.getMap()) {
                    window.close(map, marker);
                } else {
                    window.open(map, marker);
                }
            });

            bounds.extend(bound);
            markers.push(marker);
        }

    });

    return {
        bounds:   bounds,
        markers:  markers,
    }

}


var replaceListingMarkup = function(markup) {
    var root = $_('.sr-map-search-list-view');
    if(root.length)
        root.html(markup);
}


var updatePagination = function(that) {

    var prevEl = null,
        nextEl = null,
        pagWrapper = $_('.sr-pagination');


    if(pagWrapper.length) {

        pagWrapper.empty(); // clear the current pagination elements

        var prev = "<a href=\"#\">Prev</a> | ";
        var next = "<a href=\"#\">Next</a>";

        var pag;
        if(that.offset === 0) {
            pag = next;
        } else {
            pag = prev + next;
        }

        if(that.offset === 0 && that.listings.length < 25) {
            pag = null;
        }

        if(that.offset > 0 && that.listings.length < 25) {
            pag = prev;
        }

        pagWrapper.append(pag);

        var childs = pagWrapper.children();
        if(childs.length >= 1) {
            $_.each(childs, function(c) {
                if(childs[c].text === "Next") {
                    nextEl = childs[c];
                }
                if(childs[c].text === "Prev") {
                    prevEl = childs[c];
                }
            });
        }
    }

    return {
        prev: prevEl,
        next: nextEl
    }
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

    this.element    = 'sr-map-search';
    this.bounds     = [];
    this.markers    = [];
    this.listings   = [];
    this.polygon    = null;
    this.rectangle  = null;
    this.popup      = null;
    this.drawCtrl   = null;
    this.loaded     = false;
    this.options    = { zoom: 8 }
    this.pagination = null;
    this.limit      = 25;
    this.offset     = 0;
    this.linkStyle  = 'default';
    this.siteRoot   = window.location.href
    this.vendor     = document.getElementById('sr-map-search').dataset.vendor;

    this.map     = new google.maps.Map(
        document.getElementById('sr-map-search'), this.options
    );
    this.loadMsg = new google.maps.InfoWindow({
        map: null,
        content: "Loading..."
    });

    return this;
}


/**
 * Map prototype methods
 */


/** `rec`: google.maps.OverlayType === RECTANGLE */
Map.prototype.getRectanglePoints = function(rec) {

    var latLngs = [];
    var bounds  = new google.maps.LatLngBounds();

    var b  = rec.getBounds();
    var nE = [ b.getNorthEast().lat(), b.getNorthEast().lng() ];
    var nW = [ b.getNorthEast().lat(), b.getSouthWest().lng() ];
    var sE = [ b.getSouthWest().lat(), b.getNorthEast().lng() ];
    var sW = [ b.getSouthWest().lat(), b.getSouthWest().lng() ];

    $_.map([nE, nW, sE, sW], function(o) {
        latLngs.push({
            name: "points",
            value: o[0] + "," + o[1]
        });
        bounds.extend(new google.maps.LatLng(o[0], o[1]));
    });

    this.bounds = bounds;
    this.map.fitBounds(bounds);

    return latLngs;
}

Map.prototype.getPolygonPoints = function(polygon) {

    var paths  = polygon.getPaths();
    var points = [];
    var bounds = new google.maps.LatLngBounds();

    for (var p = 0; p < paths.getLength(); p++) {

        var path = paths.getAt(p);

        for (var i = 0; i < path.getLength(); i++) {

            points.push(
                [ path.getAt(i).lat(), path.getAt(i).lng() ]
            );

            bounds.extend(
                new google.maps.LatLng(
                    path.getAt(i).lat(), path.getAt(i).lng()
                )
            );

        }
    }

    var latLngs = $_.map(points, function(o) {
        return {
            name: "points",
            value: o[0] + "," + o[1]
        }
    });

    this.bounds = bounds;
    this.map.fitBounds(bounds);

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

Map.prototype.setDrawCtrlOptions = function(opts) {
    return this.drawCtrl.setOptions(opts);
}


Map.prototype.handlePolygonDraw = function(that, overlay) {

    that.clearMarkers();
    that.clearPolygon();
    that.setDrawCtrlOptions({ drawingMode: null });

    var pts   = that.getPolygonPoints(overlay);
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
    that.setDrawCtrlOptions({ drawingMode: null });

    var pts   = that.getRectanglePoints(overlay);
    var query = that.searchFormValues();

    that.shape   = "rectangle";
    that.polygon = overlay;
    that.markers = [];

    return {
        points: pts,
        query: query
    }
}


Map.prototype.handleFormSubmit = function(e) {
    e.preventDefault();

    this.clearMarkers();

    var params = this.searchFormValues();
    var points = this.shape === "rectangle" ? this.getRectanglePoints(this.polygon)
               : this.shape === "polygon"   ? this.getPolygonPoints(this.polygon)
               : [];

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

    // Remove data from map before request
    that.setMapOnMarkers(null);
    that.setLoadMsgMap(null);

    // New map data, empty
    that.bounds   = [];
    that.listings = [];

    var idxImg = document.getElementById('sr-map-search').dataset.idxImg;
    var officeOnThumbnails = document.getElementById('sr-map-search').dataset.officeOnThumbnails;
    var linkStyle = data.permalink_structure === "" ? "default" : "pretty";
    var statusText = data.show_mls_status_text;

    that.siteRoot = data.site_root
    that.linkStyle = linkStyle;

    var listings = data.result.response.length > 0
                 ? data.result.response
                 : [];
    var markers  = makeMapMarkers(that.map
                                , listings
                                , that.linkStyle
                                , that.siteRoot
                                , idxImg
                                , officeOnThumbnails
                                , statusText );

    that.bounds   = markers.bounds;
    that.markers  = markers.markers;
    that.listings = listings;

    if(listings.length < 1)
        that.offset = 0;

    if(that.loaded === false)
        that.map.fitBounds(that.bounds);

    replaceListingMarkup(data.markup);

    var pagination = updatePagination(that);

    that.pagination = pagination;

    that.initPaginationEventHandlers(that, that.pagination);

}

Map.prototype.initPaginationEventHandlers = function(that, pag) {

    if(pag.next !== null) {

        $_(pag.next).on('click', function(e) {

            e.preventDefault();

            var params = that.handleFormSubmit(e),
                points = params.points,
                query  = params.query;

            that.sendRequest(points, query, 'next').done(function(data) {
                that.handleRequest(that, data);
            });

        });
    }

    if(pag.prev !== null) {

        $_(pag.prev).on('click', function(e) {

            e.preventDefault();

            var params = that.handleFormSubmit(e),
                points = params.points,
                query  = params.query;

            that.sendRequest(points, query, 'prev').done(function(data) {
                that.handleRequest(that, data);
            });

        });
    }

}


Map.prototype.setLoadMsgMap = function(map) {

    if(!this.polygon && !this.rectangle) return;

    this.loadMsg.setPosition(this.map.getCenter());
    this.loadMsg.setMap(map);

}


Map.prototype.sendRequest = function(points, params, paginate) {

    this.setLoadMsgMap(this.map);

    /** Update pagination */
    if(paginate !== null && paginate !== undefined) {

        if(paginate === "next") {
            scrollToAnchor('sr-search-wrapper');
            this.offset = this.offset + this.limit;
        } else if(paginate === "prev") {
            scrollToAnchor('sr-search-wrapper');
            this.offset = this.offset - this.limit;
        }

    }

    var limit  = this.limit;
    var offset = this.offset;
    var vendor = this.vendor

    /** Remove unused keys */
    for (var p in params) {
        if(params[p] === null || params[p] === undefined || params[p] === "") {
            delete params[p];
        }
    }

    /** URL Encode them all */
    var pointsQ = $_.param(points);
    var paramsQ = $_.param(params);

    /** Put the query in a string and send the request */
    var query = pointsQ + "&limit=" + limit + "&offset=" + offset + "&vendor=" + vendor + "&" + paramsQ;

    var req = $_.ajax({
        type: 'post',
        url: sr_ajaxUrl, // defined in <head>
        data: {
            action: 'update_int_map_data', // server controller
            data: pointsQ,
            parameters: query,
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

    this.drawCtrl = drawingManager;

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
            that.sendRequest([], {}).done(function(data) {
                that.handleRequest(that, data);
                that.loaded = true;
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

}


$_(document).ready(function() {

    classicGalleryToggle();
    classicGalleryTextToggle();
    advSearchFormToggler();
    listingSliderCarousel();

    if(document.getElementById('sr-map-search')) {

        if(typeof google === 'object' && typeof googlemaps === 'object') {
            // google.maps exists - start map
            startMap();

        } else {
            var key = document.getElementById('sr-map-search').dataset.apiKey;

            // if google.maps doesn't exist - load it, then start map
            var url = "https://maps.googleapis.com/maps/api/js?signed_in=true&libraries=drawing&callback=startMap" +
                      "&key=" + key;

            var script = document.createElement("script");

            script.type = "text/javascript";
            script.src = url;

            document.body.appendChild(script);

        }
    }

});
