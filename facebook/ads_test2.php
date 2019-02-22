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

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/ads_function.php';

$facebookAppID = "177106629538573";
$facebookAppSecret = "2ce4b2bdfa61ec8b861bd1ffa5dc61c6";
$facebook_access_token = "EAACEdEose0cBAGXu1MrVo4J0NdlRgfn2lZB9M6ian2anwJ5xxcJT3hba2bZAKSGh3jPDy5v3ExgrBSpdTUs5BQ6HK0AziCimBvAzjjHKszq8XJOZB9U8rAAMGzzu2gdq2XrlXcOiolBexHa3eRwMeTBOH9da5TwqhTTb3TUZCYSHitseufFFM6m02O8ZBgbzRm6PjNaKL6AZDZD";
$viewID = "1520139461372217";

if (isset($_GET['viewID'])) $viewID = $_GET['viewID'];
if ($viewID == "") exit();

if (isset($_GET['refreshToken'])) $facebook_access_token = $_GET['refreshToken'];
if ($facebook_access_token == "") exit();

$start_date = "2017-01-01";
$end_date = "2017-11-11";
die("Q");

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];

$fb = initFbClient($facebookAppID, $facebookAppSecret);


// Initialize a new Session and instantiate an Api object
Api::init(
    '177106629538573', // App ID
    '2ce4b2bdfa61ec8b861bd1ffa5dc61c6',
    $facebook_access_token // Your user access token
);
initApiVersion();

$campaign_fields = array(
    'name',
    'status',
    'effective_status',
    'objective'
);

$insight_fields = array(
    'clicks',
    'impressions',
    'cpc',
    'cpm',
    'cpp',
    'ctr',
    'reach',
    'spend'
);

$insight_params = array(
    'level' => 'account',
    'date_preset' => 'lifetime',
    'time_range' => array(
        'since' => $start_date,
        'until' => $end_date
    )
);

$ret = new stdClass();
foreach ($insight_fields as $insight_name) {
    $ret->$insight_name = 0;
}

$account = new AdAccount("act_" . $viewID);
$arr_insights = json_decode($account->getInsights($insight_fields, $insight_params)->getResponse()->getBody());

if (count($arr_insights->data) > 0) {
    $campaign_insights = $arr_insights->data[0];
    foreach ($insight_fields as $insight_name) {
        $ret->$insight_name = $campaign_insights->$insight_name;
    }
}

foreach ($insight_fields as $insight_name) {
    $ret->$insight_name = round($ret->$insight_name, 2);
}
echo json_encode($ret);

?>
