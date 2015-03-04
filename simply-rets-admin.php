<?php

/*
 * simply-rets-admin.php - Copyright (C) 2014 Reichert Brothers
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
  }
  
  function sr_admin_page() {
      global $wpdb;
      $logo_path = plugin_dir_url(__FILE__) . 'assets/img/logo_button.png';
      ?>
      <div class="wrap sr-admin-wrap">
        <img class="sr-admin-logo" src="<?php echo $logo_path; ?>">
        <h1 class="sr-admin-title">SimplyRETS Admin Settings</h2>
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
        </form>
        <div>
          <span>
            <i>Note - to use the SimplyRETS demo data, you can use these  API credentials: </i>
            <strong>API Username: </strong><span>simplyrets</span>
            <strong>API Key: </strong><span>simplyrets</span>
          </span>
        <div>
        <hr>
        <div class="sr-doc-links">
          <p>
            <a target="_blank" href="http://simplyrets.com">SimplyRETS Website</a> |
            <a target="_blank" href="https://wordpress.org/plugins/simply-rets/other_notes/">SimplyRETS Wordpress Plugin Documentation</a> |
            <a target="_blank" href="http://docs.simplyrets.com">SimplyRETS API Documentation</a> |
            <a target="_blank" href="mailto:support@simplyrets.com">support@simplyrets.com</a>
          </p>
      </div> <?php
  }
} ?>