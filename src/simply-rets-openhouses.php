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
     * Get open house data for a listingId.
     * Return an empty array if no openhouses exist.
     */
    public static function getOpenHousesByListingId($listing_id) {
        $params = array_filter([
            "listingId" => $listing_id,
            "startdate" => date("Y-m-d"),
            "vendor" => get_query_var("sr_vendor", NULL)
        ]);

        $response = SimplyRetsApiHelper::makeApiRequest($params, "openhouses");
        $data = $response["response"];

        if (!is_array($data) && property_exists($data, "error")) {
            return array();
        } else {
            return $data;
        }
    }

    public static function getOpenHouseDateTimes($openhouse) {
        /**
         * Some MLS's don't use UTC timestamps; this allows the user
         * to specify a timezone used to parse/convert the MLS's open
         * house times for display.
         */
        $default_time_zone = get_option("sr_date_default_timezone", "");
        if (!empty($default_time_zone)) {
            date_default_timezone_set($default_time_zone);
        }

        // Open house date information
        $date = date("M jS", strtotime($openhouse->startTime));
        $day = date("D", strtotime($openhouse->startTime));
        $day_date = "<span>{$day}, {$date}</span>";

        // Open house time information
        $start = date("g:ia", strtotime($openhouse->startTime));
        $end = date("g:ia", strtotime($openhouse->endTime));
        $start_end_time = "<span>{$start} - {$end}</span>";

        return array(
            "day" => $day_date,
            "time" => $start_end_time
        );
    }

    /**
     * Generate markup /openhouses search response.
     */
    public static function openHousesSearchResults($search_response, $settings) {
        $res = $search_response["response"];
        $pag = $search_response["pagination"];

        $markup = "";
        $pagination = SrUtils::buildPaginationLinks($pag);

        if(array_key_exists("error", $res)) {

            $markup .= <<<HTML
              <div class="sr-error-message">
                <p>
                  <strong>Error: {$res->error}</strong>
                </p>
              </div>
HTML;

        } else if (count($res) === 0) {

            return SrMessages::noResultsMsg($res);

        } else {

            // Generate markup for each open house result
            foreach($res as $idx=>$oh) {
                $markup .= SimplyRetsOpenHouses::openHouseSearchResultMarkup(
                    $oh,
                    $settings
                );
            }

            $markup .= <<<HTML
              <div class="sr-pagination-wrapper">
                <hr/>
                {$pagination["prev"]} {$pagination["next"]}
              </div>
HTML;
        }

        return $markup;
    }

    /**
     * Generate markup for a single open house search result
     */
    public static function openHouseSearchResultMarkup($openhouse, $settings) {
        $listing = $openhouse->listing;
        $full_address = SrUtils::buildFullAddressString($listing);
        $list_price_fmtd = '$' . number_format($listing->listPrice);
        $listing_id = $listing->listingId;

        // Photo markup and styles
        $main_photo = SrListing::mainPhotoOrDefault($listing);
        $photo_style = "background-image:url('$main_photo');background-size:cover;";

        // Agent/office compliance markup
        $listing_office = $listing->office->name;
        $listing_agent = $listing->agent->firstName . ' ' . $listing->agent->lastName;
        $compliance_markup = SrUtils::mkListingSummaryCompliance(
            $listing_office,
            $listing_agent
        );

        // Listing details page link
        $link_settings = array_key_exists("vendor", $settings) ? array(
            "sr_vendor" => $settings["vendor"]
        ) : array();

        $details_link = SrUtils::buildDetailsLink($listing, $link_settings);

        // Open house times
        $openhouse_times = SimplyRetsOpenHouses::getOpenHouseDateTimes($openhouse);
        $day = $openhouse_times["day"];
        $time = $openhouse_times["time"];

        $banner_style = "position:absolute;z-index:1;padding:10px;font-size:1.2rem;width:30%;"
                      . "background-color:green;border-radius:2px;color:white;bottom:3%;"
                      . "line-height:1.5";

        $open_house_banner = "<div style=\"{$banner_style}\">"
                           . "  <strong>Open house</strong>"
                           . "  <br/>"
                           . "  {$day} &middot; {$time}"
                           . "</div>";

        $status = $listing->mls->status;
        $bedrooms = !empty($listing->property->bedrooms)
                  ? "<strong>Bedrooms: </strong> {$listing->property->bedrooms}<br/>"
                  : "";

        $bathrooms = (!empty($listing->property->bathrooms)
                   ? "<strong>Bathrooms: </strong> {$listing->property->bathrooms}<br/>"
                   : !empty($listing->property->bathsFull))
                   ? "<strong>Full baths: </strong> {$listing->property->bathsFull}<br/>"
                   : "";

        $mls_area = $listing->mls->area;
        $county = $listing->geo->county;
        $city = $listing->address->city;

        // Find a non-empty field for geographical location
        $area = !empty($mls_area) ? "<strong>MLS area: </strong> {$mls_area}<br/>" : "";
        $area = empty($area) && !empty($county) ? "<strong>County: </strong> {$county}<br/>" : "";
        $area = empty($area) && !empty($city) ? "<strong>City: </strong> {$city}<br/>" : "";

        $living_area = !empty($listing->property->area)
                     ? number_format($listing->property->area)
                     : "";

        $sqft = !empty($living_area) ? "<strong>SqFt: </strong>{$living_area} sqft<br/>" : "";

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
                  <strong>Status: </strong> $status<br/>
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
