<?php

ini_set('max_execution_time', 100000);
error_reporting(E_STRICT | E_ALL);

require_once(__DIR__ . '/../config.php');

function getSslPage($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function get_content($url, $hours = 6)
{
    $file = md5($url);
    $file = HASH_PATH . $file;

    $current_time = time();
    $expire_time = $hours * 60 * 60;
    $expire_time = 0;
    if (file_exists($file)) {
        $file_time = filemtime($file);
        if ($current_time - $expire_time < $file_time)
            return file_get_contents($file);
    }
    $content = get_url2($url);
    file_put_contents($file, $content);
    return $content;
}

function get_url2($url)
{
    return file_get_contents($url);
}

function get_url($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}

$dashboardID = "";

if (isset($_GET['test']) && $_GET['test'] == 'ok') {
    $dashboardID = 27;
    $startDate = '2017-01-01';
    $endDate = '2017-01-01';
    $networkID = 6;
}

if (isset($_GET['dashboardID'])) $dashboardID = $_GET['dashboardID'];
if ($dashboardID == "") exit();

$startDate = "";

if (isset($_GET['test']) && $_GET['test'] == 'ok') {
    $dashboardID = 27;
    $startDate = '2017-01-01';
    $endDate = '2017-01-01';
    $networkID = 6;
}
if (isset($_GET['startDate'])) $startDate = $_GET['startDate'];
if ($startDate == "") exit();

$endDate = "";

if (isset($_GET['test']) && $_GET['test'] == 'ok') {
    $dashboardID = 27;
    $startDate = '2017-01-01';
    $endDate = '2017-01-01';
    $networkID = 6;
}

if (isset($_GET['endDate'])) $endDate = $_GET['endDate'];
if ($endDate == "") exit();

$networkID = "";

if (isset($_GET['test']) && $_GET['test'] == 'ok') {
    $dashboardID = 27;
    $startDate = '2017-01-01';
    $endDate = '2017-01-01';
    $networkID = 6;
}
if (isset($_GET['networkID'])) $networkID = $_GET['networkID'];
if ($networkID == "") exit();

$filterIndex = 0;
$menuIndex = 0;
if ($networkID >= 100) {
    $filterIndex = round(($networkID - $networkID % 1000000) / 1000000);
    $filterIndex--;
    $networkID = $networkID % 1000000;
    $menuIndex = round(($networkID - $networkID % 100) / 100);
    $networkID = $networkID % 100;
}

$result = $db->select("SELECT `refresh_token`, `access_token`, `authResponse` FROM `users_networks` 
                            WHERE `dashboardID` = :dashboardID 
                              AND `networkID` = :networkID 
                              AND `defaultCheck` = 1",
    ['dashboardID' => $dashboardID, 'networkID' => $networkID]
);
if ($result) {
    $checkObj = new stdClass();
    $checkObj->networkID = $networkID;
    $checkObj->refresh_token = $result[0]["refresh_token"];
    $checkObj->viewID = "";
    $tempObj = json_decode($result[0]["authResponse"]);
    $checkObj->checkURL = "";
    if ($checkObj->networkID == 1) {
        $checkObj->viewID = $tempObj->id;
        $checkObj->checkURL = "google/analytics_detailData_" . $menuIndex . ".php";
        if ($filterIndex > 0) $checkObj->checkURL .= "?filterIndex=" . $filterIndex;
    } else if ($checkObj->networkID == 2) {
        $checkObj->viewID = $tempObj->id;
        $checkObj->checkURL = "adwords/adwords_detailData_" . $menuIndex . ".php?filter=" . urlencode($_GET['filter']);
        $filterIndex = 1;
    } else if ($checkObj->networkID == 3) {
        $checkObj->viewID = $tempObj->websiteUrl;
        $checkObj->checkURL = "google/console_detailData.php";
    } else if ($checkObj->networkID == 5) {
        $checkObj->viewID = $tempObj->id;
        $checkObj->checkURL = "google/youtube_detailData.php";
    } else if ($checkObj->networkID == 7) {
        $checkObj->viewID = $tempObj->accountId;
        $checkObj->checkURL = "facebook/ads_detailData_" . $menuIndex . ".php?filter=" . (isset($_GET['filter']) ? urlencode($_GET['filter']) : "");
        $filterIndex = 1;
    } else if ($checkObj->networkID == 8) {
        $checkObj->viewID = $tempObj->id;
        $checkObj->checkURL = "facebook/facebook_detailData.php";
    } else if ($checkObj->networkID == 9) {
        $checkObj->viewID = $tempObj->id;
        $checkObj->checkURL = "callrail/newCallRail.php";
        // $checkObj->checkURL = "callrail/getDetailData.php";
    } else if ($checkObj->networkID == 6) {
        $tempObj = json_decode($result[0]["access_token"]);
        $checkObj->viewID = $tempObj->data->dc;
        $checkObj->checkURL = "mailchimp/getDetailData.php";
    }

    if (($checkObj->viewID) && ($checkObj->checkURL)) {

        if ($filterIndex > 0) {
            $catchURL = sprintf(SITE_URL . "%s&refreshToken=%s&viewID=%s&start_date=%s&end_date=%s", $checkObj->checkURL, $checkObj->refresh_token, $checkObj->viewID, $startDate, $endDate);
        } else
            $catchURL = sprintf(SITE_URL . "%s?refreshToken=%s&viewID=%s&start_date=%s&end_date=%s", $checkObj->checkURL, $checkObj->refresh_token, $checkObj->viewID, $startDate, $endDate);

        $ret_obj = new stdClass();
        $ret_obj->result = array();

        $result = getSslPage($catchURL);

        $test = json_decode($result);

        if (($result) && (json_last_error() === JSON_ERROR_NONE)) echo $result;
        else echo json_encode($ret_obj);
        exit();
    }
}

$ret_obj = new stdClass();
$ret_obj->result = array();
echo json_encode($ret_obj);

?>