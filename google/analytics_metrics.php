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
function getReport($analytics, $VIEW_ID)
{
    global $start_date, $end_date;

    // Create the DateRange object.
    $dateRange = new Google_Service_AnalyticsReporting_DateRange();
    $dateRange->setStartDate($start_date);
    $dateRange->setEndDate($end_date);

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

    $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
    $body->setReportRequests(array($request));
    return $analytics->reports->batchGet($body);
}


function correct_number($_num)
{
    if (substr($_num, -3) == ".00")
        $_num = substr($_num, 0, strpos($_num, ".00"));
    return $_num;
}

/**
 * Parses and prints the Analytics Reporting API V4 response.
 *
 * @param An Analytics Reporting API V4 response.
 */
function printResults($reports)
{
    $ret = new stdClass();
    for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
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
                    $ret->$fld_name = round($fld_value, 2);
                else
                    $ret->$fld_name = round($fld_value, 3) . "%";
            }
        }
        /*
        for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
          $row = $rows[ $rowIndex ];
          $dimensions = $row->getDimensions();
          $metrics = $row->getMetrics();
          for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
            print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
          }

          for ($j = 0; $j < count($metrics); $j++) {
            $values = $metrics[$j]->getValues();
            for ($k = 0; $k < count($values); $k++) {
              $entry = $metricHeaders[$k];
              print($entry->getName() . ": " . $values[$k] . "\n");
            }
          }
        }*/
    }
    echo json_encode($ret);
}

$analytics = new Google_Service_AnalyticsReporting($client);
$response = getReport($analytics, $viewID);
printResults($response);

?>