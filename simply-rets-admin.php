<?php

/*
 * simply-rets-admin.php - Copyright (C) 2014-2015 SimplyRETS
 * This file provides the logic for the SimplyRETS admin panel settings and features pages.
 *
*/


/* Code starts here */

class SrAdminSettings {

  function add_to_admin_menu() {
      add_options_page('SimplyRETS Settings'
                       , 'SimplyRETS'
                       , 'manage_options'
                       , 'simplyrets-admin.php'
                       , array('SrAdminSettings', 'sr_admin_page')
      );
  }
  
  function register_admin_settings() {
      register_setting('sr_admin_settings', 'sr_api_name');
      register_setting('sr_admin_settings', 'sr_api_key');
      register_setting('sr_admin_settings', 'sr_contact_page');
      register_setting('sr_admin_settings', 'sr_show_listingmeta');
      register_setting('sr_admin_settings', 'sr_show_listing_remarks');
      register_setting('sr_admin_settings', 'sr_listing_gallery');
      register_setting('sr_admin_settings', 'sr_show_leadcapture');
      register_setting('sr_admin_settings', 'sr_listhub_analytics');
      register_setting('sr_admin_settings', 'sr_listhub_analytics_id');
      register_setting('sr_admin_settings', 'sr_search_map_position');
  }
  
  function sr_admin_page() {
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
      ?>
      <div class="wrap sr-admin-wrap">
        <h2 id="message"></h2>
        <img class="sr-admin-logo" src="<?php echo $logo_path; ?>">
        <h1 class="sr-admin-title">SimplyRETS Admin Settings</h1>
        <div class="sr-doc-links">
          <p>
            <a target="_blank" href="http://simplyrets.com">SimplyRETS Website</a> |
            <a target="_blank" href="https://wordpress.org/plugins/simply-rets/other_notes/">SimplyRETS Wordpress Plugin Documentation</a> |
            <a target="_blank" href="http://docs.simplyrets.com">SimplyRETS API Documentation</a> |
            <a target="_blank" href="mailto:support@simplyrets.com">support@simplyrets.com</a>
            <form method="post" action="options-general.php?page=simplyrets-admin.php" style="display:inline-block;">
                <?php submit_button( "Refresh Meta Data", "submit", "sr_update_meta", 0 ); ?>
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
                    <strong>API Username</strong>
                  </td>
                  <td>
                    <input type="text" name="sr_api_name" value="<?php echo esc_attr( get_option('sr_api_name') ); ?>" />
                  </td>
                </tr>
                <tr>
                  <td>
                    <strong>API Key</strong>
                  </td>
                  <td>
                    <input type="text" name="sr_api_key" value="<?php echo esc_attr( get_option('sr_api_key') ); ?>" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div>
            <span>
              <i>Note - to use the SimplyRETS demo data, you can use these  API credentials: </i>
              <strong>API Username: </strong><span>simplyrets</span>
              <strong>API Key: </strong><span>simplyrets</span>
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
                        '<input type="checkbox" id="sr_show_listing_remarks" name="sr_show_listing_remarks" value="1" '
                        . checked(1, get_option('sr_show_listing_remarks'), false) . '/>'
                      ?>
                      Hide 'Listing Remarks' (description) field from property details?
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
        </form>
        <?php
  }
} ?>
