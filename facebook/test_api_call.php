<?php

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../Mysql.php');
require_once(__DIR__ . '/functions.php');
require_once(__DIR__ . '/ads_function.php');

// Add to header of your file
use FacebookAds\Api;
use FacebookAds\Object\AdUser;
use FacebookAds\Object\Campaign;

use Facebook\Facebook;
use Facebook\FacebookRequest;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

$db = new Mysql();

// Add after echo "You are logged in "
session_start();


$facebookAppID = "456797558007270";
$facebookAppSecret = "c69f7f97677d5852875a23045305cc8e";

$fb = initFbClient($facebookAppID, $facebookAppSecret);

if (isset($_GET['code']) and !isset($_SESSION['facebook_access_token'])) {

    $helper = $fb->getRedirectLoginHelper();

    try {
        $accessToken = $helper->getAccessToken();
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    if (!isset($accessToken)) {
        if ($helper->getError()) {
            header('HTTP/1.0 401 Unauthorized');
            echo "Error: " . $helper->getError() . "\n";
            echo "Error Code: " . $helper->getErrorCode() . "\n";
            echo "Error Reason: " . $helper->getErrorReason() . "\n";
            echo "Error Description: " . $helper->getErrorDescription() . "\n";
        } else {
            header('HTTP/1.0 400 Bad Request');
            echo 'Bad request';
        }
        exit;
    }

    $_SESSION['facebook_access_token'] = $accessToken->getValue();

}

if (!isset($_SESSION['facebook_access_token'])) {
    $helper = $fb->getRedirectLoginHelper();

    $permissions = ['ads_read']; // Optional permissions
    $loginUrl = $helper->getLoginUrl(SITE_URL . 'facebookads/test_api_call.php', $permissions);

    echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';
    exit;
}

// Initialize a new Session and instantiate an Api object
Api::init(
    '670344313142949', // App ID
    '103b7615e435583bf5a8a1bbd302f871',
    $_SESSION['facebook_access_token'] // Your user access token
);
initApiVersion();

$me = new AdUser('me');
$my_ad_accounts = $me->getAdAccounts()->getObjects();

for ($i = 0; $i < sizeof($my_ad_accounts); $i++) {

    $my_ad_account = $my_ad_accounts[$i];

    echo "<h3>Your ad account id: " . $my_ad_account->account_id . "</h3>";
    // Get Campaings

    try {
        $account_campaigns = $my_ad_account->getCampaigns();
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    $my_campaigns = $account_campaigns->getObjects();

    $campaign_fields = array(
        'name',
        'status',
        'effective_status',
        'objective'
    );

    $insight_fields = array(
        'actions',
        'call_to_action_clicks',
        'impressions',
        'cpc',
        'cpm',
        'cpp',
        'ctr',
        'reach',
        'clicks',
        'unique_clicks'
    );

    $insight_params = array(
        'date_preset' => 'lifetime'
    );


    echo 'You have ' . sizeof($my_campaigns) . ' campaigns on Facebook. Here are the details of the first active campaign<br /><br />';

    // CAMPAIGNS

    if (sizeof($my_campaigns) < 1)
        continue;

    $campaign = $my_campaigns[0];
    $campaign = $campaign->getSelf($campaign_fields);

    foreach ($campaign_fields as $field_name) {
        echo $field_name . ': ' . $campaign->$field_name . '<br />';
    }

    // CAMPAIGN INSIGHTS
    $campaign_insights = $campaign->getInsights($insight_fields, $insight_params)->current();
    $campaign_action_insights = array('comments' => 0, 'likes' => 0,);
    echo "<h3>Campaign Metrics</h3>";
    foreach ($insight_fields as $insight_name) {

        if ($insight_name == "actions") {

            $data_arr = $campaign_insights->$insight_name;

            foreach ($data_arr as $data) {

                echo $data['action_type'] . ': ' . $data['value'] . '<br />';

            }

        } else {
            echo $insight_name . ': ' . $campaign_insights->$insight_name . '<br />';
        }
    }
    echo "<br /><br />";

    $qry = "INSERT INTO users_data_facebook (userID, viewID, campaignName, effectiveStatus, objective, impressions, reach, clicks, cpc, cpm, cpp, ctr, comments, likes, unique_clicks) VALUES ";

    $qry .= "(22,'3258355322'," . $campaign->name . "','" . $campaign->effective_status . "','" . $campaign->objective . "'," . $campaign_insights->impressions . ',' . $campaign_insights->reach . ',' . $campaign_insights->clicks . ',' . $campaign_insights->cpc . ',' . $campaign_insights->cpm . ',' . $campaign_insights->cpp . ',' . $campaign_insights->ctr . ',' . $campaign_action_insights['comments'] . ',' . $campaign_action_insights['likes'] . ',' . $campaign_insights->unique_clicks . ')';

    $results = $db->exe($qry, null);
}

?>
