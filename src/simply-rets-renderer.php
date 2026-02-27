<?php
/*
 * simply-rets-renderer.php - Copyright (C) 2014-2024 SimplyRETS, Inc.
 * This file provides the SimplyRetsRenderer class for HTML generation.
 */

class SimplyRetsRenderer {

    /**
     * Run fields through this function before rendering them on single listing
     * pages to hide fields that are null.
     */
    public static function srDetailsTable($val, $name, $additional = NULL, $desc = NULL) {
        if( $val == "" ) {
            $val = "";
        } else {
            $data_attr = str_replace(" ", "-", strtolower($name));
            if(!$additional && !$desc) {
                $val = '<tr data-attribute="' . $data_attr . '">'
                     . '  <td>' . $name . '</td>'
                     . '  <td colspan="2">' . $val . '</td>'
                     . '</tr>';
            } elseif ($additional && !$desc) {
                $val = '<tr data-attribute="' . $data_attr . '">'
                     . '  <td>' . $name . '</td>'
                     . '  <td>' . $val . '</td>'
                     . '  <td>' . $additional . '</td>'
                     . '</tr>';
            } else {
                $val = '<tr data-attribute="' . $data_attr . '">'
                     . '  <td rowspan="2" style="vertical-align: middle;border-bottom:solid 1px #eee;">'
                     .       $name
                     . '  </td>'
                     . '  <td colspan="1">' . $val . '</td>'
                     . '  <td colspan="1">' . $additional . '</td>'
                     . '  </tr>'
                     . '  <tr data-attribute="' . $data_attr . '">'
                     . '  <td colspan="2">' . $desc . '</td>'
                     . '</tr>';
            }
        }
        return $val;
    }


    /**
     * Build the photo gallery shown on single listing details pages
     */
    public static function srDetailsGallery($listing) {
        $photos = $listing->photos;
        $photo_gallery = array();

        if( empty($photos) ) {
            $main_photo = SrListing::mainPhotoOrDefault($listing);
            $main_photo_url = esc_url($main_photo);
            // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
            $markup = "<img src='$main_photo_url'>";
            $photo_gallery['markup'] = $markup;
            $photo_gallery['more']   = '';
            return $photo_gallery;

        } else {
            $markup = '';
            if(get_option('sr_listing_gallery') == 'classic') {
                $photo_counter = 0;
                $main_photo = esc_url($photos[0]);
                $more = '<span id="sr-toggle-gallery">See more photos</span> |';
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                $markup .= "<div class='sr-slider'><img class='sr-slider-img-act' src='$main_photo'>";
                foreach( $photos as $photo ) {
                    $image = SrListing::normalizeListingPhotoUrl($photo);
                    $image_url = esc_url($image);

                    $markup .=
                        "<input class='sr-slider-input' type='radio' name='slide_switch' id='id$photo_counter' value='$photo' />";
                    $markup .= "<label for='id$photo_counter'>";
                    // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                    $markup .= "  <img src='$image_url' width='100'>";
                    $markup .= "</label>";
                    $photo_counter++;
                }
                $markup .= "</div>";
                $photo_gallery['markup'] = $markup;
                $photo_gallery['more'] = $more;
                return $photo_gallery;

            } else {
                // Details shown for each image in the gallery
                $photos_count = count($photos);
                $full_address = SrUtils::buildFullAddressString($listing);
                $remarks_ellipsis = strlen($listing->remarks) >= 200 ? "..." : "";
                $remarks = substr($listing->remarks, 0, 200) . $remarks_ellipsis;
                $remarks_html = esc_html($remarks);

                $description_style = "font-style:normal;"
                                   . "font:initial;"
                                   . "font-size:13px;"
                                   . "padding-top:10px;"
                                   . "line-height:1.25";

                $more = '';
                $markup .= '<div class="sr-gallery" id="sr-fancy-gallery">';

                foreach( $photos as $idx=>$photo ) {
                    $num = $idx + 1;
                    $image = SrListing::normalizeListingPhotoUrl($photo);
                    $image_url = esc_url($image);

                    $img_description = "<div>"
                                     . "  <div>Photo {$num} of {$photos_count}</div>"
                                     . "  <div style=\"{$description_style}\">"
                                     . "    {$remarks_html}"
                                     . "  </div>"
                                     . "</div>";

                    // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                    $markup .= "<img src='$image_url' "
                            . "data-title='$full_address'"
                            . "data-description='" . htmlentities($img_description) . "'>";
                }

                $markup .= "</div>";
                $photo_gallery['markup'] = $markup;
                $photo_gallery['more'] = $more;

                return $photo_gallery;
            }
        }
        return $photo_gallery;

    }


    public static function srResidentialDetailsGenerator( $listing ) {
        $br = "<br>";
        $cont = "";
        $contact_page = get_option('sr_contact_page');

        // Boolean for fetching open houses
        $has_openhouses = in_array(
            "/openhouses",
            (array)get_option("sr_adv_search_meta_endpoints", array())
        );

        $last_update = $listing['lastUpdate'];
        $listing = $listing['response'];
        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if($listing == NULL
           || property_exists($listing, "error")
           || property_exists($listing, "message")
           || property_exists($listing, "errors")) {
            $err = SrMessages::noResultsMsg((array)$listing);
            return $err;
        }

        // internal unique id
        $listing_uid = $listing->mlsId;

        // Get the text "MLS"
        $MLS_text = SrUtils::mkMLSText();

        // Display permissions
        $internetAddressDisplay = $listing->internetAddressDisplay;

        /**
         * Get the listing status to show. Note that if the
         * sr_show_mls_status_text admin option is set to true, we
         * will show the listing's "statusText" and not the normalized
         * status.
         */
        $listing_mls_status = SrListing::listingStatus($listing);
        $mls_status = SimplyRetsRenderer::srDetailsTable($listing_mls_status, $MLS_text . " Status");

        // price
        $price_to_display = SrUtils::formatListingPrice($listing, false);
        $price = SimplyRetsRenderer::srDetailsTable($price_to_display, "Price");

        // close price
        $listing_close_price = "";
        if (!empty($listing->sales) && !empty($listing->sales->closePrice)) {
            $listing_close_price = SrUtils::formatListingPrice($listing, true);
        }
        $close_price = SimplyRetsRenderer::srDetailsTable(
            $listing_close_price, "Close Price"
        );
        // DOM
        $listing_days_on_market = $listing->mls->daysOnMarket;
        $days_on_market = SimplyRetsRenderer::srDetailsTable($listing_days_on_market, "Days on market");
        // type
        $listing_type = SrUtils::normalizePropertyType($listing->property->type);
        $type = SimplyRetsRenderer::srDetailsTable($listing_type, "Property Type");
        // subtype
        $listing_subType = $listing->property->subType;
        $subType = SimplyRetsRenderer::srDetailsTable($listing_subType, "Sub type");
        $listing_subTypeText = $listing->property->subTypeText;
        $subTypeText = SimplyRetsRenderer::srDetailsTable($listing_subTypeText, $MLS_text . " Sub type");
        // bedrooms
        $listing_bedrooms = $listing->property->bedrooms;
        $bedrooms = SimplyRetsRenderer::srDetailsTable($listing_bedrooms, "Bedrooms");
        // full baths
        $listing_bathsFull = $listing->property->bathsFull;
        $bathsFull = SimplyRetsRenderer::srDetailsTable($listing_bathsFull, "Full Baths");
        // half baths
        $listing_bathsHalf = $listing->property->bathsHalf;
        $bathsHalf = SimplyRetsRenderer::srDetailsTable($listing_bathsHalf, "Half Baths");
        // total baths
        $listing_bathsTotal = $listing->property->bathrooms;
        $bathsTotal = SimplyRetsRenderer::srDetailsTable($listing_bathsTotal, "Total Baths");
        // stories
        $listing_stories = $listing->property->stories;
        $stories = SimplyRetsRenderer::srDetailsTable($listing_stories, "Stories");
        // fireplaces
        $listing_fireplaces = $listing->property->fireplaces;
        $fireplaces = SimplyRetsRenderer::srDetailsTable($listing_fireplaces, "Fireplaces");
        // Long
        $listing_longitude = $internetAddressDisplay === FALSE ? NULL : $listing->geo->lng;
        $geo_longitude = SimplyRetsRenderer::srDetailsTable($listing_longitude, "Longitude");
        // Long
        $listing_lat = $internetAddressDisplay === FALSE ? NULL : $listing->geo->lat;
        $geo_latitude = SimplyRetsRenderer::srDetailsTable($listing_lat, "Latitude");
        // County
        $listing_county = $listing->geo->county;
        $geo_county = SimplyRetsRenderer::srDetailsTable($listing_county, "County");
        // County
        $listing_directions = $listing->geo->directions;
        $geo_directions = SimplyRetsRenderer::srDetailsTable($listing_directions , "Directions");
        // Market Area
        $listing_market_area = $listing->geo->marketArea;
        $geo_market_area = SimplyRetsRenderer::srDetailsTable($listing_market_area, "Market Area");
        // mls area
        $listing_mlsarea = $listing->mls->area;
        $mls_area = SimplyRetsRenderer::srDetailsTable($listing_mlsarea, $MLS_text . " Area");
        // mls area minor
        $mls_area_minor = SimplyRetsRenderer::srDetailsTable(
            $listing->mls->areaMinor,
            $MLS_text . " MLS area minor"
        );
        // tax data
        $listing_taxdata = $listing->tax->id;
        $tax_data = SimplyRetsRenderer::srDetailsTable($listing_taxdata, "Tax ID");
        // tax year
        $listing_tax_year = $listing->tax->taxYear;
        $tax_year = SimplyRetsRenderer::srDetailsTable($listing_tax_year, "Tax Year");
        // tax annual amount
        $listing_tax_annual_amount = $listing->tax->taxAnnualAmount;
        $tax_annual_amount = SimplyRetsRenderer::srDetailsTable($listing_tax_annual_amount, "Tax Annual Amount");
        // roof
        $listing_roof = $listing->property->roof;
        $roof = SimplyRetsRenderer::srDetailsTable($listing_roof, "Roof");
        // style
        $listing_style = $listing->property->style;
        $style = SimplyRetsRenderer::srDetailsTable($listing_style, "Property Style");
        // subdivision
        $listing_subdivision = $listing->property->subdivision;
        $subdivision = SimplyRetsRenderer::srDetailsTable($listing_subdivision, "Subdivision");
        // unit
        $listing_unit_value = $listing->address->unit;
        $listing_unit = $internetAddressDisplay === FALSE ? NULL : $listing_unit_value;
        $unit = SimplyRetsRenderer::srDetailsTable($listing_unit, "Unit");
        // int/ext features
        $listing_interiorFeatures = $listing->property->interiorFeatures;
        $interiorFeatures = SimplyRetsRenderer::srDetailsTable($listing_interiorFeatures, "Features");
        // int/ext features
        $listing_exteriorFeatures = $listing->property->exteriorFeatures;
        $exteriorFeatures = SimplyRetsRenderer::srDetailsTable($listing_exteriorFeatures, "Exterior Features");
        // year built
        $listing_yearBuilt = $listing->property->yearBuilt;
        $yearBuilt = SimplyRetsRenderer::srDetailsTable($listing_yearBuilt, "Year Built");
        // listing id (MLS #)
        $listing_mlsid = $listing->listingId;
        $mlsid = SimplyRetsRenderer::srDetailsTable($listing_mlsid, $MLS_text . " #");
        // heating
        $listing_heating = $listing->property->heating;
        $heating = SimplyRetsRenderer::srDetailsTable($listing_heating, "Heating");
        // foundation
        $listing_foundation = $listing->property->foundation;
        $foundation = SimplyRetsRenderer::srDetailsTable($listing_foundation, "Foundation");
        // laundry features
        $listing_laundry = $listing->property->laundryFeatures;
        $laundry_features = SimplyRetsRenderer::srDetailsTable($listing_laundry, "Laundry Features");
        // lot description
        $listing_lot_descrip = $listing->property->lotDescription;
        $lot_description = SimplyRetsRenderer::srDetailsTable($listing_lot_descrip, "Lot Description");
        // additional rooms
        $listing_rooms = $listing->property->additionalRooms;
        $additional_rooms = SimplyRetsRenderer::srDetailsTable($listing_rooms, "Additional Rooms");
        // view
        $listing_view = $listing->property->view;
        $view = SimplyRetsRenderer::srDetailsTable($listing_view, "View");
        // accessibility
        $listing_accessibility = $listing->property->accessibility;
        $accessibility = SimplyRetsRenderer::srDetailsTable($listing_accessibility, "Accessibility");
        // waterfront
        $listing_water = $listing->property->water;
        $water = SimplyRetsRenderer::srDetailsTable($listing_water, "Waterfront");
        // listing date
        $listing_list_date = $listing->listDate;
        $list_date_formatted = $listing_list_date
                             ? gmdate("M j, Y", strtotime($listing_list_date))
                             : null;
        $list_date = SimplyRetsRenderer::srDetailsTable($list_date_formatted, "Listing Date");
        // listing date modified
        $listing_modified = $listing->modified;
        if($listing_modified) { $date_modified = gmdate("M j, Y", strtotime($listing_modified)); }
        $date_modified_markup = SimplyRetsRenderer::srDetailsTable($date_modified, "Listing Last Modified");
        // lot size
        $listing_lotSize = $listing->property->lotSize;
        $lotsize_markup  = SimplyRetsRenderer::srDetailsTable($listing_lotSize, "Lot Size");
        // lot size area
        $listing_lotSizeArea = $listing->property->lotSizeArea;
        $lotsizearea_markup  = SimplyRetsRenderer::srDetailsTable($listing_lotSizeArea, "Lot Size Area");
        // lot size area units
        $listing_lotSizeAreaUnits = $listing->property->lotSizeAreaUnits;
        $lotsizeareaunits_markup  = SimplyRetsRenderer::srDetailsTable($listing_lotSizeAreaUnits, "Lot Size Area Units");
        // acres
        $listing_acres = $listing->property->acres;
        $acres_markup  = SimplyRetsRenderer::srDetailsTable($listing_acres, "Acres");
        // street address info
        $listing_postal_code = $listing->address->postalCode;
        $postal_code = SimplyRetsRenderer::srDetailsTable($listing_postal_code, "Postal Code");

        $listing_country = $listing->address->country;
        $country = SimplyRetsRenderer::srDetailsTable($listing_country, "Country");

        $listing_address = $listing->address->full;
        $full_address = SrUtils::buildFullAddressString($listing);
        $address = SimplyRetsRenderer::srDetailsTable($full_address, "Address");

        $listing_city = $listing->address->city;
        $city = SimplyRetsRenderer::srDetailsTable($listing_city, "City");

        $listing_cross_street = $listing->address->crossStreet;
        $cross_street = SimplyRetsRenderer::srDetailsTable($listing_cross_street, "Cross Street");

        $listing_state = $listing->address->state;
        $state = SimplyRetsRenderer::srDetailsTable($listing_state, "State");

        $listing_terms = $listing->terms;
        $terms = SimplyRetsRenderer::srDetailsTable($listing_terms, "Terms");

        $listing_special_listing_conditions = $listing->specialListingConditions;
        $special_listing_conditions = SimplyRetsRenderer::srDetailsTable(
            $listing_special_listing_conditions, "Special listing conditions"
        );

        $listing_ownership = $listing->ownership;
        $ownership = SimplyRetsRenderer::srDetailsTable(
            $listing_ownership, "Ownership"
        );

        // Compliance/compensation data
        $complianceData = $listing->compliance;
        $complianceExtras = "";
        foreach ($complianceData as $compKey => $compValue) {
            // Normalize camelCase keys to words
            $compKey = ucfirst(preg_replace('/(?<=\\w)(?=[A-Z])/', ' $1', $compKey));
            $compKey = preg_replace(['/\bMls\b/', '/\bM L S\b/'], 'MLS', $compKey);
            $complianceExtras .= SimplyRetsRenderer::srDetailsTable($compValue, $compKey);
        }

        $compensationDisclaimer = "";
        if (!empty($complianceExtras)) {
            $compensationDisclaimer .= SimplyRetsRenderer::srDetailsTable(
                "The offer of compensation is made only to participants of " .
                "the MLS where the listing is filed.",
                "Compensation Disclaimer"
            );
        }

        $listing_lease_term = $listing->leaseTerm;
        $lease_term = SimplyRetsRenderer::srDetailsTable($listing_lease_term, "Lease Term");

        $listing_lease_type = $listing->leaseType;
        $lease_type = SimplyRetsRenderer::srDetailsTable($listing_lease_type, "Lease Type");

        $listing_pool = $listing->property->pool;
        $pool = SimplyRetsRenderer::srDetailsTable($listing_pool, "Pool features");

        // Garage and Parking info
        $listing_garage_spaces = $listing->property->garageSpaces;
        $garage_spaces = SimplyRetsRenderer::srDetailsTable($listing_garage_spaces, "Garage spaces");

        $listing_parking_spaces = $listing->property->parking->spaces;
        $parking_spaces = SimplyRetsRenderer::srDetailsTable($listing_parking_spaces, "Parking Spaces");

        $listing_parking_description = $listing->property->parking->description;
        $parking_description = SimplyRetsRenderer::srDetailsTable(
            $listing_parking_description, "Parking Description"
        );

        // association data
        $listing_association_fee = $listing->association->fee;
        $association_fee = SimplyRetsRenderer::srDetailsTable($listing_association_fee, "Association Fee");

        $listing_association_name = $listing->association->name;
        $association_name = SimplyRetsRenderer::srDetailsTable($listing_association_name, "Association Name");

        $listing_association_amenities = $listing->association->amenities;
        $association_amenities = SimplyRetsRenderer::srDetailsTable(
            $listing_association_amenities, "Association Amenities"
        );

        // Virtual tour URL
        $listing_virtual_tour = $listing->virtualTourUrl;
        if (!empty($listing_virtual_tour)) {
            // Make the URL a link
            $listing_virtual_tour = "<a href='$listing_virtual_tour' target='_blank'>"
                                  . $listing_virtual_tour
                                  . "</a>";

        }

        $virtual_tour = SimplyRetsRenderer::srDetailsTable($listing_virtual_tour, "Virtual Tour URL");


        // area
        $area = $listing->property->area == 0
              ? 'n/a'
              : number_format($listing->property->area);


        // Determine the best field to show in the primary-details section
        $primary_baths = SrListing::getBathroomsDisplay(
            $listing_bathsTotal,
            $listing_bathsFull,
            true
        );


        if( $listing_bedrooms == null || $listing_bedrooms == "" ) {
            $listing_bedrooms = 0;
        }
        if( $listing_bathsFull == null || $listing_bathsFull == "" ) {
            $listing_bathsFull = 0;
        }


        // Rooms data
        $roomsMarkup = '';
        $has_rooms = !empty($listing->property->rooms)
                   AND is_array($listing->property->rooms);

        if($has_rooms == TRUE) {
            $rooms = $listing->property->rooms;

            usort($rooms, function ($a, $b) {
                return (is_null($a->level) OR $a->level == "") ? 1 : -1;
            });

            $roomsMarkup .= count($rooms) < 1 ? "" : "
              <thead>
                <tr>
                  <th colspan=\"3\"><h5>Room Details</h5></th></tr></thead>";

            foreach($rooms as $room) {

                if(!is_null($room->dimensions)) {
                    $roomSize = $room->dimensions;
                } else {
                    $roomSize = "$room->length" .  " x " . "$room->width";
                }
                $level = $room->level;
                $levelText = empty($level) ? '' : SrUtils::ordinalSuffix($level) . " level";
                $roomsMarkup .= SimplyRetsRenderer::srDetailsTable(
                    $roomSize,
                    $room->typeText,
                    $levelText,
                    $room->description
                );
            }
        }

        // photo gallery
        $photos         = $listing->photos;
        $photo_gallery  = SimplyRetsRenderer::srDetailsGallery($listing);
        $gallery_markup = $photo_gallery['markup'];
        $more_photos    = $photo_gallery['more'];

        $default_photo = SrListing::$default_photo;
        $main_photo = SrListing::mainPhotoOrDefault($listing);

        // geographic data
        $geo_table_header = "";
        if($geo_directions
           || $listing_lat
           || $listing_longitude
           || $listing_county
           || $listing_market_area
        ) {
            $geo_table_header = '<thead>'
                              . '  <tr>'
                              . '    <th colspan="3">'
                              . '      <h5>Geographic Data</h5>'
                              . '    </th>'
                              . '  </tr>'
                              . '</thead>'
                              . '<tbody>';
        }

        // school data
        $has_school_data = !empty($listing->school);

        $listing_school_district = $has_school_data ? $listing->school->district : NULL;
        $school_district = SimplyRetsRenderer::srDetailsTable($listing_school_district, "District");
        // elementary school
        $listing_elementary = $has_school_data ? $listing->school->elementarySchool : NULL;
        $school_elementary = SimplyRetsRenderer::srDetailsTable($listing_elementary, "Elementary School");
        // middle school
        $listing_middle_school = $has_school_data ? $listing->school->middleSchool : NULL;
        $school_middle = SimplyRetsRenderer::srDetailsTable($listing_middle_school, "Middle School");
        // high school
        $listing_high_school = $has_school_data ? $listing->school->highSchool : NULL;
        $school_high = SimplyRetsRenderer::srDetailsTable($listing_high_school, "High School");

        $school_data = "";
        if($listing_school_district
           || $listing_elementary
           || $listing_middle_school
           || $listing_high_school
        ) {
            $school_data = '<thead>'
                         . '  <tr>'
                         . '    <th colspan="3">'
                         . '      <h5>School Information</h5>'
                         . '    </th>'
                         . '  </tr>'
                         . '</thead>'
                         . '<tbody>'
                         .    $school_district
                         .    $school_elementary
                         .    $school_middle
                         .    $school_high
                         . '</tbody>';
        }

        // list date and listing last modified
        $show_listing_meta = SrUtils::srShowListingMeta();
        if($show_listing_meta !== true) {
            $list_date = '';
            $date_modified_markup = '';
            $tax_data = '';
            $tax_year = '';
            $tax_annual_amount = '';
        }

        if( get_option('sr_show_listing_remarks') ) {
            $remarks_markup = "";
        } else {
            $remarks = $listing->remarks;
            $remarks_markup = '<div class="sr-remarks-details">'
                            . '  <p>' . $remarks . '</p>'
                            . '</div>';
        }

        if( get_option('sr_show_leadcapture') ) {
            $contact_text = 'Contact us about this listing';
            $cf_listing = $full_address . ' ( ' . $MLS_text . ' #' . $listing_mlsid . ' )';
            $contact_markup = SimplyRetsRenderer::srContactFormMarkup($cf_listing);
        } else {
            $contact_text = '';
            $contact_markup = '';
        }


        /**
         * Check for ListHub Analytics
         */
        if( get_option( 'sr_listhub_analytics' ) ) {

            $lh_analytics = SimplyRetsApiHelper::srListhubAnalytics();
            $lh_id = get_option('sr_listhub_analytics_id', false);
            $lh_test = get_option('sr_listhub_analytics_test_events') ? 1 : false;

            if($lh_id) {
                $lh_send_details = SimplyRetsApiHelper::srListhubSendDetails(
                    $lh_id,
                    $lh_test,
                    $listing_mlsid,
                    $listing_postal_code
                );

                $lh_analytics .= $lh_send_details;
            }
        } else {
            $lh_analytics = '';
        }

        ///////////////////////////////////////////////////////

        $show_contact_info = SrUtils::showAgentContact();

        // agent data
        $listing_agent_id    = $listing->agent->id;
        $listing_agent_name  = $listing->agent->firstName . ' ' . $listing->agent->lastName;

        $listing_agent_email = '';
        $has_agent_contact_info = !empty($listing->agent->contact)
                                AND !empty($listing->agent->contact->email);

        if($show_contact_info AND $has_agent_contact_info) {
            $listing_agent_email = $listing->agent->contact->email;
        } else {
            $listing_agent_email = '';
        }

        // agent email is available
        $agent_email = trim($listing_agent_email);
        if(!empty($agent_email)) {
            $listing_agent_name = "<a href='mailto:$listing_agent_email'>$listing_agent_name</a>";
        }
        //agent name is not available - use their id
        $agent_name = trim($listing_agent_name);
        if(empty($agent_name)) {
            $listing_agent_name = $listing_agent_id;
        }

        $agent = SimplyRetsRenderer::srDetailsTable($listing_agent_name, "Listing Agent");

        $listing_agent_cell_phone = $has_agent_contact_info ? $listing->agent->contact->cell : '';
        $listing_agent_office_phone = $has_agent_contact_info ? $listing->agent->contact->office: '';
        $listing_agent_phone = $listing_agent_cell_phone
                             ? $listing_agent_cell_phone
                             : $listing_agent_office_phone;
        $agent_phone = SimplyRetsRenderer::srDetailsTable($listing_agent_phone, "Listing Agent Phone");


        // Office
        $has_office_contact_info = !empty($listing->office->contact);

        $listing_office = $listing->office->name;
        $office = SimplyRetsRenderer::srDetailsTable($listing_office, "Listing Office");

        $listing_office_phone = $has_office_contact_info ? $listing->office->contact->office : '';
        $officePhone = SimplyRetsRenderer::srDetailsTable($listing_office_phone, "Listing Office Phone");

        $listing_office_email = $has_office_contact_info ? $listing->office->contact->email : '';
        $officeEmail = SimplyRetsRenderer::srDetailsTable($listing_office_email, "Listing Office Email");

        /* If show_contact_info is false, stub these fields */
        if(!$show_contact_info) {
            $agent_phone = '';
            $officePhone = '';
            $officeEmail = '';
        }

        /**
         * If user has EnterpriseAccess, check for open houses
         */
        $openhouses = $has_openhouses ? SimplyRetsOpenHouses::getOpenHousesByListingId(
            $listing->listingId
        ) : array();

        $upcoming_openhouses = count($openhouses);
        $next_openhouses = $upcoming_openhouses > 0 ? $openhouses : NULL;

        $next_openhouses_banner = "";
        if ($has_openhouses && $next_openhouses) {

            $next_openhouses_details = "";
            $next_openhouses_item_class = "sr-listing-openhouses-banner-item";

            foreach($next_openhouses as $next_oh) {

                $next_oh_times = SimplyRetsOpenHouses::getOpenHouseDateTimes(
                    $next_oh
                );

                $next_oh_day = $next_oh_times["day"];
                $next_oh_time = $next_oh_times["time"];

                $next_openhouses_details .=
                      "<div class=\"{$next_openhouses_item_class}\">"
                    . "  <strong>{$next_oh_day}</strong>"
                    . "  <br/>"
                    . "  <span>{$next_oh_time}</span>"
                    . "</div>";
            }

            $upcoming_openhouses_text =
                $upcoming_openhouses === 1 ? " upcoming open house" : " upcoming open houses";

            $next_openhouses_banner = '<div class="sr-listing-openhouses-banner">'
                                    . '  <h3>'
                                    .     $upcoming_openhouses
                                    .     $upcoming_openhouses_text
                                    . '  </h3>'
                                    .    $next_openhouses_details
                                    . '</div>';
        }

        /**
         * Create the custom compliance markup for map marker
         */
        $compliance_markup = SrUtils::mkListingSummaryCompliance($listing_office, $listing_agent_name);

        /**
         * Find available contact information to display
         * Then, create the "Listing by" markup
         */
        $attribution_contact = !empty($complianceData)
                               && property_exists($complianceData, "attributionContact")
                             ? $complianceData->attributionContact
                             : NULL;
        $listing_by_contact = current(array_filter(array(
            $attribution_contact,
            $listing_agent_phone,
            $agent_email,
            $listing_office_phone,
            $listing_office_email
        )));

        $listing_by_markup = SrUtils::mkAgentOfficeAboveTheFold(
            $listing_agent_name,
            $listing_office,
            $listing_by_contact
        );

        $galleria_theme = plugins_url('assets/galleria/themes/classic/galleria.classic.min.js', __FILE__);

        // Build details link for map marker
        $vendor = get_query_var("sr_vendor", null);
        $link = SrUtils::buildDetailsLink(
            $listing,
            !empty($vendor) ? array("sr_vendor" => $vendor) : array()
        );

        /**
         * Google Map for single listing
         */
        $hide_map = get_option('sr_disable_listing_details_map', false);

        if( $listing_lat  && $listing_longitude && !$hide_map ) {
            $map       = SrSearchMap::mapWithDefaults();
            $marker    = SrSearchMap::markerWithDefaults();
            $iw        = SrSearchMap::infoWindowWithDefaults();
            $mapHelper = SrSearchMap::srMapHelper();
            $iwCont    = SrSearchMap::infoWindowMarkup(
                $link,
                $main_photo,
                $full_address,
                $price_to_display,
                $listing_bedrooms,
                SrListing::getBathroomsDisplay(
                    $listing_bathsTotal,
                    $listing_bathsFull
                ),
                $listing_mls_status,
                $listing_mlsid,
                $listing_type,
                $area,
                $listing_style,
                $compliance_markup
            );
            $iw->setContent($iwCont);
            $marker->setPosition($listing_lat, $listing_longitude, true);
            $map->setCenter($listing_lat, $listing_longitude, true);
            $marker->setInfoWindow($iw);
            $map->addMarker($marker);
            $map->setAutoZoom(false);
            $map->setMapOption('zoom', 12);
            $mapM = $mapHelper->render($map);
            $mapMarkup = '<hr>'
                       . '<div id="details-map">'
                       . '  <h3>Map View</h3>'
                       .    $mapM
                       . '</div>';
            $mapLink = '<span style="float:left;">'
                     . '  <a href="#details-map">'
                     . '    View on map'
                     . '  </a>'
                     . '</span>';
        } else {
            $mapMarkup = '';
            $mapLink = '';
        }
        /************************************************/


        // listing markup
        $cont .=
              '<div class="sr-details" style="text-align:left;">'
            .    $listing_by_markup
            . '  <p class="sr-details-links" style="clear:both;">'
            .    $mapLink
            .    $more_photos
            . '   <span id="sr-listing-contact">'
            . '     <a href="#sr-contact-form">' . $contact_text . '</a>'
            . '   </span>'
            . '   </p>'
            .     $gallery_markup
            . '   <script>'
            . '     if(document.getElementById("sr-fancy-gallery")) {'
            . '          Galleria.loadTheme("' . $galleria_theme . '");'
            . '          Galleria.configure({'
            . '              height: 500,'
            . '              width:  "90%",'
            . '              showinfo: false,'
            . '              dummy: "' . $default_photo . '",'
            . '              lightbox: true,'
            . '              imageCrop: false,'
            . '              imageMargin: 0,'
            . '              fullscreenDoubleTap: true'
            . '          });'
            . '          Galleria.run(".sr-gallery");'
            . '     }'
            . '</script>'
            . '<div class="sr-primary-details">'
            . ' <div class="sr-detail" id="sr-primary-details-beds">'
            . '   <h3>' . $listing_bedrooms . ' <small>Beds</small></h3>'
            . ' </div>'
            . ' <div class="sr-detail" id="sr-primary-details-baths">'
            . '   <h3>' . $primary_baths . '</h3>'
            . ' </div>'
            . ' <div class="sr-detail" id="sr-primary-details-size">'
            . '   <h3>'
            .        $area . '<small class="sr-listing-area-sqft">SqFt</small>'
            . '    </h3>'
            . ' </div>'
            . ' <div class="sr-detail" id="sr-primary-details-status">'
            . '   <h3>' . $listing_mls_status . '</h3>'
            . '</div>'
            . '</div>'
            .    $remarks_markup
            . '<div>'
            .    $next_openhouses_banner
            . '</div>'
            . '<table style="width:100%;">'
            . '  <thead>'
            . '    <tr>'
            . '      <th colspan="3">'
            . '        <h5>Property Details</h5>'
            . '      </th>'
            . '    </tr>'
            . '  </thead>'
            . '  <tbody>'
            .      $price
            .      $close_price
            .      $bedrooms
            .      $bathsFull
            .      $bathsHalf
            .      $bathsTotal
            .      $style
            .      $lotsize_markup
            .      $lotsizearea_markup
            .      $lotsizeareaunits_markup
            .      $acres_markup
            .      $type
            .      $subType
            .      $subTypeText
            .      $stories
            .      $interiorFeatures
            .      $exteriorFeatures
            .      $yearBuilt
            .      $fireplaces
            .      $subdivision
            .      $view
            .      $roof
            .      $water
            .      $heating
            .      $foundation
            .      $accessibility
            .      $lot_description
            .      $laundry_features
            .      $pool
            .      $parking_description
            .      $parking_spaces
            .      $garage_spaces
            .      $association_name
            .      $association_fee
            .      $association_amenities
            .      $additional_rooms
            .      $roomsMarkup
            . '  </tbody>'
            .      $geo_table_header
            .      $geo_directions
            .      $geo_county
            .      $geo_latitude
            .      $geo_longitude
            .      $geo_market_area
            . '  </tbody>'
            . '  <thead>'
            . '    <tr>'
            . '      <th colspan="3">'
            . '        <h5>Address Information</h5>'
            . '      </th>'
            . '    </tr>'
            . '  </thead>'
            . '  <tbody>'
            .      $address
            .      $unit
            .      $postal_code
            .      $city
            .      $cross_street
            .      $state
            .      $country
            . '  </tbody>'
            . '  <thead>'
            . '    <tr>'
            . '      <th colspan="3">'
            . '        <h5>Listing Information</h5>'
            . '      </th>'
            . '    </tr>'
            . '  </thead>'
            . '    <tbody>'
            .      $office
            .      $officePhone
            .      $officeEmail
            .      $agent
            .      $agent_phone
            .      $complianceExtras
            .      $compensationDisclaimer
            .      $special_listing_conditions
            .      $ownership
            .      $terms
            .      $virtual_tour
            . '   </tbody>'
            .     $school_data
            . '  <thead>'
            . '    <tr>'
            . '      <th colspan="3">'
            . '        <h5>' . $MLS_text . ' Information</h5>'
            . '      </th>'
            . '     </tr>'
            . '  </thead>'
            . '  <tbody>'
            .       $days_on_market
            .       $mls_status
            .       $list_date
            .       $date_modified_markup
            .       $tax_data
            .       $tax_year
            .       $tax_annual_amount
            .       $mls_area
            .       $mls_area_minor
            .       $mlsid
            . '    </tbody>'
            . '  </table>'
            .    $mapMarkup
            . '  <script>' . $lh_analytics . '</script>'
            . '</div>';

        $cont .= SimplyRetsApiHelper::srContactFormDeliver();
        $cont .= $contact_markup;

        // Add disclaimer to the bottom of the page
        $disclaimer = SrUtils::mkDisclaimerText($last_update);
        $cont .= "<br/>{$disclaimer}";

        return $cont;
    }


    public static function resultDataColumnMarkup($val, $name, $reverse=false, $id="") {
        if (empty($val)) {
            return "";
        }

        $li = "<li class='sr-data-column-item'";
        $li .= $id ? " id='$id'>" : ">";
        $li .= $reverse ? "$name $val" : "$val $name";
        $li .= "</li>";

        return $li;
    }



    public static function srResidentialResultsGenerator($request_response, $settings) {
        $cont              = "";
        $pagination        = $request_response['pagination'];
        $lastUpdate        = $request_response['lastUpdate'];
        $response          = $request_response['response'];
        $MLS_text          = SrUtils::mkMLSText();
        $show_listing_meta = SrUtils::srShowListingMeta();

        /* Check for an and display any `.error` response */
        if(!is_array($response) && property_exists($response, "error")) {
            return SrMessages::noResultsMsg($response);
        }

        /* Check for 0 matching listings (no results) */
        if (empty($response)) {
            return SrMessages::noResultsMsg($response);
        }

        /** Build pagination links HTML **/
        $page_count = count($response);
        $limit = isset($settings['limit']) ? $settings['limit'] : 20;
        $pag = SrUtils::buildPaginationLinks( $pagination );
        $prev_link = $pag['prev'];
        $next_link = $page_count < $limit ? "" : $pag['next'];

        $grid_view = $settings['grid_view'] == TRUE;

        /** Allow override of "map_position" admin setting on a per short-code basis */
        $map_setting = isset($settings['show_map']) ? $settings['show_map'] : true;
        $map_position = isset($settings['map_position'])
                      ? $settings['map_position']
                      : get_option('sr_search_map_position', 'map_above');

        $vendor = isset($settings['vendor'])
                ? $settings['vendor']
                : get_query_var('sr_vendor', '');

        $mappable_listings = SrSearchMap::filter_mappable($response);
        $uniq_geos = SrSearchMap::uniqGeos($mappable_listings);

        $map       = SrSearchMap::mapWithDefaults();
        $mapHelper = SrSearchMap::srMapHelper();
        $markerCount = 0;

        /**
         * If only one listing (or one unique lat/lng) is being
         * mapped, set a custom zoom level because the default is way
         * to far in.
         */
        if (count($uniq_geos) === 1) {
            $map->setCenter(
                $uniq_geos[0][0],
                $uniq_geos[0][1],
                true
            );
            $map->setMapOption('zoom', 12);
        } else {
            $map->setAutoZoom(true);
        }

        $resultsMarkup = "";
        foreach( $response as $listing ) {
            $listing_uid        = $listing->mlsId;
            $mlsid              = $listing->listingId;
            $listing_price      = $listing->listPrice;
            $remarks            = $listing->remarks;
            $city               = $listing->address->city;
            $county             = $listing->geo->county;
            $address            = $listing->address->full;
            $zip                = $listing->address->postalCode;
            $listing_agent_id   = $listing->agent->id;
            $listing_agent_name = $listing->agent->firstName . ' ' . $listing->agent->lastName;
            $lng                = $listing->geo->lng;
            $lat                = $listing->geo->lat;
            $propType           = $listing->property->type;
            $bedrooms           = $listing->property->bedrooms;
            $bathsFull          = $listing->property->bathsFull;
            $bathsHalf          = $listing->property->bathsHalf;
            $bathsTotal         = $listing->property->bathrooms;
            $area               = $listing->property->area; // might be empty
            $lotSize            = $listing->property->lotSize; // might be empty
            $subdivision        = $listing->property->subdivision;
            $style              = $listing->property->style;
            $yearBuilt          = $listing->property->yearBuilt;
            $internetAddressDisplay = $listing->internetAddressDisplay;

            /**
             * Listing status to show. This may return a statusText.
             */
            $standard_status = $listing->mls->status;
            $mls_status = SrListing::listingStatus($listing);
            $full_address = SrUtils::buildFullAddressString($listing);

            $status_class = "sr-listing-status-" . strtolower($standard_status);
            $status_banner_class = SrListing::listingStatusBannerClass($standard_status);

            $status_banner_info = "";
            if($standard_status === "Closed") {
                $close_date = gmdate("m/d/y", strtotime($listing->sales->closeDate));
                $status_banner_info = "<span class='sr-listing-status-banner-close-date'>"
                                    . "{$close_date}"
                                    . "</span>";
            }

            $price_to_display = SrUtils::formatListingPrice(
                $listing,
                $standard_status === "Closed"
            );

            if( $bedrooms == null || $bedrooms == "" ) {
                $bedrooms = 0;
            }
            if( $bathsFull == null || $bathsFull == "" ) {
                $bathsFull = 0;
            }
            if( $bathsHalf == null || $bathsHalf == "" ) {
                $bathsHalf = 0;
            }
            if( !$area == 0 ) {
                $area = number_format( $area );
            }

            // listing photos
            $main_photo = SrListing::mainPhotoOrDefault($listing);

            // listing link to details
            $link = SrUtils::buildDetailsLink(
                $listing,
                !empty($vendor) ? array("sr_vendor" => $vendor) : array()
            );


            /**
             * Show 'Listing Courtesy of ...' if setting is enabled
             */
            $listing_office = $listing->office->name;
            $compliance_markup = SrUtils::mkListingSummaryCompliance($listing_office, $listing_agent_name);


            /************************************************
             * Make our map marker for this listing
             */
            if($lat && $lng && $internetAddressDisplay !== FALSE) {
                $marker = SrSearchMap::markerWithDefaults();
                $iw     = SrSearchMap::infoWindowWithDefaults();
                $iwCont = SrSearchMap::infoWindowMarkup(
                    $link,
                    $main_photo,
                    $full_address,
                    $price_to_display,
                    $bedrooms,
                    SrListing::getBathroomsDisplay(
                        $bathsTotal,
                        $bathsFull
                    ),
                    $mls_status,
                    $mlsid,
                    $propType,
                    $area,
                    $style,
                    $compliance_markup
                );
                $iw->setContent($iwCont);
                $marker->setPosition($lat, $lng, true);
                $marker->setInfoWindow($iw);
                $map->addMarker($marker);
                $markerCount = $markerCount + 1;
            }
            /************************************************/

            /*
             * Variables that contain markup for sr-data-column
             * If the field is empty, they'll be hidden
             * TODO: Create a ranking system 1 - 10 to smartly replace missing values
             */
            $bedsMarkup  = SimplyRetsRenderer::resultDataColumnMarkup(
                $bedrooms,
                "Bedrooms",
                false,
                "sr-data-column-bedrooms"
            );

            $areaMarkup  = SimplyRetsRenderer::resultDataColumnMarkup(
                $area,
                '<span class="sr-listing-area-sqft">SqFt</span>',
                false,
                "sr-data-column-living-area"
            );

            $yearMarkup  = SimplyRetsRenderer::resultDataColumnMarkup(
                $yearBuilt,
                'Built in',
                true,
                "sr-data-column-year-built"
            );

            $cityMarkup  = SimplyRetsRenderer::resultDataColumnMarkup(
                $city,
                'Located in',
                true,
                "sr-data-column-city"
            );

            $mlsidMarkup = SimplyRetsRenderer::resultDataColumnMarkup(
                $mlsid,
                $MLS_text . ' #:',
                true,
                "sr-data-column-mlsid"
            );

            // Use another field if SqFt is empty
            if( $area == 0 ) {
                $areaMarkup = SimplyRetsRenderer::resultDataColumnMarkup(
                    $bathsHalf,
                    'Half Baths',
                    false,
                    "sr-data-column-half-baths"
                );

                if( $areaMarkup == 0 ) {
                    $areaMarkup = SimplyRetsRenderer::resultDataColumnMarkup(
                        SrUtils::normalizeCountyText($county),
                        "County",
                        false,
                        "sr-data-column-county"
                    );
                }
            }

            if( $yearBuilt == 0 ) {
                $yearMarkup = SimplyRetsRenderer::resultDataColumnMarkup(
                    $subdivision,
                    "",
                    false,
                    "sr-data-column-subdivision"
                );
            }

            $bathrooms_display = SrListing::getBathroomsDisplay(
                $bathsTotal,
                $bathsFull
            );

            $bathsMarkup = SimplyRetsRenderer::resultDataColumnMarkup(
                $bathrooms_display,
                "",
                false,
                "sr-data-column-bathrooms"
            );

            if ($grid_view == true) {
                // append markup for this listing to the content
                $resultsMarkup .=
                      '<div class="sr-listing-grid-item ' . $status_class . '">'
                    . '  <a href="' . $link . '">'
                    . '    <div class="sr-photo" style="background-image:url(' . $main_photo . ');">'
                    . '        <span class="sr-listing-status-banner ' . $status_banner_class . '">'
                    . '            <span class="sr-listing-status-banner-text">'
                    .                  $mls_status . $status_banner_info
                    . '            </span>'
                    . '        </span>'
                    . '    </div>'
                    . '  </a>'
                    . '  <div class="sr-listing-data-wrapper">'
                    . '    <div class="sr-primary-data">'
                    . '      <a href="' . $link . '">'
                    . '        <h4>' . $full_address
                    . '          <small class="sr-price">'
                    . '            <i> - ' . $price_to_display . '</i>'
                    . '          </small>'
                    . '        </h4>'
                    . '      </a>'
                    . '    </div>'
                    . '    <div class="sr-secondary-data">'
                    . '      <ul class="sr-data-column">'
                    .          $cityMarkup
                    .          $yearMarkup
                    .          $mlsidMarkup
                    . '      </ul>'
                    . '      <ul class="sr-data-column">'
                    .          $bedsMarkup
                    .          $bathsMarkup
                    .          $areaMarkup
                    . '      </ul>'
                    . '    </div>'
                    . '  </div>'
                    . '  <div class="more-details-wrapper">'
                    . '    <span class="more-details-link">'
                    . '        <a href="' . $link . '">More details</a>'
                    . '    </span>'
                    . '    <span class="result-compliance-markup">'
                    .        $compliance_markup
                    . '    </span>'
                    . '  </div>'
                    . '</div>';
            } else {
                // append markup for this listing to the content
                $resultsMarkup .=
                      '<hr>'
                    . '<div class="sr-listing ' . $status_class . '">'
                    . '  <a href="' . $link . '">'
                    . '    <div class="sr-photo" style="background-image:url(' . $main_photo . ');">'
                    . '        <span class="sr-listing-status-banner ' . $status_banner_class . '">'
                    . '            <span class="sr-listing-status-banner-text">'
                    .                  $mls_status
                    . '            </span>'
                    . '        </span>'
                    . '    </div>'
                    . '  </a>'
                    . '  <div class="sr-listing-data-wrapper">'
                    . '    <div class="sr-primary-data">'
                    . '      <a href="' . $link . '">'
                    . '        <h4>' . $full_address
                    . '          <small class="sr-price">'
                    . '            <i> - ' . $price_to_display . '</i>'
                    . '          </small>'
                    . '        </h4>'
                    . '      </a>'
                    . '    </div>'
                    . '    <div class="sr-secondary-data">'
                    . '      <ul class="sr-data-column">'
                    .          $cityMarkup
                    .          $yearMarkup
                    .          $mlsidMarkup
                    . '      </ul>'
                    . '      <ul class="sr-data-column">'
                    .          $bedsMarkup
                    .          $bathsMarkup
                    .          $areaMarkup
                    . '      </ul>'
                    . '    </div>'
                    . '  </div>'
                    . '  <div class="more-details-wrapper">'
                    . '    <span class="more-details-link">'
                    . '        <a href="' . $link . '">More details</a>'
                    . '    </span>'
                    . '    <span class="result-compliance-markup">'
                    .        $compliance_markup
                    . '    </span>'
                    . '  </div>'
                    . '</div>';
            }

        }

        $markupGridViewClass = $grid_view == true ? "sr-listings-grid-view" : "";
        $resultsMarkup = "<div id='sr-listings-results-list' class='{$markupGridViewClass}'>"
                       . "{$resultsMarkup}"
                       . "</div>";
        $markerCount > 0 ? $mapMarkup = $mapHelper->render($map) : $mapMarkup = '';

        if( $map_setting === "false" ) {
            $mapMarkup = '';
        }

        if( $map_position == 'list_only' )
        {
            $cont .= $resultsMarkup;
        }
        elseif( $map_position == 'map_only' )
        {
            $cont .= $mapMarkup;
        }
        elseif( $map_position == 'map_above' )
        {
            $cont .= $mapMarkup;
            $cont .= $resultsMarkup;
        }
        elseif( $map_position == 'map_below' )
        {
            $cont .= $resultsMarkup;
            $cont .= '<hr>';
            $cont .= $mapMarkup;
        }
        else
        {
            $cont .= $resultsMarkup;
        }

        $disclaimer_text = SrUtils::mkDisclaimerText($lastUpdate);

        $cont .= "<div class='sr-pagination-wrapper'>"
               . "  <p class='sr-pagination'>$prev_link $next_link</p>"
               . "  <div class='sr-disclaimer-text'>{$disclaimer_text}</div>"
               . "</div>";

        return $cont;

    }


    public static function srWidgetListingGenerator( $response, $settings ) {
        $br   = "<br>";
        $cont = "";

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        $response = $response['response'];
        $response_size = is_array($response) ? sizeof($response) : 0;

        /* Check for an `.error` response */
        if(!is_array($response) && property_exists($response, "error")) {
            return SrMessages::noResultsMsg($response);
        }

        /* Check for 0 matching listings (no results) */
        if (empty($response)) {
            return SrMessages::noResultsMsg($response);
        }

        foreach ( $response as $listing ) {
            $listing_uid = $listing->mlsId;
            $listing_remarks  = $listing->remarks;

            // widget details
            $bedrooms = $listing->property->bedrooms;
            if( $bedrooms == null || $bedrooms == "" ) {
                $bedrooms = 0;
            }

            $bathrooms_display = SrListing::getBathroomsDisplay(
                $listing->property->bathrooms,
                $listing->property->bathsFull
            );

            $mls_status = SrListing::listingStatus($listing);

            $price_to_display = SrUtils::formatListingPrice(
                $listing,
                $listing->mls->status === "Closed"
            );

            // widget title
            $address = SrUtils::buildFullAddressString($listing);

            // Primary listing photo
            $main_photo = SrListing::mainPhotoOrDefault($listing);
            $main_photo_url = esc_url($main_photo);

            // Compliance markup (agent/office)
            $listing_office  = $listing->office->name;
            $listing_agent = $listing->agent->firstName . ' ' . $listing->agent->lastName;
            $compliance_markup = SrUtils::mkListingSummaryCompliance($listing_office, $listing_agent);

            $vendor = isset($settings['vendor']) ? $settings['vendor'] : '';
            // create link to listing
            $link = SrUtils::buildDetailsLink(
                $listing,
                !empty($vendor) ? array("sr_vendor" => $vendor) : array()
            );

            // append markup for this listing to the content
            $cont .=
                '<div class="sr-listing-wdgt">'
              . '  <a href="' . $link . '">'
              . '    <h5>' . $address
              . '      <small> -' . $price_to_display . '</small>'
              . '    </h5>'
              . '  </a>'
              . '  <a href="' . $link . '">'
              // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
              . '    <img src="' . $main_photo_url . '" width="100%" alt="' . $address .'">'
              . '  </a>'
              . '  <div class="sr-listing-wdgt-primary">'
              . '    <div id="sr-listing-wdgt-details">'
              . '      <span>' . $bedrooms . ' Bed | ' . $bathrooms_display . ' | ' . $mls_status . '</span>'
              . '    </div>'
              . '    <hr>'
              . '    <div id="sr-listing-wdgt-remarks">'
              . '      <p>' . $listing_remarks . '</p>'
              . '    </div>'
              . '  </div>'
              . '  <div>'
              . '    <i>' . $compliance_markup . '</i>'
              . '  </div>'
              . '  <div id="sr-listing-wdgt-btn">'
              . '    <a href="' . $link . '">'
              . '      <button class="button btn">'
              . '        More about this listing'
              . '      </button>'
              . '    </a>'
              . '  </div>'
              . '</div>';
        }
        return $cont;
    }


    public static function srContactFormMarkup($listing) {
        $custom_form = get_option('sr_leadcapture_custom_form', false);

        // Use custom form if configured
        if ($custom_form != false) {
            return '<div id="sr-contact-form">'
                 . do_shortcode($custom_form)
                 . '</div>' ;
        }

        // Default lead capture form
        $markup = '';
        $markup .= '<hr>';
        $markup .= '<div id="sr-contact-form">';
        $markup .= '<h3>Contact us about this listing</h3>';
        $markup .= '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
        $markup .= '<p>';
        $markup .= '<input type="hidden" name="sr-cf-listing" value="' . $listing . '" />';
        $markup .= 'Your Name (required) <br/>';
        $markup .= '<input type="text" name="sr-cf-name" value="'
            . ( isset( $_POST["sr-cf-name"] ) ? esc_attr( $_POST["sr-cf-name"] ) : '' ) . '" size="40" />';
        $markup .= '</p>';
        $markup .= '<p>';
        $markup .= 'Your Email (required) <br/>';
        $markup .= '<input type="email" name="sr-cf-email" value="'
            . ( isset( $_POST["sr-cf-email"] ) ? esc_attr( $_POST["sr-cf-email"] ) : '' ) . '" size="40" />';
        $markup .= '</p>';
        $markup .= '<p>';
        $markup .= 'Subject (required) <br/>';
        $markup .= '<input type="text" name="sr-cf-subject" value="'
            . ( isset( $_POST["sr-cf-subject"] ) ? esc_attr( $_POST["sr-cf-subject"] ) : '' ) . '" size="40" />';
        $markup .= '</p>';
        $markup .= '<p>';
        $markup .= 'Your Message (required) <br/>';
        $markup .= '<textarea rows="10" cols="35" name="sr-cf-message">'
            . ( isset( $_POST["sr-cf-message"] ) ? esc_attr( $_POST["sr-cf-message"] ) : '' ) . '</textarea>';
        $markup .= '</p>';
        $markup .= '<p><input class="btn button btn-submit" type="submit" name="sr-cf-submitted" value="Send"></p>';
        $markup .= '</form>';
        $markup .= '</div>';

        return $markup;

    }


    public static function srListingSliderGenerator( $response, $settings ) {
        $listings = $response['response'];
        $inner = "";

        $last_update = $response['lastUpdate'];
        $disclaimer = SrUtils::mkDisclaimerText($last_update);

        if(!empty($settings['random']) && $settings['random'] === "true") {
            shuffle($listings);
        }

        foreach($listings as $l) {
            $address = SrUtils::buildFullAddressString($l);
            $uid     = $l->mlsId;
            $beds    = $l->property->bedrooms;
            $area    = $l->property->area;

            $price_to_display = SrUtils::formatListingPrice(
                $l,
                $l->mls->status === "Closed"
            );

            $photo = SrListing::mainPhotoOrDefault($l);
            $vendor = isset($settings['vendor']) ? $settings['vendor'] : '';

            // create link to listing
            $link = SrUtils::buildDetailsLink(
                $l,
                !empty($vendor) ? array("sr_vendor" => $vendor) : array()
            );

            if( $area == 0 ) {
                $area = 'na';
            } else {
                $area = number_format( $area );
            }

            $bathsFull  = $l->property->bathsFull;
            $bathsTotal = $l->property->bathrooms;
            $bathrooms_display = SrListing::getBathroomsDisplay(
                $bathsTotal,
                $bathsFull
            );

            /**
             * Show listing brokerage, if applicable
             */
            $listing_office  = $l->office->name;
            $listing_agent = $l->agent->firstName . ' ' . $l->agent->lastName;
            $compliance_markup = SrUtils::mkListingSummaryCompliance($listing_office, $listing_agent);

            $inner .=
                  '<div class="sr-listing-slider-item">'
                . '  <a href="' . $link . '">'
                . '    <div class="sr-listing-slider-item-img" style="background-image: url(' . $photo . ')"></div>'
                . '  </a>'
                . '  <a href="' . $link . '">'
                . '    <h4 class="sr-listing-slider-item-address">' . $address . ' <small>' . $price_to_display . '</small></h4>'
                . '  </a>'
                . '  <p class="sr-listing-slider-item-specs">' . $beds . ' Bed / ' . $bathrooms_display . ' / ' . $area . ' SqFt</p>'
                . '  <p class="sr-listing-slider-item-specs">' . $compliance_markup . '</p>'
                . '</div>';
        }

        $content =
              '<div>'
            . '  <div id="simplyrets-listings-slider" class="owl-carousel owl-theme sr-listing-carousel">'
            .      $inner
            . '  </div>'
            . '  <div id="simplyrets-listings-slider-disclaimer" style="text-align:center;">'
            .      $disclaimer
            . '  </div>'
            . '  <br/>'
            . '</div>';

        return $content;
    }


}
