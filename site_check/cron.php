<?php

ini_set('max_execution_time', 100000);
error_reporting(E_STRICT | E_ALL);

require_once __DIR__ . '/../config.php';

$hour_val = intval(date("H"));

$time = date("Y-m-d H:i:s");
$isRunned = $hour_val > 3 ? 'no' : 'yes';

$sql = "INSERT INTO counting_cron (time, script, runned) values (?, ?, ?)";
$sqlData = [
    $time,
    'cron.php',
    $isRunned,
];

$sth = $db->prepare($sql);
$sth->execute($sqlData);

function getSslPage($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$sql = "SELECT id, lastUpdate, CURRENT_DATE cur_date FROM dashboards ORDER BY lastUpdate, id LIMIT 2";
$result = $db->select($sql);

if (count($result) > 0) {
    foreach ($result as $row) {
        $dashboardId = $row["id"] ?? 0;
        $lastUpdate = $row["lastUpdate"];
        $check_review = $lastUpdate != $row["cur_date"];

        if ($check_review) {
            $db->exe("UPDATE `dashboards` SET `lastUpdate` = CURRENT_DATE WHERE id = :id", ['id' => $dashboardId]);
            getSslPage(SITE_URL . "site_check/get_widget_data.php?dashboardID=" . $dashboardId);
        }
    }
}

?>
