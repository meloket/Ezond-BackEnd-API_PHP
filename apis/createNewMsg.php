<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$user_id = $_GET['user_id'] ?? 0;
$dashboardId = $_GET['dashboardId'] ?? 0;
$msgContent = $_GET['msgContent'] ?? "";

if ($user_id === 0 || $dashboardId == 0 || $msgContent == "") {
    exit();
}

$insertData = [
    'userIdx' => $user_id,
    'actionTime' => date("Y-m-d H:i:s"),
    'actionContent' => $msgContent,
    'actionType' => 0,
    'dashboardId' => $dashboardId,
    'actionDetail' => ''
];

$db->insert('user_actions', $insertData);
$db->exe('UPDATE `dashboards` SET `actionCount` = actionCount + 1 WHERE `id`= :id', ['id' => $dashboardId]);

$ret = new stdClass();
$ret->error = 0;
echo json_encode($ret);
?>