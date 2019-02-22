<?php
require_once __DIR__ . '/RestClient.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

$client = new RestClient($apiUrl, null, $dfsUsername, $dfsPassword);

$defaultIsoCode = 'us';
$defaultLocId = '2840';
$searchEngines = ['google', 'bing', 'yahoo'];
$mapPackEngine = 'map pack';
$today = date("Y-m-d");

$tasks = [];
$postBody = file_get_contents('php://input');
$postData = json_decode($postBody);
$keywordsIds = json_decode($postData->keywordsIds);
$dashboardUrl = $postData->url;
$ownerId = $postData->ownerId;
$dashboardId = $postData->dashId;

$data = [];

$sql = 'SELECT * FROM keywords WHERE id IN (';
foreach ($keywordsIds as $id) {
    $data[] = $id;
    $sql .= '?,';
}
$sql = substr_replace($sql, ')', -1);

$sth = $db->prepare($sql);
$sth->execute($data);
$keywords = $sth->fetchAll();

if (empty($keywords)) {
    exit;
}

$query = 'INSERT INTO rank_tracking (keyword, dashboard_id, post_id, dated, user_id, location, se_id, keyword_id) VALUES';
$queryData = [];

$searchEnginesIds = [];
$locationIds = [];
$keywordsData = $keywordsSql = [];

try {
    foreach ($keywords as $keyword) {
        $keywordIsoCode = $keyword['country_iso_code'] ?? $defaultIsoCode;
        $keywordId = $keyword['id'];
        $keywordLocation = empty($keyword['city']) ? $keyword['country'] : $keyword['city'];
        $keywordAddress = $keyword['address'];
        $word = $keyword['keyword'];

        if (!isset($searchEnginesIds[$keywordIsoCode])) {
            $searchEnginesIds[$keywordIsoCode] = [
                'google_se_id' => null,
                'bing_se_id' => null,
                'yahoo_se_id' => null,
                'map_pack_se_id' => null,
            ];

            $seForCountry = $client->get('/v2/cmn_se/' . $keywordIsoCode);

            if (!empty($seForCountry['results'])) {
                foreach ($seForCountry['results'] as $item) {
                    $seName = $item['se_name'];
                    $seLanguage = $item['se_language'];
                    $seId = $item['se_id'];

                    if (strripos($seName, $mapPackEngine) !== false && $seLanguage == $defaultLanguage) {
                        $searchEnginesIds[$keywordIsoCode]['map_pack_se_id'] = $seId;
                        continue;
                    }

                    foreach ($searchEngines as $engine) {
                        if (strripos($seName, $engine) !== false && strripos($seName, ' ') === false && $seLanguage == $defaultLanguage) {
                            $searchEnginesIds[$keywordIsoCode][$engine . '_se_id'] = $seId;
                        }
                    }
                }
            }
        }

        $seIds = $searchEnginesIds[$keywordIsoCode];

        if (!isset($locationIds[$keywordAddress])) {
            $locationIds[$keywordAddress] = null;
            $locationsForKeyword = $client->get('/v2/cmn_locations/' . $keywordIsoCode);

            if (!empty($locationsForKeyword['results'])) {
                foreach ($locationsForKeyword['results'] as $location) {
                    if ($location['loc_name'] == $keywordLocation) {
                        $locationIds[$keywordAddress] = $location['loc_id'];
                        break;
                    }
                    if ($location['loc_type'] == 'Country') {
                        $locationIds[$keywordAddress] = $location['loc_id'];
                    }
                }
            }
        }

        $locationId = $locationIds[$keywordAddress] ?? $defaultLocId;

        $sql = 'UPDATE keywords 
                    SET google_se_id = :google_se_id, 
                        bing_se_id = :bing_se_id, 
                        yahoo_se_id = :yahoo_se_id,
                        map_pack_se_id = :map_pack_se_id, 
                        loc_id = :loc_id
                    WHERE id = :id';

        $sqlData = array_merge($seIds, ['loc_id' => $locationId, 'id' => $keywordId]);

        $sth = $db->prepare($sql);
        $sth->execute($sqlData);

        $keyword = array_merge($keyword, ['loc_id' => $locationId]);

        $taskData = buildKeywordBatchAndSqlForSerpTasks($seIds, $keyword, $defaultLanguage, $dashboardId, $ownerId);

        $keywordsSql[] = implode(',', $taskData['query']);
        $queryData = array_merge($queryData, $taskData['queryData']);
        $tasks = array_merge($tasks, $taskData['tasks']);
    }

    $query = $query . implode(',', $keywordsSql);

    $sth = $db->prepare($query);
    $sth->execute($queryData);

    $taskPostResult = $client->post('/v2/srp_tasks_post', array('data' => $tasks));

    if (!empty($taskPostResult['results'])) {
        saveTasksData($taskPostResult['results']);
    }
} catch (\Exception $e) {
    echo $e->getMessage();
}

$client = null;
?>
