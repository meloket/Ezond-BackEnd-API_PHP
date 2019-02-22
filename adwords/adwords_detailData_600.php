<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');
require_once(__DIR__ . '/adword_function.php');

ini_set('max_execution_time', 100000);
error_reporting(E_STRICT | E_ALL);

$customerID = "5304258854";
$refresh_token = "1/xjF88OGdQ7YldvOVKczYAQkK0Z2AfchQqoIXtLDoeEtSAFdOB8dRkXlKFatTT0i4";

if (isset($_GET['viewID'])) $customerID = $_GET['viewID'];
if ($customerID == "") exit();

if (isset($_GET['refreshToken'])) $refresh_token = $_GET['refreshToken'];
if ($refresh_token == "") exit();

$start_date = "2017-06-01";
$end_date = "2017-07-01";

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];

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
use Google\AdsApi\AdWords\v201809\cm\ReportDefinitionService;
use Google\AdsApi\AdWords\v201809\cm\DateRange;

$userAgent = $googleAppName;

$callbackUrl = SITE_URL . "adwords/callback.php";
$scopes = "https://www.googleapis.com/auth/adwords https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/plus.me";
$AUTHORIZATION_URI = 'https://accounts.google.com/o/oauth2/v2/auth';

$googleClientID = "797046810169-hgcral5fjvhoeatbb2tv4l4bsm57cuiq.apps.googleusercontent.com";
$googleClientSecret = "x6kcwJkg1Jd4K1UUn91OOoNV";

$oauth2 = new OAuth2([
    'authorizationUri' => $AUTHORIZATION_URI,
    'redirectUri' => $callbackUrl,
    'tokenCredentialUri' => CredentialsLoader::TOKEN_CREDENTIAL_URI,
    'clientId' => $googleClientID,
    'clientSecret' => $googleClientSecret,
    'scope' => $scopes,
    'refresh_token' => $refresh_token
    //'access_token' => $refresh_token
]);

$authToken = $oauth2->fetchAuthToken();

$session = (new AdWordsSessionBuilder())
    ->withDeveloperToken('c6fWiBgJrYC58qHfFCqWnA')
    ->withOAuth2Credential($oauth2)
    ->withClientCustomerId($customerID)
    ->build();

$arrHeaderFlds = ['Campaign', 'Status', 'Network'];

$arrOriginFld = ['Clicks', 'Impressions', 'Cost', 'Average CPC', 'CTR', 'Conversions', 'Cost Per Conversion', 'Conversion Rate', 'View-Through Conv.', 'Avg. Position', 'Search Impr. Share'];

$arrFlds = ['CampaignName', 'CampaignStatus', 'AdNetworkType1', 'Clicks', 'Impressions', 'Cost', 'AverageCpc', 'Ctr', 'Conversions', 'CostPerConversion', 'ConversionRate', 'ViewThroughConversions', 'AveragePosition', 'SearchImpressionShare'];

$arrFlds_date = ['Date', 'Clicks', 'Impressions', 'Cost', 'AverageCpc', 'Ctr', 'Conversions', 'CostPerConversion', 'ConversionRate', 'ViewThroughConversions', 'AveragePosition', 'SearchImpressionShare'];

$filter_obj = new stdClass();
$filter = $_GET['filter'];
$arrFilter = explode("@", $filter);
if ($arrFilter[0] == "Search Network")
    $filter_obj->networkName = "SEARCH";
else if ($arrFilter[0] == "Display Network")
    $filter_obj->networkName = "DISPLAY";

$result = GetReport($session, $start_date, $end_date, ReportDefinitionReportType::CAMPAIGN_PERFORMANCE_REPORT, $arrFlds, $filter_obj);
$ret = GetMetricValues($result, $arrOriginFld, $arrHeaderFlds);
$result = GetReport($session, $start_date, $end_date, ReportDefinitionReportType::ACCOUNT_PERFORMANCE_REPORT, $arrFlds_date, $filter_obj);
$ret = GetMetricChartValues($ret, $result, $arrOriginFld);
try {
    $ret->campaigns = GetCampaigns(new AdWordsServices(), $session);
} catch (Exception $e) {
    echo "<pre>";
    print_r($e);
    // print_r($ret->campaigns);
    // print_r($ret->adGroups);
    echo "</pre>";
}
$ret->adGroups = GetAdGroups(new AdWordsServices(), $session);
echo json_encode($ret);

?>