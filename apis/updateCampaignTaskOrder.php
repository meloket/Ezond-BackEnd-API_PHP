<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$dashboardId = $_GET['campaign_id'] ?? 0;
$taskOrder = $_POST['taskOrder'] ?? "";

if ($dashboardId == 0) {
    exit();
}

$arrTasks = explode(",", $taskOrder);
for ($i = 0; $i < count($arrTasks); $i++) {
    $currentTaskOrder = (999 - $i);

    $sql = "UPDATE `user_actions` 
                SET `taskOrder` = :taskOrder 
                WHERE `dashboardId` = :dashboardId 
                  AND `actionIdx` = :actionIdx";
    $data = [
        'taskOrder' => $currentTaskOrder,
        'dashboardId' => $dashboardId,
        'actionIdx' => $arrTasks[$i],
    ];
    $db->exe($sql, $data);
}

$ret = new stdClass();
$ret->error = 0;
echo json_encode($ret);
?>