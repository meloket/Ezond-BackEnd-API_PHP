<?php
//You can download this file from here https://api.dataforseo.com/_examples/php/_php_RestClient.zip
require('RestClient.php');

$api_url = 'https://api.dataforseo.com/';
try {
    //Instead of 'login' and 'password' use your credentials from https://my.dataforseo.com/
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

try {
    // $post_array[] = array(
    // "language" => "en",
    // "loc_name_canonical"=> "United States",
    // "key" => "average page rpm adsense"
    // );
    // $post_array[] = array(
    // "language" => "en",
    // "loc_id" => 2840,
    // "key" => "adsense blank ads how long"
    // );
    $post_array[] = array(
        "language" => "en",
        "loc_id" => 2554,
        "key" => "Bulawebs"
    );

    $sv_post_result = $client->post('v2/kwrd_sv', array('data' => $post_array));
    echo "<pre>";
    print_r($sv_post_result);
    echo "</pre>";

    //do something with results

} catch (RestClientException $e) {
    echo "\n";
    print "HTTP code: {$e->getHttpCode()}\n";
    print "Error code: {$e->getCode()}\n";
    print "Message: {$e->getMessage()}\n";
    print  $e->getTraceAsString();
    echo "\n";
    exit();
}

$client = null;
?>