<?php

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');

ini_set('max_execution_time', 100000);
error_reporting(E_STRICT | E_ALL);
ini_set('display_errors', 1);

$code = "";
if (isset($_GET['code'])) $code = $_GET['code'];
if ($code == "") exit();

use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;

use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\ReportSettings;
use Google\AdsApi\AdWords\ReportSettingsBuilder;
use Google\AdsApi\AdWords\Reporting\v201809\DownloadFormat;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinition;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinitionDateRangeType;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDownloader;
use Google\AdsApi\AdWords\v201809\cm\ApiException;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\ReportDefinitionReportType;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\OrderBy;
use Google\AdsApi\AdWords\v201809\cm\SortOrder;
use Google\AdsApi\AdWords\v201809\mcm\ManagedCustomerService;
use Google\AdsApi\AdWords\v201809\mcm\CustomerService;
use Google\AdsApi\AdWords\v201809\cm\ReportDefinitionService;
use Google\AdsApi\Common\OAuth2TokenBuilder;

$userID = $_GET["state"];
$userAgent = $googleAppName;

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
    'approval_prompt' => "force"
]);


$oauth2->setCode($code);
$authToken = $oauth2->fetchAuthToken();

//        $authToken['access_token'] = 'ya29.GluTBOoGptQgCH7svl7Y3TsoE5LQ1xLKOHwsoy_sEYEXRkxMEQAbUwHs8fnrP6ncLUmRNBH0jorbHXZIWkH5GaQAl_1o1hBDIgC6NvWKfljn7jVm8jVxhFxL_6Ng';
//        $authToken['token_type'] = 'Bearer';
//        $authToken['expires_in'] = '3600';
//        $authToken['refresh_token'] = '1/CVTJP2ubXiK4Rglx5dvnSSCvie7X0gawQ-yUscFvZ6U';
//        $authToken['id_token'] = 'eyJhbGciOiJSUzI1NiIsImtpZCI6ImIzYjYwMzQ0ZjcyYWNmYTI3YmRmZjc2YTQ0MTkyNDc1N2QzYmVlYzQifQ.eyJhenAiOiI3OTcwNDY4MTAxNjktaGdjcmFsNWZqdmhvZWF0YmIydHY0bDRic201N2N1aXEuYXBwcy5nb29nbGV1c2VyY29udGVudC5jb20iLCJhdWQiOiI3OTcwNDY4MTAxNjktaGdjcmFsNWZqdmhvZWF0YmIydHY0bDRic201N2N1aXEuYXBwcy5nb29nbGV1c2VyY29udGVudC5jb20iLCJzdWIiOiIxMTY5MjM2NDEzMDM0NjQ4NDg2MzIiLCJhdF9oYXNoIjoiOW1CV3VpR2Z2X09qYWJnSUVnaXotdyIsImlzcyI6Imh0dHBzOi8vYWNjb3VudHMuZ29vZ2xlLmNvbSIsImlhdCI6MTUwMDk1MzQ4NCwiZXhwIjoxNTAwOTU3MDg0LCJuYW1lIjoiVmlqYXlzaW5oIFBhcm1hciIsInBpY3R1cmUiOiJodHRwczovL2xoNi5nb29nbGV1c2VyY29udGVudC5jb20vLVZKU1BvMlVpVEI4L0FBQUFBQUFBQUFJL0FBQUFBQUFBQUJ3L2lCdkdpaTc0OE4wL3M5Ni1jL3Bob3RvLmpwZyIsImdpdmVuX25hbWUiOiJWaWpheXNpbmgiLCJmYW1pbHlfbmFtZSI6IlBhcm1hciIsImxvY2FsZSI6ImVuIn0.NDZawRk-IpxJp4a549vtaRLfX6JRS6ucTcm3GzKHuFrsUxqGTMCwiATVkvMkk1X3BW9ljGXqri_0CPlWwXI5Xgn4gowEwd1RpG_VPUr9DR65J2j5EqDuZBBFlARkcHcWm_vSxUfSBxMVs-Y0vTqh23IMD95Gbk0iUUEIbnNcBmK4h20I4rY1_gz7OOI7w7hwm6MGQb369KVhZYLFfIVCrb_7t7tPIeTwJyCCswgdXxZEZ8_tbJ6h3tA13R5wzeY0hnqnMpTLuzPFy4rTHL7yQqONC4avSKGPuxWpSqHL4u_L0Z3LbazByoVX8y9D8AXozc0ic1moNfjRYQakV20ZaQ';
$refreshToken = $authToken['refresh_token'];


//error_log(json_encode($authToken));
//print_r($authToken);
/*
if($authToken['refresh_token']){
    file_put_contents("adwords_".date("Ymd_His").".txt", json_encode($authToken));
}*/

//	$session = (new AdWordsSessionBuilder())
//        ->fromFile()
//        ->withOAuth2Credential($oauth2)
//        ->build();

$oAuth2Credential = (new OAuth2TokenBuilder())
    ->withClientId($googleClientID)
    ->withClientSecret($googleClientSecret)
    ->withRefreshToken($refreshToken)
    ->build();
$session = (new AdWordsSessionBuilder())
    ->withDeveloperToken('c6fWiBgJrYC58qHfFCqWnA')
    ->withOAuth2Credential($oAuth2Credential)
    ->build();

$adWordsServices = new AdWordsServices();
$customer_service = $adWordsServices->get($session, CustomerService::class);
$customer = $customer_service->getCustomers()[0];
$clientCustomerId = $customer->getCustomerId();

$session = (new AdWordsSessionBuilder())
    ->withDeveloperToken('c6fWiBgJrYC58qHfFCqWnA')
    ->withOAuth2Credential($oAuth2Credential)
    ->withClientCustomerId($clientCustomerId)
    ->build();


/*
    $customerService = $adWordsServices->get($session, CustomerService::class);
    $page = $customerService->getCustomers();
    echo ($page[0]->getCanManageClients());
*/

$managedCustomerService =
    $adWordsServices->get($session, ManagedCustomerService::class);

$selector = new Selector();
$selector->setFields(['CustomerId', 'Name']);
$selector->setOrdering([new OrderBy('CustomerId', SortOrder::ASCENDING)]);
$selector->setPaging(new Paging(0, 100));

$page = $managedCustomerService->get($selector);

$retObj = new stdClass();
$retObj->error = "0";
$retObj->items = array();

$networkID = 2;
$networkName = "Google Adwords";
$arrUserInfo = explode("@", $userID);
$dashboardID = 0;
if (count($arrUserInfo) > 1) $dashboardID = $arrUserInfo[1];
$userID = $arrUserInfo[0];
if ($dashboardID == "") $dashboardID = 0;

db_clear_func($userID, $dashboardID, $networkID);

$items = array();
foreach ($page->getEntries() as $account) {
    $itemObj = new stdClass();
    $itemObj->id = $account->getCustomerId();
    $itemObj->accountId = $account->getCustomerId();
    $itemObj->websiteUrl = $account->getName();
    $itemObj->webPropertyId = $account->getCustomerId();
    array_push($items, $itemObj);

    db_insert_func($userID, $dashboardID, $authToken['refresh_token'], "", $account->getName(), $account->getCustomerId(), $networkID, $networkName, json_encode($itemObj));
}
$retObj->items = $items;
$retObj->error = "0";
//echo json_encode($retObj);

/*

$oauth2 = new OAuth2([
    'authorizationUri' => $AUTHORIZATION_URI,
    'redirectUri' => $callbackUrl,
    'tokenCredentialUri' => CredentialsLoader::TOKEN_CREDENTIAL_URI,
    'clientId' => $googleClientID,
    'clientSecret' => $googleClientSecret,
    'scope' => $scopes,
    'refresh_token' => $refreshToken
]);

$authToken = $oauth2->fetchAuthToken();

*/

?>

<script>
    window.opener.postMessage('<?php echo json_encode($retObj);?>', "*");