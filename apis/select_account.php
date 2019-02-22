<?php

ini_set('max_execution_time', 100000);
error_reporting(E_STRICT | E_ALL);

require_once '../config.php';

$userID = $_GET['userID'] ?? "";
$dashboardID = $_GET['dashboardID'] ?? "";
$networkID = $_GET['networkID'] ?? "";
$account = $_GET['account'] ?? "";

if ($userID == "" || $dashboardID == "" || $networkID == "" || $account == "") {
    exit();
}

$sql = "UPDATE `users_networks` SET `defaultCheck` = :defaultCheck WHERE `networkID` = :networkID AND `dashboardID` = :dashboardID";
$data = [
    'defaultCheck' => '0',
    'networkID' => $networkID,
    'dashboardID' => $dashboardID
];
$db->exe($sql, $data);

$data['defaultCheck'] = '1';
$data['account'] = $account;
$db->exe($sql . " AND `account` = :account", $data);

?>