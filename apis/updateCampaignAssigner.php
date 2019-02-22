<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$dashboardId = $_GET['campaign_id'] ?? 0;
$userIdx = $_GET['userIdx'] ?? 0;
$user_id = $_GET['user_id'] ?? 0;

if ($dashboardId == 0) {
    exit();
}

$db->exe("UPDATE `dashboards` SET `assignerID` = :assignerID WHERE id = :id", ['assignerID' => $userIdx, 'id' => $dashboardId]);

$task_name = "";
$result = $db->select("SELECT `company_name` FROM `dashboards` WHERE `id` = :id", ['id' => $dashboardId]);
if (count($result) > 0) {
    $row = $result[0];
    if (!is_null($row["company_name"])) {
        $task_name = $row["company_name"];
    }
}

$user_name = "";
$result = $db->select("SELECT concat(first_name, ' ', last_name) AS `user_name` FROM `users` WHERE `id` = :id", ['id' => $userIdx]);
if (count($result) > 0) {
    $row = $result[0];
    if (!is_null($row["user_name"])) {
        $user_name = $row["user_name"];
    }
}
$result = $db->select("SELECT concat(first_name, ' ', last_name) AS `user_name` FROM `agency_users` WHERE id = :id", ['id' => $userIdx]);
if (count($result) > 0) {
    $row = $result[0];
    if (!is_null($row["user_name"])) {
        $user_name = $row["user_name"];
    }
}

$table = 'user_actions';
$insertData = [
    'userIdx' => $user_id,
    'actionTime' => date("Y-m-d H:i:s"),
    'actionContent' => 'Assigned campaign ' . $task_name . ' to ' . $user_name,
    'actionType' => 4,
    'dashboardId' => $dashboardId,
    'actionDetail' => '',
];
$db->insert($table, $insertData);

$ret = new stdClass();
$ret->error = 0;
$ret->ownerName = $user_name;
echo json_encode($ret);
?>