<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');

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

$webmaster = new Google_Service_Webmasters($client);

$search = new Google_Service_Webmasters_SearchAnalyticsQueryRequest;
$search->setStartDate($start_date);
$search->setEndDate($end_date);

$results = $webmaster->searchanalytics->query($viewID, $search, array())->getRows();

$ret["Top Queries_Clicks"] = 0;
$ret["Top Queries_Impressions"] = 0;
$ret["Top Queries_CTR"] = '0.00%';
$ret["Top Queries_Avg Position"] = 0;

if (count($results) > 0) {
    $result = $results[0];
    $ret["Top Queries_Clicks"] = number_format($result->clicks);
    $ret["Top Queries_Impressions"] = number_format($result->impressions);
    $ret["Top Queries_CTR"] = round($result->ctr * 100, 2) . '%';
    $ret["Top Queries_Avg Position"] = round($result->position, 1);
}

$ret["Top Pages_Clicks"] = $ret["Top Queries_Clicks"];
$ret["Top Pages_Impressions"] = $ret["Top Queries_Impressions"];
$ret["Top Pages_CTR"] = $ret["Top Queries_CTR"];
$ret["Top Pages_Avg Position"] = $ret["Top Queries_Avg Position"];

$webmaster = new Google_Service_Webmasters($client);

$search = new Google_Service_Webmasters_SearchAnalyticsQueryRequest;
$search->setStartDate($start_date);
$search->setEndDate($end_date);
$search->setDimensions(array('query'));
$search->setRowLimit(1500);

$results = $webmaster->searchanalytics->query($viewID, $search, array())->getRows();

/*
Top Queries          Keyword, Clicks, Impressions, Avg Position, CTR
*/

$arrResult1 = array();
if (!empty($results)) {
    foreach ($results as $key => $result) {
        $srchObj = array();
        $srchObj["Keyword"] = $result->keys[0];
        $srchObj["Clicks"] = number_format($result->clicks);
        $srchObj["Impressions"] = number_format($result->impressions);
        $srchObj["CTR"] = round($result->ctr * 100, 2) . '%';
        $srchObj["Avg Position"] = round($result->position, 1);
        array_push($arrResult1, $srchObj);
    }
}
$ret["Top Queries_result"] = $arrResult1;

/*
Top Pages         Page, Clicks, Impressions, Avg Position, CTR
*/
$search2 = new Google_Service_Webmasters_SearchAnalyticsQueryRequest;
$search2->setStartDate($start_date);
$search2->setEndDate($end_date);
$search2->setDimensions(array('page'));
$search2->setRowLimit(1500);

$results = $webmaster->searchanalytics->query($viewID, $search2, array())->getRows();

$arrResult2 = array();
if (!empty($results)) {
    foreach ($results as $key => $result) {
        $srchObj = array();
        $srchObj["Page"] = $result->keys[0];
        $srchObj["Clicks"] = number_format($result->clicks);
        $srchObj["Impressions"] = number_format($result->impressions);
        $srchObj["CTR"] = round($result->ctr * 100, 2) . '%';
        $srchObj["Avg Position"] = round($result->position, 1);
        array_push($arrResult2, $srchObj);
    }
}
$ret["Top Pages_result"] = $arrResult2;

$notFound = 0;
$notFollowed = 0;
$authPermissions = 0;
$serverError = 0;
$soft404 = 0;
$roboted = 0;
$manyToOneRedirect = 0;
$flashContent = 0;
$other = 0;

$results = $webmaster->urlcrawlerrorscounts->query($viewID, array("latestCountsOnly" => true))->getCountPerTypes();

$arrCategory3 = array("notFound", "notFollowed", "authPermissions", "serverError", "soft404", "roboted", "manyToOneRedirect", "flashContent", "other");
foreach ($arrCategory3 as $key => $value) {
    $fldName = "sumArray" . $value;
    $fldName2 = "_sumArray" . $value;
    $$fldName2 = 0;
    $$fldName = array();
    for ($i = 0; $i <= (strtotime($end_date) - strtotime($start_date)) / 86400; $i++) {
        $$fldName[date("Y-m-d", strtotime($start_date) + 86400 * $i)] = 0;
    }
}

if (!empty($results)) {
    foreach ($results as $key => $result) {
        $category = $result->category;
        $platform = $result->platform;
        $entries = $result->getEntries();
        foreach ($entries as $key => $entry) {
            $$category += $entry->count;
        }
    }
}

$arrCategory = array("notFound", "notFollowed", "authPermissions", "serverError", "soft404", "roboted", "manyToOneRedirect", "flashContent", "other");
$arrCategory2 = array('Not Found', 'Not Followed', 'Auth Permissions', 'Server Error', 'soft404', 'Roboted', 'Many To One Redirect', 'Flash Content', 'Other');
$arrPlatform = array("web", "smartphoneOnly");
$arr_errors = array();
foreach ($arrCategory as $key1 => $category) {
    foreach ($arrPlatform as $key => $platform) {
        if (!(($platform == "web") && (($category == "roboted") || ($category == "manyToOneRedirect") || ($category == "flashContent")))) {
            $results = $webmaster->urlcrawlerrorssamples->listUrlcrawlerrorssamples($viewID, $category, $platform)->getUrlCrawlErrorSample();
            if (!empty($results)) {
                foreach ($results as $key => $result) {

                    $date_val = substr($result->firstDetected, 0, 10);
                    $fldName = "sumArray" . $category;
                    $fldName2 = "_sumArray" . $category;
                    if (strtotime($date_val) >= strtotime($start_date) && strtotime($date_val) <= strtotime($end_date)) {
                        $$fldName[$date_val] += 1;
                    } else if (strtotime($date_val) > strtotime($end_date))
                        $$fldName2++;

                    $srchObj = array();
                    $srchObj["Last Crawled"] = $result->lastCrawled;
                    $srchObj["Page Url"] = $result->pageUrl;
                    $srchObj["First Detected"] = $result->firstDetected;
                    $srchObj["Category"] = $arrCategory2[$key1];
                    $srchObj["Response Code"] = $result->responseCode;
                    array_push($arr_errors, $srchObj);
                }
            }
        }
    }
}
foreach ($arrCategory3 as $key => $value) {
    $fldName = "sumArray" . $value;
    $fldName2 = "_sumArray" . $value;
    $last_val = 0;

    for ($i = 1; $i <= (strtotime($end_date) - strtotime($start_date)) / 86400; $i++) {
        $curFldIdx = date("Y-m-d", strtotime($start_date) + 86400 * $i);
        $preFldIdx = date("Y-m-d", strtotime($start_date) + 86400 * ($i - 1));
        $$fldName[$curFldIdx] += $$fldName[$preFldIdx];
        $last_val = $$fldName[$curFldIdx];
    }

    for ($i = 0; $i <= (strtotime($end_date) - strtotime($start_date)) / 86400; $i++) {
        $$fldName[date("Y-m-d", strtotime($start_date) + 86400 * $i)] += $$value - $$fldName2 - $last_val;
    }
}

$ret["Crawl Errors_result"] = $arr_errors;


$strChart = "Day,Not Found\n";
foreach ($sumArraynotFound as $id => $value) {
    $strChart .= $id . "," . $sumArraynotFound[$id] . "\n";
}
$ret["Crawl Errors_Not FoundChart"] = $strChart;

$strChart = "Day,Not Followed\n";
foreach ($sumArraynotFollowed as $id => $value) {
    $strChart .= $id . "," . $sumArraynotFollowed[$id] . "\n";
}
$ret["Crawl Errors_Not FollowedChart"] = $strChart;

$strChart = "Day,Auth Permissions\n";
foreach ($sumArrayauthPermissions as $id => $value) {
    $strChart .= $id . "," . $sumArrayauthPermissions[$id] . "\n";
}
$ret["Crawl Errors_Auth PermissionsChart"] = $strChart;

$strChart = "Day,Server Error\n";
foreach ($sumArrayserverError as $id => $value) {
    $strChart .= $id . "," . $sumArrayserverError[$id] . "\n";
}
$ret["Crawl Errors_Server ErrorChart"] = $strChart;

$strChart = "Day,Soft 404\n";
foreach ($sumArraysoft404 as $id => $value) {
    $strChart .= $id . "," . $sumArraysoft404[$id] . "\n";
}
$ret["Crawl Errors_Soft 404Chart"] = $strChart;

$strChart = "Day,Roboted\n";
foreach ($sumArrayroboted as $id => $value) {
    $strChart .= $id . "," . $sumArrayroboted[$id] . "\n";
}
$ret["Crawl Errors_RobotedChart"] = $strChart;

$strChart = "Day,Many To One\n";
foreach ($sumArraymanyToOneRedirect as $id => $value) {
    $strChart .= $id . "," . $sumArraymanyToOneRedirect[$id] . "\n";
}
$ret["Crawl Errors_Many To OneChart"] = $strChart;

$strChart = "Day,Flash Content\n";
foreach ($sumArrayflashContent as $id => $value) {
    $strChart .= $id . "," . $sumArrayflashContent[$id] . "\n";
}
$ret["Crawl Errors_Flash ContentChart"] = $strChart;

$strChart = "Day,Other\n";
foreach ($sumArrayother as $id => $value) {
    $strChart .= $id . "," . $sumArrayother[$id] . "\n";
}
$ret["Crawl Errors_OtherChart"] = $strChart;

$ret["Crawl Errors_Not Found"] = $notFound;
$ret["Crawl Errors_Auth Permissions"] = $authPermissions;
$ret["Crawl Errors_Flash Content"] = $flashContent;
$ret["Crawl Errors_Many To One"] = $manyToOneRedirect;
$ret["Crawl Errors_Not Followed"] = $notFollowed;
$ret["Crawl Errors_Server Error"] = $serverError;
$ret["Crawl Errors_Other"] = $other;
$ret["Crawl Errors_Roboted"] = $roboted;
$ret["Crawl Errors_Soft 404"] = $soft404;

$search = new Google_Service_Webmasters_SearchAnalyticsQueryRequest;
$search->setStartDate($start_date);
$search->setEndDate($end_date);
$search->setDimensions(array('date'));
$search->setRowLimit(1500);

$results = $webmaster->searchanalytics->query($viewID, $search, array())->getRows();

/*
Top Queries          Keyword, Clicks, Impressions, Avg Position, CTR
*/

$strClick = "Date,Clicks\n";
$strImpressions = "Date,Impressions\n";
$strCTR = "Date,CTR\n";
$strPosition = "Date,Avg Position\n";

if (!empty($results)) {
    foreach ($results as $key => $result) {
        $strClick .= $result->keys[0] . "," . $result->clicks . "\n";
        $strImpressions .= $result->keys[0] . "," . $result->impressions . "\n";
        $strCTR .= $result->keys[0] . "," . round($result->ctr * 100, 2) . "\n";
        $strPosition .= $result->keys[0] . "," . round($result->position, 1) . "\n";
    }
}

$ret["Top Queries_ClicksChart"] = $strClick;
$ret["Top Queries_ImpressionsChart"] = $strImpressions;
$ret["Top Queries_CTRChart"] = $strCTR;
$ret["Top Queries_Avg PositionChart"] = $strPosition;

$ret["Top Pages_ClicksChart"] = $strClick;
$ret["Top Pages_ImpressionsChart"] = $strImpressions;
$ret["Top Pages_CTRChart"] = $strCTR;
$ret["Top Pages_Avg PositionChart"] = $strPosition;

echo json_encode($ret);

/*
Crawl Errors              Last Crawled, Page Url, First Detected, Category, Response Code
  Not Found, Auth Permissions, Flash Content, Many To One, Not Followed, Server Error, Other, Roboted, Soft 404

*/
?>