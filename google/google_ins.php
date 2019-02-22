<?php
ini_set('max_execution_time', 300);
error_reporting(E_STRICT | E_ALL);

$userID = $_GET["state"];
$code = "";
if (isset($_GET['code'])) $code = $_GET['code'];
if ($code == "") exit();

$client = new Google_Client();
$client->setApplicationName($googleAppName);
$client->setAccessType("offline");
$client->setClientId($googleClientID);
$client->setClientSecret($googleClientSecret);
$client->setRedirectUri($redirect_uri);

$check = $client->authenticate($code);
$token = $client->getAccessToken();
?>