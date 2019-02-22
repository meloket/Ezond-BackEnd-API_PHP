<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once (__DIR__ . '/../aws/AwsS3.php');

$userId = $_GET['user_id'] ?? 0;
if(!$userId) {
    exit;
}

$targetFile = 'photo/agency_' . $userId . '_2.jpg';
$sourceFile = $_FILES["uploadedFile"]["tmp_name"] ?? '';

if($sourceFile) {
    $s3 = new AwsS3();
    $s3->uploadFile($targetFile, $sourceFile);
}

?>