<?php

require_once(__DIR__ . '/../vendor/autoload.php');

// Add to header of your file
use FacebookAds\Api;
use FacebookAds\Object\User;
use FacebookAds\Object\Campaign;

use Facebook\Facebook;
use Facebook\FacebookRequest;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Fields\AdAccountFields;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\Fields\AdSetFields;

$facebookAppID = "456797558007270";
$facebookAppSecret = "c69f7f97677d5852875a23045305cc8e";
$facebook_access_token = "EAAGfdHgt5eYBAMcXZB5zdsmU1h3rG3RAPzM7VjZBu89J2r5RuqkXF1GHqpibZCfshBbDPIQDyUu4zZAT9DoADR3tfegAZAWltVz9g4Qnm7eqrBsD4bQPU1eVsNFbunZBBSo3wzPiOfswYGdbR8m3Wgh6bWRpcPYfyelkyYglYuQg5spJZCaoufL";

require_once(__DIR__ . "/ads_function.php");

$viewID = "119662315288281";

if (isset($_GET['viewID'])) $viewID = $_GET['viewID'];
if ($viewID == "") exit();

if (isset($_GET['refreshToken'])) $facebook_access_token = $_GET['refreshToken'];
if ($facebook_access_token == "") exit();

$start_date = "2017-06-01";
$end_date = "2017-07-01";

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];

// Initialize a new Session and instantiate an Api object
Api::init(
    $facebookAppID, // App ID
    $facebookAppSecret,
    $facebook_access_token // Your user access token
);
initApiVersion();

$account = new AdAccount("act_" . $viewID);

$ret = new stdClass();

$data = GetMetricDatas($account, $start_date, $end_date, "account", false);
$ret = GetMainMetricData($data);

$data = GetMetricDatas($account, $start_date, $end_date, "campaign", false);
$ret->result = GetResultMetricData($data);

$data = GetMetricDatas($account, $start_date, $end_date, "account", true);
$ret = GetMetricChartData($ret, $data);
$ret->campaigns = GetCampaigns($account);
$ret->adGroups = GetAdGroups($account);

echo json_encode($ret);

?>
