<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$dashboardId = $_GET['campaign_id'] ?? 0;
$story = $_POST['story'] ?? "";

if ($dashboardId == 0) {
    exit();
}
$db->exe("UPDATE `dashboards` SET `story` = :story WHERE `id` = :id", ['story' => $story, 'id' => $dashboardId]);

$ret = new stdClass();
$ret->error = 0;
echo json_encode($ret);
?>