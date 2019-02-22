<?php
// If you are using Composer (recommended)
require_once(__DIR__ . '/../vendor/autoload.php');

function __send_email($__from_mail, $__from_name, $__to_mail, $__to_name, $__mail_title, $__mail_content)
{

    $from = new SendGrid\Email($__from_name, $__from_mail);
    $subject = $__mail_title;
    $to = new SendGrid\Email($__to_name, $__to_mail);
    $content = new SendGrid\Content("text/plain", $__mail_content);
    $mail = new SendGrid\Mail($from, $subject, $to, $content);

    $apiKey = "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";
    $sg = new \SendGrid($apiKey);

    $response = $sg->client->mail()->send()->post($mail);

}