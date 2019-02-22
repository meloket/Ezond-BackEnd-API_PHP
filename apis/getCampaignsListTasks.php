<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once(__DIR__ . '/../config.php');

$idsList = $_GET['list'] ?? '';
$dashboardIds = explode(',', $idsList);

$data = $db->prepareDataForSqlInClause($dashboardIds, 'dashboardId_');

$ret = new stdClass();
$ret->campcount= array();

$strsql = sprintf("SELECT count(*) AS count, dashboardId FROM user_actions WHERE dashboardId IN ({$data['inStr']}) AND actionType = 2 GROUP BY dashboardId");
$result = $db->select($strsql, $data['inData']);

if($result) {
    foreach ($result as $row) {
        $obj = new stdClass();
        $obj->dash_id = $row["dashboardId"];
        $obj->count = $row["count"];
        array_push($ret->campcount, $obj);
    }
}

echo json_encode($ret);
?>