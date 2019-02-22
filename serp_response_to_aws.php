<?php

require_once (__DIR__ . '/config.php');
require_once (__DIR__ . '/aws/AwsS3.php');
require_once (__DIR__ . '/functions.php');

$isCli = php_sapi_name() == "cli";
if(!$isCli) {
    exit;
}

$startDate = formatDate($argv[1] ?? '2018-06-18');
$endDate = formatDate($argv[2] ?? date('Y-m-d', strtotime('today')));
$loopDate = formatDate($startDate . ' -1 day');

$s3 = new AwsS3();
$path = '';

do{
    $loopDate = formatDate($loopDate . ' +1 day');

    echo "Processed data for " . $loopDate . "\n";

    $sql = 'SELECT DISTINCT `dashboard_id` FROM `serp_response` WHERE `day` = ?';
    $sth = $db->prepare($sql);
    $sth->execute([$loopDate]);
    $dashboardIds = $sth->fetchAll(PDO::FETCH_COLUMN);

    foreach ($dashboardIds as $id) {
        $query = 'SELECT * FROM `serp_response` WHERE `day` = ? AND dashboard_id = ?';
        $sth = $db->prepare($query);
        $sth->execute([$loopDate, $id]);
        $serpResponses = $sth->fetchAll(PDO::FETCH_ASSOC);

        foreach ($serpResponses as $row) {
            $path = generatePathToSerpResponseFile($id, $row['day'], $row['task_id']);
            $s3->createFileByContent($path, $row['data']);
        }
        echo "\t- data for dashboard " . $id . " processed successfully.\n";
    }

    echo "Data processing for " . $loopDate . " completed \n";
} while ($loopDate != $endDate);

echo "Done.\n";
