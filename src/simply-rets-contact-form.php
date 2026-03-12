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

            if (!isset($_POST['sr_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sr_contact_nonce'])), 'sr_contact_action')) {
                return '';
            }

            // sanitize form values
            $listing = isset($_POST["sr-cf-listing"]) ? sanitize_text_field(wp_unslash($_POST["sr-cf-listing"])) : '';
            $name    = isset($_POST["sr-cf-name"]) ? sanitize_text_field(wp_unslash($_POST["sr-cf-name"])) : '';
            $email   = isset($_POST["sr-cf-email"]) ? sanitize_email(wp_unslash($_POST["sr-cf-email"])) : '';
            $subject = isset($_POST["sr-cf-subject"]) ? sanitize_text_field(wp_unslash($_POST["sr-cf-subject"])) : '';
            $message = isset($_POST["sr-cf-message"]) ? sanitize_textarea_field(wp_unslash($_POST["sr-cf-message"])) : '';

            $message = $message
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
                return '<div id="sr-contact-form-error">An unexpected error occurred</div>';
            }
        }
        return '';
    }
}
