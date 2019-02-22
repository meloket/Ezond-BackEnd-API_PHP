<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');

$redirect_uri = SITE_URL . "google/console_callback.php";

require_once(__DIR__ . '/google_ins.php');

$access_token = $token['access_token'];

$check_url = ("https://www.googleapis.com/webmasters/v3/sites?access_token=$access_token");

$method = "console";

require_once(__DIR__ . '/google_result.php');

exit();
?>