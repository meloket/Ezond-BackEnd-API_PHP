<?php

ini_set('max_execution_time', 100000);
error_reporting(E_STRICT | E_ALL);

require_once '../config.php';

$userID = "";
if (isset($_GET['userID'])) $userID = $_GET['userID'];
if ($userID == "") exit();

$dashboardID = "";
if (isset($_GET['dashboardID'])) $dashboardID = $_GET['dashboardID'];
if ($dashboardID == "") exit();

$networkID = "";
if (isset($_GET['networkID'])) $networkID = $_GET['networkID'];

$sql = "SELECT `id`, `networkID`, `account`, `viewID` FROM `users_networks` 
              WHERE `userID` = :userID 
                AND `dashboardID` = :dashboardID 
                AND defaultCheck='1'";
$data = [
    'userID' => $userID,
    'dashboardID' => $dashboardID,
];

if ($networkID) {
    $sql .= ' AND networkID = :networkID';
    $data['networkID'] = $networkID;
}

$result = $db->select($sql, $data);

echo json_encode($result);
?>