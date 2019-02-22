<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');

error_reporting(E_STRICT | E_ALL);

if (isset($_GET["userID"])) {
    $userID = $_GET["userID"];
} else {
    die();
}

use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;

$googleClientID = "797046810169-hgcral5fjvhoeatbb2tv4l4bsm57cuiq.apps.googleusercontent.com";
$googleClientSecret = "x6kcwJkg1Jd4K1UUn91OOoNV";

$callbackUrl = SITE_URL . "adwords/callback.php";
$scopes = "https://www.googleapis.com/auth/adwords https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/plus.me";
$AUTHORIZATION_URI = 'https://accounts.google.com/o/oauth2/v2/auth';

$oauth2 = new OAuth2([
    'authorizationUri' => $AUTHORIZATION_URI,
    'redirectUri' => $callbackUrl,
    'tokenCredentialUri' => CredentialsLoader::TOKEN_CREDENTIAL_URI,
    'clientId' => $googleClientID,
    'clientSecret' => $googleClientSecret,
    'scope' => $scopes,
    'state' => $userID
]);

$authUrl = $oauth2->buildFullAuthorizationUri();

echo $authUrl;

?>