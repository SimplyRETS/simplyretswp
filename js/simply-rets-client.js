/**
 *
 * simply-rets-client.js - Copyright (c) Reichert Brothers 2014
 *
 * This file provides the (minimal) client side javascript for the
 * SimplyRETS Wordpress plugin.
 * 
 * Author: Cody Reichert, Reichert Brothers
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

});
