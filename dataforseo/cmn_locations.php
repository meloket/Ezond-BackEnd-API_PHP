<?php
header('Access-Control-Allow-Origin: *');
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

$location = $_GET['location'];

// echo "$location";
try {
    $loc_get_result = $client->get('v2/cmn_locations');
    echo "<pre>";
    print_r($loc_get_result);
    echo "</pre>";
    die();
    if ($location) {
        $bestcompatibly = 0;
        $bestaddress = 0;
        $bestaddress_id = 0;
        $bestaddress_iso = 'us';
        foreach ($loc_get_result['results'] as $key) {
            similar_text($location, $key['loc_name'], $result);
            if ($result > $bestcompatibly) {
                // echo $key[loc_name]." : ".$result."<br>";

                $bestcompatibly = $result;
                $bestaddress = $key['loc_name'];
                $bestaddress_iso = $key['loc_country_iso_code'];
                $bestaddress_id = $key['loc_id'];
            }
        }

        echo $bestaddress_id . ":" . $bestaddress_iso;
    } else {
        echo "<pre>";
        print_r($loc_get_result);
        echo "</pre>";
    }


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
