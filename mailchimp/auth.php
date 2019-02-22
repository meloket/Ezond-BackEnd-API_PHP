<?php
require_once('MC_OAuth2Client.php');

$client = new MC_OAuth2Client($_GET['userID']);
echo $client->getLoginUri();
?>