<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once(__DIR__ . '/../config.php');

$parent_id = $_GET['parent_id'] ?? 0;

if($parent_id == 0) {
    exit();
}

$result = $db->select("SELECT * FROM `agency_users` WHERE `parent_id` = :parent_id", ['parent_id' => $parent_id]);
$users = array();

if($result) {
    foreach ($result as $row) {
        $obj = new stdClass();
        $obj->id= $row["id"];
        $obj->campaign_access = $row["campaign_access"];
        $obj->campaigns_allowed = $row["campaigns_allowed"];
        array_push($users, $obj);
    }
}

echo json_encode($users);
?>