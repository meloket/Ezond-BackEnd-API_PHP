<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');

$redirect_uri = SITE_URL . "google/callback.php";

require_once(__DIR__ . '/google_ins.php');

$access_token = $token['access_token'];

print_r($token);
?>