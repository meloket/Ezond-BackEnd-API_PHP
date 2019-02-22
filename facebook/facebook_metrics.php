<?php

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/functions.php');

// Add to header of your file
use Facebook\Facebook;
use Facebook\FacebookRequest;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\FacebookResponse;
use FacebookAds\Api;
use FacebookAds\Object\User;

$refresh_token = "EAAGfdHgt5eYBAMcXZB5zdsmU1h3rG3RAPzM7VjZBu89J2r5RuqkXF1GHqpibZCfshBbDPIQDyUu4zZAT9DoADR3tfegAZAWltVz9g4Qnm7eqrBsD4bQPU1eVsNFbunZBBSo3wzPiOfswYGdbR8m3Wgh6bWRpcPYfyelkyYglYuQg5spJZCaoufL";
$viewID = "437267536628237";


if (isset($_GET['viewID'])) $viewID = $_GET['viewID'];
if ($viewID == "") exit();
if (isset($_GET['refreshToken'])) $refresh_token = $_GET['refreshToken'];
if ($refresh_token == "") exit();

$start_date = "2017-07-01";
$end_date = "2017-07-05";

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];

session_start();

$facebookAppID = "456797558007270";
$facebookAppSecret = "c69f7f97677d5852875a23045305cc8e";

$fb = initFbClient($facebookAppID, $facebookAppSecret);
$pageAccessToken = getPageAccessToken($fb, $viewID, $refresh_token);

$ret = new stdClass();
$ret->likes = 0;
$ret->reach = 0;
$ret->engageduser = 0;

$response = $fb->get('/' . $viewID . '/insights?metric=page_engaged_users,page_fans,page_impressions&since=' . (strtotime($start_date) - 86400) . '&until=' . (strtotime($end_date)), $pageAccessToken);
$user_obj = json_decode($response->getBody());
$data = $user_obj->data;
for ($i = 0; $i < count($data); $i++) {
    $proc_obj = $data[$i];
    if (($proc_obj->name == "page_fans") && ($proc_obj->period == "lifetime")) {
        $values = $proc_obj->values;
        $ret->likes = $values[count($values) - 1]->value;
    } else if (($proc_obj->name == "page_engaged_users") && ($proc_obj->period == "day")) {
        $values = $proc_obj->values;
        for ($j = 0; $j < count($values); $j++)
            $ret->engageduser += $values[$j]->value;
    } else if (($proc_obj->name == "page_impressions") && ($proc_obj->period == "day")) {
        $values = $proc_obj->values;
        for ($j = 0; $j < count($values); $j++)
            $ret->reach += $values[$j]->value;
    }
}

echo json_encode($ret);

?>
