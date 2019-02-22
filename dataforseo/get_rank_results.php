<?php

require_once '../config.php';
require_once '../Mysql.php';

$db = new Mysql();

$dashboard_id = $_GET[dashboard_id];


$results = $db->SELECT("SELECT dated, keyword, position FROM rank_tracking WHERE dashboard_id = {$dashboard_id}");

$return_val = "[";
$return_ar = array();
// print_r($results);
foreach ($results as $key) {
    array_push($return_ar, array('date' => $key[dated], 'keyword' => $key[keyword], 'position' => $key[position]));
    // $return_val .= "{'date':'".$key[dated]."', 'keyword': '".$key[keyword]."', 'position': '".$key[position]."'},";
    // print_r($key);
}
$return_val .= "]";

echo json_encode($return_ar);

// print_r($return_val);

?>