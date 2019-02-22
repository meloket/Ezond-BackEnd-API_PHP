<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once (__DIR__ . '/../aws/AwsS3.php');
require_once (__DIR__ . '/../functions.php');

$userId = $_GET['user_id'] ?? 0;
if(!$userId) {
    exit;
}

$fileNamesPrefixes = getPhotoNamePrefixes();
$s3 = new AwsS3();

foreach ($fileNamesPrefixes as $prefix) {
    $file = $prefix . $userId . '_2.jpg';
    $s3->removeFile($file);
}

?>