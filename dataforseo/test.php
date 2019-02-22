<?php

require_once(__DIR__ . '/../config.php');

$strsql = sprintf("SELECT keywords, id FROM dashboards");
$result = $db->select($strsql);
for ($j = 0; $j < count($result); $j++) {
    $row = $result[$j];
    if (isset($row["keywords"])) {
        $keywords = $row["keywords"];
        if ($keywords != "") {
            $keywords = str_replace(chr(10), '', $keywords);
            $keywords_arr = json_decode($keywords);
            for ($i = 0; $i < count($keywords_arr); $i++) {
                $query_keyword = $keywords_arr[$i];
                echo $query_keyword . "<br>";
            }
        }
    }
}


?>