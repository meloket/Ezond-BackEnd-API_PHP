<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$user_id = $_GET['user_id'] ?? 0;
$dashboardId = $_GET['dashboardId'] ?? 0;
$actionDetail = $_POST['actionDetail'] ?? "New Task ...";
$taskOrder = $_POST['taskOrder'] ?? 999;

if ($user_id == 0 || $dashboardId == 0) {
    exit();
}

$updateSql = 'UPDATE `user_actions` 
                      SET `taskOrder` = taskOrder - 1 
                      WHERE `dashboardId` = :dashboardId
                        AND `taskOrder` <= :taskOrder';
$updateData = [
    'dashboardId' => $dashboardId,
    'taskOrder' => $taskOrder
];

$table = 'user_actions';
$insertData = [
    'userIdx' => $user_id,
    'actionTime' => date("Y-m-d H:i:s"),
    'actionContent' => 'Created a task',
    'actionType' => 2,
    'dashboardId' => $dashboardId,
    'actionDetail' => $actionDetail,
    'taskOrder' => $taskOrder,
];

$db->exe($updateSql, $updateData);
$db->insert($table, $insertData);

$actionIdx = $db->lastInsertId();
echo json_encode(array($actionIdx));

exit();

$tasks = array();
$strWhere = " where actionIdx='" . $actionIdx . "'";

$result = $db->select("SELECT a.*, concat(b.first_name, ' ', b.last_name) AS user_name, c.company_name FROM user_actions a INNER JOIN users b ON a.userIdx = b.id INNER JOIN dashboards c ON a.dashboardId = c.id " . $strWhere . " AND actionType = 2 ORDER BY actionTime DESC");
for ($i = 0; $i < count($result); $i++) {
    $row = $result[$i];
    if (is_null($row["userIdx"])) continue;
    $obj = new stdClass();
    $obj->actionIdx = $row["actionIdx"];
    $obj->userIdx = $row["userIdx"];
    $obj->userName = $row["user_name"];
    $obj->actionTime = $row["actionTime"];
    $obj->actionDisplayTime = date("j M", strtotime($row["actionTime"])) . " at " . date("h:i A", strtotime($row["actionTime"]));
    $obj->actionContent = $row["actionContent"];
    $obj->actionDetail = $row["actionDetail"];
    $obj->actionType = $row["actionType"];
    $obj->dashboardId = $row["dashboardId"];
    $obj->dashboard = $row["company_name"];
    $obj->taskProgress = $row["taskProgress"];

    array_push($tasks, $obj);
}
echo json_encode($tasks);
?>