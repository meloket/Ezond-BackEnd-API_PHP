<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once (__DIR__ . '/../config.php');
require_once (__DIR__ . '/../aws/AwsS3.php');

$actionIdx = $_GET['actionIdx'] ?? 0;
if ($actionIdx == 0) {
    exit();
}

$result = $db->select("SELECT * FROM `user_actions` WHERE `actionIdx` = :actionIdx", ['actionIdx' => $actionIdx]);
if (count($result) > 0) {
    $row = $result[0];
    if (!is_null($row['actionDetail'])) {
        $realFileName = $row['actionDetail'];
        $fileName = $row['filePath'];

        $s3 = new AwsS3();
        $file = $s3->getFile('uploads/' . $fileName);
        if ($file) {
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $file['ContentType']);
            header('Content-Disposition: attachment; filename="' . basename($realFileName) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $file['ContentLength']);
            echo $file['Body'];
            exit;
        }
    }
}
?>