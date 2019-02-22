<?php

require_once 'csrest_general.php';

$authorize_url = CS_REST_General::authorize_url(
    'Client ID for your application',
    'Redirect URI for your application',
    'The permission level your application requires',
    'Optional state data to be included'
);


echo $authorize_url;

?>