<?php
require_once (__DIR__ . '/../aws/AwsS3.php');
require_once (__DIR__ . '/../functions.php');

$defaultFileName = "agency_blank.png";
$fileName = getImageNameForUser($_GET['user_id'] ?? 0, $_GET['preview'] ?? 0, 'agency_');

$s3 = new AwsS3();
$s3->sendResizeImage('/photo/', $fileName, $defaultFileName, 180, 70);

?>