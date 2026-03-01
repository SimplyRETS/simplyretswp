<?php

/*
 * simply-rets-contact-form.php - Copyright (C) 2014-2024 SimplyRETS, Inc.
 *
 * This file provides a class that handles contact form deliveries.
 *
*/

class SimplyRetsContactForm {

    public static function srContactFormDeliver() {

        // if the submit button is clicked, send the email
        if (isset($_POST['sr-cf-submitted'])) {

            // sanitize form values
            $listing = sanitize_text_field($_POST["sr-cf-listing"]);
            $name    = sanitize_text_field($_POST["sr-cf-name"]);
            $email   = sanitize_email($_POST["sr-cf-email"]);
            $subject = sanitize_text_field($_POST["sr-cf-subject"]);
            $message = esc_textarea($_POST["sr-cf-message"])
                . "\r\n" . "\r\n"
                . "Form submission information: "
                . "\r\n"
                . "Listing: " . $listing
                . "\r\n"
                . "Name: " . $name
                . "\r\n"
                . "Email: " . $email;

            // get the blog administrator's email address
            $to = get_option('sr_leadcapture_recipient', '');
            $to = empty($to) ? get_option('admin_email') : $to;

            $headers = "From: $name <$email>" . "\r\n";

            // If email has been process for sending, display a success message
            if (wp_mail($to, $subject, $message, $headers)) {
                return '<div id="sr-contact-form-success">Your message was delivered successfully.</div>';
            } else {
                return 'An unexpected error occurred';
            }
        }
        return '';
    }
}
