<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/functions.php');

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

$facebookAppID = "456797558007270";
$facebookAppSecret = "c69f7f97677d5852875a23045305cc8e";

session_start();

$fb = initFbClient($facebookAppID, $facebookAppSecret);

$helper = $fb->getRedirectLoginHelper();

$permissions = ['ads_read', 'ads_management', 'read_insights', 'pages_show_list', 'business_management', 'ads_management'];

$method = "facebook";
if (isset($_GET['method'])) $method = $_GET['method'];

if ($method == "ads")
    $loginUrl = $helper->getLoginUrl(SITE_URL . 'facebook/ads_callback.php?userID=' . $_GET['userID'], $permissions);
else
    $loginUrl = $helper->getLoginUrl(SITE_URL . 'facebook/callback.php?userID=' . $_GET['userID'], $permissions);

echo $loginUrl;
?>
