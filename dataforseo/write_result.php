<?php

// $dbconn = pg_connect("host=78.46.124.49 dbname=challenger11 user=challenger11 password=Fl5oF2Fp55Ft2") or die('Could not connect: ' . pg_last_error());
require('RestClient.php');

require_once '../config.php';
require_once '../Mysql.php';

$db = new Mysql();

$api_url = 'https://api.dataforseo.com/';
try {
    $client = new RestClient($api_url, null, 'login', 'password');
} catch (RestClientException $e) {
    echo "\n";
    print "HTTP code: {$e->getHttpCode()}\n";
    print "Error code: {$e->getCode()}\n";
    print "Message: {$e->getMessage()}\n";
    print  $e->getTraceAsString();
    echo "\n";
    exit();
}


$task_get_result = $client->get('v2/rnk_tasks_get/' . $_GET['taskId']);

if (is_null($task_get_result['results']['organic'][0]['result_position'])) {
    $position = -1;
} else {
    $position = $task_get_result['results']['organic'][0]['result_position'];
}
$task_id = $_GET['taskId'];

$query = "UPDATE rank_tracking SET position = $position WHERE task_id = $task_id";
echo $query;
$db->exec($query);
// $result = pg_query($query) or var_dump('Ошибка запроса: ' . pg_last_error());
// pg_close($dbconn);
?>
