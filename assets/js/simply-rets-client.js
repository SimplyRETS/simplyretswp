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


jQuery(document).ready(function() {

    var $_ = jQuery; // reassign jQuery


    // Single Listing Image Slider
    $_(".sr-slider-input").click(function() {
        var imgSrc = $_("input[name='slide_switch']:checked").val();
        $_(".sr-slider-img-act").fadeOut("fast", function() {
            $_(".sr-slider-img-act").attr('src', imgSrc);
            $_(".sr-slider-img-act").fadeIn("fast");
        });
    });


    // & toggle
    $_('#sr-toggle-gallery').click(function() {
        $_('.sr-slider label').toggle(100);
        if($_(this).text() == 'Hide photos') {
            $_(this).text('Show more photos');
        } else {
            $_(this).text('Hide photos');
        }
    });


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


    // [sr_listings_slider] default num of items
    $_("#simplyrets-listings-slider").owlCarousel({
        items: 4
    });


});
