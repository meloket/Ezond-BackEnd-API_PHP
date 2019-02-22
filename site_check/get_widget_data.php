<?php

ini_set('max_execution_time', 100000);
error_reporting(E_STRICT | E_ALL);

require_once __DIR__ . '/../config.php';

$files = glob(HASH_PATH . '*'); // get all file names
foreach ($files as $file) { // iterate files
    if (is_file($file)) {
        $now = strtotime(date("Y-m-d")) - 86400;
        if (filemtime($file) <= $now) {
            //echo date("Y-m-d H:i:s", filemtime($file))."<br>";
            //echo date("Y-m-d H:i:s", $now)."<br>";
            unlink($file); // delete file
        }
    }
}

$sql = "DELETE FROM `users_notices` WHERE date(date_add(noticeDate, INTERVAL 8 DAY)) <= CURRENT_DATE";
$db->delete($sql);

function __delete_mini_warning($__dashboardId)
{
    global $db;

    $db->exe('DELETE FROM `users_notices` WHERE `dashboardIdx` = :dashboardIdx', ['dashboardIdx' => $__dashboardId]);
}

/* gets the contents of a file if it exists, otherwise grabs and caches */
function get_content($url, $hours = 6)
{
    $content = get_url2($url);
    return $content;
}

function get_url2($url)
{
    return @file_get_contents($url);
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

$dashboardID = $_GET['dashboardID'] ?? "";
$startDate = $_GET['startDate'] ?? date("Y-m-d", strtotime(date("Y-m-d")) - 86400 * 7);
$endDate = $_GET['endDate'] ?? date("Y-m-d");

if ($dashboardID == "" || $startDate == "" || $endDate == "") {
    exit();
}

function __check_website_review_data($__site_url)
{
    global $db;

    $checkDate = date("Y-m-d", strtotime(date("Y-m-d")) - 86400 * 7);

    $sql = sprintf("SELECT count(*) cn FROM `website_check_reviews` 
                            WHERE `webSiteURL` = :webSiteURL 
                              AND `reviewDate` >= :reviewDate");
    $data = [
        'webSiteURL' => $__site_url,
        'reviewDate' => $checkDate,
    ];
    $result = $db->select($sql, $data);

    if (count($result) == 0) return false;
    $row = $result[0];
    if (isset($row["cn"])) return ($row["cn"] > 0);
    return false;
}

function __get_analytics_start_date($__dashboardId, $__reference)
{
    global $db, $startDate;

    $sql = "SELECT max(metricDate) start_date FROM `users_data_google_analytics` 
                  WHERE `dashboardId` = :dashboardId 
                    AND `reference` = :reference";
    $data = [
        'dashboardId' => $__dashboardId,
        'reference' => $__reference,
    ];
    $result = $db->select($sql, $data);
    if (count($result) == 0) return $startDate;
    $row = $result[0];
    if (isset($row["start_date"])) return date("Y-m-d", strtotime($row["start_date"]) + 86400);
    return $startDate;
}

function __get_adwords_start_date($__dashboardId, $__reference)
{
    global $db, $startDate;

    $sql = "SELECT max(metricDate) start_date FROM `users_data_google_adwords` 
                  WHERE `dashboardId` = :dashboardId 
                    AND `reference` = :reference";
    $data = [
        'dashboardId' => $__dashboardId,
        'reference' => $__reference,
    ];
    $result = $db->select($sql, $data);
    if (count($result) == 0) return $startDate;
    $row = $result[0];
    if (isset($row["start_date"])) return date("Y-m-d", strtotime($row["start_date"]) + 86400);
    return $startDate;
}

function __get_console_start_date($__dashboardId, $__reference)
{
    global $db, $startDate;

    $sql = "SELECT max(metricDate) start_date FROM `users_data_google_search_console` 
                  WHERE `dashboardId` = :dashboardId 
                    AND `reference` = :reference";
    $data = [
        'dashboardId' => $__dashboardId,
        'reference' => $__reference,
    ];
    $result = $db->select($sql, $data);
    if (count($result) == 0) return $startDate;
    $row = $result[0];
    if (isset($row["start_date"])) return date("Y-m-d", strtotime($row["start_date"]) + 86400);
    return $startDate;
}

function __insert_analytics_data_to_db($__dashboardId, $__checkObj)
{
    global $db;

    $_arr_metrics = json_decode($__checkObj->metricsResult);
    $reference = $__checkObj->reference;
    $arr = array('sessions', 'users', 'pageviews', 'pageviewsPerSession', 'avgSessionDuration', 'percentNewSessions', 'bounceRate', 'goalCompletionsAll', 'goalValueAll', 'goalConversionRateAll');

    for ($i = 0; $i < count($_arr_metrics); $i++) {
        $metricDate = $_arr_metrics[$i]->dimensions;
        $metricDate = sprintf("%s-%s-%s", substr($metricDate, 0, 4), substr($metricDate, 4, 2), substr($metricDate, 6, 2));
        for ($j = 0; $j < count($arr); $j++) {
            $__fld_name = $arr[$j];
            $$__fld_name = $_arr_metrics[$i]->$__fld_name;
        }

        $table = 'users_data_google_analytics';
        $insertData = [
            'metricDate' => $metricDate,
            'dashboardId' => $__dashboardId,
            'reference' => $reference,
            'sessions' => $sessions,
            'users' => $users,
            'pageviews' => $pageviews,
            'pageviewsPerSession' => $pageviewsPerSession,
            'avgSessionDuration' => $avgSessionDuration,
            'percentNewSessions' => $percentNewSessions,
            'bounceRate' => $bounceRate,
            'goalCompletionsAll' => $goalCompletionsAll,
            'goalValueAll' => $goalValueAll,
            'goalConversionRateAll' => $goalConversionRateAll,
        ];

        $db->insert($table, $insertData);
    }
}

function __insert_adwords_data_to_db($__dashboardId, $__checkObj)
{
    global $db;

    $_arr_metrics = json_decode($__checkObj->metricsResult);
    $reference = $__checkObj->reference;
    $arr = array('Impressions', 'Clicks', 'Cost', 'AverageCpc', 'Conversions', 'Ctr', 'CostPerConversion');

    for ($i = 0; $i < count($_arr_metrics); $i++) {
        $metricDate = $_arr_metrics[$i]->Date;
        for ($j = 0; $j < count($arr); $j++) {
            $__fld_name = $arr[$j];
            $$__fld_name = $_arr_metrics[$i]->$__fld_name;
        }

        $table = 'users_data_google_adwords';
        $insertData = [
            'metricDate' => $metricDate,
            'dashboardId' => $__dashboardId,
            'reference' => $reference,
            'Impressions' => $Impressions,
            'Clicks' => $Clicks,
            'Cost' => $Cost,
            'AverageCpc' => $AverageCpc,
            'Conversions' => $Conversions,
            'Ctr' => $Ctr,
            'CostPerConversion' => $CostPerConversion,
        ];

        $db->insert($table, $insertData);
    }
}

function __insert_console_data_to_db($__dashboardId, $__checkObj)
{
    global $db;

    $_arr_metrics = json_decode($__checkObj->metricsResult);
    $reference = $__checkObj->reference;
    $arr = array('clicks', 'impressions', 'position', 'ctr');
    $arr_2 = array('lastCrawled', 'pageUrl', 'firstDetected', 'category', 'responseCode');

    for ($i = 0; $i < count($_arr_metrics); $i++) {
        if ($_arr_metrics[$i]->caseNumber == 0) {
            $metricDate = $_arr_metrics[$i]->dimensions;
            for ($j = 0; $j < count($arr); $j++) {
                $__fld_name = $arr[$j];
                $$__fld_name = $_arr_metrics[$i]->$__fld_name;
            }

            $table = 'users_data_google_search_console';
            $insertData = [
                'metricDate' => $metricDate,
                'dashboardId' => $__dashboardId,
                'reference' => $reference,
                'clicks' => $clicks,
                'impressions' => $impressions,
                'position' => $position,
                'ctr' => $ctr,
            ];

            $db->insert($table, $insertData);
        } else if ($_arr_metrics[$i]->caseNumber == 1) {
            for ($j = 0; $j < count($arr_2); $j++) {
                $__fld_name = $arr_2[$j];
                $$__fld_name = $_arr_metrics[$i]->$__fld_name;
            }

            $table = 'users_data_google_search_console_errors';
            $insertData = [
                'dashboardId' => $__dashboardId,
                'reference' => $reference,
                'lastCrawled' => $lastCrawled,
                'pageUrl' => $pageUrl,
                'firstDetected' => $firstDetected,
                'category' => $category,
                'responseCode' => $responseCode,
            ];

            $db->insert($table, $insertData);
        }
    }
}

function __get_site_domain_name($__site_url)
{
    $__site_url = strtolower($__site_url);
    $__site_url = str_replace("https://", "", $__site_url);
    $__site_url = str_replace("http://", "", $__site_url);
    $__site_url = str_replace("https:", "", $__site_url);
    $__site_url = str_replace("http:", "", $__site_url);
    $__site_url = str_replace("\/", "", $__site_url);
    $__site_url = str_replace("/", "", $__site_url);

    return $__site_url;
}

$result = $db->select("SELECT DISTINCT `refresh_token`, `authResponse`, `networkID`, `account`, `dashboardID` 
                            FROM `users_networks` 
                                WHERE `dashboardID` = :dashboardID 
                                  AND defaultCheck = 1",
    ['dashboardID' => $dashboardID]
);

$arr_return = array();

//    $arr_return = array("instinctfurniture.com.au");
//    $result = array();

for ($i = 0; $i < count($result); $i++) {
    $checkObj = new stdClass();
    $checkObj->dashboardID = $result[$i]["dashboardID"];
    $checkObj->reference = "";
    $checkObj->startDate = $startDate;
    $checkObj->endDate = $endDate;
    $checkObj->networkID = $result[$i]["networkID"];
    $checkObj->refresh_token = $result[$i]["refresh_token"];
    $checkObj->viewID = "";
    $tempObj = json_decode($result[$i]["authResponse"]);
    $checkObj->checkURL = "";
    if ($checkObj->networkID == 1) {
        $checkObj->reference = $result[$i]["account"];
        $checkObj->startDate = __get_analytics_start_date($checkObj->dashboardID, $checkObj->reference);
        $checkObj->viewID = $tempObj->id;
        $checkObj->checkURL = "google/analytics_metrics_detail.php";
        $url_host = __get_site_domain_name($checkObj->reference);
        if (!(in_array($url_host, $arr_return)))
            array_push($arr_return, $url_host);
    } else if ($checkObj->networkID == 2) {
        $checkObj->reference = $tempObj->id;
        $checkObj->startDate = __get_adwords_start_date($checkObj->dashboardID, $checkObj->reference);
        $checkObj->viewID = $tempObj->id;
        $checkObj->checkURL = "adwords/metrics_detail.php";
    } else if ($checkObj->networkID == 3) {
        $checkObj->reference = $result[$i]["account"];
        $checkObj->startDate = __get_console_start_date($checkObj->dashboardID, $checkObj->reference);
        $checkObj->viewID = $tempObj->websiteUrl;
        $checkObj->checkURL = "google/console_metrics_detail.php";
        $url_host = __get_site_domain_name($checkObj->reference);
        if (!(in_array($url_host, $arr_return)))
            array_push($arr_return, $url_host);
    }

    if (($checkObj->viewID) && ($checkObj->checkURL) && (strtotime($checkObj->startDate) <= strtotime($checkObj->endDate))) {
        $catchURL = sprintf(SITE_URL . "%s?refreshToken=%s&viewID=%s&start_date=%s&end_date=%s", $checkObj->checkURL, $checkObj->refresh_token, $checkObj->viewID, $checkObj->startDate, $checkObj->endDate);

        $checkObj->metricsResult = get_content($catchURL);

        if ($checkObj->networkID == 1) {
            __insert_analytics_data_to_db($checkObj->dashboardID, $checkObj);
        } else if ($checkObj->networkID == 2) {
            __insert_adwords_data_to_db($checkObj->dashboardID, $checkObj);
        } else if ($checkObj->networkID == 3) {
            __insert_console_data_to_db($checkObj->dashboardID, $checkObj);
        } else {

        }
    }
}

$siteURL = "";

$result = $db->select('SELECT `description`, `keywords` FROM `dashboards` WHERE id = :id', ['id' => $dashboardID]);
$dashboardId = 0;
if (count($result) > 0) {
    $row = $result[0];
    if (isset($row["description"])) {
        $description = $row["description"];
        if ($description != "") {
            $descr_obj = json_decode($description);
            if (isset($descr_obj->url)) {
                $this_url = __get_site_domain_name($descr_obj->url);
                $arr_return = array($this_url);
            }
        }
    }
}
if (count($arr_return) > 0) $siteURL = $arr_return[0];

function __get_site_status($site)
{
    $ch = curl_init($site);

    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => "",
        CURLOPT_AUTOREFERER => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_MAXREDIRS => 10,
    );
    curl_setopt_array($ch, $options);
    $data = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode;
}

for ($i = 0; $i < count($arr_return); $i++) {
    if (__check_website_review_data($arr_return[$i])) {
//            echo "Already review exists";
    } else {
        $__site_status = __get_site_status($arr_return[$i]);
        $__warning_description = "";
        if ($__site_status == 0)
            $__warning_description = "Could not resolve host, Name or service not known.";
        else if ($__site_status == 500)
            $__warning_description = "Internal Server Error.";

        if ($__warning_description == "")
            get_content(SITE_URL . "site_check/site_check.php?url=" . $arr_return[$i]);
        else {
            __insert_mini_warning($dashboardID, 0, $__warning_description, "Web Site Error");
        }
    }
}

function __check_website_status_according_rule($ruleObj, $__dashboardId, $__site_url, $reviewObj)
{
    $ruleTitle = $ruleObj["RuleTitle"];
    $ruleType = $ruleObj["RuleType"];
    $networkType = $ruleObj["NetworkType"];
    $networkMetric = $ruleObj["NetworkMetric"];
    $networkAPI_URL = $ruleObj["NetworkAPI_URL"];
    $calculateCase = $ruleObj["CalculateCase"];
    $benchmarkLogic = $ruleObj["BenchmarkLogic"];
    $benchmarkValue = $ruleObj["BenchmarkValue"];
    $preCheckPeriod_start = $ruleObj["PreCheckPeriod_start"];
    $preCheckPeriod_end = $ruleObj["PreCheckPeriod_end"];
    $currCheckPeriod_start = $ruleObj["CurrCheckPeriod_start"];
    $currCheckPeriod_end = $ruleObj["CurrCheckPeriod_end"];
//        $cronDateTimeFormula = $ruleObj["CronDateTimeFormula"];
    $ruleDescription = $ruleObj["RuleDescription"];

    if (__check_rule_result($__dashboardId, $__site_url, $reviewObj, $networkType, $networkMetric, $networkAPI_URL, $calculateCase, $benchmarkLogic, $benchmarkValue, $preCheckPeriod_start, $preCheckPeriod_end, $currCheckPeriod_start, $currCheckPeriod_end, $ruleType, $ruleDescription, $ruleTitle)) {
        __insert_mini_warning($__dashboardId, $ruleType, $ruleDescription, $ruleTitle);
    } else {
        //if($networkType == 0) __remove_mini_warning($__dashboardId, $ruleType, $ruleDescription, $ruleTitle);
    }
}

function __check_rule_result($__dashboardId, $__site_url, $reviewObj, $networkType, $networkMetric, $networkAPI_URL, $calculateCase, $benchmarkLogic, $benchmarkValue, $preCheckPeriod_start, $preCheckPeriod_end, $currCheckPeriod_start, $currCheckPeriod_end, $__ruleType, $__ruleDescription, $__ruleTitle)
{
    global $db;

    $is_date_check = false;
    $is_attr_check = false;
    if ($benchmarkValue == "NOW") {
        $benchmarkValue = date("Y-m-d");
        $is_date_check = true;
    } else if ($benchmarkValue == "blank") {
        $benchmarkValue = "";
    } else if (substr($benchmarkValue, 0, 1) == "@") {
        $__metrci_search_val = substr($benchmarkValue, 1);
        $benchmarkValue = 0;
        $is_attr_check = true;
    }
    if (($networkType == 0) && ($reviewObj)) {
        $__metric_value = $reviewObj[$networkMetric];
        if ($is_attr_check) {
            if ($__metric_value) {
                $__temp_metric_obj = json_decode($__metric_value);
                if ($__temp_metric_obj) {
                    if (isset($__temp_metric_obj->$__metrci_search_val))
                        $__metric_value = $__temp_metric_obj->$__metrci_search_val;
                }
            }
        }
        if ($calculateCase == 2) {
            if ($is_date_check && ($__metric_value == "0000-00-00")) return false;
            if ($benchmarkLogic == 0) {
                if (!($__metric_value == $benchmarkValue)) {
                    __remove_mini_warning_2($__dashboardId, $__ruleType, $__ruleDescription, $__ruleTitle);
                }
                return ($__metric_value == $benchmarkValue);
            } else if ($benchmarkLogic == 1) {
                if (!($__metric_value < $benchmarkValue)) {
                    __remove_mini_warning_2($__dashboardId, $__ruleType, $__ruleDescription, $__ruleTitle);
                }
                return ($__metric_value < $benchmarkValue);
            } else if ($benchmarkLogic == 2) {
                if (!($__metric_value > $benchmarkValue)) {
                    __remove_mini_warning_2($__dashboardId, $__ruleType, $__ruleDescription, $__ruleTitle);
                }
                return ($__metric_value > $benchmarkValue);
            }
        }
    } else if ($networkType == 1) {
        $__tbl_name = "users_data_" . $networkAPI_URL;
        $prev_result = array();
        $curr_result = array();
        $__metric_value = 0;

        if ($calculateCase < 2) {
            $sql = sprintf("select $networkMetric, metricDate from $__tbl_name where dashboardId='$__dashboardId' and metricDate>=date_sub(CURRENT_DATE, Interval $currCheckPeriod_start day) and metricDate<=date_sub(CURRENT_DATE, Interval $currCheckPeriod_end day) order by metricDate");
            $curr_result = $db->select($sql);

            if ($calculateCase > 0) {
                $sql = sprintf("select $networkMetric, metricDate from $__tbl_name where dashboardId='$__dashboardId' and metricDate>=date_sub(CURRENT_DATE, Interval $preCheckPeriod_start day) and metricDate<=date_sub(CURRENT_DATE, Interval $preCheckPeriod_end day) order by metricDate");
                $prev_result = $db->select($sql);
            }
        }
        if ($networkAPI_URL == "google_analytics") {
            if ($networkMetric == "pageviews") {
                $__curr_value = 0;
                $__old_value = 0;

                $__arr_chart = array();

                $__mid_point = (count($curr_result) - count($curr_result) % 2) / 2;
                for ($i = 0; $i < count($curr_result); $i++) {
                    array_push($__arr_chart, $curr_result[$i][$networkMetric]);
                    if ($i < $__mid_point)
                        $__old_value += $curr_result[$i][$networkMetric];
                    else if ($i >= count($curr_result) - $__mid_point)
                        $__curr_value += $curr_result[$i][$networkMetric];
                }

                if ($__curr_value > $__old_value) $__metric_value = 1;
                if (count($__arr_chart) > 0) {
                    $__str_chart = implode(",", $__arr_chart);
                    if (trim($__str_chart) != "") {
                        $sql = "UPDATE `dashboards` SET `chartValue` = :chartValue WHERE id= :id";
                        $data = [
                            'chartValue' => $__str_chart,
                            'id' => $__dashboardId,
                        ];
                        $db->exe($sql, $data);
                    } else {
                        $__metric_value = -1;
                    }
                } else {
                    $__metric_value = -1;
                }
            }
        } else if ($networkAPI_URL == "google_adwords") {
            if ($networkMetric == "Conversions") {
                $__curr_value = 0;
                for ($i = 0; $i < count($curr_result); $i++) {
                    $__curr_value += $curr_result[$i][$networkMetric];
                }
                $__metric_value = $__curr_value;
            } else if ($networkMetric == "CostPerConversion") {
                $__curr_value = 0;
                for ($i = 0; $i < count($curr_result); $i++) {
                    $__curr_value += $curr_result[$i][$networkMetric];
                }
                $__prev_value = 0;
                for ($i = 0; $i < count($prev_result); $i++) {
                    $__prev_value += $prev_result[$i][$networkMetric];
                }
                if ($__curr_value < $__prev_value) $__metric_value = 1;
            }
        }

        if ($benchmarkLogic == 0) {
            return ($__metric_value == $benchmarkValue);
        } else if ($benchmarkLogic == 1) {
            return ($__metric_value < $benchmarkValue);
        } else if ($benchmarkLogic == 2) {
            return ($__metric_value > $benchmarkValue);
        }
    } else if ($networkType == 2) {
        $sql = "SELECT `actionDetail`, `filePath` FROM `user_actions` 
                        WHERE `dashboardId` = :dashboardId 
                          AND `actionType` = 2 
                          AND `taskProgress` = :taskProgress 
                          AND filePath <> ''";
        $selectData = [
            'dashboardId' => $__dashboardId,
            'taskProgress' => 0,
        ];

        $__task_arr = $db->select($sql, $selectData);

        for ($i = 0; $i < count($__task_arr); $i++) {
            if (isset($__task_arr[$i]["actionDetail"]) && $__task_arr[$i]["actionDetail"] != null) {
                $__task_name = $__task_arr[$i]["actionDetail"];
                $__task_limit_date = $__task_arr[$i]["filePath"];

                if (strtotime($__task_limit_date) < strtotime(date("Y-m-d")))
                    __insert_mini_warning($__dashboardId, $__ruleType, str_replace("{0}", $__task_name, $__ruleDescription), $__ruleTitle);
            }
        }

        $selectData['taskProgress'] = 1;
        $__task_arr = $db->select($sql, $selectData);

        for ($i = 0; $i < count($__task_arr); $i++) {
            if (isset($__task_arr[$i]["actionDetail"]) && $__task_arr[$i]["actionDetail"] != null) {
                $__task_name = $__task_arr[$i]["actionDetail"];
                __remove_mini_warning_2($__dashboardId, $__ruleType, str_replace("{0}", $__task_name, $__ruleDescription), $__ruleTitle);
            }
        }
        return false;
    }
    return false;
}

function __remove_mini_warning($__dashboardId, $__ruleType, $__ruleDescription, $__ruleTitle)
{
    global $db;

    $prev_check = true;
    $sql = "DELETE FROM `users_notices` 
                      WHERE `dashboardIdx` = :dashboardIdx 
                        AND `noticeType` = :noticeType 
                        AND `noticeContent` = :noticeContent 
                        AND `noticeTitle` = :noticeTitle";
    $data = [
        'dashboardIdx' => $__dashboardId,
        'noticeType' => 1 - $__ruleType,
        'noticeContent' => $__ruleTitle,
        'noticeTitle' => $__ruleDescription
    ];
    $db->delete($sql, $data);
}

function __remove_mini_warning_2($__dashboardId, $__ruleType, $__ruleDescription, $__ruleTitle)
{
    global $db;

    $prev_check = true;

    $sql = "DELETE FROM `users_notices` 
                      WHERE `dashboardIdx` = :dashboardIdx 
                        AND `noticeType` = :noticeType 
                        AND `noticeContent` = :noticeContent 
                        AND `noticeTitle` = :noticeTitle";
    $data = [
        'dashboardIdx' => $__dashboardId,
        'noticeType' => $__ruleType,
        'noticeContent' => $__ruleTitle,
        'noticeTitle' => $__ruleDescription,
    ];
    $db->delete($sql, $data);
}

function __insert_mini_warning($__dashboardId, $__ruleType, $__ruleDescription, $__ruleTitle)
{
    global $db;

    $prev_check = true;

    $sql = "DELETE FROM `users_notices` 
                      WHERE `dashboardIdx` = :dashboardIdx 
                        AND `noticeType` = :noticeType 
                        AND `noticeContent` = :noticeContent 
                        AND date(date_add(noticeDate, INTERVAL 7 DAY)) > CURRENT_DATE";
    $data = [
        'dashboardIdx' => $__dashboardId,
        'noticeType' => 1 - $__ruleType,
        'noticeContent' => $__ruleTitle,
    ];
    $db->delete($sql, $data);

    $selectSql = "SELECT count(*) cn FROM `users_notices` 
                        WHERE `dashboardIdx` = :dashboardIdx 
                          AND `noticeType` = :noticeType 
                          AND `noticeContent` = :noticeContent 
                          AND `noticeTitle` = :noticeTitle 
                          AND date(date_add(noticeDate, INTERVAL 7 DAY)) > CURRENT_DATE";
    $selectData = [
        'dashboardIdx' => $__dashboardId,
        'noticeType' => $__ruleType,
        'noticeContent' => $__ruleTitle,
        'noticeTitle' => $__ruleDescription,
    ];

    $result = $db->select($selectSql, $selectData);
    if (count($result) > 0) {
        if (isset($result[0]["cn"]))
            if ($result[0]["cn"] > 0) $prev_check = false;
    }
    if ($prev_check) {
        $table = 'users_notices';
        $insertData = [
            'userIdx' => '0',
            'dashboardIdx' => $__dashboardId,
            'noticeType' => $__ruleType,
            'noticeTitle' => $__ruleDescription,
            'noticeContent' => $__ruleTitle,
            'noticeDate' => date("Y-m-d H:i:s"),
        ];

        $db->insert('users_notices', $insertData);
    }
}

function __get_review_obj($__site_url)
{
    global $db;

    $sql = sprintf("SELECT * FROM website_check_reviews 
                                    WHERE webSiteURL = :webSiteURL 
                                    ORDER BY reviewDate DESC LIMIT 1");

    $result = $db->select($sql, ['webSiteURL' => $__site_url]);
    $reviewObj = false;
    if (count($result) > 0) {
        if (isset($result[0]["webSiteURL"]))
            $reviewObj = $result[0];
    }
    return $reviewObj;
}

$reviewObj = __get_review_obj($siteURL);

$result = $db->select("SELECT * FROM website_check_rules WHERE RuleStatus = 1 ORDER BY NetworkType, RuleIdx");
for ($i = 0; $i < count($result); $i++) {

    $ruleObj = $result[$i];

    __check_website_status_according_rule($ruleObj, $dashboardID, $siteURL, $reviewObj);
}

?>