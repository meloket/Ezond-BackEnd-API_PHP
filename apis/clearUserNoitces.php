<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$user_id = $_GET['user_id'] ?? 0;

if ($user_id == 0) {
    exit();
}

$__dash_obj = __get_dashboard_ids($user_id);
$__dash_ids = $__dash_obj->dashboards_ids;

$dashIds = explode(',', $__dash_ids);
$data = $db->prepareDataForSqlInClause($dashIds, 'dashboardId_');
$inStr = $data['inStr'];
$updateData = $data['inData'];

$sql = "UPDATE `users_notices` 
                   SET `readMarks` = concat(readMarks, :user_mask) 
                   WHERE dashboardIdx IN ({$inStr}) 
                    AND INSTR(readMarks, :user_mask) = 0";

$updateData['user_mask'] = "@{$user_id}@";
$db->exe($sql, $updateData);

$ret = new stdClass();
$ret->error = 0;
echo json_encode($ret);
?>