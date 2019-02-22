<?php

$url = "http://www.ezond.com";
if (isset($_GET['url'])) $url = $_GET['url'];


function getMyData($site)
{
    return @file_get_contents($site);
}

function getMyDatas($site)
{
    $ch = curl_init($site);

    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => "",
        CURLOPT_AUTOREFERER => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_MAXREDIRS => 10,
    );
    curl_setopt_array($ch, $options);
    $data = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo $httpCode . "============";

    $error = curl_errno($ch) ? curl_error($ch) : '';
    echo $error;

    curl_close($ch);

    return $data;
}

echo "#########" . getMyDatas($url) . "#############";

?>