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
$search->setDimensions(array('date'));

$results = $webmaster->searchanalytics->query($viewID, $search, array())->getRows();

$arr_result = array();
if (!empty($results)) {
    foreach ($results as $key => $result) {
        $ret = new stdClass();
        $ret->caseNumber = 0;
        $ret->clicks = $result->clicks;
        $ret->impressions = $result->impressions;
        $ret->position = round($result->position, 1);
        $ret->ctr = round($result->ctr * 100, 2);
        $ret->dimensions = $result->keys[0];

        array_push($arr_result, $ret);
    }
}

function __convert_date_format($__date_string)
{
    if (strlen($__date_string) > 18)
        return substr($__date_string, 0, 10) . " " . substr($__date_string, 11, 8);
    else
        return $__date_string;
}

$arrCategory = array("notFound", "notFollowed", "authPermissions", "serverError", "soft404", "roboted", "manyToOneRedirect", "flashContent", "other");
$arrPlatform = array("web", "smartphoneOnly");
$arr_errors = array();
foreach ($arrCategory as $key1 => $category) {
    foreach ($arrPlatform as $key => $platform) {
        if (!(($platform == "web") && (($category == "roboted") || ($category == "manyToOneRedirect") || ($category == "flashContent")))) {
            $results = $webmaster->urlcrawlerrorssamples->listUrlcrawlerrorssamples($viewID, $category, $platform)->getUrlCrawlErrorSample();
            if (!empty($results)) {
                foreach ($results as $key => $result) {
                    $ret = new stdClass();
                    $ret->caseNumber = 1;
                    $ret->lastCrawled = __convert_date_format($result->lastCrawled);
                    $ret->pageUrl = $result->pageUrl;
                    $ret->firstDetected = __convert_date_format($result->firstDetected);
                    $ret->category = $category;
                    $ret->responseCode = $result->responseCode;
                    $__test_date = substr($ret->lastCrawled, 0, 10);
                    if ((strtotime($__test_date) >= strtotime($start_date)) && (strtotime($__test_date) <= strtotime($end_date)))
                        array_push($arr_result, $ret);
                }
            }
        }
    }
}


echo json_encode($arr_result);
?>