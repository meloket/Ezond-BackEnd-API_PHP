<?php
require_once('MC_OAuth2Client.php');
require_once('MC_RestClient.php');
require_once('miniMCAPI.class.php');

require_once '../config.php';

$client = new MC_OAuth2Client($_GET['userID']);

$retObj = new stdClass();
$retObj->error = "0";
$retObj->items = array();

$session = $client->getSession();

$rest = new MC_RestClient($session);
$data = $rest->getMetadata();

$userID = $_GET['userID'];
$networkID = 6;
$networkName = "Mail Chimp";
$arrUserInfo = explode("@", $userID);
$dashboardID = 0;
if (count($arrUserInfo) > 1) $dashboardID = $arrUserInfo[1];
$userID = $arrUserInfo[0];
if ($dashboardID == "") $dashboardID = 0;

db_clear_func($userID, $dashboardID, $networkID);

$auth_info = new stdClass();
$auth_info->GET = $_GET;
$auth_info->session = $session;
$auth_info->data = $data;

$items = array();

$itemObj = new stdClass();
$itemObj->id = $data['user_id'];
$itemObj->accountId = $data['login']['login_id'];
$itemObj->websiteUrl = $data['accountname'];
$itemObj->webPropertyId = $data['login']['login_name'];
array_push($items, $itemObj);

//  $retObj->items = $items;
$retObj->error = "0";

db_insert_func($userID, $dashboardID, $session['access_token'], json_encode($auth_info), $data['accountname'], $data['login']['login_name'], $networkID, $networkName, json_encode($itemObj));
?>

<script>
    window.opener.postMessage('<?php echo json_encode($retObj);?>', "*");
</script>