<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/functions.php');
require_once(__DIR__ . '/ads_function.php');

// Add to header of your file
use FacebookAds\Api;
use Facebook\Facebook;
use Facebook\FacebookRequest;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\FacebookResponse;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Fields\AdAccountFields;

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

Api::init(
    $facebookAppID, // App ID
    $facebookAppSecret,
    $_SESSION['facebook_access_token'] // Your user access token
);
initApiVersion();

$retObj = new stdClass();
$retObj->error = "0";
$retObj->items = array();

if (isset($_SESSION['facebook_access_token'])) {
    try {
        $response = $fb->get('/me', $_SESSION['facebook_access_token']);
        $user_obj = json_decode($response->getBody());

        $response = $fb->get('/me/adaccounts', $_SESSION['facebook_access_token']);
        $result = json_decode($response->getBody());

        $userID = $_GET['userID'];
        $networkID = 7;
        $networkName = "Facebook Ads";
        $arrUserInfo = explode("@", $userID);
        $dashboardID = 0;
        if (count($arrUserInfo) > 1) $dashboardID = $arrUserInfo[1];
        $userID = $arrUserInfo[0];
        if ($dashboardID == "") $dashboardID = 0;

        db_clear_func($userID, $dashboardID, $networkID);

        $items = array();

        foreach ($result->data as $account) {
            $account_name = $user_obj->name;
            try {
                $account2 = new AdAccount("act_" . $account->account_id);
                $account2->read(array(AdAccountFields::NAME,));
                $account_name = $account2->{AdAccountFields::NAME};
            } finally {

            }

            $itemObj = new stdClass();
            $itemObj->id = $user_obj->id;
            $itemObj->accountId = $account->account_id;
            $itemObj->websiteUrl = $account_name;
            $itemObj->webPropertyId = $account->account_id;
            array_push($items, $itemObj);

            db_insert_func($userID, $dashboardID, $_SESSION['facebook_access_token'], "", $itemObj->websiteUrl, $itemObj->webPropertyId, $networkID, $networkName, json_encode($itemObj));
        }
    } catch (FacebookAds\Http\Exception\AuthorizationException $e) {
        // error_log("Facebook AuthorizationException");
    }
    // $retObj->items = $items;
    $retObj->error = "0";

}
?>
<script>
    window.opener.postMessage('<?php echo json_encode($retObj);?>', "*");
</script>
