<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');

$redirect_uri = SITE_URL . "google/analytics_callback.php";

require_once(__DIR__ . '/google_ins.php');

$access_token = $token['access_token'];

$check_url = ("https://www.googleapis.com/analytics/v3/management/accounts/~all/webproperties/~all/profiles?access_token=$access_token&start-index=1");

$method = "analytics";

require_once(__DIR__ . '/google_result.php');

exit();
?>