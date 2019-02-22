<?php
require_once(__DIR__ . '/../config.php');

error_reporting(E_STRICT | E_ALL);

if (isset($_GET["userID"])) {
    $userID = $_GET["userID"];
} else {
    die();
}

$CONSUMER_KEY = "";
$CONSUMER_SECRET = "";
$OAUTH_CALLBACK = SITE_URL . "twitter/callback.php";

require_once 'autoload.php';

use Abraham\TwitterOAuth\TwitterOAuth;

$twitter = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET);
$result = $twitter->oauth('oauth/request_token', ['oauth_callback' => $OAUTH_CALLBACK]);

?>