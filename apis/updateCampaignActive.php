<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$user_id = $_GET['user_id'] ?? 0;
$campaign_id = $_GET['campaign_id'] ?? 0;
$campaign_active = $_GET['campaign_active'] ?? 0;
$actionDetail = $_GET['actionDetail'] ?? '';

if ($user_id == 0 || $campaign_id == 0 || $campaign_active == 0) {
    exit();
}

$db->exe("UPDATE `dashboards` SET `campaignStatus` = :campaignStatus WHERE id = :id",
    ['campaignStatus' => $campaign_active, 'id' => $campaign_id]
);

$campaignStatus = "Normal";
if ($campaign_active == 2) $campaignStatus = "Review Required";
else if ($campaign_active == 3) $campaignStatus = "Urgent Attention";

$insertData = [
    'userIdx' => $user_id,
    'actionTime' => date("Y-m-d H:i:s"),
    'actionContent' => 'Changed campaign status to ' . $campaignStatus,
    'actionType' => 4,
    'dashboardId' => $campaign_id,
    'actionDetail' => $actionDetail,
];
$db->insert('user_actions', $insertData);
?>