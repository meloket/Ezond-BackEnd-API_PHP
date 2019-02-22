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

function GetMetricDatas($post_id)
{
    global $fb, $start_date, $end_date, $pageAccessToken;

    $response = $fb->get('/' . $post_id . '/insights?metric=page_engaged_users,page_impressions,page_content_activity&period=day&since=' . (strtotime($start_date) - 86400) . '&until=' . (strtotime($end_date)), $pageAccessToken);

    $user_obj = json_decode($response->getBody());

    $ret = new stdClass();
    $reachVal = 0;
    $engagedVal = 0;
    $storyVal = 0;

    $data = $user_obj->data;
    for ($i = 0; $i < count($data); $i++) {
        $proc_obj = $data[$i];
        if (($proc_obj->name == "page_impressions") && ($proc_obj->period == "day")) {
            $values = $proc_obj->values;
            for ($j = 0; $j < count($values); $j++) {
                $reachVal += $values[$j]->value;
            }
        } else if (($proc_obj->name == "page_engaged_users") && ($proc_obj->period == "day")) {
            $values = $proc_obj->values;
            for ($j = 0; $j < count($values); $j++) {
                $engagedVal += $values[$j]->value;
            }
        } else if (($proc_obj->name == "page_content_activity") && ($proc_obj->period == "day")) {
            $values = $proc_obj->values;
            for ($j = 0; $j < count($values); $j++) {
                $storyVal += $values[$j]->value;
            }
        }
    }
    $ret->reach = $reachVal;
    $ret->engage = $engagedVal;
    $ret->story = $storyVal;

    return $ret;
}


$ret = new stdClass();
$ret->likes = 0;
$ret->reach = 0;
$ret->engageduser = 0;

try {
    $publicFeed = $fb->get('/' . $viewID . '/posts', $refresh_token);
} catch (Exception $e) {
    echo "<pre>";
    print_r($e);
    echo "</pre>";
}
$feed_obj = json_decode($publicFeed->getBody());
$arrPost = $feed_obj->data;
$arrResult = array();
for ($i = 0; $i < count($arrPost); $i++) {
    $title = "";
    if (isset($arrPost[$i]->message)) $title = $arrPost[$i]->message;
    if (isset($arrPost[$i]->story)) $title = $arrPost[$i]->story;
    if ($title != "") {
        if (strlen($title) > 60) $title = substr($title, 0, 60) . "...";
        $post_obj = new stdClass();
        $post_obj->id = $arrPost[$i]->id;
        $post_obj->Date = substr($arrPost[$i]->created_time, 0, 10);
        $post_obj->Post = $title;
        $ret_obj = GetMetricDatas($arrPost[$i]->id);
        $post_obj->Reach = $ret_obj->reach;
        $fldName = "Talking About This";
        $post_obj->$fldName = $ret_obj->story;
        $fldName = "Engaged Users";
        $post_obj->$fldName = $ret_obj->engage;
        array_push($arrResult, $post_obj);
    }
}

$response = $fb->get('/' . $viewID . '/insights?metric=page_engaged_users,page_impressions,page_content_activity&period=day&since=' . (strtotime($start_date) - 86400) . '&until=' . (strtotime($end_date)), $pageAccessToken);
$user_obj = json_decode($response->getBody());

$ret = new stdClass();
$reach = "Day,Reach\n";
$engageduser = "Day,Engaged Users\n";
$reachVal = 0;
$engagedVal = 0;

$data = $user_obj->data;
for ($i = 0; $i < count($data); $i++) {
    $proc_obj = $data[$i];
    if (($proc_obj->name == "page_impressions") && ($proc_obj->period == "day")) {
        $values = $proc_obj->values;
        for ($j = 0; $j < count($values); $j++) {
            $reach .= substr($values[$j]->end_time, 0, 10) . "," . $values[$j]->value . "\n";
            $reachVal += $values[$j]->value;
        }
    } else if (($proc_obj->name == "page_engaged_users") && ($proc_obj->period == "day")) {
        $values = $proc_obj->values;
        for ($j = 0; $j < count($values); $j++) {
            $engageduser .= substr($values[$j]->end_time, 0, 10) . "," . $values[$j]->value . "\n";
            $engagedVal += $values[$j]->value;
        }
    }
}

$ret->Reach_Chart = $reach;
$fldName = "Engaged Users_Chart";
$ret->$fldName = $engageduser;
$ret->Reach = $reachVal;
$fldName = "Engaged Users";
$ret->$fldName = $engagedVal;

$ret->result = $arrResult;

echo json_encode($ret);

?>
