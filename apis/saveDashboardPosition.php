<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$posData = $_GET['posData'] ?? "";
$posData = $_POST['posData'] ?? $posData;

if ($posData == "") {
    exit();
}

// 15,0,1:64,0,4:90,5,0:60,5,2

$arrData = explode(":", $posData);
for ($i = 0; $i < count($arrData); $i++) {
    $arrPos = explode(",", $arrData[$i]);
    $dashboardId = $arrPos[0];
    $posX = $arrPos[1];
    $posY = $arrPos[2];
    $db->exe("UPDATE `dashboards` SET `posX` = :posX, posY = :posY WHERE id = :id",
        ['posX' => $posX, 'posY' => $posY, 'id' => $dashboardId]
    );
}

$ret = new stdClass();
$ret->error = 0;

echo json_encode($ret);
?>