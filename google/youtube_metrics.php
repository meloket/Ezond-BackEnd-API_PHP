<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');

$test = 0;
$start_date = date("Y-m-d");
$end_date = date("Y-m-d");

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];

if ($test == 1) {
    $viewID = "UCzwmTlUSvQTObGb5G-Zo9dw";
} else {
    $refreshToken = "";
    $viewID = "";

    if (isset($_GET['refreshToken'])) $refreshToken = $_GET['refreshToken'];
    if (isset($_GET['viewID'])) $viewID = $_GET['viewID'];
    if ($refreshToken == "") exit();
    if ($viewID == "") exit();

    $redirect_uri = SITE_URL . "google/analytics_callback.php";

    $client = new Google_Client();
    $client->setApplicationName($googleAppName);
    $client->setAccessType("offline");
    $client->setClientId($googleClientID);
    $client->setClientSecret($googleClientSecret);
    $client->setRedirectUri($redirect_uri);

    $client->refreshToken($refreshToken);
    $token = $client->getAccessToken();
}

$analytics = new Google_Service_YouTubeAnalytics($client);

$ret = new stdClass();
$ret->views = "0";
$ret->likes = "0";
$ret->dislikes = "0";

$id = "channel==" . $viewID;
$metrics = 'views,likes,dislikes';

try {
    $api = $analytics->reports->query($id, $start_date, $end_date, $metrics);
    if ($api->getRows()) {
        $fld_value = $api->getRows()[0];

        $ret->views = $fld_value[0];
        $ret->likes = $fld_value[1];
        $ret->dislikes = $fld_value[2];
    }
} catch (Exception $e) {
    //throw new Exception("Google API Exception: ", $e->getMessage());
}

echo json_encode($ret);

?>