<?php

require_once __DIR__ . '/RestClient.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';


$client = new RestClient($apiUrl, null, $dfsUsername, $dfsPassword);

$searchEnginesIds = [];
$today = date("Y-m-d");

$sql = 'SHOW COLUMNS FROM keywords';
$columns = $db->query($sql)->fetchAll(PDO::FETCH_GROUP);

foreach ($columns as $name => $value) {
    if (preg_match('/_se_id/', $name)) {
        $searchEnginesIds[$name] = null;
    }
}

$sql = 'SELECT dashboard_id, id, keyword, address, city, region, country, country_iso_code, google_se_id, bing_se_id, yahoo_se_id, map_pack_se_id, loc_id
          FROM keywords WHERE deleted_at IS NULL';

$result = $db->query($sql)->fetchAll(PDO::FETCH_GROUP);

if (empty($result)) {
    exit();
}

try {
    foreach ($result as $dashboardId => $keywords) {
        $tasks = [];
        $sth = $db->prepare('SELECT ownerID FROM dashboards WHERE id = ?');
        $sth->execute([$dashboardId]);
        $ownerId = $sth->fetch()['ownerID'] ?? null;

        $query = 'INSERT INTO rank_tracking (keyword, dashboard_id, post_id, dated, user_id, location, se_id, keyword_id) VALUES';
        $queryData = $keywordsSql = [];

        foreach ($keywords as $item) {
            foreach ($searchEnginesIds as $key => $value) {
                $searchEnginesIds[$key] = $item[$key];
            }
            if (empty($item['keyword'])) {
                continue;
            }
            $taskData = buildKeywordBatchAndSqlForSerpTasks($searchEnginesIds, $item, $defaultLanguage, $dashboardId, $ownerId);

            if (empty($taskData['tasks'])) {
                continue;
            }

            $keywordsSql[] = implode(',', $taskData['query']);
            $queryData = array_merge($queryData, $taskData['queryData']);
            $tasks = array_merge($tasks, $taskData['tasks']);
        }

        $query = $query . implode(',', $keywordsSql);
        $sth = $db->prepare($query);
        $sth->execute($queryData);

        if (empty($tasks)) {
            continue;
        }

        $taskPostResult = $client->post('/v2/srp_tasks_post', array('data' => $tasks));
        if (!empty($taskPostResult['results'])) {
            saveTasksData($taskPostResult['results']);
        }
    }
} catch (\Exception $e) {
    echo $e->getMessage();
}

