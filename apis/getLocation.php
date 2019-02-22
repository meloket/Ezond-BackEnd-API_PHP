<?php

// {"ip":"217.12.208.192","country_code":"NL","country_name":"Netherlands","region_code":"FL","region_name":"Provincie Flevoland","city":"Dronten","zip_code":"8253pj","time_zone":"Europe/Amsterdam"}

$ip = "";
if (isset($_GET['data'])) $ip = $_GET['data'];
if ($ip != "") {
    $data = file_get_contents("http://freegeoip.net/json/" . $ip);
    $ret_obj = json_decode($data);
    if (json_last_error() === JSON_ERROR_NONE) {

        if ($ret_obj) {
            $region_name = $ret_obj->city;
            if ($region_name != "") {
                if ($ret_obj->region_name != "")
                    $region_name .= " " . $ret_obj->region_name;
            } else $region_name = $ret_obj->region_name;

            if ($region_name != "") {
                if ($ret_obj->country_name != "")
                    $region_name .= ", " . $ret_obj->country_name;
            } else $region_name = $ret_obj->country_name;
            echo $region_name;
        }
    }
}

?>