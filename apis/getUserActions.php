<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

if (empty($_GET['user_id']) && empty($_GET['dashboardId'])) {
    exit();
}
$user_id = $_GET['user_id'] ?? 0;
$page_id = $_GET['page_id'] ?? 0;
$page_count = 7;
$dashboardId = $_GET['dashboardId'] ?? 0;

$ret = new stdClass();
$ret->actions = array();
$ret->isMore = 0;

$__dash_obj = __get_dashboard_ids($user_id);
$__dash_ids = $__dash_obj->dashboards_ids;

$dashIds = explode(',', $__dash_ids);

$data = $db->prepareDataForSqlInClause($dashIds, 'dashboardId_');
$inStr = $data['inStr'];
$selectData = $data['inData'];

$strWhere = " where a.dashboardId in ({$inStr})";
if ($dashboardId > 0) {
    $strWhere = " where a.dashboardId = :dashboardId";
    $selectData = ['dashboardId' => $dashboardId];
}

$sql = "SELECT count(*) cn FROM user_actions a INNER JOIN dashboards c ON a.dashboardId = c.id {$strWhere}";
$result = $db->select($sql, $selectData);

$total_count = 0;
if ($result)
    if (count($result) > 0)
        $total_count = $result[0]["cn"];
if ($total_count > $page_count * ($page_id + 1)) $ret->isMore = 1;

$ownerName = "";
$ownerID = "";
$email = "";
$tasks = array();
$uploads = array();
$users = array();

function krsort_function($_arr)
{
    $new_arr = array();
    for ($i = count($_arr) - 1; $i >= 0; $i--)
        array_push($new_arr, $_arr[$i]);
    return $new_arr;
}

$str_uid = "0";

$generateUserDataQuery = function ($joinTable) {
    return "SELECT concat(b.first_name, ' ', b.last_name) AS user_name, a.ownerID, b.email FROM dashboards a 
                    INNER JOIN {$joinTable} b 
                            ON a.ownerID = b.id 
                              WHERE a.id = :id";
};

$generateUserNameQuery = function ($joinTable) {
    return "SELECT concat(b.first_name, ' ', b.last_name) AS user_name FROM dashboards a 
                    INNER JOIN {$joinTable} b 
                            ON a.assignerID = b.id 
                              WHERE a.id = :id";
};

$generateUserIdNameQuery = function ($selectTable, $inStr) {
    return "SELECT id, concat(first_name, ' ', last_name) AS user_name FROM {$selectTable} WHERE id IN ({$inStr})";
};

if (($dashboardId > 0) && ($page_id == 0)) {
    $result = $db->select($generateUserDataQuery('users'), ['id' => $dashboardId]);
    if (count($result) > 0) {
        $row = $result[0];
        if (!is_null($row["user_name"])) {
            $ownerName = $row["user_name"];
            $ownerID = $row["ownerID"];
            $email = $row["email"];
        }
    }

    $result = $db->select($generateUserDataQuery('agency_users'), ['id' => $dashboardId]);
    if (count($result) > 0) {
        $row = $result[0];
        if (!is_null($row["user_name"])) {
            if ($ownerName == "") {
                $ownerName = $row["user_name"];
                $ownerID = $row["ownerID"];
                $email = $row["email"];
            }
        }
    }
    if ($ownerName != "") {
        $userObj = new stdClass();
        $userObj->userIdx = $ownerID;
        $userObj->userName = $ownerName;
        $userObj->email = $email;
        array_push($users, $userObj);
    }

    $result = $db->select($generateUserNameQuery('users'), ['id' => $dashboardId]);
    if (count($result) > 0) {
        $row = $result[0];
        if (!is_null($row["user_name"])) {
            if ($row["user_name"] != "") {
                $ownerName = $row["user_name"];
            }
        }
    }

    $result = $db->select($generateUserNameQuery('agency_users'), ['id' => $dashboardId]);
    if (count($result) > 0) {
        $row = $result[0];
        if (!is_null($row["user_name"])) {
            if ($row["user_name"] != "") {
                $ownerName = $row["user_name"];
            }
        }
    }

    $sql = "SELECT concat(first_name, ' ', last_name) AS user_name, id, email, campaign_access, campaigns_allowed 
                  FROM agency_users 
                    WHERE parent_id = :parent_id 
                      AND role='staff'";
    $result = $db->select($sql, ['parent_id' => $ownerID]);

    for ($i = 0; $i < count($result); $i++) {
        $row = $result[$i];
        if (is_null($row["id"])) continue;
        $obj = new stdClass();
        $obj->userIdx = $row["id"];
        $obj->userName = $row["user_name"];
        $obj->email = $row["email"];

        if ($row["campaign_access"] == "restricted") {
            $campaigns_allowed = $row["campaigns_allowed"];
            $__arr_campaigns = json_decode($campaigns_allowed);
            if (in_array($dashboardId, $__arr_campaigns))
                array_push($users, $obj);
        } else
            array_push($users, $obj);
    }

    $sql = "SELECT a.*, c.company_name FROM user_actions a 
                    INNER JOIN dashboards c 
                            ON a.dashboardId = c.id {$strWhere} 
                              AND actionType = :actionType 
                              ORDER BY actionTime DESC";

    $result = $db->select($sql, array_merge($selectData, ['actionType' => 2]));
    for ($i = 0; $i < count($result); $i++) {
        $row = $result[$i];
        if (is_null($row["userIdx"])) continue;
        $str_uid .= "," . $row["userIdx"];
        $obj = new stdClass();
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
        $obj->taskAssigner = $row["taskAssigner"];
        $obj->taskOrder = $row["taskOrder"];

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
$ret->ownerName = $ownerName;
if ($user_id == 0) {
    $tasks = krsort_function($tasks);
    $uploads = krsort_function($uploads);
}
$ret->tasks = $tasks;
$ret->uploads = $uploads;

$actionTime = "";

$actions = array();

$sql = "SELECT a.*, c.company_name FROM user_actions a 
                INNER JOIN dashboards c 
                        ON a.dashboardId = c.id {$strWhere} 
                        ORDER BY actionTime DESC LIMIT :limit, :offset";

$selectData['limit'] = $page_count * $page_id;
$selectData['offset'] = $page_count;
$result = $db->select($sql, $selectData);

for ($i = 0; $i < count($result); $i++) {
    $row = $result[$i];
    if (is_null($row["userIdx"])) continue;
    $obj = new stdClass();
    $str_uid .= "," . $row["userIdx"];
    $obj->actionIdx = $row["actionIdx"];
    $obj->userIdx = $row["userIdx"];
    $obj->userName = "";
    if ($actionTime == "") $actionTime = $row["actionTime"];
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
$ret->actionTime = $actionTime;
$ret->actions = $actions;

$arr_users = array();

$uids = explode(',', $str_uid);
$data = $db->prepareDataForSqlInClause($uids, 'uid_');
$uidStr = $data['inStr'];
$selectData = $data['inData'];

$sql = $generateUserIdNameQuery('users', $uidStr);
$result = $db->select($sql, $selectData);

for ($i = 0; $i < count($result); $i++) {
    $row = $result[$i];
    if (!is_null($row["user_name"])) {
        $arr_users[$row["id"]] = $row["user_name"];
    }
}

$sql = $generateUserIdNameQuery('agency_users', $uidStr);
$result = $db->select($sql, $selectData);
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

$ret->users = $users;

echo json_encode($ret);
?>