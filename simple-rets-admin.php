<?php
/*
 *
 * simple-rets-admin.php - Copyright (C) 2014 Reichert Brothers
 * This file provides the logic for the simple-rets admin panel settings and features pages.
 *
*/

/* Code starts here */

function add_to_admin_menu() {
    add_options_page('RetsD Settings', 'RetsD', 'manage_options', 'rets-admin.php', 'admin_page');
}

function register_admin_settings() {
    register_setting('rets_admin_settings', 'api_name');
    register_setting('rets_admin_settings', 'api_key');
}

function admin_page() {
    global $wpdb;
    ?>
    <div class="wrap">
      <h2>RetsD Admin Settings</h2>
      <hr>

      <form method="post" action="options.php">
        <?php settings_fields( 'rets_admin_settings'); ?>
        <?php do_settings_sections( 'rets_admin_settings'); ?>

        <!-- api username -->
        <strong>Api Username: </strong>
        <input type="text" name="api_name" value="<?php echo esc_attr( get_option('api_name') ); ?>" />
        <span>(current: <?php echo esc_attr( get_option('api_name') ); ?>)</span>
        <br>
        <br>

        <!-- api password -->
        <strong>Api Key: </strong>
        <input type="text" name="api_key" value="<?php echo esc_attr( get_option('api_key') ); ?>" />
        <span>(current: <?php echo esc_attr( get_option('api_key') ); ?>)</span>
        <?php submit_button(); ?>
      </form>

    </div>
    <?php
}

?>