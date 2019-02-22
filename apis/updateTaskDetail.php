<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$actionIdx = $_GET['actionIdx'] ?? 0;
$actionDetail = $_GET['actionDetail'] ?? "";
$taskOrder = $_POST['taskOrder'] ?? 999;

if ($actionIdx == 0) {
    exit();
}

$sql = "UPDATE `user_actions` 
                SET `actionDetail` = :actionDetail, 
                    `taskOrder` = :taskOrder 
                    WHERE `actionIdx` = :actionIdx";

$data = [
    'actionDetail' => $actionDetail,
    'taskOrder' => $taskOrder,
    'actionIdx' => $actionIdx,
];
$db->exe($sql, $data);

$ret = new stdClass();
$ret->error = 0;
echo json_encode($ret);
?>