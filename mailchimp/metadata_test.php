<html>
<head>
  <title>Oauth2 Tester</title>
</head>
<body>
      <pre>
<?php

require_once('MC_OAuth2Client.php');
require_once('MC_RestClient.php');
$session = array("access_token" => "255f80e58bd8e901bbba57e9a47302e6", "expires_in" => 0, "scope" => "", "base_domain" => "", "expires" => 1500280335, "refresh_token" => "", "secret" => "eba31e4fd129966b6c8fa09af66e78e8", "sig" => "c2a46e39bce5765090af44850df89d2f");


$rest = new MC_RestClient($session);
$data = $rest->getMetadata();
?>
</pre>
      Here are the results of the metadata call: <?= print_r($data, true) ?>

</body>
</html>
<?php

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://us16.api.mailchimp.com/3.0/reports");
curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "anystring:255f80e58bd8e901bbba57e9a47302e6-us16");

$json_data = curl_exec($ch);
$parsed_data = json_decode($json_data);
curl_close($ch);
/*
$parsed_data->reports[0]->emails_sent
$parsed_data->reports[0]->opens->opens_rate
$parsed_data->reports[0]->clicks->clicks_rate
$parsed_data->reports[0]->unsubscribed
$parsed_data->reports[0]->bounces->hard_bounces
$parsed_data->reports[0]->bounces->soft_bounces
*/
print_r($parsed_data);

?>