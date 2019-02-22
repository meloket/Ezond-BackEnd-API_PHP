<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$user_id = $_GET['user_id'] ?? 0;
$actionTime = $_GET['actionTime'] ?? "2017-01-01 00:00:00";
$dashboardId = $_GET['dashboardId'] ?? 0;

if (($user_id == 0) && ($dashboardId == 0)) {
    exit();
}

$ret = new stdClass();
$ret->actions = array();

$__dash_obj = __get_dashboard_ids($user_id);
$__dash_ids = $__dash_obj->dashboards_ids;
$dashIds = explode(',', $__dash_ids);

$data = $db->prepareDataForSqlInClause($dashIds, 'dashboardId_');
$inStr = $data['inStr'];
$selectData = $data['inData'];

$strWhere = " where a.dashboardId in (" . $inStr . ")";
if ($dashboardId > 0) {
    $strWhere = " where a.dashboardId = :dashboardId";
    $selectData = ['dashboardId' => $dashboardId];
}

$sqlPrefix = "SELECT a.*, c.company_name FROM `user_actions` a 
                  INNER JOIN `dashboards` c 
                          ON a.dashboardId = c.id {$strWhere} ";

function krsort_function($_arr)
{
    $new_arr = array();
    for ($i = count($_arr) - 1; $i >= 0; $i--)
        array_push($new_arr, $_arr[$i]);
    return $new_arr;
}

$tasks = array();
$uploads = array();
$str_uid = "0";

if ($dashboardId > 0) {
    $sql = $sqlPrefix . "AND `actionType` = :actionType ORDER BY `actionTime` DESC";
    $selectData['actionType'] = 2;
    $result = $db->select($sql, $selectData);

    for ($i = 0; $i < count($result); $i++) {
        $row = $result[$i];
        if (is_null($row["userIdx"])) continue;
        $obj = new stdClass();
        $str_uid .= "," . $row["userIdx"];
        $obj->actionIdx = $row["actionIdx"];
        $obj->userIdx = $row["userIdx"];
        $obj->userName = "";
        $obj->actionTime = $row["actionTime"];
        $obj->actionDisplayTime = date("j M", strtotime($row["actionTime"])) . " at " . date("h:i A", strtotime($row["actionTime"]));
        $obj->actionContent = $row["actionContent"];
        $obj->actionDetail = $row["actionDetail"];
        $obj->actionType = $row["actionType"];
        $obj->dashboardId = $row["dashboardId"];
        $obj->dashboard = $row["company_name"];
        $obj->taskProgress = $row["taskProgress"];
        $obj->filePath = $row["filePath"];
        $obj->taskOrder = $row["taskOrder"];
        $obj->taskAssigner = $row["taskAssigner"];

        array_push($tasks, $obj);
    }

    $selectData['actionType'] = 1;
    $result = $db->select($sql, $selectData);

    for ($i = 0; $i < count($result); $i++) {
        $row = $result[$i];
        if (is_null($row["userIdx"])) continue;
        $obj = new stdClass();
        $str_uid .= "," . $row["userIdx"];
        $obj->actionIdx = $row["actionIdx"];
        $obj->userIdx = $row["userIdx"];
        $obj->userName = "";
        $obj->actionTime = $row["actionTime"];
        $obj->actionDisplayTime = date("j M", strtotime($row["actionTime"])) . " at " . date("h:i A", strtotime($row["actionTime"]));
        $obj->actionContent = $row["actionContent"];
        $obj->actionDetail = $row["actionDetail"];
        $obj->actionType = $row["actionType"];
        $obj->dashboardId = $row["dashboardId"];
        $obj->dashboard = $row["company_name"];
        $obj->taskProgress = $row["taskProgress"];
        $obj->filePath = $row["filePath"];
        $obj->taskOrder = $row["taskOrder"];

        array_push($uploads, $obj);
    }
    unset($selectData['actionType']);
}
if ($user_id == 0) {
    $tasks = krsort_function($tasks);
    $uploads = krsort_function($uploads);
}
$ret->tasks = $tasks;
$ret->uploads = $uploads;

$actions = array();

$sql = $sqlPrefix . "AND `actionTime` > :actionTime ORDER BY `actionTime` DESC";
$selectData['actionTime'] = $actionTime;
$result = $db->select($sql, $selectData);

for ($i = 0; $i < count($result); $i++) {
    $row = $result[$i];
    if (is_null($row["userIdx"])) continue;
    $obj = new stdClass();
    $str_uid .= "," . $row["userIdx"];
    $obj->actionIdx = $row["actionIdx"];
    $obj->userIdx = $row["userIdx"];
    $obj->userName = "";
    $obj->actionTime = $row["actionTime"];
    $obj->actionDisplayTime = date("j M", strtotime($row["actionTime"])) . " at " . date("h:i A", strtotime($row["actionTime"]));
    $obj->actionContent = $row["actionContent"];
    $obj->actionDetail = $row["actionDetail"];
    //if(strlen($obj->actionDetail) > 30) $obj->actionDetail = substr($obj->actionDetail, 0, 30)." ...";
    $obj->actionType = $row["actionType"];
    $obj->dashboardId = $row["dashboardId"];
    $obj->dashboard = $row["company_name"];
    $obj->taskProgress = $row["taskProgress"];
    $obj->filePath = $row["filePath"];
    $obj->taskOrder = $row["taskOrder"];

    array_push($actions, $obj);
}
if ($user_id == 0) {
    $actions = krsort_function($actions);
}
$ret->actions = $actions;

$arr_users = array();

$uids = explode(',', $str_uid);
$data = $db->prepareDataForSqlInClause($uids, 'uid_');
$uidStr = $data['inStr'];
$selectData = $data['inData'];

$generateSqlQuery = function ($table) use ($uidStr) {
    return "SELECT `id`, concat(first_name, ' ', last_name) AS `user_name` FROM `{$table}` WHERE `id` IN ({$uidStr})";
};

$result = $db->select($generateSqlQuery('users'), $selectData);
for ($i = 0; $i < count($result); $i++) {
    $row = $result[$i];
    if (!is_null($row["user_name"])) {
        $arr_users[$row["id"]] = $row["user_name"];
    }
}

$result = $db->select($generateSqlQuery('agency_users'), $selectData);
for ($i = 0; $i < count($result); $i++) {
    $row = $result[$i];
    if (!is_null($row["user_name"])) {
        if (!isset($arr_users[$row["id"]])) $arr_users[$row["id"]] = $row["user_name"];
    }
}

for ($i = 0; $i < count($ret->tasks); $i++) {
    $ret->tasks[$i]->userName = $arr_users[$ret->tasks[$i]->userIdx];
}

for ($i = 0; $i < count($ret->uploads); $i++) {
    $ret->uploads[$i]->userName = $arr_users[$ret->uploads[$i]->userIdx];
}

for ($i = 0; $i < count($ret->actions); $i++) {
    $ret->actions[$i]->userName = $arr_users[$ret->actions[$i]->userIdx];
}
echo json_encode($ret);
?>