<?php
/*
  Google Analystics, Google Adwords, Google Search Console, Google Sheets, Youtube
  Array ( [state] => test [code] => 4/7EiDOrfwgc6F-noHfvqdqGQBo4dwKYQd3dFrSsMvBsM )
  Array ( [error] => access_denied [state] => test )
  */

require_once(__DIR__ . '/../config.php');

/*
	if($token['refresh_token']){
		file_put_contents($method."_".date("Ymd_His").".txt", json_encode($token)."<====>".$userID);
	}*/

$retObj = new stdClass();
$retObj->error = "0";
$retObj->items = array();

$response = file_get_contents($check_url);
if ($response) {
    if ($method == "analytics") {

        $networkID = 1;
        $networkName = "Google Analytics";
        $arrUserInfo = explode("@", $userID);
        $dashboardID = 0;
        if (count($arrUserInfo) > 1) $dashboardID = $arrUserInfo[1];
        $userID = $arrUserInfo[0];
        if ($dashboardID == "") $dashboardID = 0;

        db_clear_func($userID, $dashboardID, $networkID);

        $items = array();
        $resp_obj = json_decode($response);

        $items_t = $resp_obj->items;

        //error_log("Google Analytics Records Length : ".count($items_t));

        for ($i = 0; $i < count($items_t); $i++) {
            $itemObj = new stdClass();
            $itemObj->id = $items_t[$i]->id;
            $itemObj->accountId = $items_t[$i]->accountId;
            $itemObj->websiteUrl = $items_t[$i]->websiteUrl;
            $itemObj->webPropertyId = $items_t[$i]->webPropertyId;
            array_push($items, $itemObj);

            db_insert_func($userID, $dashboardID, $token['refresh_token'], json_encode($token), $items_t[$i]->websiteUrl, $items_t[$i]->webPropertyId, $networkID, $networkName, json_encode($itemObj));

            //if($i >= 70) break;
        }
        //$retObj->items = $items;
        $retObj->error = "0";
    } else if ($method == "console") {

        $networkID = 3;
        $networkName = "Google Search Console";
        $arrUserInfo = explode("@", $userID);
        $dashboardID = 0;
        if (count($arrUserInfo) > 1) $dashboardID = $arrUserInfo[1];
        $userID = $arrUserInfo[0];
        if ($dashboardID == "") $dashboardID = 0;

        db_clear_func($userID, $dashboardID, $networkID);

        $items = array();
        $response = str_replace("\n", "", $response);

        $resp_obj = json_decode($response);

        $items_t = $resp_obj->siteEntry;

        //error_log("Google Search Console Records Length : ".count($items_t));

        for ($i = 0; $i < count($items_t); $i++) {
            $itemObj = new stdClass();
            $itemObj->id = "";
            $itemObj->accountId = "";
            $itemObj->websiteUrl = $items_t[$i]->siteUrl;
            $itemObj->webPropertyId = "";
            array_push($items, $itemObj);

            db_insert_func($userID, $dashboardID, $token['refresh_token'], "", $items_t[$i]->siteUrl, "", $networkID, $networkName, json_encode($itemObj));
        }
        //$retObj->items = $items;
        $retObj->error = "0";
    } else if ($method == "youtube") {

        $networkID = 5;
        $networkName = "Youtube";
        $arrUserInfo = explode("@", $userID);
        $dashboardID = 0;
        if (count($arrUserInfo) > 1) $dashboardID = $arrUserInfo[1];
        $userID = $arrUserInfo[0];
        if ($dashboardID == "") $dashboardID = 0;

        db_clear_func($userID, $dashboardID, $networkID);

        $items = array();
        $response = str_replace("\n", "", $response);

        $resp_obj = json_decode($response);

        $items_t = $resp_obj->items;

        //error_log("Youtube Records Length : ".count($items_t));

        for ($i = 0; $i < count($items_t); $i++) {
            $itemObj = new stdClass();
            $itemObj->id = $items_t[$i]->id;
            $itemObj->accountId = str_replace('"', "", $items_t[$i]->etag);
            $snippet = $items_t[$i]->snippet;
            $itemObj->websiteUrl = $snippet->title;
            $itemObj->webPropertyId = $snippet->title;
            array_push($items, $itemObj);

            db_insert_func($userID, $dashboardID, $token['refresh_token'], json_encode($token), $snippet->title, $snippet->title, $networkID, $networkName, json_encode($itemObj));
        }
        //$retObj->items = $items;
        $retObj->error = "0";
    } else if ($method == "sheet") {

        $networkID = 4;
        $networkName = "Google Sheet";
        $arrUserInfo = explode("@", $userID);
        $dashboardID = 0;
        if (count($arrUserInfo) > 1) $dashboardID = $arrUserInfo[1];
        $userID = $arrUserInfo[0];
        if ($dashboardID == "") $dashboardID = 0;

        db_clear_func($userID, $dashboardID, $networkID);

        $items = array();
        $response = str_replace("\n", "", $response);

        $resp_obj = json_decode($response);

        if (!$resp_obj) error_log("Google Sheet Records Length : 0");

        if ($resp_obj) {
            $itemObj = new stdClass();
            $itemObj->id = $resp_obj->id;
            $itemObj->accountId = "";
            $itemObj->websiteUrl = $resp_obj->displayName;
            $itemObj->webPropertyId = $resp_obj->id;
            array_push($items, $itemObj);

            db_insert_func($userID, $dashboardID, $token['refresh_token'], json_encode($token), $resp_obj->displayName, $resp_obj->id, $networkID, $networkName, json_encode($itemObj));
        }
        //$retObj->items = $items;
        $retObj->error = "0";
    }
}
// echo json_encode($retObj);
?>

<script>
    window.opener.postMessage('<?php echo json_encode($retObj);?>', "*");
</script>