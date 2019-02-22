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

$api_url = "https://api.callrail.com/v1/calls.json?company_id={$account_id}&start_date=" . $start_date . "&end_date=" . $end_date;
$ret = new stdClass();

$ch = curl_init($api_url);

curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Token token=\"{$api_key}\""));

$json_data = curl_exec($ch);
$parsed_data = json_decode($json_data);

curl_close($ch);
$total_records = 0;
if ($parsed_data->total_records) $total_records = $parsed_data->total_records;
$ret->calls = $total_records;

$ch = curl_init($api_url . "&call_type=missed");

curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Token token=\"{$api_key}\""));

$json_data = curl_exec($ch);
$parsed_data = json_decode($json_data);

curl_close($ch);

$total_records = 0;
if ($parsed_data->total_records) $total_records = $parsed_data->total_records;
$ret->missed = $total_records;

$ret->answered = $ret->calls - $ret->missed;

echo json_encode($ret);

?>