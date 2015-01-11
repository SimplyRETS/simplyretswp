/*
 * 
 * Retsd.js
 * A Javascript library for Reichert Brothers RETSD API
 * Copyright (c) Reichert Brothers 2014
 * 
 */

console.log("This is RETSD");

jQuery(document).ready(function() {

  jQuery('#id0').attr('checked', 'checked');

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
    jQuery('.slider label').toggle(100);
    if(jQuery(this).text() == 'Hide photos') {
      jQuery(this).text('Show more photos');
    } else {
      jQuery(this).text('Hide photos');
    }
  });

});