<?php

require_once __DIR__ . '/../config.php';

$apiUrl = 'https://api.dataforseo.com/';
$dfsUsername = 'info@ezond.com';
$dfsPassword = 'mIdOgRRpJ6OEnt4R';

$defaultLanguage = 'English';

function buildKeywordBatchAndSqlForSerpTasks($searchEnginesIds, $keyword, $defaultLanguage, $dashboardId, $ownerId)
{
    $query = [];
    $queryData = $tasks = [];
    $today = date("Y-m-d");

    foreach ($searchEnginesIds as $key => $seId) {
        if (!empty($seId) && !empty($keyword['loc_id'])) {
            $myUnqId = uniqid() . ':' . $key;
            $tasks[$myUnqId] = [
                'priority' => 1,
                'se_id' => $seId,
                'se_language' => $defaultLanguage,
                'key' => mb_convert_encoding($keyword['keyword'], 'UTF-8'),
                'loc_id' => $keyword['loc_id'],
                'pingback_url' => SITE_URL . 'dataforseo/write_srp_result.php?taskId=$task_id',
            ];
            $query[] = ' (?, ?, ?, ?, ?, ?, ?, ?)';
            $queryData = array_merge($queryData, [$keyword['keyword'], $dashboardId, $myUnqId, $today, $ownerId, $keyword['address'], $seId, $keyword['id']]);
        }
    }

    return ['query' => $query, 'queryData' => $queryData, 'tasks' => $tasks];
}

function saveTasksData($results)
{
    global $db;

    foreach ($results as $result) {
        $sql = 'DELETE FROM rank_tracking WHERE post_id = ? AND position IS NULL';
        $sqlData = [$result['post_id']];

        if ($result['status'] == 'ok') {
            $sql = 'UPDATE rank_tracking SET task_id = ? WHERE post_id = ?';
            $sqlData = [$result['task_id'], $result['post_id']];
        }

        $sth = $db->prepare($sql);
        $sth->execute($sqlData);
    }
}
