<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');

$redirect_uri = SITE_URL . "google/console_callback.php";

$refreshToken = "1/oZ3IHaVtXl9sd1VQULVVK2aJ9k4kO4s2qDCVT3gCZQbpO0N0EAV6jOOO8JuF9KA7";


$redirect_uri = SITE_URL . "google/callback.php";

$refreshToken = "1/_Zpwdq_qa2iDCBgYUO9Ol73xMIlpTUlmVBw9dNY8Pe4";

ini_set('max_execution_time', 300);
error_reporting(E_STRICT | E_ALL);

$client = new Google_Client();
$client->setApplicationName($googleAppName);
$client->setAccessType("offline");
$client->setClientId($googleClientID);
$client->setClientSecret($googleClientSecret);
$client->setRedirectUri($redirect_uri);

$client->refreshToken($refreshToken);
$token = $client->getAccessToken();

print_r($token);
echo "<br><br>";
echo file_get_contents("https://www.googleapis.com/oauth2/v3/tokeninfo?access_token=" . $token['access_token']);
?>