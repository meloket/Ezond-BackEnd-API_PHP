<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
/*
    require_once '../config.php';

	$user_id = 0;
	$dashboardId = 0;
	$msgContent = "";

	if(isset($_POST['user_id'])) $user_id = $_POST['user_id'];
	if(isset($_POST['dashboardId'])) $dashboardId = $_POST['dashboardId'];
	if(isset($_POST['msgContent'])) $msgContent = $_POST['msgContent'];

    if($user_id == 0) exit();
    if($dashboardId == 0) exit();
    if($msgContent == "") exit();

	$db->__exec__("insert into users_notices (userIdx, dashboardIdx, noticeTitle, noticeDate) values ('".$user_id."', '".$dashboardId."', '".$msgContent."', NOW()); ");
*/
$ret = new stdClass();
$ret->error = 0;
echo json_encode($ret);
?>