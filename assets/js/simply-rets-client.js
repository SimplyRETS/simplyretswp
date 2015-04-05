/**
 *
 * simply-rets-client.js - Copyright (c) 2014-2015 SimplyRETS
 *
 * This file provides the (minimal) client side javascript for the
 * SimplyRETS Wordpress plugin.
 * 
 * Author: Cody Reichert
 * License: GPLv3 (http://www.gnu.org/licenses/gpl.html)
 *
**/

jQuery(document).ready(function() {

  // Single Listing Image Slider
  jQuery(".sr-slider-input").click(function() {
    var imgSrc = jQuery("input[name='slide_switch']:checked").val();
    jQuery(".sr-slider-img-act").fadeOut("fast", function() {
      jQuery(".sr-slider-img-act").attr('src', imgSrc);
      jQuery(".sr-slider-img-act").fadeIn("fast");
    });
  });
  // & toggle
  jQuery('#sr-toggle-gallery').click(function() {
    jQuery('.sr-slider label').toggle(100);
    if(jQuery(this).text() == 'Hide photos') {
      jQuery(this).text('Show more photos');
    } else {
      jQuery(this).text('Hide photos');
    }
  });


  // function mapInit() {
  //     var lat = srMapLat,
  //         lng = srMapLng,
  //         address = srMapAddr,
  //         mapOptions = {
  //             zoom: 8
  //         }

  //     var map = new google.maps.Map(jQuery('#sr-map-canvas')[0], mapOptions);

  //     console.log(lat);
  //     console.log(lng);
  //     console.log(address);

  //     // if lat/lng is empty, geocode the address
  //     if(lat == "" && lng == "") {

  //         console.log('hello');
  //         var geocoder = new google.maps.Geocoder();
  //         geocoder.geocode( { 'address': address}, function(results, status) {
  //             if (status == google.maps.GeocoderStatus.OK) {
  //                 map.setCenter(results[0].geometry.location);
  //                 var marker = new google.maps.Marker({
  //                     map: map,
  //                     position: results[0].geometry.location
  //                 });
  //             } else {
  //                 console.log('Geocode was not successful for the following reason: ' + status);
  //             }
  //         });

  //     // else use use the lat/lng
  //     } else {
  //         var latlng = new google.maps.LatLng(lat, lng);
  //         var marker = new google.maps.Marker({
  //                 position: latlng,
  //                 map: map,
  //                 title: address
  //         });
  //         map.setCenter(marker.getPosition());
  //     }

  // }

  // google.maps.event.addDomListener(window, 'load', mapInit);

});
