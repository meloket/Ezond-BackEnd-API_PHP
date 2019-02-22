<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

if (empty($_GET['user_id'])) {
    exit;
}
$user_id = $_GET['user_id'];

$ret = new stdClass();

$notice_arr = array();
$dash_notis_arr = array();

function changeDateString($__date_str, $__date_str_now)
{
    $__ret_val = 0;
    $__ret_str = 0;

    $__date_now_val = strtotime($__date_str_now);
    $__date_str_val = strtotime($__date_str);
    if ($__date_now_val - $__date_str_val >= 86400) {
        $__days_ago = round(($__date_now_val - $__date_str_val) / 86400);
        if ($__days_ago >= 365) {
            $__ret_val = round($__days_ago / 365);
            if ($__ret_val > 1) $__ret_str = "years";
            else $__ret_str = "year";
        } else if ($__days_ago >= 30) {
            $__ret_val = round($__days_ago / 30);
            if ($__ret_val > 1) $__ret_str = "months";
            else $__ret_str = "month";
        } else {
            $__ret_val = $__days_ago;
            if ($__ret_val > 1) $__ret_str = "days";
            else $__ret_str = "day";
        }
    } else {
        $__days_ago = round(($__date_now_val - $__date_str_val) / 60);
        if ($__days_ago >= 60) {
            $__ret_val = round($__days_ago / 60);
            if ($__ret_val > 1) $__ret_str = "hours";
            else $__ret_str = "hour";
        } else {
            $__ret_val = $__days_ago;
            if ($__ret_val > 1) $__ret_str = "mins";
            else {
                $__ret_val = 1;
                $__ret_str = "min";
            }
        }
    }

    return $__ret_val . " " . $__ret_str;
}

$__dash_obj = __get_dashboard_ids($user_id);
$__dash_ids = $__dash_obj->dashboards_ids;
$dashIds = explode(',', $__dash_ids);

$data = $db->prepareDataForSqlInClause($dashIds, 'dashboardId_');
$inStr = $data['inStr'];
$selectData = $data['inData'];
$selectData['readMarks'] = "@{$user_id}@";

$sql = "SELECT a.*, b.company_name, NOW() AS current_t FROM users_notices a 
                  INNER JOIN dashboards b 
                          ON a.dashboardIdx = b.id 
                            WHERE a.dashboardIdx in ({$inStr}) 
                              AND INSTR(readMarks, :readMarks) = 0 
                              ORDER BY noticeDate DESC";

$result = $db->select($sql, $selectData);
unset($selectData['readMarks']);

$notice_count = count($result);
if ($notice_count == 1) {
    $row = $result[0];
    if (is_null($row["userIdx"])) $notice_count = 0;
}
$ret->notice_count = $notice_count;

for ($i = 0; $i < count($result); $i++) {
    $row = $result[$i];
    if (is_null($row["userIdx"])) continue;
    //if($i>3) break;
    $notice_obj = new stdClass();
    $notice_obj->title = $row["noticeTitle"];
    $notice_obj->time = changeDateString($row["noticeDate"], $row["current_t"]);
    $notice_obj->campaign = $row["company_name"];
    $notice_obj->noticeType = $row["noticeType"];
    $notice_obj->noticeIdx = $row["Id"];

    array_push($notice_arr, $notice_obj);
}

$sql = "SELECT DISTINCT a.noticeTitle, b.company_name, a.dashboardIdx FROM users_notices a 
                  INNER JOIN dashboards b 
                          ON a.dashboardIdx = b.id 
                            WHERE a.dashboardIdx IN ({$inStr}) 
                              AND datediff(CURRENT_DATE, Date(noticeDate))<7 
                              AND noticeType=0 
                              ORDER BY noticeDate DESK";
$result = $db->select($sql, $selectData);

for ($i = 0; $i < count($result); $i++) {
    $row = $result[$i];
    if (is_null($row["noticeTitle"])) continue;
    $notice_obj = new stdClass();
    $notice_obj->title = $row["noticeTitle"];
    $notice_obj->dashboardId = $row["dashboardIdx"];
    array_push($dash_notis_arr, $notice_obj);
}

$ret->notifications = $notice_arr;

$ret->dash_notis = $dash_notis_arr;
echo json_encode($ret);
?>