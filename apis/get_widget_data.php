<?php

ini_set('max_execution_time', 100000);
error_reporting(E_STRICT | E_ALL);

require_once(__DIR__ . '/../config.php');
require_once (__DIR__ . '/../aws/AwsS3.php');

if ($_GET['test']) {
    $_GET['dashboardID'] = 27;
    $_GET['startDate'] = '2018-01-01';
    $_GET['endDate'] = date("Y-m-d");
}


/* gets the contents of a file if it exists, otherwise grabs and caches */
function get_content($url, $hours = 6)
{
    $file = 'hash/' . md5($url);
    $s3 = new AwsS3();

    $current_time = time();
    $expire_time = $hours * 60 * 60;
    if ($_GET['test'])
        $expire_time = 0;

    if ($hash_file = $s3->getFile($file)) {
        $file_time = strtotime( (string)$hash_file['LastModified']);
        if ($current_time - $expire_time < $file_time){

            return (string) $hash_file['Body'];
        }
        $s3->removeFile($file);
    }
    $content = get_url2($url);
    $s3->createFileByContent($file, $content);

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
if (isset($_GET['dashboardID'])) $dashboardID = $_GET['dashboardID'];
if ($dashboardID == "") exit();

$startDate = "";
if (isset($_GET['startDate'])) $startDate = $_GET['startDate'];
if ($startDate == "") exit();

$endDate = "";
if (isset($_GET['endDate'])) $endDate = $_GET['endDate'];
if ($endDate == "") exit();

$sql = "SELECT DISTINCT b.refresh_token, b.authResponse, b.networkID, b.networkName FROM widgets a 
              INNER JOIN users_networks b ON a.network = b.networkID 
                    WHERE a.dashboardID = :dashboardID 
                      AND b.dashboardID = :dashboardID 
                      AND b.defaultCheck = 1";
$data = [
    'dashboardID' => $dashboardID,
];
$result = $db->select($sql, $data);

$arr_return = array();
for ($i = 0; $i < count($result); $i++) {
    $checkObj = new stdClass();
    $checkObj->networkID = $result[$i]["networkID"];
    $checkObj->refresh_token = $result[$i]["refresh_token"];
    $checkObj->viewID = "";
    $tempObj = json_decode($result[$i]["authResponse"]);
    $checkObj->checkURL = "";
    if ($checkObj->networkID == 1) {
        $checkObj->viewID = $tempObj->id;
        $checkObj->checkURL = "google/analytics_metrics.php";
    } else if ($checkObj->networkID == 2) {
        $checkObj->viewID = $tempObj->id;
        $checkObj->checkURL = "adwords/metrics.php";
    } else if ($checkObj->networkID == 3) {
        $checkObj->viewID = $tempObj->websiteUrl;
        $checkObj->checkURL = "google/console_metrics.php";
    } else if ($checkObj->networkID == 5) {
        $checkObj->viewID = $tempObj->id;
        $checkObj->checkURL = "google/youtube_metrics.php";
    } else if ($checkObj->networkID == 7) {
        $checkObj->viewID = $tempObj->accountId;
        $checkObj->checkURL = "facebook/ads_test.php";
    } else if ($checkObj->networkID == 8) {
        $checkObj->viewID = $tempObj->id;
        $checkObj->checkURL = "facebook/facebook_metrics.php";
    } else if ($checkObj->networkID == 9) {
        $checkObj->viewID = $tempObj->id;
        $checkObj->checkURL = "callrail/getData.php";
    }

    if ($_GET['test']) {
        $checkObj->networkName = $result[$i]['networkName'];
        $checkObj->id = $result[$i]['networkID'];
    }

    if (($checkObj->viewID) && ($checkObj->checkURL)) {
        $catchURL = sprintf(SITE_URL . "%s?refreshToken=%s&viewID=%s&start_date=%s&end_date=%s", $checkObj->checkURL, $checkObj->refresh_token, $checkObj->viewID, $startDate, $endDate);

        $checkObj->metricsResult = get_content($catchURL);
        array_push($arr_return, $checkObj);

    }
}

echo json_encode($arr_return);

?>
