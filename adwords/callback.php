<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');

ini_set('max_execution_time', 100000);
error_reporting(E_STRICT | E_ALL);

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

$refreshToken = $authToken['refresh_token'];

$session = (new AdWordsSessionBuilder())
    ->withDeveloperToken('c6fWiBgJrYC58qHfFCqWnA')
    ->withOAuth2Credential($oauth2)
    ->build();

$adWordsServices = new AdWordsServices();

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

$customerService = $adWordsServices->get($session, CustomerService::class);
try {
    $page = $customerService->getCustomers();

    $arr_customers = array();
    if ($page[0]->getCanManageClients()) {
        $clientCustomerId = $page[0]->getCustomerId();

        $session = (new AdWordsSessionBuilder())
            ->withDeveloperToken('c6fWiBgJrYC58qHfFCqWnA')
            ->withOAuth2Credential($oauth2)
            ->withClientCustomerId($clientCustomerId)
            ->build();

        $managedCustomerService =
            $adWordsServices->get($session, ManagedCustomerService::class);

        $selector = new Selector();
        $selector->setFields(['CustomerId', 'Name']);
        $selector->setOrdering([new OrderBy('CustomerId', SortOrder::ASCENDING)]);
        $selector->setPaging(new Paging(0, 100));

        $page = $managedCustomerService->get($selector);

        foreach ($page->getEntries() as $account) {
            $_cuser = new stdClass();
            $_cuser->customerId = $account->getCustomerId();
            $_cuser->customerName = $account->getName();
            array_push($arr_customers, $_cuser);
        }

    } else {
        $_cuser = new stdClass();
        $_cuser->customerId = $page[0]->getCustomerId();
        $_cuser->customerName = $page[0]->getDescriptiveName();
        array_push($arr_customers, $_cuser);
    }
    $items = array();
    foreach ($arr_customers as $account) {
        $itemObj = new stdClass();
        $itemObj->id = $account->customerId;
        $itemObj->accountId = $account->customerId;
        $itemObj->websiteUrl = str_replace("'", "", $account->customerName);
        $itemObj->webPropertyId = $account->customerId;
        array_push($items, $itemObj);

        db_insert_func($userID, $dashboardID, $authToken['refresh_token'], "", $account->customerName, $account->customerId, $networkID, $networkName, json_encode($itemObj));
    }
    //$retObj->items = $items;
    $retObj->error = "0";
} catch (Google\AdsApi\AdWords\v201802\cm\ApiException $e) {

}

?>

<script>
    window.opener.postMessage('<?php echo json_encode($retObj);?>', "*");
</script>