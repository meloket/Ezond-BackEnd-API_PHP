<?php

ini_set('max_execution_time', 100000);
error_reporting(E_STRICT | E_ALL);

require_once '../config.php';

$dashboardID = "";
if (isset($_GET['dashboardID'])) $dashboardID = $_GET['dashboardID'];
if ($dashboardID == "") exit();

$networkID = "";
if (isset($_GET['networkID'])) $networkID = $_GET['networkID'];
if ($networkID == "") exit();

$result = $db->select("SELECT * FROM `users_networks` WHERE `networkID` = :networkID AND `dashboardID` = :dashboardID",
    ['networkID' => $networkID, 'dashboardID' => $dashboardID]
);

$retObj = new stdClass();

$arr = array();
for ($i = 0; $i < count($result); $i++) {
    $net_obj = json_decode($result[$i]["authResponse"]);
    array_push($arr, $net_obj);
}

$retObj->items = $arr;
$retObj->error = "0";
echo json_encode($retObj);
?>