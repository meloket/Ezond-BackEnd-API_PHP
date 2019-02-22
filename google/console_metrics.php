<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');

$test = 0;
if ($test == 1) {
    $viewID = "http://venus.web44.net/";
} else {
    $refreshToken = "";
    $viewID = "";

    if (isset($_GET['refreshToken'])) $refreshToken = $_GET['refreshToken'];
    if (isset($_GET['viewID'])) $viewID = $_GET['viewID'];
    if ($refreshToken == "") exit();
    if ($viewID == "") exit();

    $start_date = date("Y-m-d");
    $end_date = date("Y-m-d");

    if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
    if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];

    $start_date = date("Y-m-d", strtotime($start_date) - 86400);
    $end_date = date("Y-m-d", strtotime($end_date) - 86400);

    $redirect_uri = SITE_URL . "google/console_callback.php";

    $client = new Google_Client();
    $client->setApplicationName($googleAppName);
    $client->setAccessType("offline");
    $client->setClientId($googleClientID);
    $client->setClientSecret($googleClientSecret);
    $client->setRedirectUri($redirect_uri);

    $client->refreshToken($refreshToken);
    $token = $client->getAccessToken();
}

$webmaster = new Google_Service_Webmasters($client);

$search = new Google_Service_Webmasters_SearchAnalyticsQueryRequest;
$search->setStartDate($start_date);
$search->setEndDate($end_date);

$results = $webmaster->searchanalytics->query($viewID, $search, array())->getRows();

$ret = new stdClass();
$ret->clicks = 0;
$ret->impressions = 0;
$ret->ctr = "0.00%";
$ret->position = "0.0";

if (count($results) > 0) {
    $result = $results[0];
    $ret->clicks = number_format($result->clicks);
    $ret->impressions = number_format($result->impressions);
    $ret->ctr = round($result->ctr * 100, 2) . '%';
    $ret->position = round($result->position, 1);
}

echo json_encode($ret);
?>