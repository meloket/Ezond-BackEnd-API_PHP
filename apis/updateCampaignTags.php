<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$campaign_id = $_GET['campaign_id'] ?? 0;
$description = $_GET['description'] ?? "";

if ($campaign_id == 0 || $description == "") {
    exit();
}

$db->exe("UPDATE `dashboards` SET `description` = :description WHERE `id` = :id",
    ['description' => $description, 'id' => $campaign_id]
);
?>