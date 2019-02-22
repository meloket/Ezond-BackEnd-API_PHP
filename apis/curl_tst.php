<?php
require_once(__DIR__ . '/../config.php');

$url = "https://127.0.0.1/facebook/ads_test2.php";

echo "KEK";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
$content = curl_exec($ch);
curl_close($ch);
echo $content;

$content2 = file_get_contents(SITE_URL . "facebook/ads_test2.php");

echo $content2;

echo "KU";

?>