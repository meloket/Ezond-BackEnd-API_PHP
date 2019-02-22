<?php
ini_set('max_execution_time', 100000);
error_reporting(E_STRICT | E_ALL);

require_once '../config.php';

$data = $_GET['data'] ?? "";
if ($data == "") {
    exit();
}

$arrWidget = explode(":", $data);
for ($i = 0; $i < count($arrWidget); $i++) {
    $widgetInfo = $arrWidget[$i];
    $arrInfos = explode(",", $widgetInfo);

    $sql = "UPDATE `widgets` 
                    SET `positionRow` = :positionRow, `positionCol` = :positionCol 
                    WHERE `id` = :id";

    $sqlData = [
        'positionRow' => $arrInfos[1],
        'positionCol' => $arrInfos[2],
        'id' => $arrInfos[0],
    ];
    $db->exe($sql, $sqlData);
}

$ret = new stdClass();
$ret->success = 1;
echo json_encode($ret);
?>