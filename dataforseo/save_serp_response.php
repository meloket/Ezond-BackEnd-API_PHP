<?php

require_once __DIR__ . '/RestClient.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

$isCli = php_sapi_name() == "cli";

$startDate = '2018-06-05';
$endDate = date("Y-m-d");

if ($isCli) {
    $client = new RestClient($apiUrl, null, $dfsUsername, $dfsPassword);
    $date = $argv[1] ?? '2018-06-05';
    $endDate = $argv[2] ?? $endDate;

    if (!$date = strtotime($date)) {
        $date = strtotime('yesterday');
    }

    if (!$endDate = strtotime($endDate)) {
        $endDate = strtotime('today');
    }

    $date = date('Y-m-d', $date);
    $endDate = date('Y-m-d', $endDate);

    $query = 'SELECT dated, dashboard_id, task_id FROM rank_tracking 
                  WHERE keyword_id > 0 
                    AND dated >= ? 
                    AND dated <= ?';

    $sth = $db->prepare($query);
    $sth->execute([$date, $endDate]);
    $tasks = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sql = 'INSERT INTO serp_response (task_id, dashboard_id, day, data) VALUES';

    $query = [];
    $sqlData = [];

    foreach ($tasks as $item) {
        $checkQuery = 'SELECT task_id FROM serp_response WHERE task_id = ?';
        $st = $db->prepare($checkQuery);
        $st->execute([$item['task_id']]);
        $result = $st->fetch()['task_id'] ?? null;

        if ($result) {
            continue;
        }

        $response = new stdClass();
        try {
            $response = $client->get('v2/srp_tasks_get/' . $item['task_id']);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        $query[] = ' (?, ?, ?, ?)';
        $sqlData = array_merge($sqlData, [$item['task_id'], $item['dashboard_id'], $item['dated'], json_encode($response)]);
    }
    $sql = $sql . implode(',', $query);

    $sth = $db->prepare($sql);
    $sth->execute($sqlData);
}
