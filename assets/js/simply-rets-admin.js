/*
 * 
 * simply-rets-admin.js
 * Javascript for the admin functionality of the Simple Rets plugin.
 * Copyright (c) 2014-2015 SimplyRETS
 * 
 */


jQuery(function() {

  // create jquery objects for all of our different input boxes
  var minPriceFilterInput = jQuery('#sr-min-price-span');
  var maxPriceFilterInput = jQuery('#sr-max-price-span');
  var minBedFilterInput   = jQuery('#sr-min-bed-span');
  var maxBedFilterInput   = jQuery('#sr-max-bed-span');
  var minBathFilterInput  = jQuery('#sr-min-bath-span');
  var maxBathFilterInput  = jQuery('#sr-max-bath-span');
  var agentFilterInput    = jQuery('#sr-listing-agent-span');
  var listingTypeSelect   = jQuery('#sr-listing-type-span');
  var limitInput          = jQuery('#sr-limit-span');

  var filterArea      = jQuery('.current-filters');
  var filterSelectBox = jQuery('#sr-filter-select');
  filterSelectBox.change(function() {

    var filterVal = filterSelectBox.val();

    // when a new filter is selected, show the input box and remove the
    // option from the dropdown menu
    switch(filterVal) {
      case "Minimum Price":
          filterArea.append(minPriceFilterInput);
          minPriceFilterInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      case "Maximum Price":
          filterArea.append(maxPriceFilterInput);
          maxPriceFilterInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      case "Minimum Beds":
          filterArea.append(minBedFilterInput);
          minBedFilterInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      case "Maximum Beds":
          filterArea.append(maxBedFilterInput);
          maxBedFilterInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      case "Minimum Bathrooms":
          filterArea.append(minBathFilterInput);
          minBathFilterInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      case "Maximum Bathrooms":
          filterArea.append(maxBathFilterInput);
          maxBathFilterInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      case "Listing Agent":
          filterArea.append(agentFilterInput);
          agentFilterInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      case "Listing Type":
          filterArea.append(listingTypeSelect);
          listingTypeSelect.show();
          jQuery(this).find("option:selected").remove();
          break;
      case "Amount of listings":
          filterArea.append(limitInput);
          limitInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      default:
          break;
    }

  });

  jQuery('.sr-remove-filter').click(function() {
      jQuery(this).parent('.sr-filter-input').hide();
      jQuery(this).prev('input').val("");
  });

});
