<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$actionIdx = $_GET['actionIdx'] ?? 0;

if ($actionIdx == 0) {
    exit();
}

$db->delete("DELETE FROM `user_actions` WHERE `actionIdx` = :actionIdx", ['actionIdx' => $actionIdx]);

$actionType = 100;
$dashboardId = 0;
$result = $db->select("SELECT `actionType`, `dashboardId` FROM `user_actions` 
                              WHERE `actionIdx` = :actionIdx", ['actionIdx' => $actionIdx]);
if (count($result) > 0) {
    $row = $result[0];
    if (!is_null($row["dashboardId"])) {
        $actionType = $row["actionType"];
        $dashboardId = $row["dashboardId"];
    }
}
if ($actionType == 0) {
    $db->exe("UPDATE `dashboards` SET `actionCount` = actionCount - 1 
                      WHERE `id` = :id", ['id' => $dashboardId]);
}

$ret = new stdClass();
$ret->error = 0;
echo json_encode($ret);
?>