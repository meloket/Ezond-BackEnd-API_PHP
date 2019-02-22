<?php
require_once (__DIR__ . '/vendor/autoload.php');

$define = function($name, $default = null) {
    define($name, getenv($name) ?: $default);
};

$mysqlUrl = getenv('JAWSDB_URL') ?: getenv('CLEARDB_DATABASE_URL') ?: getenv('DATABASE_URL');
if ($mysqlUrl) {
    $dataBase = parse_url($mysqlUrl);
    putenv('DB_HOST='. $dataBase['host']);
    putenv('DB_NAME='. substr($dataBase['path'], 1));
    putenv('DB_USER='. $dataBase['user']);
    putenv('DB_PASSWORD='. $dataBase['pass']);

    if(!empty($dataBase['port'])) {
        putenv('DB_PORT='. $dataBase['port']);
        $define('DB_PORT');
    }
}

$define('DB_TYPE', 'mysql');
$define('DB_HOST', 'localhost');
$define('DB_NAME', 'ezond');
$define('DB_USER', 'root');
$define('DB_PASSWORD', 'treecat21tub');
$define('TIME_ZONE', 'Pacific/Auckland');
$define('SITE_URL', 'https://networks.ezond.com/');
$define('HASH_PATH', __DIR__ . '/apis/hash/');
$define('S3_BUCKET', 'ezond');
$define('S3_REGION', 'ap-southeast-2');
$define('SENTRY_DSN', false);

getenv('AWS_ACCESS_KEY_ID') ?: putenv('AWS_ACCESS_KEY_ID=AKIAITCZ7ODKAOF2M63A');
getenv('AWS_SECRET_ACCESS_KEY') ?: putenv("AWS_SECRET_ACCESS_KEY=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");

require_once(__DIR__ . '/Mysql.php');

$db = new Mysql();

if (SENTRY_DSN) {
    $sentryClient = new Raven_Client(SENTRY_DSN);
    $error_handler = new Raven_ErrorHandler($sentryClient);
    $error_handler->registerExceptionHandler();
    $error_handler->registerErrorHandler();
    $error_handler->registerShutdownFunction();
}

function __get_dashboard_ids($__user_id)
{
    global $db;

    $__is_agency = false;
    $__agency_id = 0;
    $__levelup_id = 0;
    $__campaign_access = "all";
    $__campaigns_allowed = "[]";
    $__role = "client";

    $__sql = 'SELECT `id` FROM `users` WHERE `id` = :id';

    $__user_infos = $db->select($__sql, ['id' => $__user_id]);

    if (isset($__user_infos[0]["id"])) {
        $__is_agency = true;
        $__agency_id = $__user_infos[0]["id"];
        $__levelup_id = $__agency_id;
        $__role = "agency";
    }
    if ($__is_agency == false) {
        $__sql = 'SELECT * FROM `agency_users` WHERE `id` = :id';
        $__user_infos = $db->select($__sql, ['id' => $__user_id]);
        if (isset($__user_infos[0]["id"])) {
            $__agency_id = $__user_infos[0]["parent_id"];
            $__levelup_id = $__user_infos[0]["agencyID"];
            $__role = $__user_infos[0]["role"];
            $__campaign_access = $__user_infos[0]["campaign_access"];
            $__campaigns_allowed = $__user_infos[0]["campaigns_allowed"];
            if (($__campaign_access == "all") && ($__role == "client")) {
                $__user_infos = $db->select($__sql, ['id' => $__levelup_id]);
                if (isset($__user_infos[0]["id"])) {
                    $__campaign_access = $__user_infos[0]["campaign_access"];
                    $__campaigns_allowed = $__user_infos[0]["campaigns_allowed"];
                }
            }
        }
    }
    $__user_obj = new stdClass();
    $__user_obj->__is_agency = $__is_agency;
    $__user_obj->__agency_id = $__agency_id;
    $__user_obj->__levelup_id = $__levelup_id;
    $__user_obj->__campaign_access = $__campaign_access;
    $__user_obj->__campaigns_allowed = $__campaigns_allowed;
    $__user_obj->__role = $__role;

    if ($__campaign_access == "all") {
        $__sql = 'SELECT * FROM `dashboards` WHERE `ownerID` = :ownerId';
        $__dash_infos = $db->select($__sql, ['ownerId' => $__agency_id]);

        $__campaigns = array();
        if ($__dash_infos) {
            for ($i = 0; $i < count($__dash_infos); $i++) {
                if (isset($__dash_infos[$i]["id"])) {
                    array_push($__campaigns, $__dash_infos[$i]["id"]);
                }
            }
        }
    } else {
        $__campaigns = json_decode($__campaigns_allowed);
    }

    array_push($__campaigns, "0");
    $__user_obj->__campaigns = $__campaigns;
    $__user_obj->dashboards_ids = implode(",", $__campaigns);

    return $__user_obj;
}


function db_clear_func($userID, $dashboardID, $networkID)
{
    return;
    global $db;

    $sql = "DELETE FROM `users_networks` WHERE `userID` = :userID AND `networkID` = :networkID";
    $data = [
        'userID' => $userID,
        'networkID' => $networkID,
    ];

    if ($dashboardID !== 0) {
        $sql .= " AND dashboardID = :dashboardID";
        $data['dashboardID'] = $dashboardID;
    }

    $db->delete($sql, $data);
}

function __replace_single_quote($__str)
{
    $__str = str_replace("'", "\'", $__str);
    $__str = str_replace('"', '\"', $__str);
    return $__str;
}

function db_insert_func($userID, $dashboardID, $refresh_token, $access_token, $account, $viewID, $networkID, $networkName, $authResponse = "")
{
    global $db;
    $table = 'users_networks';

    if ($networkID != "6") {
        $access_token = "";
    }

    $account = __replace_single_quote($account);
    $viewID = __replace_single_quote($viewID);

    $selectSql = 'SELECT * FROM `' . $table . '` 
                        WHERE `userID` = :userID 
                          AND `networkID` = :networkID 
                          AND `account` = :account 
                          AND `viewID` = :viewID';

    $updateSql = 'UPDATE `' . $table . '` 
                        SET `lastRetrieval` = :lastRetrieval, 
                            `refresh_token` = :refresh_token
                        WHERE `networkID` = :networkID
                           AND `account` = :account
                           AND `viewID` = :viewID';

    $commonData = [
        'networkID' => $networkID,
        'account' => $account,
        'viewID' => $viewID,
    ];

    $selectData = array_merge($commonData, ['userID' => $userID]);

    $insertData = array_merge($selectData, [
        'networkName' => $networkName,
        'access_token' => $access_token,
        'refresh_token' => $refresh_token,
        'authResponse' => $authResponse,
        'lastRetrieval' => date("Y-m-d H:i:s"),
    ]);

    $updateData = array_merge($commonData, [
        'lastRetrieval' => date("Y-m-d H:i:s"),
        'refresh_token' => $refresh_token,
    ]);

    if ($dashboardID != 0) {
        $selectSql .= ' AND `dashboardID` = :dashboardID';
        $selectData['dashboardID'] = $dashboardID;
        $insertData['dashboardID'] = $dashboardID;
    }

    $result = $db->select($selectSql, $selectData);
    if (!$result) {
        $db->insert($table, $insertData);
    }

    $db->exe($updateSql, $updateData);
}

?>
