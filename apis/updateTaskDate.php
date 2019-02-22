<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$actionIdx = $_GET['actionIdx'] ?? 0;
$taskDate = $_GET['taskDate'] ?? "";

if ($actionIdx == 0) {
    exit();
}

$db->exe("UPDATE `user_actions` 
                  SET `filePath` = :filePath 
                      WHERE `actionIdx` = :actionIdx", ['filePath' => $taskDate, 'actionIdx' => $actionIdx]);

$ret = new stdClass();
$ret->error = 0;
echo json_encode($ret);
?>