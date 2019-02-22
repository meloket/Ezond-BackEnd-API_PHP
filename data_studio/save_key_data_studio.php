<?php
require_once '../config.php';

$api_key = '';
if (isset($_GET['api_key'])) $api_key = $_GET['api_key'];
if ($api_key == "") exit();

$userID = $_GET['userID'];
$networkID = 10;
$networkName = "Data studio";

$arrUserInfo = explode("@", $userID);
$dashboardID = 0;
if (count($arrUserInfo) > 1) $dashboardID = $arrUserInfo[1];
$userID = $arrUserInfo[0];
if ($dashboardID == "") $dashboardID = 0;

db_insert_func($userID, $dashboardID, "", "", "Google data studio", $api_key, $networkID, $networkName, "");

// function db_insert_func($userID, $dashboardID, $refresh_token, $access_token, $account, $viewID, $networkID, $networkName, $authResponse="")
