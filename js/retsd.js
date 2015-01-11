/*
 * 
 * Retsd.js
 * A Javascript library for Reichert Brothers RETSD API
 * Copyright (c) Reichert Brothers 2014
 * 
 */

console.log("This is RETSD");

jQuery(document).ready(function() {
  jQuery(".sr-slider-input").click(function() {
    var imgSrc = jQuery("input[name='slide_switch']:checked").val();
    jQuery(".sr-slider-img-act").fadeOut("fast", function() {
      jQuery(".sr-slider-img-act").attr('src', imgSrc);
      jQuery(".sr-slider-img-act").fadeIn("fast");
    });
    console.log(imgSrc);
  });
});