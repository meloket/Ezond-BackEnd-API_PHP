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

$domain = $_GET['domain'];
$iso = $_GET['iso'];

$result = array();
try {


    $kw_get_result = $client->get("v2/kwrd_for_domain/'$domain'/" . $iso . "/en");

    for ($i = 0; $i < 20; $i++) {
        array_push($result, $kw_get_result[results][$i]['key']);
    }
    // foreach ($kw_get_result[results] as $key) {
    // array_push($result, $key['key']);
    // }
    echo json_encode($result);

} catch (RestClientException $e) {
    echo "\n";
    print "HTTP code: {$e->getHttpCode()}\n";
    print "Error code: {$e->getCode()}\n";
    print "Message: {$e->getMessage()}\n";
    print  $e->getTraceAsString();
    echo "\n";
    exit();
}

//for page, use '
// try {
// 	$kw_get_result = $client->get("v2/kwrd_for_domain/'https://ranksonic.com/generate-keywords.html'/us/en");
// 	print_r($kw_get_result);

// 	//do something with results

// } catch (RestClientException $e) {
// 	echo "\n";
// 	print "HTTP code: {$e->getHttpCode()}\n";
// 	print "Error code: {$e->getCode()}\n";
// 	print "Message: {$e->getMessage()}\n";
// 	print  $e->getTraceAsString();
// 	echo "\n";
// 	exit();
// }

$client = null;
?>