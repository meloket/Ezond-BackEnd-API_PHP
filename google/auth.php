<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');

$method = "";
if (isset($_GET['method'])) $method = $_GET['method'];

$redirect_uri = SITE_URL . "callback.php";

if ($method == "sheet")
    $redirect_uri = SITE_URL . "google/sheet_callback.php";
else if ($method == "console")
    $redirect_uri = SITE_URL . "google/console_callback.php";
else if ($method == "youtube")
    $redirect_uri = SITE_URL . "google/youtube_callback.php";
else if ($method == "ads")
    $redirect_uri = SITE_URL . "adwords/callback.php";
else
    $redirect_uri = SITE_URL . "google/analytics_callback.php";

if (!isset($_GET["userID"])) exit();
$userID = $_GET["userID"];

if ($method == "ads") {

    $googleClientID = "797046810169-hgcral5fjvhoeatbb2tv4l4bsm57cuiq.apps.googleusercontent.com";
    $googleClientSecret = "x6kcwJkg1Jd4K1UUn91OOoNV";
}

$client = new Google_Client();
$client->setApplicationName($googleAppName);
$client->setAccessType("offline");
$client->setClientId($googleClientID);
$client->setClientSecret($googleClientSecret);
$client->setRedirectUri($redirect_uri);

if ($method == "sheet") {
    $client->addScope("https://www.googleapis.com/auth/spreadsheets.readonly");
    $client->addScope("https://www.googleapis.com/auth/drive.readonly");
    $client->addScope("https://www.googleapis.com/auth/plus.me");
    $client->addScope("https://www.googleapis.com/auth/userinfo.profile");
} else if ($method == "console") {
    $client->addScope("https://www.googleapis.com/auth/webmasters");
    $client->addScope("https://www.googleapis.com/auth/plus.me");
    $client->addScope("https://www.googleapis.com/auth/userinfo.profile");
} else if ($method == "youtube") {
    $client->addScope("https://www.googleapis.com/auth/youtube");
    $client->addScope("https://www.googleapis.com/auth/youtube.readonly");
    $client->addScope("https://www.googleapis.com/auth/yt-analytics.readonly");
    $client->addScope("https://www.googleapis.com/auth/plus.me");
    $client->addScope("https://www.googleapis.com/auth/userinfo.profile");
} else if ($method == "ads") {
    $client->addScope("https://www.googleapis.com/auth/adwords");
    $client->addScope("https://www.googleapis.com/auth/plus.me");
    $client->addScope("https://www.googleapis.com/auth/userinfo.profile");
} else {
    $client->addScope("https://www.googleapis.com/auth/analytics.readonly");
    $client->addScope("https://www.googleapis.com/auth/plus.me");
    $client->addScope("https://www.googleapis.com/auth/userinfo.profile");
}
$client->setState($userID);

$auth_url = $client->createAuthUrl();
$auth_url = str_replace("approval_prompt=auto", "", $auth_url);

echo $auth_url . "&approval_prompt=force";

// https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=
?>
