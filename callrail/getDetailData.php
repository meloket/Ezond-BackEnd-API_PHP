<?php

// Replace with your API Key
//$api_key = '546b4819c5219c81bf71b747b1d6dcb8';
//$account_id = '685427758';
$api_key = '';
$account_id = '';
$start_date = date("Y-m-d");
$end_date = date("Y-m-d");

if (isset($_GET['refreshToken'])) $api_key = $_GET['refreshToken'];
if ($api_key == "") exit();

if (isset($_GET['viewID'])) $account_id = $_GET['viewID'];
if ($account_id == "") exit();

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];

if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];


$start_date = date("Y-m-d", strtotime($start_date) - 86400);
$end_date = date("Y-m-d", strtotime($end_date) - 86400);

$api_url = "https://api.callrail.com/v1/calls.json?per_page=250&company_id=" . $account_id . "&start_date=" . $start_date . "&end_date=" . $end_date;
$ret = new stdClass();


$ch = curl_init($api_url);

curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Token token=\"{$api_key}\""));

$json_data = curl_exec($ch);
$parsed_data = json_decode($json_data);
curl_close($ch);


/*
calls
Calls         1
Answered      "answered" = true
Missed        "answered" = false
First Call      "first_call" = true
Name        "formatted_customer_name"
Source        "source_name"
Source Type     "source_type"
Phone Number    "formatted_customer_phone_number"
Duration      "duration"
Location      "caller_country"  
Date          "start_time"
*/

$answered = 0;
$missed = 0;
$first = 0;
$arrResult = array();

$arr_source = array();

$arrCalls = $parsed_data->calls;
for ($i = 0; $i < count($arrCalls); $i++) {
    if ($arrCalls[$i]->answered) $answered++;
    else $missed++;
    if ($arrCalls[$i]->first_call) $first++;
    $callObj = array();
    $callObj["Answered"] = ($arrCalls[$i]->answered ? "Yes" : "No");
    $callObj["Missed"] = ($arrCalls[$i]->answered ? "No" : "Yes");
    $callObj["First Call"] = ($arrCalls[$i]->first_call ? "Yes" : "No");
    $callObj["Name"] = $arrCalls[$i]->formatted_customer_name;
    $callObj["Source"] = $arrCalls[$i]->source_name;
    if (!in_array($callObj["Source"], $arr_source))
        array_push($arr_source, $callObj["Source"]);
    $callObj["Source Type"] = $arrCalls[$i]->source_type;
    $callObj["Phone Number"] = $arrCalls[$i]->formatted_customer_phone_number;
    $callObj["Duration"] = $arrCalls[$i]->duration . "s";
    $callObj["Location"] = $arrCalls[$i]->caller_country;
    $callObj["Date"] = date("Y-m-d H:i:s", strtotime($arrCalls[$i]->start_time) + 43200);
    $callObj["Date_2"] = substr($callObj["Date"], 0, 10);
    array_push($arrResult, $callObj);
}
$ret = array();
$ret["Calls"] = count($arrCalls);
$ret["Answered"] = $answered;
$ret["Missed"] = $missed;
$ret["First Call"] = $first;
$ret["result"] = $arrResult;

$sumArray = array();
for ($i = 0; $i <= (strtotime($end_date) - strtotime($start_date)) / 86400; $i++) {
    $sumArray[date("Y-m-d", strtotime($start_date) + 86400 * $i)] = 0;
}
for ($i = 0; $i < count($arrResult); $i++) {
    $call_obj = $arrResult[$i];
    if (isset($sumArray[$call_obj["Date_2"]])) $sumArray[$call_obj["Date_2"]]++;
    else $sumArray[$call_obj["Date_2"]] = 1;
}

$str_source_array = "";
if (count($arr_source) > 0) {
    $str_source_array = "," . implode(",", $arr_source);
}

function __get_call_count($__arrResult, $__source_name, $__call_date, $__call_type)
{
    $__call_count = 0;

    for ($i = 0; $i < count($__arrResult); $i++) {
        $call_obj = $__arrResult[$i];
        if (($call_obj["Date_2"] == $__call_date) && ($call_obj["Source"] == $__source_name)) {
            if ($__call_type == 1) {
                $__call_count++;
            } else if ($__call_type == 2) {
                if ($call_obj["Answered"] == "Yes") $__call_count++;
            } else if ($__call_type == 3) {
                if ($call_obj["Answered"] == "No") $__call_count++;
            } else if ($__call_type == 4) {
                if ($call_obj["First Call"] == "Yes") $__call_count++;
            }
        }
    }

    return $__call_count;
}

$strChart = "Day,Calls" . $str_source_array . "\n";
foreach ($sumArray as $id => $value) {
    $strChart .= $id . "," . $sumArray[$id];
    for ($j = 0; $j < count($arr_source); $j++) {
        $strChart .= "," . __get_call_count($arrResult, $arr_source[$j], $id, 1);
    }
    $strChart .= "\n";
}
$ret["Calls_Chart"] = $strChart;

$sumArray = array();
for ($i = 0; $i <= (strtotime($end_date) - strtotime($start_date)) / 86400; $i++) {
    $sumArray[date("Y-m-d", strtotime($start_date) + 86400 * $i)] = 0;
}
for ($i = 0; $i < count($arrResult); $i++) {
    $call_obj = $arrResult[$i];
    if ($call_obj["Answered"] == "Yes") {
        if (isset($sumArray[$call_obj["Date_2"]])) $sumArray[$call_obj["Date_2"]]++;
        else $sumArray[$call_obj["Date_2"]] = 1;
    }
}
$strChart = "Day,Answered Calls" . $str_source_array . "\n";
foreach ($sumArray as $id => $value) {
    $strChart .= $id . "," . $sumArray[$id];
    for ($j = 0; $j < count($arr_source); $j++) {
        $strChart .= "," . __get_call_count($arrResult, $arr_source[$j], $id, 2);
    }
    $strChart .= "\n";
}
$ret["Answered_Chart"] = $strChart;

$sumArray = array();
for ($i = 0; $i <= (strtotime($end_date) - strtotime($start_date)) / 86400; $i++) {
    $sumArray[date("Y-m-d", strtotime($start_date) + 86400 * $i)] = 0;
}
for ($i = 0; $i < count($arrResult); $i++) {
    $call_obj = $arrResult[$i];
    if ($call_obj["Answered"] == "No") {
        if (isset($sumArray[$call_obj["Date_2"]])) $sumArray[$call_obj["Date_2"]]++;
        else $sumArray[$call_obj["Date_2"]] = 1;
    }
}
$strChart = "Day,Missed Calls" . $str_source_array . "\n";
foreach ($sumArray as $id => $value) {
    $strChart .= $id . "," . $sumArray[$id];
    for ($j = 0; $j < count($arr_source); $j++) {
        $strChart .= "," . __get_call_count($arrResult, $arr_source[$j], $id, 3);
    }
    $strChart .= "\n";
}
$ret["Missed_Chart"] = $strChart;

$sumArray = array();
for ($i = 0; $i <= (strtotime($end_date) - strtotime($start_date)) / 86400; $i++) {
    $sumArray[date("Y-m-d", strtotime($start_date) + 86400 * $i)] = 0;
}
for ($i = 0; $i < count($arrResult); $i++) {
    $call_obj = $arrResult[$i];
    if ($call_obj["First Call"] == "Yes") {
        if (isset($sumArray[$call_obj["Date_2"]])) $sumArray[$call_obj["Date_2"]]++;
        else $sumArray[$call_obj["Date_2"]] = 1;
    }
}
$strChart = "Day,First Calls" . $str_source_array . "\n";
foreach ($sumArray as $id => $value) {
    $strChart .= $id . "," . $sumArray[$id];
    for ($j = 0; $j < count($arr_source); $j++) {
        $strChart .= "," . __get_call_count($arrResult, $arr_source[$j], $id, 4);
    }
    $strChart .= "\n";
}
$ret["First Call_Chart"] = $strChart;

$sumArray = array();
$sumArray1 = array();
$sumArray2 = array();
for ($i = 0; $i < count($arrResult); $i++) {
    $call_obj = $arrResult[$i];
    if (isset($sumArray[$call_obj["Date_2"]])) $sumArray[$call_obj["Date_2"]]++;
    else $sumArray[$call_obj["Date_2"]] = 1;
    if ($call_obj["Answered"] == "Yes") {
        if (isset($sumArray1[$call_obj["Date_2"]])) $sumArray1[$call_obj["Date_2"]]++;
        else $sumArray1[$call_obj["Date_2"]] = 1;
    } else {
        if (isset($sumArray2[$call_obj["Date_2"]])) $sumArray2[$call_obj["Date_2"]]++;
        else $sumArray2[$call_obj["Date_2"]] = 1;
    }
}
$strChart = "Day,Answered,Missed\n";
foreach ($sumArray as $id => $value) {
    $strChart .= $id . "," . ((isset($sumArray1[$id])) ? $sumArray1[$id] : 0) . "," . ((isset($sumArray2[$id])) ? $sumArray2[$id] : 0) . "\n";
}
$ret["CompareChart"] = $strChart;

echo json_encode($ret);

?>