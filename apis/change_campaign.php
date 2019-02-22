<?php

ini_set('max_execution_time', 100000);
error_reporting(E_STRICT | E_ALL);

require_once '../config.php';

$userID = $_GET['userID'] ?? '';
$dashboardID = $_GET['dashboardID'] ?? '';
if ($userID === '' || $dashboardID === '') {
    exit();
}

$sql = 'UPDATE `users_networks` 
              SET `dashboardID` = :dashboardID 
              WHERE `userID`= :userID 
                AND `dashboardID` = 999';

$data = [
    'dashboardID' => $dashboardID,
    'userID' => $userID,
];
$db->exe($sql, $data);

?>