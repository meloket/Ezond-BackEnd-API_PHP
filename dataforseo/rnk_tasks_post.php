<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=utf-8');
require('RestClient.php');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Mysql.php';


$dashboardtoupdate = $_GET['dash_id'];

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


$post_array = array();
$queries = array();


$today = date("Y-m-d");

$post_body = file_get_contents('php://input');
$pdata = json_decode($post_body);
$keywords = json_decode($pdata->keywords);


if (isset($keywords)) {
    $url = $pdata->url;
    $location = $pdata->location;
    $ownerID = $pdata->ownerID;
    $dashId = $pdata->dashId;

    foreach ($keywords as $word) {
        if ($word != "") {
            $my_unq_id = mt_rand(0, 30000000);
            $post_array[$my_unq_id] = array(
                'priority' => 1,
                'site' => $url,
                'se_id' => 1107,
                // "loc_name_canonical" => $location,
                'loc_id' => 2554,
                'key' => $word,
                'pingback_url' => SITE_URL . 'dataforseo/write_result.php?taskId=$task_id'
            );
            $today = date("Y-m-d");
            // $queries[$my_unq_id] = "INSERT INTO rank_tracking (keyword, dashboard_id, post_id, dated,  user_id) VALUES ('{$word}', {$dashId}, {$my_unq_id}, '{$today}', {$ownerID})";
        }
    }
} else {
    echo "MARKS2";
    $result = $db->SELECT("SELECT ownerID, description, id, keywords FROM dashboards WHERE keywords <> '[\"\"]' ");
}

if (!isset($keywords)) {
    echo "MARKS3";
    foreach ($result as $key) {
        $key['keywords'] = str_replace("\n", "", $key['keywords']);
        $url = json_decode($key['description'])->url;

        $location = json_decode($key['description'])->location;
        if (strpos($url, ".")) {
            foreach (json_decode($key['keywords']) as $word) {
                if ($word != "") {
                    $my_unq_id = mt_rand(0, 30000000);
                    $post_array[$my_unq_id] = array(
                        'priority' => 1,
                        'site' => $url,
                        'se_id' => 1107,
                        // "loc_name_canonical" => $location,
                        'loc_id' => 2554,
                        'key' => $word,
                        'pingback_url' => SITE_URL . 'dataforseo/write_result.php?taskId=$task_id'
                    );
                    $today = date("Y-m-d");
                    $queries[$my_unq_id] = "INSERT INTO rank_tracking (keyword, dashboard_id, post_id, dated,  user_id) VALUES ('{$word}', {$key['id']}, {$my_unq_id}, '{$today}', {$key['ownerID']})";
                }
            }
        } else {

        }
    }
}


foreach ($queries as $key) {
    // $db->exec($key);
}


$task_post_result = $client->post('v2/rnk_tasks_post', array('data' => $post_array));


echo "============================================================================";
echo "<pre>";
print_r($task_post_result);
// print_r($task_post_result);
foreach ($task_post_result[results] as $key) {
    if ($key['status'] == 'ok') {
        $query = "UPDATE rank_tracking SET task_id = {$key['task_id']} WHERE post_id = {$key['post_id']}";
        $db->exec($query);
    } else {
        $query = "DELETE FROM rank_tracking WHERE post_id = {$key['post_id']} AND position is null";
        $db->exec($query);
    }
    // print_r($key);
    echo "<br>";
}
echo "</pre>";

$client = null;
?>