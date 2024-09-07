<?php

/*
 * simply-rets-admin.php - Copyright (C) 2014-2024 SimplyRETS
 * This file provides the logic for the SimplyRETS admin panel settings and features pages.
 *
*/


/* Code starts here */

add_action("wp_loaded", array("SrAdminSettings", "createDemoPage"));
add_action("admin_notices", array("SrAdminSettings", "adminMessages"));

class SrAdminSettings {

  public static function add_to_admin_menu() {
      add_options_page('SimplyRETS Settings'
                       , 'SimplyRETS'
                       , 'manage_options'
                       , 'simplyrets-admin.php'
                       , array('SrAdminSettings', 'sr_admin_page')
      );
  }

  public static function register_admin_settings() {
      register_setting('sr_admin_settings', 'sr_api_name');
      register_setting('sr_admin_settings', 'sr_api_key');
      register_setting('sr_admin_settings', 'sr_contact_page');
      register_setting('sr_admin_settings', 'sr_show_listingmeta');
      register_setting('sr_admin_settings', 'sr_show_listing_remarks');
      register_setting('sr_admin_settings', 'sr_show_agent_contact');
      register_setting('sr_admin_settings', 'sr_listing_gallery');
      register_setting('sr_admin_settings', 'sr_show_leadcapture');
      register_setting('sr_admin_settings', 'sr_leadcapture_recipient');
      register_setting('sr_admin_settings', 'sr_additional_rooms');
      register_setting('sr_admin_settings', 'sr_listhub_analytics');
      register_setting('sr_admin_settings', 'sr_listhub_analytics_id');
      register_setting('sr_admin_settings', 'sr_listhub_analytics_test_events');
      register_setting('sr_admin_settings', 'sr_search_map_position');
      register_setting('sr_admin_settings', 'sr_permalink_structure');
      register_setting('sr_admin_settings', 'sr_google_api_key');
      register_setting('sr_admin_settings', 'sr_office_on_thumbnails');
      register_setting('sr_admin_settings', 'sr_agent_on_thumbnails');
      register_setting('sr_admin_settings', 'sr_thumbnail_idx_image');
      register_setting('sr_admin_settings', 'sr_custom_disclaimer');
      register_setting('sr_admin_settings', 'sr_custom_no_results_message');
      register_setting('sr_admin_settings', 'sr_show_mls_status_text');
      register_setting('sr_admin_settings', 'sr_agent_office_above_the_fold');
      register_setting('sr_admin_settings', 'sr_show_mls_trademark_symbol');
      register_setting('sr_admin_settings', 'sr_disable_listing_details_map');
      register_setting('sr_admin_settings', 'sr_default_idx_filter', array(
          "default" => "null"
      ));
      register_setting('sr_admin_settings', 'sr_idx_address_display_text', array(
          "default" => "Undisclosed address"
      ));
      register_setting('sr_admin_settings', 'sr_date_default_timezone', array(
          "default" => ""
      ));
      register_setting('sr_admin_settings', 'sr_listing_force_image_https', array(
          "default" => false
      ));
  }

  public static $timezones = array(
      "Eastern (US)" => "America/New_York",
      "Central (US)" => "America/Chicago",
      "Mountain (US)" => "America/Denver",
      "Pacific (US)" => "America/Los_Angeles",
      "Alaska (US)" => "America/Anchorage",
      "Hawaii (US)" => "Pacific/Honolulu",
      "Newfoundland (Canada)" => "America/St_Johns",
      "Atlantic (Canada)" => "America/Halifax",
      "Eastern (Canada)" => "America/Toronto",
      "Central (Canada)" => "America/Winnipeg",
      "Mountain (Canada)" => "America/Edmonton",
      "Pacific (Canada)" => "America/Vancouver"
  );

  public static function adminMessages () {
      $page_created = get_option("sr_demo_page_created", false);
      $show_msg     = get_option("sr_show_admin_message", true);

      if($page_created OR !$show_msg) {
          return;
      } else {
          $notice = SimplyRetsCustomPostPages::onActivationNotice();
          echo $notice;
      }
  }

  public static function createDemoPage() {
      if(isset( $_POST['sr_create_demo_page'])) {
          $demo_post = array(
              "post_content" => "[sr_map_search search_form=\"true\" list_view=\"true\"]",
              "post_name" => "simplyrets-listings",
              "post_title" => "SimplyRETS Demo Page",
              "post_status" => "publish",
              "post_type" => "page"
          );
          $post_id = wp_insert_post($demo_post);
          $permalink = get_post_permalink($post_id);
          update_option("sr_demo_page_created", true);
          wp_redirect($permalink);
          exit();
      } else if(isset( $_POST['sr_dismiss_admin_msg'])) {
          update_option("sr_show_admin_message", false);
      } else {
          return;
      }
  }

  public static function sr_admin_page() {
      global $wpdb;
      $logo_path = plugin_dir_url(__FILE__) . 'assets/img/logo_button.png';

      // update meta data fields manually
      if( isset( $_POST['sr_update_meta'] ) ) {
          echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">'.
               '<p><strong>Meta Data Updated!</strong></p>'.
               '<button type="button" class="notice-dismiss">'.
               '<span class="screen-reader-text">Dismiss this notice.</span></button></div>';
          SimplyRetsApiHelper::srUpdateAdvSearchOptions();
      }

      // Custom POST handler for updating the custom disclaimer
      // so we can properly sanitize the input.
      if (isset( $_POST['sr_custom_disclaimer'] )) {
          update_option('sr_custom_disclaimer', htmlentities(stripslashes($_POST['sr_custom_disclaimer'])));
      }

      // Custom POST handler for updating the custom disclaimer
      // so we can properly sanitize the input.
      if (isset( $_POST['sr_custom_no_results_message'] )) {
          update_option(
              'sr_custom_no_results_message',
              htmlentities(stripslashes($_POST['sr_custom_no_results_message']))
          );
      }

      ?>
      <div class="wrap sr-admin-wrap">
        <h2 id="message"></h2>
        <img class="sr-admin-logo" src="<?php echo $logo_path; ?>">
        <h1 class="sr-admin-title">SimplyRETS Admin Settings</h1>
        <div class="sr-doc-links">
          <p>
            <a target="_blank" href="http://simplyrets.com">
                simplyrets.com
            </a> |
            <a target="_blank" href="https://wordpress-demo.simplyrets.com/">
                Plugin demo and documentation
            </a> |
            <a target="_blank" href="http://status.simplyrets.com">
                Service Status
            </a> |
            <a target="_blank" href="https://simplyrets.com/account">
                Support Request
            </a>
            <form method="post" action="options-general.php?page=simplyrets-admin.php" style="display:inline-block;">
                <?php submit_button( "Refresh Meta Data", "submit", "sr_update_meta", 0 ); ?>
            </form>
            <form method="post" action="options-general.php?page=simplyrets-admin.php" style="display:inline-block;">
                <?php submit_button( "Create Demo Page", "submit", "sr_create_demo_page", 0 ); ?>
            </form>
          </p>
        </div>
        <hr>
        <form method="post" action="options.php">
          <?php settings_fields( 'sr_admin_settings'); ?>
          <?php do_settings_sections( 'sr_admin_settings'); ?>
          <div class="sr-admin-api">
            <h2>Account Credentials</h2>
            <p>
              Enter your SimplyRETS API credentials in the fields below.
            </p>
            <table>
              <tbody>
                <tr>
                  <td>
                    <strong>API Key</strong>
                  </td>
                  <td>
                    <input type="text" name="sr_api_name" value="<?php echo esc_attr( get_option('sr_api_name') ); ?>" />
                  </td>
                </tr>
                <tr>
                  <td>
                    <strong>API Secret</strong>
                  </td>
                  <td>
                    <input type="text" name="sr_api_key" value="<?php echo esc_attr( get_option('sr_api_key') ); ?>" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div style="margin-top: 15px;">
            <span>
              <i>Note - to use the SimplyRETS demo data, you can use these  API credentials: </i>
              <strong>API Key: </strong><span>simplyrets</span>
              <strong>API Secret: </strong><span>simplyrets</span>
            </span>
          <div>
          <?php submit_button(); ?>
          <hr>
          <div class="sr-admin-settings">
            <h2>Single Listing Page Settings</h2>
            <h3>Contact Form Lead Capture</h3>
            <table>
              <tbody>
                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_show_leadcapture" name="sr_show_leadcapture" value="1" '
                        . checked(1, get_option('sr_show_leadcapture'), false) . '/>'
                      ?>
                      Enable Contact Form Lead Capture on single listing pages?
                    </label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <p><strong>Send Lead Capture forms submissions to:<p></strong>
                    <input
                        type="email"
                        name="sr_leadcapture_recipient"
                        multiple
                        value="<?php echo esc_attr( get_option('sr_leadcapture_recipient') ); ?>"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
            <h3>Map settings</h3>
            <table>
              <tbody>
                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_disable_listing_details_map" name="sr_disable_listing_details_map" value="1" '
                        . checked(1, get_option('sr_disable_listing_details_map'), false) . '/>'
                      ?>
                        Disable map view on listing details page
                    </label>
                  </td>
                </tr>
              </tbody>
            </table>
            <h3>Show/Hide Fields</h3>
            <table>
              <tbody>
                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_show_listingmeta" name="sr_show_listingmeta" value="1" '
                        . checked(1, get_option('sr_show_listingmeta'), false) . '/>'
                      ?>
                      Hide 'Listing Meta Information' fields from property details?
                    </label>
                  </td>
                </tr>
                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_show_agent_contact" name="sr_show_agent_contact" value="1" '
                        . checked(1, get_option('sr_show_agent_contact'), false) . '/>'
                      ?>
                      Do not show Agent and Office phone number and email address (names are still shown).
                    </label>
                  </td>
                </tr>
                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_show_listing_remarks" name="sr_show_listing_remarks" value="1" '
                        . checked(1, get_option('sr_show_listing_remarks'), false) . '/>'
                      ?>
                      Hide 'Listing Remarks' (description) field from property details?
                    </label>
                  </td>
                </tr>

                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_additional_rooms" name="sr_additional_rooms" value="1" '
                        . checked(1, get_option('sr_additional_rooms'), false) . '/>'
                      ?>
                      Show additional room details?
                    </label>
                  </td>
                </tr>

                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_show_mls_status_text" name="sr_show_mls_status_text" value="1" '
                        . checked(1, get_option('sr_show_mls_status_text'), false) . '/>'
                      ?>
                      Show MLS status text if available (in place of standardized status)?
                    </label>
                  </td>
                </tr>

              </tbody>
            </table>
            <h3>Image Gallery Settings</h3>
            <table>
              <tbody>
                <tr>
                  <td>
                    <label>
                      <?php echo
                        '<input type="radio" id="sr_listing_gallery" name="sr_listing_gallery" value="fancy" '
                        . checked('fancy', get_option('sr_listing_gallery'), false) . '/>'
                      ?>
                      Fancy Gallery
                    </label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <label>
                      <?php echo
                        '<input type="radio" id="sr_listing_gallery" name="sr_listing_gallery" value="classic" '
                        . checked('classic', get_option('sr_listing_gallery'), false) . '/>'
                      ?>
                      Classic Gallery
                    </label>
                  </td>
                </tr>
              </tbody>
            </table>
            <br/>
            <table>
              <tbody>
                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_listing_force_image_https" name="sr_listing_force_image_https" value="1" '
                        . checked(1, get_option('sr_listing_force_image_https'), false) . '/>'
                      ?>
                        Force images to load using <code>https://</code>
                    </label>
                    <br/>
                    <p style="padding-left:25px;max-width:350px;margin-bottom:0px">
                        <small>
                            Enabling this loads all images using
                            <code>https://</code> to prevent a Mixed
                            Content warning in the browser on sites
                            using SSL. (Note that not all data
                            providers support HTTPS image URLs.)
                        </small>
                    </p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <?php submit_button(); ?>
          <hr>

          <div class="sr-admin-open-house-settings">
            <h2>Open house settings</h2>
            <h3 style="margin-bottom:5px;">
                Default timezone
            </h3>
            <table>
              <tbody>
                <tr>
                  <td colspan="2">
                      <select name="sr_date_default_timezone">
                          <option value="">None</option>
                          <?php foreach(SrAdminSettings::$timezones as $txt=>$tz) { ?>
                              <option
                                  value="<?php echo $tz; ?>"
                                  <?php selected(
                                      get_option("sr_date_default_timezone"), $tz);
                                  ?>>
                                  <?php echo $txt; ?>
                              </option>
                          <?php } ?>
                      </select>
                  </td>
                </tr>
              </tbody>
            </table>
            <p style="margin-top:0px;">
                Select the timezone used to display open house start
                and end times.<br/>If you don't want to convert any
                timestamps, select "None".
            </p>
          </div>
          <?php submit_button(); ?>
          <hr>

          <div class="sr-admin-settings">
            <h2>Listing Compliance Settings</h2>
            <h3>Show listing agent and office information</h3>
            <table>
              <tbody>
                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_office_on_thumbnails" name="sr_office_on_thumbnails" value="1" '
                        . checked(1, get_option('sr_office_on_thumbnails'), false) . '/>'
                      ?>
                      Show listing office name on listing summary thumbnails
                    </label>
                  </td>
                </tr>
                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_agent_on_thumbnails" name="sr_agent_on_thumbnails" value="1" '
                        . checked(1, get_option('sr_agent_on_thumbnails'), false) . '/>'
                      ?>
                      Show listing agent name on listing summary thumbnails
                    </label>
                  </td>
                </tr>
                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_agent_office_above_the_fold" name="sr_agent_office_above_the_fold" value="1" '
                        . checked(1, get_option('sr_agent_office_above_the_fold'), false) . '/>'
                      ?>
                      Show Listing Agent and Office above the fold on single listing pages
                    </label>
                  </td>
                </tr>
                <tr>
                  <td>
                      <p>IDX image for listing thumbnails <i>(enter a URL)</i>: </p>
                  </td>
                  <td>
                      <input
                          type="text"
                          name="sr_thumbnail_idx_image"
                          value="<?php echo esc_attr( get_option('sr_thumbnail_idx_image') ); ?>"
                      />
                  </td>
                </tr>
              </tbody>
            </table>
            <h3>MLS trademark symbol</h3>
            <table>
              <tbody>
                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_show_mls_trademark_symbol" name="sr_show_mls_trademark_symbol" value="1" '
                        . checked(1, get_option('sr_show_mls_trademark_symbol'), false) . '/>'
                      ?>
                        Show trademark symbol next to MLS text (eg, "MLS®")
                    </label>
                  </td>
                </tr>
              </tbody>
            </table>

            <h3>Default IDX filter (Display rules)</h3>
            <p style="margin-top:-10px">
                <i>Note: </i>You can override this on any short-code by using
                <a href="https://wordpress-demo.simplyrets.com/documentation">
                    the <code>idx</code> attribute.
                </a>
            </p>
            <table>
              <tbody>

                <!-- idx=null (default) -->
                <tr>
                  <td>
                    <label>
                        <?php echo
                        '<input type="radio" id="sr_default_idx_filter" name="sr_default_idx_filter" value="null" '
                        . checked("null", get_option('sr_default_idx_filter'), false) . '/>'
                        ?>
                        Show listings approved for IDX display and IDX address display
                        <strong><i> (default)</i></strong>
                    </label>
                    <div style="margin-left:25px; margin-bottom:10px; max-width:50%">
                        <small>
                            This is the default option and probably the one you want if you're unsure.
                        </small>
                    </div>
                  </td>
                </tr>

                <!-- idx=listing -->
                <tr>
                  <td>
                    <label>
                        <?php echo
                        '<input type="radio" id="sr_default_idx_filter" name="sr_default_idx_filter" value="listing" '
                        . checked("listing", get_option('sr_default_idx_filter'), false) . '/>'
                        ?>
                        Show listings approved for IDX display, but ignore IDX address display rules
                    </label>
                    <div style="margin-left:25px; margin-bottom:-5px; max-width:50%">
                        <small>
                            Show listings approved for IDX display, but ignore any restrictions on
                            displaying the address.
                        </small>
                    </div>
                    <p style="margin-left:25px">
                        <strong>Suppressed address text replacement</strong>
                        <br/>
                        <small>
                            Show this text in place of the address for listings with IDX address restrictions.
                        </small>
                        <br/>
                        <input
                            type="text"
                            name="sr_idx_address_display_text"
                            value="<?php echo esc_attr( get_option("sr_idx_address_display_text") ); ?>"
                        />
                    </p>
                  </td>
                </tr>

                <!-- idx=address -->
                <tr>
                  <td>
                    <label>
                        <?php echo
                        '<input type="radio" id="sr_default_idx_filter" name="sr_default_idx_filter" value="address" '
                        . checked("address", get_option('sr_default_idx_filter'), false) . '/>'
                        ?>
                        Show listings approved for IDX address display, but ignore IDX listing display restrictions.
                    </label>
                    <div style="margin-left:25px; margin-bottom:10px; max-width:50%">
                        <small>
                            Show listings that are NOT approved for IDX display, but ARE approved for
                            IDX address display. <strong>Use this option carefully</strong> and check
                            the display requirements with your data feed provider.
                        </small>
                    </div>
                  </td>
                </tr>

                <!-- idx=ignore -->
                <tr>
                  <td>
                    <label>
                        <?php echo
                        '<input type="radio" id="sr_default_idx_filter" name="sr_default_idx_filter" value="ignore" '
                        . checked("ignore", get_option('sr_default_idx_filter'), false) . '/>'
                        ?>
                        Ignore all IDX restrictions
                    </label>
                    <div style="margin-left:25px; margin-bottom:0px; max-width:50%">
                        <small>
                            Show all listings regardless of any IDX restrictions.
                            <strong>USE CAUTION</strong> enabling this on public sites and check the display
                            requirements for your data feed provider or MLS.
                        </small>
                    </div>
                  </td>
                </tr>

              </tbody>
            </table>
          </div>
          <?php submit_button(); ?>
          <hr>
          <div class="sr-admin-settings-permalinks">
            <h2>Permalink Structure</h2>
            <p>
                If you're using Wordpress' pretty permalinks, we have
                a few different options you can choose from for single
                listing pages.
            </p>
            <table>
              <tbody>
                <tr>
                  <td>
                    <label>
                      <?php echo
                        '<input type="radio" id="sr_permalink_structure" name="sr_permalink_structure" value="pretty" '
                        . checked('pretty', get_option('sr_permalink_structure'), false) . '/>'
                      ?>
                      Basic Pretty Links <i>(Ex: "/listings/{id}/{address})</i>
                    </label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <label>
                      <?php echo
                        '<input type="radio" id="sr_permalink_structure" name="sr_permalink_structure" value="pretty_extra" '
                        . checked('pretty_extra', get_option('sr_permalink_structure'), false) . '/>'
                      ?>
                      Pretty Links Extra <i>(Ex: "/listings/{city}/{state}/{zip}/{address}/{id}
                    </label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <label>
                      <?php echo
                        '<input type="radio" id="sr_permalink_structure" name="sr_permalink_structure" value="query_string" '
                        . checked('query_string', get_option('sr_permalink_structure'), false) . '/>'
                      ?>
                      Query String Links <i>(Ex: "/?sr-listings=sr-single&address={address}&listing_id={id}")</i>
                    </label>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <?php submit_button(); ?>
          <hr>
          <div class="sr-admin-settings-map">
            <h2>Map Settings</h2>
            <p>On pages with multiple results, how would you like to show the map and list views?</p>
            <table>
              <tbody>
                <tr>
                  <td>
                    <label>
                      <?php echo
                        '<input type="radio" id="sr_search_map_position" name="sr_search_map_position" value="list_only" '
                        . checked('list_only', get_option('sr_search_map_position'), false) . '/>'
                      ?>
                      Only Show List View
                    </label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <label>
                      <?php echo
                        '<input type="radio" id="sr_search_map_position" name="sr_search_map_position" value="map_only" '
                        . checked('map_only', get_option('sr_search_map_position'), false) . '/>'
                      ?>
                      Only Show Map View
                    </label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <label>
                      <?php echo
                        '<input type="radio" id="sr_search_map_position" name="sr_search_map_position" value="map_above" '
                        . checked('map_above', get_option('sr_search_map_position'), false) . '/>'
                      ?>
                      Show Map View Above List View
                    </label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <label>
                      <?php echo
                        '<input type="radio" id="sr_search_map_position" name="sr_search_map_position" value="map_below" '
                        . checked('map_below', get_option('sr_search_map_position'), false) . '/>'
                      ?>
                      Show Map View Below List View
                    </label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <br/>
                    <strong>Google Maps API Key</strong>
                    <br>
                    <i>(Required for maps. Get one <a href="https://console.developers.google.com/flows/enableapi?apiid=maps_backend,geocoding_backend,directions_backend,distance_matrix_backend,elevation_backend,places_backend&keyType=CLIENT_SIDE&reusekey=true" target="_blank">here</a>.)</i>
                  </td>
                  <td>
                    <input type="text" name="sr_google_api_key" value="<?php echo esc_attr( get_option('sr_google_api_key') ); ?>" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <?php submit_button(); ?>
          <hr>
          <div class="sr-admin-settings-lh">
            <h2>Listhub Analytics Settings</h2>
            <table>
              <tbody>
                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_listhub_analytics" name="sr_listhub_analytics" value="1" '
                        . checked(1, get_option('sr_listhub_analytics'), false) . '/>'
                      ?>
                      Enable Listhub Analytics? <i>(requires an account with Listhub)</i>
                    </label>
                  </td>
                </tr>
                <tr>
                  <td colspan="2">
                    <label>
                      <?php echo
                        '<input type="checkbox" id="sr_listhub_analytics_test_events" name="sr_listhub_analytics_test_events" value="1" '
                        . checked(1, get_option('sr_listhub_analytics_test_events'), false) . '/>'
                      ?>
                      Send test events to ListHub <i>(disable for live events)</i>
                    </label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <strong>Metrics Provider ID</strong><br><i> (provided by Listhub)</i>
                  </td>
                  <td>
                    <input type="text" name="sr_listhub_analytics_id" value="<?php echo esc_attr( get_option('sr_listhub_analytics_id') ); ?>" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <?php submit_button(); ?>
          <div>
            <h3>Custom disclaimer</h3>
            <p>Custom disclaimer to be shown with all short-codes</p>
            <textarea
                id="sr_custom_disclaimer"
                name="sr_custom_disclaimer"
                cols="50"
                rows="8"><?php echo esc_attr( get_option('sr_custom_disclaimer') ); ?></textarea>
            <ul>
                <li>
                    - Use the variable "{lastUpdate}" to interpolate
                    the time of the last feed update.
                </li>
                <li>
                    - You can use HTML or plain text.
                </li>
            </ul>
          </div>
          <?php submit_button(); ?>
          <div>
            <h3>No search results message</h3>
            <p>The messasge shown when a search doesn't return results.</p>
            <textarea
                id="sr_custom_no_results_message"
                name="sr_custom_no_results_message"
                cols="50"
                rows="5"><?php echo esc_attr( get_option('sr_custom_no_results_message') ); ?></textarea>
            <div>
                <i>
                    Default: There are 0 listings that match this
                    search. Try broadening your search criteria or
                    try again later.
                </i>
            </div>
            <?php submit_button(); ?>
          </div>
        </form>
      </div>
    <?php
  }
} ?>
