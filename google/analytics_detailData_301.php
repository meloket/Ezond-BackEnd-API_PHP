<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');
require_once(__DIR__ . '/analytics_functions_2.php');

$start_date = date("Y-m-d");
$end_date = date("Y-m-d");

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];

$refreshToken = "";
$viewID = "";

if (isset($_GET['refreshToken'])) $refreshToken = $_GET['refreshToken'];
if (isset($_GET['viewID'])) $viewID = $_GET['viewID'];
if ($refreshToken == "") exit();
if ($viewID == "") exit();

$filterIndex = 0;
if (isset($_GET['filterIndex'])) $filterIndex = $_GET['filterIndex'];

$redirect_uri = SITE_URL . "google/analytics_callback.php";

$client = new Google_Client();
$client->setApplicationName($googleAppName);
$client->setAccessType("offline");
$client->setClientId($googleClientID);
$client->setClientSecret($googleClientSecret);
$client->setRedirectUri($redirect_uri);

$client->refreshToken($refreshToken);
$token = $client->getAccessToken();

$arr_alias = array("Quantity", "Unique Purchases", "Item Revenue", "Revenue Per Item", "Items Per Purchase", "Transactions", "Revenue", "Average Order Value");

$analytics = new Google_Service_AnalyticsReporting($client);

$ret = new stdClass();

$arrMedium = array("", "", "Organic Search", "Paid Search", "Social", "Referral", "Display", "Email", "cpv");

$medium = $arrMedium[$filterIndex];
$impressions = "ga:channelGrouping";
$headerFld = "Ecommerce";

$response = getReport($analytics, $viewID, array($medium));
$result = printTotals($response);
if (count($result) > 0) $ret = $result[0];

$response = getReport($analytics, $viewID, array($medium), false, $impressions);
$result = printResults($response);
for ($i = 0; $i < count($result); $i++)
    $result[$i]->$headerFld = $result[$i]->dimensions;
$ret->result = $result;

foreach ($arr_alias as $key => $alias) {
    $fldName = $alias . "_Chart";
    $$fldName = "Date," . $alias . "\n";
}
$response = getReport($analytics, $viewID, array($medium), true);
$result = printResults($response);
for ($i = 0; $i < count($result); $i++) {
    foreach ($arr_alias as $key => $alias) {
        $date_val = $result[$i]->dimensions;
        $date_val = substr($date_val, 0, 4) . "-" . substr($date_val, 4, 2) . "-" . substr($date_val, 6, 2);
        $fldValue = 0;
        if (isset($result[$i]->$alias)) $fldValue = $result[$i]->$alias;
        $fldName = $alias . "_Chart";
        $$fldName .= $date_val . "," . $fldValue . "\n";
    }
}

foreach ($arr_alias as $key => $alias) {
    $fldName = $alias . "_Chart";
    $ret->$fldName = $$fldName;
}

echo json_encode($ret);

?>