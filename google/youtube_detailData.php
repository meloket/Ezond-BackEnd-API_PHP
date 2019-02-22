<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');

$start_date = date("Y-m-d");
$end_date = date("Y-m-d");

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];

$refreshToken = "";
$viewID = "";

if (isset($_GET['refreshToken'])) $refreshToken = $_GET['refreshToken'];
if (isset($_GET['viewID'])) $viewID = $_GET['viewID'];
if ($refreshToken == "") exit();
if ($viewID == "") exit();

$redirect_uri = SITE_URL . "google/analytics_callback.php";

$client = new Google_Client();
$client->setApplicationName($googleAppName);
$client->setAccessType("offline");
$client->setClientId($googleClientID);
$client->setClientSecret($googleClientSecret);
$client->setRedirectUri($redirect_uri);

$client->refreshToken($refreshToken);
$token = $client->getAccessToken();

$analytics = new Google_Service_YouTubeAnalytics($client);
$youtube = new Google_Service_YouTube($client);

$ret = new stdClass();

$id = "channel==" . $viewID;
$optparams = array(
    'dimensions' => 'day',
    'sort' => 'day',
);
$optparams2 = array(
    'dimensions' => 'video',
    'max-results' => 200,
    'sort' => '-views',
);

$metrics = 'views,likes,dislikes';
$views = "Day,Views\n";
$likes = "Day,Likes\n";
$dislikes = "Day,DisLikes\n";

$ret->Views = 0;
$ret->Likes = 0;
$ret->DisLikes = 0;
$ret->result = array();

try {
    $api = $analytics->reports->query($id, $start_date, $end_date, 'views,likes,dislikes', $optparams2);

    $result = array();
    $ids = "";
    if ($api->getRows()) {
        for ($i = 0; $i < count($api->getRows()); $i++) {
            $fld_value = $api->getRows()[$i];
            if ($ids != "") $ids .= ",";
            $ids .= $fld_value[0];
            $retObj = new stdClass();
            $retObj->id = $fld_value[0];
            $retObj->Views = $fld_value[1];
            $retObj->Likes = $fld_value[2];
            $retObj->Dislikes = $fld_value[3];
            $retObj->Date = "";
            $retObj->Video = "";
            $retObj->Duration = "";

            array_push($result, $retObj);
        }
    }
    if ($ids != "") {
        $response = $youtube->videos->listVideos('snippet,contentDetails', array('id' => $ids));

        foreach ($response['items'] as $videoResult) {

            for ($j = 0; $j < count($result); $j++) {

                if ($result[$j]->id == $videoResult['id']) {
                    $result[$j]->Date = substr($videoResult['snippet']['publishedAt'], 0, 10);
                    $result[$j]->Video = $videoResult['snippet']['title'];
                    $_Duration = $videoResult['contentDetails']['duration'];
                    $_Duration = str_replace("PT", "", $_Duration);
                    $_Duration = str_replace("H", "hour ", $_Duration);
                    $_Duration = str_replace("M", "min ", $_Duration);
                    $_Duration = str_replace("S", "sec", $_Duration);
                    $result[$j]->Duration = $_Duration;
                }
            }
        }
    }

    $ret->result = $result;

    $api = $analytics->reports->query($id, $start_date, $end_date, $metrics);
    if ($api->getRows()) {
        $fld_value = $api->getRows()[0];

        $ret->Views = $fld_value[0];
        $ret->Likes = $fld_value[1];
        $ret->DisLikes = $fld_value[2];
    }
    $api = $analytics->reports->query($id, $start_date, $end_date, $metrics, $optparams);

    $headers = $api->getColumnHeaders();

    if ($api->getRows()) {
        for ($i = 0; $i < count($api->getRows()); $i++) {
            $fld_value = $api->getRows()[$i];
            $day_val = $fld_value[0];
            $views_val = $fld_value[1];
            $likes_val = $fld_value[2];
            $dislikes_val = $fld_value[3];
            if ($day_val) {
                $views .= $day_val . "," . $views_val . "\n";
                $likes .= $day_val . "," . $likes_val . "\n";
                $dislikes .= $day_val . "," . $dislikes_val . "\n";
            }
        }
    }
} catch (Exception $e) {
    //print_r($e);
    //throw new Exception("Google API Exception: ", $e->getMessage());
}
//echo json_encode($ret);

$ret->Views_Chart = $views;
$ret->Likes_Chart = $likes;
$ret->Dislikes_Chart = $dislikes;


echo json_encode($ret);

?>