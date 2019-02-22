<?php
require_once __DIR__ . '/RestClient.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../aws/AwsS3.php';
require_once __DIR__ . '/../functions.php';

$client = new RestClient($apiUrl, null, $dfsUsername, $dfsPassword);

$taskId = $_GET['taskId'] ?? null;

$sql = 'SELECT dashboard_id FROM rank_tracking WHERE task_id = ?';
$sth = $db->prepare($sql);
$sth->execute([$taskId]);
$dashboardId = $sth->fetch()['dashboard_id'] ?? null;

if (empty($dashboardId)) {
    exit();
}

$sql = 'SELECT description FROM dashboards WHERE id = ?';
$sth = $db->prepare($sql);
$sth->execute([$dashboardId]);
$description = $sth->fetch()['description'];

$dasboardUrl = json_decode($description)->url;
$dasboardHost = parse_url($dasboardUrl, PHP_URL_HOST) ?? $dasboardUrl;
$sql = 'UPDATE rank_tracking SET position = ?, se_name = ? WHERE task_id = ?';
$position = -1;
$seName = NULL;
$tasks_get_result = new stdClass();

try {
    $tasks_get_result = $client->get('v2/srp_tasks_get/' . $taskId);
    if (!empty($tasks_get_result['results']['organic'])) {
        $position = -1;

        foreach ($tasks_get_result['results']['organic'] as $result) {
            $hostResultUrl = parse_url($result['result_url'], PHP_URL_HOST);

            $seName = explode(':', $result['post_id'])[1];
            $seName = str_replace('_se_id', '', $seName);
            preg_match('~' . $dasboardHost . '~', $hostResultUrl, $matches);

            if (!empty($matches)) {
                $position = $result['result_position'];
                break;
            }
        }
    }
} catch (\Exception $e) {
}
$data = [$position, $seName, $taskId];
$sth = $db->prepare($sql);
$sth->execute($data);

$s3 = new AwsS3();
$path = generatePathToSerpResponseFile($dashboardId, date("Y-m-d"), $taskId);
$s3->createFileByContent($path, json_encode($tasks_get_result));

$client = null;

