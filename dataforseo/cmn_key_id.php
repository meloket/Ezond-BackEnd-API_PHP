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
    $key_get_result = $client->get('v2/cmn_key_id/real-time%20geolocation%20service%20with%20node%2ejs');
    print_r($key_get_result);

    //do something with result

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