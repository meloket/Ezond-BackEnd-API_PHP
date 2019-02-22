<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$actionIdx = $_GET['actionIdx'] ?? 0;
$user_id = $_GET['user_id'] ?? 0;
$taskProgress = $_GET['taskProgress'] ?? 1;

if ($user_id == 0 || $actionIdx == 0) {
    exit();
}

$task_name = "";
$result = $db->select("SELECT `actionDetail`, `dashboardId` FROM `user_actions` WHERE `actionIdx` = :actionIdx",
    ['actionIdx' => $actionIdx]
);
if (count($result) > 0) {
    $row = $result[0];
    if (!is_null($row["actionDetail"])) {
        $task_name = $row["actionDetail"];
        $dashboardId = $row["dashboardId"];
    }
}

if (strlen($task_name) > 30) $task_name = substr($task_name, 0, 30) . " ...";

$table = 'user_actions';
$db->exe("UPDATE `{$table}` SET `taskProgress` = 1 - taskProgress WHERE `actionIdx` = :actionIdx",
    ['actionIdx' => $actionIdx]
);

$actionStatus = $taskProgress == 1 ? ' Complete' : ' Incomplete';
$insertData = [
    'userIdx' => $user_id,
    'actionTime' => date("Y-m-d H:i:s"),
    'actionContent' => 'Mark ' . $task_name . $actionStatus,
    'actionType' => 4,
    'dashboardId' => $dashboardId,
    'actionDetail' => '',
];
$db->insert($table, $insertData);
?>