<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once(__DIR__ . '/../config.php');

$api_key = $_POST['api_key'] ?? '';
if($api_key == '') {
    exit();
}
$document_name = $_POST['docname'];

$userID = $_POST['userID'];
$networkID = 10;
$networkName = "Data studio";

$dashboardID = $_POST['campID'];
$userID = $_POST['userid'];

db_insert_func($userID, $dashboardID, "", "", $document_name, $api_key, $networkID, $networkName, "");