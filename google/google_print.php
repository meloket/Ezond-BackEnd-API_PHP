<?php
/*
Google Analystics, Google Adwords, Google Search Console, Google Sheets, Youtube
Array ( [state] => test [code] => 4/7EiDOrfwgc6F-noHfvqdqGQBo4dwKYQd3dFrSsMvBsM )
Array ( [error] => access_denied [state] => test )
*/

$response = file_get_contents($check_url);

echo sprintf("Code : <font color=mediumblue>$code</font> <hr>");
echo sprintf("OAuth Token : <font color=mediumblue>$access_token</font> <hr>");
echo sprintf("URL : <font color=mediumblue>$check_url</font> <hr>");
echo sprintf("Result : <font color=mediumblue>%s</font>", json_encode($response));
?>