<?php
require_once(__DIR__ . '/RestClient.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../Mysql.php');

$db = new Mysql();

try {
    //Instead of 'login' and 'password' use your credentials from https://my.dataforseo.com/login
    $client = new RestClient('https://api.dataforseo.com/', null, 'login', 'password');
} catch (RestClientException $e) {
    echo "\n";
    print "HTTP code: {$e->getHttpCode()}\n";
    print "Error code: {$e->getCode()}\n";
    print "Message: {$e->getMessage()}\n";
    print  $e->getTraceAsString();
    echo "\n";
    exit();
}

$result = $db->SELECT("SELECT ownerID, description, id, keywords FROM dashboards WHERE keywords <> '[\"\"]' ");

$post_array = array();

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
                    // $word = 'some keyword';
                    $post_array[$my_unq_id] = array(
                        "language" => "en",
                        "loc_name_canonical" => "United States",
                        "key" => $word,
                        'pingback_url' => SITE_URL . 'dataforseo/kwrd_sw_write_result.php?taskId=$task_id'
                    );
                    $today = date("Y-m-d");
                    $queries[$my_unq_id] = "INSERT INTO rank_keyword_estimate (keyword, dashboard_id, post_id, dated, user_id) VALUES ('{$word}', {$key['id']}, {$my_unq_id}, '{$today}', {$key['ownerID']})";
                    break;
                }

            }
        } else {

        }

        break;
    }
}


// echo "<pre>";
// print_r($post_array);
// print_r($queries);
// echo "</pre>";

foreach ($queries as $key) {
    $db->exec($key);
}
// die();
if (count($post_array) > 0) {
    try {
        $task_post_result = $client->post('/v2/kwrd_sv_tasks_post', array('data' => $post_array));
        echo "<pre>";
        print_r($task_post_result);
        echo "</pre>";

        foreach ($task_post_result[results] as $key) {
            if ($key['status'] == 'ok') {
                $query = "UPDATE rank_keyword_estimate SET task_id = {$key['task_id']} WHERE post_id = {$key['post_id']}";
                $db->exec($query);
            } else {
                $query = "DELETE FROM rank_keyword_estimate WHERE post_id = {$key['post_id']} AND position is null";
                $db->exec($query);
            }
        }

        //do something with post results

    } catch (RestClientException $e) {
        echo "\n";
        print "HTTP code: {$e->getHttpCode()}\n";
        print "Error code: {$e->getCode()}\n";
        print "Message: {$e->getMessage()}\n";
        print  $e->getTraceAsString();
        echo "\n";
    }
}

$client = null;
?>