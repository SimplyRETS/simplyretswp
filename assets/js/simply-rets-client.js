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

  if(jQuery('#sr-search-ptype select').val() == 'Land') {
      jQuery('.sr-adv-search-amenities-wrapper').hide();
  }
  jQuery('#sr-search-ptype select').change(function() {
      if(jQuery(this).val() == 'Land') {
          jQuery('.sr-adv-search-amenities-wrapper').hide();
          jQuery('input[name="sr_features[]"]').each(function() {
              jQuery(this).attr('checked', false);
          });
      } else {
          jQuery('.sr-adv-search-amenities-wrapper').show();
      }
  });

  // [sr_listings_slider]
  jQuery("#simplyrets-listings-slider").owlCarousel({
    items: 4
  });

});
