<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Facebook\Facebook;

function initFbClient($appId, $appSecret)
{

    return new Facebook([
        'app_id' => $appId,
        'app_secret' => $appSecret,
        'default_graph_version' => 'v3.0',
    ]);
}

function getPageAccessToken($client, $pageId, $refreshToken)
{
    $result = $client->get('/' . $pageId . '?fields=access_token', $refreshToken);

    return json_decode($result->getBody())->access_token ?? '';
}

