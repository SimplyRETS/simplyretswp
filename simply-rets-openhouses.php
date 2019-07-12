<?php

/*
 * simply-rets-api-helper.php - Copyright (C) 2014-2015 SimplyRETS, Inc.
 *
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
*/

/* Code starts here */

class SimplyRetsOpenHouses {

    /**
     * Generate markup /openhouses search response.
     */
    public static function openHousesSearchResults($search_response) {
        $markup = "";

        if(!array_key_exists("response", $search_response)) {
            return $markup;
        }

        foreach($search_response["response"] as $idx=>$oh) {
            $markup .= SimplyRetsOpenHouses::openHouseSearchResultMarkup($oh);
        }

        return $markup;
    }

    /**
     * Generate markup for a single open house search result
     */
    public static function openHouseSearchResultMarkup($openhouse) {
        $listing = $openhouse->listing;
        $full_address = SrUtils::buildFullAddressString($listing);
        $details_link = SrUtils::buildDetailsLink($listing);
        $list_price_fmtd = '$' . number_format($listing->listPrice);
        $listing_id = $listing->listingId;

        $dummy = plugins_url( 'assets/img/defprop.jpg', __FILE__ );
        $main_photo = !empty($listing->photos) ? $listing->photos[0] : $dummy;
        $photo_style = "background-image:url('$main_photo');background-size:cover;";

        $listing_office = $listing->office->name;
        $listing_agent = $listing->agent->firstName . ' ' . $listing->agent->lastName;
        $compliance_markup = SrUtils::mkListingSummaryCompliance(
            $listing_office,
            $listing_agent
        );

        date_default_timezone_set("America/Los_Angeles");
        $date = date("F j, Y", strtotime($openhouse->startTime));
        $day = date("F j", strtotime($openhouse->startTime));
        $start = date("g:ia", strtotime($openhouse->startTime));
        $end = date("g:ia", strtotime($openhouse->endTime));
        $tz = date("e", strtotime($openhouse->startTime));
        $time = "<span>{$start} - {$end}</span>";

        $status = $listing->mls->status;
        $bedrooms = !empty($listing->property->bedrooms)
                  ? "<strong>Bedrooms: </strong> {$listing->property->bedrooms}<br/>"
                  : "";

        $bathrooms = !empty($listing->property->bathrooms)
                   ? "<strong>Bathrooms: </strong> {$listing->property->bathrooms}<br/>"
                   : !empty($listing->bathsFull)
                   ? "<strong>Baths full: </strong> {$listing->property->bathsFull}<br/>"
                   : "";

        $mls_area = $listing->mls->area;
        $county = $listing->geo->county;
        $area = !empty($mls_area)
              ? strlen($mls_area) >= 50 ? "{$mls_area}..." : "{$mls_area}"
              : !empty($county)
              ? strlen($county) >= 50 ? "{$county}..." : "{$county}"
              : "";
        $area = "<strong>Area: </strong> {$area}<br/>";

        $living_area = !empty($listing->property->area)
                     ? number_format($listing->property->area)
                     : "";

        $sqft = !empty($living_area) ? "<strong>SqFt: </strong>{$living_area}sqft<br/>" : "";

        $banner_style = "position:absolute;z-index:1;padding:10px;font-size:1.1rem;width:30%;"
                      . "background-color:green;border-radius:2px;color:white;bottom:3%";

        $open_house_banner = "<div style=\"{$banner_style}\">"
                      . "Open house {$day}, {$time}"
                      . "</div>";

        return <<<HTML
          <hr>
          <div class="sr-listing">
            <a href="$details_link" style="text-decoration:none">
              $open_house_banner
              <div class="sr-photo" style="$photo_style">
              </div>
            </a>
            <div class="sr-listing-data-wrapper">
              <div class="sr-primary-data">
                <a href="$details_link">
                  <h4>$full_address
                    <small class="sr-price"><i> - $list_price_fmtd</i></small>
                  </h4>
                </a>
              </div>
              <div class="sr-secondary-data">
                <p class="sr-data-column">
                  <strong>Listing status: </strong> $status<br/>
                  <strong>MLS #: </strong> $listing_id<br/>
                  $area
                </p>
                <p class="sr-data-column">
                  $bedrooms
                  $bathrooms
                  $sqft
                </p>
              </div>
            </div>
            <div class="more-details-wrapper">
              <span style="visibility:hidden">clearfix</span>
              <span class="more-details-link" style="float:right">
                  <a href="$details_link">More details</a>
              </span>
              <span class="result-compliance-markup">
                $compliance_markup
              </span>
            </div>
          </div>
HTML;
    }
}
