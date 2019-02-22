<?php
$api_url = 'https://api.callrail.com/v1/companies.json';

require_once '../config.php';

// Replace with your API Key
//$api_key = '3f5ddfafc68dfa8e1ce1fc9b0c303a9d';
$api_key = '';

if (isset($_GET['api_key'])) $api_key = $_GET['api_key'];
if ($api_key == "") exit();

$ch = curl_init($api_url);

curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Token token=\"{$api_key}\""));

$json_data = curl_exec($ch);
//$json_data = '{"agencies":[{"id":"123123","name":"mark"},{"id":"1231223233","name":"mark oriend"}]}';
//{"page":1,"per_page":100,"total_pages":1,"total_records":1,"companies":[{"id":856407865,"name":"CreativeQ","time_zone":"Auckland","time_zone_utc_offset":43200,"created_at":"2017-06-21T23:21:25Z","disabled_at":null,"script_url":"//cdn.callrail.com/companies/856407865/aeff47eca09d90216c30/12/swap.js","lead_scoring_enabled":true,"verified_caller_ids":[]}]}Total entries: Company: CreativeQ
$parsed_data = json_decode($json_data);
curl_close($ch);

if (isset($_GET['print'])) {
    print_r($json_data);
    print_r($parsed_data);
}

$retObj = new stdClass();
$retObj->error = "0";
$retObj->items = array();

$items = array();

$userID = $_GET['userID'];
$networkID = 9;
$networkName = "Call Rail";
$arrUserInfo = explode("@", $userID);
$dashboardID = 0;
if (count($arrUserInfo) > 1) $dashboardID = $arrUserInfo[1];
$userID = $arrUserInfo[0];
if ($dashboardID == "") $dashboardID = 0;

db_clear_func($userID, $dashboardID, $networkID);
if (isset($parsed_data->companies)) {
    foreach ($parsed_data->companies as $company) {
        $itemObj = new stdClass();
        $itemObj->id = $company->id;
        $itemObj->accountId = $company->id;
        $itemObj->websiteUrl = $company->name;
        $itemObj->webPropertyId = $company->id;
        array_push($items, $itemObj);

        db_insert_func($userID, $dashboardID, $api_key, "", $company->name, $company->id, $networkID, $networkName, json_encode($itemObj));
    }
    //  $retObj->items = $items;
}
$retObj->error = "0";

echo json_encode($retObj);
?>