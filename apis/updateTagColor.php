<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$campaign_id = $_GET['campaign_id'] ?? 0;
$tagColor = $_GET['tagColor'] ?? "";

if ($campaign_id == 0) {
    exit();
}

$db->exe("UPDATE `dashboards` SET `tagColor` = :tagColor WHERE id = :id",
    ['tagColor' => $tagColor, 'id' => $campaign_id]
);
?>