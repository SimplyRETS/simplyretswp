/*
 * 
 * simple-rets-admin.js
 * Javascript for the admin functionality of the Simple Rets plugin.
 * Copyright (c) Reichert Brothers 2014
 * 
 */


jQuery(function() {

  console.log("This is simple-rets-admin.js");

  var filterSelectBox = jQuery('#sr-filter-select');
  // create jquery objects for all of our different input boxes
  var minPriceFilterInput = jQuery('#sr-min-price-span');
  var maxPriceFilterInput = jQuery('#sr-max-price-span');
  var minBedFilterInput   = jQuery('#sr-min-bed-span');
  var maxBedFilterInput   = jQuery('#sr-max-bed-span');
  var agentFilterInput    = jQuery('#sr-listing-agent-span');

  filterSelectBox.change(function() {

    console.log('filter select box changed');
    var filterVal = filterSelectBox.val();

    switch(filterVal) {
      case "Minimum Price":
          filterSelectBox.after(minPriceFilterInput);
          minPriceFilterInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      case "Maximum Price":
          filterSelectBox.after(maxPriceFilterInput);
          maxPriceFilterInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      case "Minimum Beds":
          filterSelectBox.after(minBedFilterInput);
          minBedFilterInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      case "Maximum Beds":
          filterSelectBox.after(maxBedFilterInput);
          maxBedFilterInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      case "Listing Agent":
          filterSelectBox.after(agentFilterInput);
          agentFilterInput.show();
          jQuery(this).find("option:selected").remove();
          break;
      default:
          break;
    }

    console.log(filterVal);

  });

  jQuery('.sr-remove-filter').click(function() {
      console.log('remove button cliked');
      jQuery(this).parent().remove();
  });

});
