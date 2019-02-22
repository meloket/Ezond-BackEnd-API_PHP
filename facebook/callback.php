<?php

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/functions.php');

// Add to header of your file
use FacebookAds\Api;

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Fields\CampaignFields;

use Facebook\Facebook;
use Facebook\FacebookRequest;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\FacebookResponse;

require_once(__DIR__ . '/../config.php');

session_start();

$facebookAppID = "456797558007270";
$facebookAppSecret = "c69f7f97677d5852875a23045305cc8e";

$fb = initFbClient($facebookAppID, $facebookAppSecret);

if (isset($_GET['code']) and !isset($_SESSION['facebook_access_token'])) {

    $helper = $fb->getRedirectLoginHelper();
    $_SESSION['FBRLH_state'] = $_GET['state'];

    if (isset($_GET['state'])) {
        $helper->getPersistentDataHandler()->set('state', $_GET['state']);
    }

    try {
        $accessToken = $helper->getAccessToken();
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        exit;
    }

    if (!isset($accessToken)) {
        if ($helper->getError()) {
        } else {
        }
        exit;
    }

    $_SESSION['facebook_access_token'] = $accessToken->getValue();
}

$retObj = new stdClass();
$retObj->error = "0";
$retObj->items = array();

if (isset($_SESSION['facebook_access_token'])) {

    $response = $fb->get('/me/accounts', $_SESSION['facebook_access_token']);
    $result = json_decode($response->getBody());

    $userID = $_GET['userID'];
    $networkID = 8;
    $networkName = "Facebook";
    $arrUserInfo = explode("@", $userID);
    $dashboardID = 0;
    if (count($arrUserInfo) > 1) $dashboardID = $arrUserInfo[1];
    $userID = $arrUserInfo[0];
    if ($dashboardID == "") $dashboardID = 0;

    db_clear_func($userID, $dashboardID, $networkID);

    $items = array();

    foreach ($result->data as $account) {
        $itemObj = new stdClass();
        $itemObj->id = $account->id;
        $itemObj->accountId = $account->id;
        $itemObj->websiteUrl = $account->name;
        $itemObj->webPropertyId = $account->id;
        array_push($items, $itemObj);

        db_insert_func($userID, $dashboardID, $_SESSION['facebook_access_token'], "", $itemObj->websiteUrl, $itemObj->webPropertyId, $networkID, $networkName, json_encode($itemObj));
    }
//    $retObj->items = $items;
    $retObj->error = "0";

}

?>
<script>
    window.opener.postMessage('<?php echo json_encode($retObj);?>', "*");
</script>
