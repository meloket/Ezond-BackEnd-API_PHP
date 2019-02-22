<?php

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

    $arr = array("pageviews", "uniquePageviews", "pageviewsPerSession", "avgTimeOnPage", "entrances", "bounceRate", "exitRate", "pageValue", "exits");
    $arr_alias = array("Pageviews", "Unique Pageviews", "Pages/Session", "Avg. Time on Page", "Entrances", "Bounce Rate", "Exit Rate", "Page Value", "Exits");

    $arr_metircs = array();
    for ($i = 0; $i < count($arr); $i++) {
        // Create the Metrics object.
        $sessions = new Google_Service_AnalyticsReporting_Metric();
        $sessions->setExpression("ga:" . $arr[$i]);
        $sessions->setAlias($arr_alias[$i]);
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

    if ($mediumFilter != "") $request->setFiltersExpression("ga:channelGrouping==" . $mediumFilter);

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
                    $ret->$fld_name = correct_number(number_format($fld_value, 2));
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
                        $ret->$fld_name = correct_number(number_format($fld_value, 2));
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

?>