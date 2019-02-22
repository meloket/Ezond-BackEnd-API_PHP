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

//different keys and locations
try {
    $post_array = array();

    $post_array[] = array(
        "language" => "en",
        "loc_name_canonical" => "United States",
        "key" => "average page rpm adsense"
    );

    echo "<pre>";
    print_r($post_array);
    echo "</pre>";
    $sv_post_result = $client->post('v2/kwrd_for_keywords', array('data' => $post_array));
    print_r($sv_post_result);

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


//different keys and one location and one language
try {
    $post_array = array();

    $post_array[] = array(
        "language" => "en",
        "loc_id" => 2840,
        "keys" => array("seo marketing", "seo agency", "marketing agency")
    );

    $sv_post_result = $client->post('v2/kwrd_for_keywords', array('data' => $post_array));
    print_r($sv_post_result);

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
