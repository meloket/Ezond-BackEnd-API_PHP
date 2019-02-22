<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$actionIdx = $_GET['actionIdx'] ?? 0;
$userIdx = $_GET['userIdx'] ?? 0;
$user_id = $_GET['user_id'] ?? 0;

if ($actionIdx == 0) {
    exit();
}

$db->exe("UPDATE `user_actions` SET `taskAssigner` = :taskAssigner WHERE `actionIdx` = :actionIdx",
    ['taskAssigner' => $userIdx, 'actionIdx' => $actionIdx]
);

$task_name = "";
$dashboardId = 0;
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

$user_name = "";
$result = $db->select("SELECT concat(first_name, ' ', last_name) AS `user_name` FROM `users` WHERE id = :id",
    ['id' => $userIdx]
);
if (count($result) > 0) {
    $row = $result[0];
    if (!is_null($row["user_name"])) {
        $user_name = $row["user_name"];
    }
}

$result = $db->select("SELECT concat(first_name, ' ', last_name) AS `user_name` FROM `agency_users` WHERE id = :id",
    ['id' => $userIdx]);
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
    'actionContent' => 'Assigned ' . $task_name . ' to ' . $user_name,
    'actionType' => 4,
    'dashboardId' => $dashboardId,
    'actionDetail' => ''
];

$db->insert($table, $insertData);

$ret = new stdClass();
$ret->error = 0;
echo json_encode($ret);
?>