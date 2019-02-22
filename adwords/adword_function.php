<?php
require_once(__DIR__ . '/../vendor/autoload.php');

use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;

use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\ReportSettings;
use Google\AdsApi\AdWords\ReportSettingsBuilder;
use Google\AdsApi\AdWords\Reporting\v201809\DownloadFormat;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinition;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinitionDateRangeType;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDownloader;
use Google\AdsApi\AdWords\v201809\cm\ApiException;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\ReportDefinitionReportType;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\OrderBy;
use Google\AdsApi\AdWords\v201809\cm\SortOrder;
use Google\AdsApi\AdWords\v201809\mcm\ManagedCustomerService;
use Google\AdsApi\AdWords\v201809\cm\ReportDefinitionService;
use Google\AdsApi\AdWords\v201809\cm\DateRange;
use Google\AdsApi\AdWords\v201809\cm\CampaignService;
use Google\AdsApi\AdWords\v201809\cm\AdGroupService;

function GetCampaigns(AdWordsServices $adWordsServices, AdWordsSession $session)
{
    $campaignService = $adWordsServices->get($session, CampaignService::class);

    $selector = new Selector();
    $selector->setFields(['Id', 'Name', 'Labels']);
    $selector->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)]);
    $selector->setPaging(new Paging(0, 500));

    $page = $campaignService->get($selector);
    $arr_campaign = array("All Campaigns");
    if ($page->getEntries() !== null) {
        foreach ($page->getEntries() as $campaign) {
            array_push($arr_campaign, $campaign->getName());
        }
    }
    return $arr_campaign;
}

function GetAdGroups(AdWordsServices $adWordsServices, AdWordsSession $session)
{
    $adgroupService = $adWordsServices->get($session, AdGroupService::class);

    $selector = new Selector();
    $selector->setFields(['Id', 'Name', 'Labels']);
    $selector->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)]);
    $selector->setPaging(new Paging(0, 500));

    $page = $adgroupService->get($selector);
    $arr_adgroup = array("All Adgroups");
    if ($page->getEntries() !== null) {
        foreach ($page->getEntries() as $adgroup) {
            array_push($arr_adgroup, $adgroup->getName());
        }
    }
    return $arr_adgroup;
}

function runExample(AdWordsServices $adWordsServices, AdWordsSession $session, $reportType)
{
    $reportDefinitionService =
        $adWordsServices->get($session, ReportDefinitionService::class);


    // Get report fields of the report type.
    $reportDefinitionFields =
        $reportDefinitionService->getReportFields($reportType);

    printf("The report type '%s' contains the following fields:\n",
        $reportType);
    foreach ($reportDefinitionFields as $reportDefinitionField) {
        printf('  %s (%s)', $reportDefinitionField->getFieldName(),
            $reportDefinitionField->getFieldType());
        if ($reportDefinitionField->getEnumValues() !== null) {
            printf(' := [%s]',
                implode(', ', $reportDefinitionField->getEnumValues()));
        }
        print "\n";
    }
}

function GetReport($session, $start_date, $end_date, $reportType, $adwordFields, $filter = null)
{

    // Create selector.
    $selector = new Selector();
    $selector->setFields($adwordFields);
    $selector->setDateRange(new DateRange(date("Ymd", strtotime($start_date)), date("Ymd", strtotime($end_date))));
    if (isset($filter)) {
        $arr_predicate = array();
        if (isset($filter->campaignName)) array_push($arr_predicate, new Predicate('CampaignName', PredicateOperator::EQUALS, [$filter->campaignName]));
        if (isset($filter->groupName)) array_push($arr_predicate, new Predicate('AdGroupName', PredicateOperator::EQUALS, [$filter->groupName]));
        if (isset($filter->networkName)) array_push($arr_predicate, new Predicate('AdNetworkType1', PredicateOperator::EQUALS, [$filter->networkName]));
        if (count($arr_predicate) > 0) $selector->setPredicates($arr_predicate);
        //print_r($arr_predicate);
        //exit();
    }
    // Create report definition.
    $reportDefinition = new ReportDefinition();
    $reportDefinition->setSelector($selector);
    $reportDefinition->setReportName('Custom Report');
    $reportDefinition->setDateRangeType(ReportDefinitionDateRangeType::CUSTOM_DATE);
    $reportDefinition->setReportType($reportType);
    $reportDefinition->setDownloadFormat(DownloadFormat::CSV);
    /*
    $reportSettingsOverride = (new ReportSettingsBuilder())
    ->includeZeroImpressions(true)
    ->build();
    */

    $reportDownloader = new ReportDownloader($session);
    try {
        $reportDownloadResult = $reportDownloader->downloadReport($reportDefinition);
        $result = $reportDownloadResult->getAsString();
        $lines = explode(PHP_EOL, $result);
        $rows = array();
        foreach ($lines as $line) {
            $rows[] = str_getcsv($line);
        }
        return $rows;
    } catch (ApiException $e) {
        $errors = $e->getErrors();
        error_log("<++++++>" . json_encode($e) . "<++++++>");
    } finally {

    }

    return array();
}

function correct_number($_num, $signCheck = true)
{
    $_num = str_replace(",", "", $_num);
    if (strpos($_num, "%") === false) {
        if (is_float($_num)) $_num = number_format($_num, 2);
        if (strlen($_num) > 3) if (substr($_num, -3) == ".00") $_num = substr($_num, 0, strpos($_num, ".00"));
    } else
        $_num = round($_num, 2) . ($signCheck ? "%" : "");
    return $_num;
}

function GetMetricChartValues($ret, $rows, $arrFields)
{
    for ($i = 0; $i < count($arrFields); $i++) {
        $fldName = $arrFields[$i] . "_ChartD";
        $$fldName = array();
    }

    for ($j = 2; $j < count($rows) - 2; $j++) {
        for ($i = 0; $i < count($arrFields); $i++) {
            $fldName = $arrFields[$i];
            $fldName2 = $arrFields[$i] . "_ChartD";
            $fldValue = $rows[$j][$i + 1];

            if (($fldName == "Cost") || ($fldName == "Average CPC") || ($fldName == "Cost Per Conversion"))
                $fldValue = number_format($fldValue / 1000000, 2);

            $fldValue = correct_number($fldValue, false);
            $date_val = $rows[$j][0];
            if (isset($$fldName2[$date_val])) $$fldName2[$date_val] = $$fldName2[$date_val] * 1 + $fldValue * 1;
            else $$fldName2[$date_val] = $fldValue;
        }
    }

    for ($i = 0; $i < count($arrFields); $i++) {
        $fldName = $arrFields[$i] . "_Chart";
        $fldName2 = $arrFields[$i] . "_ChartD";
        $$fldName = "Date," . $arrFields[$i] . "\n";
        ksort($$fldName2);
        foreach ($$fldName2 as $key => $value) {
            $$fldName .= $key . "," . $value . "\n";
        }
        $ret->$fldName = $$fldName;
    }
    return $ret;
}

function GetMetricValues($rows, $arrFields, $arrHeaderFields)
{
    $ret = new stdClass();

    for ($i = 0; $i < count($arrFields); $i++) {
        $fldName = $arrFields[$i];
        $ret->$fldName = 0;
        $fldName2 = $arrFields[$i] . "_ChartBar";
        $$fldName2 = "Select," . $fldName . "\n";
    }

    $ret->result = array();

    if (count($rows) > 2) {
        if ($rows[count($rows) - 2][0] == "Total") {
            for ($i = 0; $i < count($arrFields); $i++) {
                $fldName = $arrFields[$i];
                $ret->$fldName = $rows[count($rows) - 2][$i + count($arrHeaderFields)];

                if (($fldName == "Cost") || ($fldName == "Average CPC") || ($fldName == "Cost Per Conversion"))
                    $ret->$fldName = number_format($ret->$fldName / 1000000, 2);

                $ret->$fldName = correct_number($ret->$fldName);
            }
        }
    }

    $result = array();
    for ($j = 2; $j < count($rows) - 2; $j++) {
        $retObj = new stdClass();
        for ($i = 0; $i < count($arrHeaderFields); $i++) {
            $fldName = $arrHeaderFields[$i];
            $retObj->$fldName = $rows[$j][$i];
        }
        for ($i = 0; $i < count($arrFields); $i++) {
            $fldName = $arrFields[$i];
            $fldName2 = $arrFields[$i] . "_ChartBar";

            $retObj->$fldName = $rows[$j][$i + count($arrHeaderFields)];

            if (($fldName == "Cost") || ($fldName == "Average CPC") || ($fldName == "Cost Per Conversion"))
                $retObj->$fldName = number_format($retObj->$fldName / 1000000, 2);

            $retObj->$fldName = correct_number($retObj->$fldName);
            $t_val = correct_number($retObj->$fldName, false);
            $$fldName2 .= $rows[$j][0] . " " . substr($rows[$j][2], 0, 1) . "," . $t_val . "\n";
        }
        array_push($result, $retObj);
    }

    $ret->result = $result;

    for ($i = 0; $i < count($arrFields); $i++) {
        $fldName2 = $arrFields[$i] . "_ChartBar";
        $ret->$fldName2 = $$fldName2;
    }

    return $ret;
}

?>