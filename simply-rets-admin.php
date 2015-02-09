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
  }
  
  function sr_admin_page() {
      global $wpdb;
      ?>
      <div class="wrap">
        <h2>SimplyRETS Admin Settings</h2>
        <hr>
        <form method="post" action="options.php">
          <?php settings_fields( 'sr_admin_settings'); ?>
          <?php do_settings_sections( 'sr_admin_settings'); ?>
          <div class="sr-admin-api">
            <h3>Account Credentials</h3>
            <p>
              Enter your SimplyRETS API credentials in the fields below.
              <i>  Note: properties will not show up until these are correct.</i>
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
            <h3>Settings</h3>
            <table>
              <tbody>
                <tr>
                  <td>
                    <strong>Contact Page Link</strong>
                  </td>
                  <td>
                    <input type="text" name="sr_contact_page" value="<?php echo esc_attr( get_option('sr_contact_page') ); ?>" />
                  </td>
                </tr>
              </tbody>
            </table>
            <p>
              <i> Use the full path to the page. Example: http://yoursite.com/contact </i>
            </p>
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
            <a target="_blank" href="http://docs.simplyrets.com">SimplyRETS Wordpress Plugin Documentation</a> |
            <a target="_blank" href="http://docs.simplyrets.com">SimplyRETS API Documentation</a> |
            <a target="_blank" href="mailto:support@simplyrets.com">support@simplyrets.com</a>
          </p>
      </div> <?php
  }
} ?>