<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$user_id = $_GET['user_id'] ?? 0;
$noticeIdx = $_GET['noticeIdx'] ?? 0;

if ($noticeIdx === 0) {
    exit();
}
$user_mask = "@{$user_id}@";

$sql = "UPDATE `users_notices` 
               SET `readMarks` = concat(readMarks, :user_mask) 
               WHERE `Id` = :Id 
                  AND INSTR(readMarks, :user_mask) = 0";

$data = [
    'user_mask' => $user_mask,
    'Id' => $noticeIdx,
];

$db->exe($sql, $data);

$ret = new stdClass();
$ret->error = 0;
echo json_encode($ret);
?>