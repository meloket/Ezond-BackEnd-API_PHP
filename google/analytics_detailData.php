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
    $viewID = "152525328";
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

/**
 * Queries the Analytics Reporting API V4.
 *
 * @param service An authorized Analytics Reporting API V4 service object.
 * @return The Analytics Reporting API V4 response.
 */
function generateReportRequest($analytics, $VIEW_ID, $startDate, $endDate, $mediumFilter = "", $dailyCheck = false, $addDimension = "")
{
    // Create the DateRange object.
    $dateRange = new Google_Service_AnalyticsReporting_DateRange();
    $dateRange->setStartDate($startDate);
    $dateRange->setEndDate($endDate);

    /*
    Channel, Sessions, Users, PageViews, Pages/Session, Avg. Session Duration, % New Sessions, Bounce Rate, Goal Completions, Goal Value, Conversion Rate
    */

    $arr = array("sessions", "users", "pageviews", "pageviewsPerSession", "avgSessionDuration", "percentNewSessions", "bounceRate", "goalCompletionsAll", "goalValueAll", "goalConversionRateAll");
    $arr_metircs = array();
    for ($i = 0; $i < count($arr); $i++) {
        // Create the Metrics object.
        $sessions = new Google_Service_AnalyticsReporting_Metric();
        $sessions->setExpression("ga:" . $arr[$i]);
        $sessions->setAlias($arr[$i]);
        array_push($arr_metircs, $sessions);
    }

    // Create the ReportRequest object.
    $request = new Google_Service_AnalyticsReporting_ReportRequest();
    $request->setViewId($VIEW_ID);
    $request->setDateRanges($dateRange);
    $request->setMetrics($arr_metircs);

    if ($addDimension) {
        $dimension = new Google_Service_AnalyticsReporting_Dimension();
        $dimension->setName($addDimension);
        $request->setDimensions(array($dimension));
    } else if ($dailyCheck) {
        $dimension = new Google_Service_AnalyticsReporting_Dimension();
        $dimension->setName("ga:date");
        $request->setDimensions(array($dimension));
    }

    if ($mediumFilter != "") $request->setFiltersExpression("ga:medium==" . $mediumFilter);

    return $request;
}

function getReport($analytics, $VIEW_ID, $arrMedium, $dailyCheck = false, $addDimension = "")
{
    global $start_date, $end_date;

    $reqArray = array();

    foreach ($arrMedium as $key => $mediumFilter) {
        array_push($reqArray, generateReportRequest($analytics, $VIEW_ID, $start_date, $end_date, $mediumFilter, $dailyCheck, $addDimension));
    }

    $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
    $body->setReportRequests($reqArray);
    return $analytics->reports->batchGet($body);
}


/**
 * Parses and prints the Analytics Reporting API V4 response.
 *
 * @param An Analytics Reporting API V4 response.
 */
function printTotals($reports)
{
    $result = array();
    for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {

        $ret = new stdClass();
        $report = $reports[$reportIndex];
        $header = $report->getColumnHeader();
        $dimensionHeaders = $header->getDimensions();
        $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
        $rows = $report->getData()->getRows();
        $totlas = $report->getData()->getTotals();

        $totalValues = $totlas[0]->getValues();
        for ($j = 0; $j < count($metricHeaders); $j++) {
            $fld_name = $metricHeaders[$j]->getName();
            $fld_value = $totalValues[$j];
            if ($fld_value) {
                if (strpos($fld_value, "%") === false)
                    $ret->$fld_name = round($fld_value, 3);
                else
                    $ret->$fld_name = round($fld_value, 3) . "%";
            }
        }
        array_push($result, $ret);
    }
    return $result;
}

function printResults($reports)
{
    $result = array();
    for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
        $report = $reports[$reportIndex];
        $header = $report->getColumnHeader();
        $dimensionHeaders = $header->getDimensions();
        $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
        $rows = $report->getData()->getRows();
        $totlas = $report->getData()->getTotals();

        for ($k = 0; $k < count($rows); $k++) {
            $row = $rows[$k];
            $metrics = $row->getMetrics();
            $dimensions = $row->getDimensions();
            for ($j = 0; $j < count($metrics); $j++) {
                $ret = new stdClass();
                $values = $metrics[$j]->getValues();
                for ($kk = 0; $kk < count($values); $kk++) {
                    $entry = $metricHeaders[$kk];
                    $fld_name = $entry->getName();
                    $fld_value = $values[$kk];

                    if (strpos($fld_value, "%") === false)
                        $ret->$fld_name = number_format(round($fld_value, 2));
                    else
                        $ret->$fld_name = round($fld_value, 3) . "%";
                }
                $ret->dimensions = $dimensions[$j];
                array_push($result, $ret);
            }

        }
    }
    return $result;
}

$arrMedium1 = array('', 'organic', 'paidsearch', 'social');
$arrMedium2 = array('referral', 'display', 'email', 'cpv');

$analytics = new Google_Service_AnalyticsReporting($client);
/*
	$response = getReport($analytics, $viewID, $arrMedium1);
  print_r($response);

  $response = getReport($analytics, $viewID, $arrMedium2);
  print_r($response);

  $response = getReport($analytics, $viewID, $arrMedium1, true);
  print_r($response);

  $response = getReport($analytics, $viewID, $arrMedium2, true);
  print_r($response);
*/
$response = getReport($analytics, $viewID, array(""));
$result = printTotals($response);
print_r($result);

$response = getReport($analytics, $viewID, array(""), true);
$result = printResults($response);
print_r($result);

$response = getReport($analytics, $viewID, array(""), false, "ga:channelGrouping");
//    $response = getReport($analytics, $viewID, array("organic"), false, "ga:keyword");
//    $response = getReport($analytics, $viewID, array("paidsearch"), false, "ga:keyword");
//    $response = getReport($analytics, $viewID, array("social"), false, "ga:socialNetwork");
$result = printResults($response);
//    $response = getReport($analytics, $viewID, array("referral"), false, "ga:source");
//    $response = getReport($analytics, $viewID, array("display"), false, "ga:source");
//    $response = getReport($analytics, $viewID, array("email"), false, "ga:source");
//    $response = getReport($analytics, $viewID, array("cpv"), false, "ga:source");

print_r($result);

?>