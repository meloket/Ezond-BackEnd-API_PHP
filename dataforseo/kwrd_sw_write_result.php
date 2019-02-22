<?php

require('RestClient.php');

require_once '../config.php';
require_once '../Mysql.php';

$db = new Mysql();


try {
    $client = new RestClient('https://api.dataforseo.com', null, 'login', 'password');

    // #1 - get task_id list of ALL ready results
    //GET /v2/kwrd_sv_tasks_get
    $id = $_GET['id'];

    if (isset($_GET['id']))
        $req = "v2/kwrd_sv_tasks_get/$id";
    else
        $req = 'v2/kwrd_sv_tasks_get';
    echo "$req";
    $tasks_get_result = $client->get($req);
    echo "<pre>";
    print_r($tasks_get_result);
    echo "</pre>";
    // die();

    // $tasks_get_result = $client->get('v2/kwrd_sv_tasks_get/$id');

    foreach ($tasks_get_result[results] as $key) {
        foreach ($key[result] as $result) {
            $yeardata = serialize($result['ms']);
            echo "$yeardata";
            $query = "UPDATE rank_keyword_estimate SET volume = {$result['sv']}, cmp = {$result['cmp']} WHERE task_id = {$key['task_id']}";
            echo $query;
            $db->exec($query);
        }
    }
} catch (RestClientException $e) {
    echo "\n";
    print "HTTP code: {$e->getHttpCode()}\n";
    print "Error code: {$e->getCode()}\n";
    print "Message: {$e->getMessage()}\n";
    print  $e->getTraceAsString();
    echo "\n";
}

?>