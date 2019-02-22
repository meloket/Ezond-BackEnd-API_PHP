<?php
//You can download this file from here https://api.dataforseo.com/_examples/php/_php_RestClient.zip
require('inc/RestClient.php');

function _in_logit_GET($id_message, $data)
{
    @file_put_contents(__DIR__ . "/pingback_url_example.log", PHP_EOL . date("Y-m-d H:i:s") . ": " . $id_message . PHP_EOL . "---------" . PHP_EOL . print_r($data, true) . PHP_EOL . "---------", FILE_APPEND);
}

if (!empty($_GET["task_id"])) {
    try {
        //Instead of 'login' and 'password' use your credentials from https://my.dataforseo.com/
        $client = new RestClient('https://api.dataforseo.com', null, 'login', 'password');
    } catch (RestClientException $e) {
        echo "\n";
        print "HTTP code: {$e->getHttpCode()}\n";
        print "Error code: {$e->getCode()}\n";
        print "Message: {$e->getMessage()}\n";
        print  $e->getTraceAsString();
        echo "\n";
        exit();
    }

    try {
        $serp_result = $client->get('v2/srp_tasks_get/' . $_GET["task_id"]);
    } catch (RestClientException $e) {
        echo "\n";
        print "HTTP code: {$e->getHttpCode()}\n";
        print "Error code: {$e->getCode()}\n";
        print "Message: {$e->getMessage()}\n";
        print  $e->getTraceAsString();
        echo "\n";
        exit();
    }
    if ($serp_result["status"] == "ok") {
        _in_logit_GET($serp_result["results"]["organic"][0]["task_id"], 'ready');
        /* do something with results
        foreach($serp_result["results"]["organic"] as $tasks_row) {
        _in_logit_GET($tasks_row["task_id"], $tasks_row);
        }
        */
    }
    echo "ok";
} else {
    echo "empty GET";
}
?>
