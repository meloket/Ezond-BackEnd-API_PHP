<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once (__DIR__ . '/../config.php');
require_once (__DIR__ . '/../aws/AwsS3.php');

$userId = $_GET['user_id'] ?? 0;
$dashboardId = $_GET['dashboardId'] ?? 0;

if ($userId == 0 || $dashboardId == 0 || empty($_FILES)) {
    exit();
}

$targetPath = 'uploads/';

$fileObj = $_FILES['file'];
$realFileName = $fileObj['name'];
$fileName = date('Ymd_His') . random_int(0, 100);

$s3 = new AwsS3();
$s3->uploadFile($targetPath . $fileName, $fileObj['tmp_name']);

$sql = 'SELECT count(*) cn FROM `user_actions` 
                WHERE `userIdx` = :userIdx 
                  AND `actionType` = 1 
                  AND `dashboardId` = :dashboardId 
                  AND `actionDetail` = :actionDetail';
$data = [
    'userIdx' => $userId,
    'dashboardId' => $dashboardId,
    'actionDetail' => $realFileName,
];
$result = $db->select($sql, $data);
$checkDuplicate = true;
if (count($result) > 0 && isset($result[0]['cn']) && $result[0]['cn'] > 0) {
    $checkDuplicate = false;
}

if ($checkDuplicate && ($realFileName)) {
    $insertData = [
        'userIdx' => $userId,
        'actionTime' => date('Y-m-d H:i:s'),
        'actionContent' => 'Uploaded file',
        'actionType' => 1,
        'dashboardId' => $dashboardId,
        'actionDetail' => $realFileName,
        'filePath' => $fileName,
    ];
    $db->insert('user_actions', $insertData);
}
?>