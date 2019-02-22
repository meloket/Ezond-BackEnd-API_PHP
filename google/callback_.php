<?php
session_start();
print_r($_GET);
print_r($_SESSION);
//exit();

ini_set('max_execution_time', 300);
error_reporting(E_STRICT | E_ALL);

$googleClientID = "602577532487-j7ta3lsnjtg3rfhujm5aq9tabenhbn19.apps.googleusercontent.com";
$googleClientSecret = "Lluug9BVq6lBLMrF2C3qJ2Iz";

require_once(__DIR__ . '/../vendor/autoload.php');
//require_once '../config.php';
//require_once '../Mysql.php';

//$db = new Mysql();
$userID = $_GET["state"];

$redirect_uri = "http://127.0.0.1/googleanalytics/callback.php";

$client = new Google_Client();
$client->setApplicationName("EzondMarkettingApp");
$client->setAccessType("offline");
$client->setClientId($googleClientID);
$client->setClientSecret($googleClientSecret);
$client->setRedirectUri($redirect_uri);

$code = "";
if (isset($_GET['code'])) $code = $_GET['code'];

if ($code) {
    $check = $client->authenticate($_GET['code']);
    $token = $client->getAccessToken();
    $client->setAccessToken($token);
    exit();
    $analytics = new Google_Service_Analytics($client);

    try {
        $profile = getAccountDetails($analytics);
    } catch (Exception $exc) {
        die("No analytics account detected.");
    }

    print_r($token);
    print_r($profile);

    exit();
    // save into db if this is first authCode
    $checkIfFirstConnect = $db->select("SELECT * FROM users_networks WHERE userID = :userID AND networkID = 1 AND account = :email",
        ["userID" => $userID, "email" => $profile["username"]]);

    if (!$checkIfFirstConnect && isset($token['refresh_token'])) {
        // save network to db
        $networkID = $db->exe("INSERT INTO users_networks (userID, networkID, networkName, account, access_token, viewID, refresh_token, lastRetrieval)
             VALUES (:userID, 1, 'Google Analytics', :account, :access_token, :viewID, :refresh_token, now())", [
            "userID" => $userID,
            "account" => $profile["username"],
            "access_token" => json_encode($token),
            "viewID" => $profile["viewID"],
            "refresh_token" => $token["refresh_token"]
        ], true);

        // Get the results from the Core Reporting API and print the results.
        //$results = getYearsData($analytics, $profile["viewID"]);
        //saveYearsData($results, $userID, $db);
        //postMessage($profile['username'], $networkID, $userID, $profile["viewID"]);
    } else {
        die("This account already exists, go to this link and remove authorization <a href='https://security.google.com/settings/security/permissions?pli=1'>https://security.google.com/settings/security/permissions?pli=1</a>");
    }
}

function postMessage($accountID, $networkID, $userID, $accounts)
{
    if (is_array($accounts)) {
        $accounts = implode(",", $accounts);
    }
    echo "<html>
        <script>
            window.opener.postMessage(JSON.stringify({
                accountID: '$accountID',
                networkName: 'Google Analytics',
                userNetworkID: '$networkID',
                networkID: 1,
                useriD: '$userID',
                views: '$accounts',
            }), 'http://app.ezond.com/');
        </script>
    </html>";
}

function getAccountDetails(&$analytics)
{
    $results = [];

    // Get the list of accounts for the authorized user.
    $accounts = $analytics->management_accounts->listManagementAccounts();

    $results["username"] = $accounts["username"];
    $res = [];

    if (count($accounts->getItems()) > 0) {
        $items = $accounts->getItems();
        $firstAccountId = $items[0]->getId();

        // Get the list of properties for the authorized user.
        $properties = $analytics->management_webproperties->listManagementWebproperties($firstAccountId);

        if (count($properties->getItems()) > 0) {
            foreach ($properties as $property) {
                foreach ($properties->getItems() as $item) {
                    // Get the list of views (profiles) for the authorized user.
                    $profiles = $analytics->management_profiles
                        ->listManagementProfiles($firstAccountId, $item->getId());
                    if (count($profiles->getItems()) > 0) {
                        $items = $profiles->getItems();
                        if (!in_array($items[0]->getId(), $res)) {
                            $res[] = $items[0]->getId();
                        }
                    }
                }
            }

            $results["viewID"] = implode(",", $res);
            return $results;
        } else {
            throw new Exception('No properties found for this user.');
        }
    } else {
        throw new Exception('No accounts found for this user.');
    }
}

function getYearsData(&$analytics, $profileId)
{

    $views = explode(",", $profileId);
    $results = [];

    foreach ($views as $view) {
        $today = date("Y-m-d");
        $optParams = array(
            'dimensions' => 'ga:date',
            'max-results' => '10000');

        // Calls the Core Reporting API and queries for the number of sessions
        // for the last seven days.
        $results[$view] = $analytics->data_ga->get(
            'ga:' . $view,
            '365daysAgo', // start date
            $today, // end date
            'ga:sessions,ga:bounces,ga:newUsers,ga:timeOnPage,ga:exits,ga:entrances,ga:pageviews,ga:uniquePageviews',
            $optParams);
    }
    var_dump($results);

    return $results;
}

function saveYearsData(&$results, $userID, $db)
{
    foreach ($results as $key => $value) {
        // Parses the response from the Core Reporting API and prints
        // the profile name and total sessions.
        $qry = "INSERT INTO users_data_google_analytics (userID, saveTime, viewid, visits, pageViews, uniqueVisitors, uniquePageViews, bounces, entrances, exits, newVisits, timeOnPage, timeOnSite) VALUES ";
        if (count($value->getRows()) > 0) {
            foreach ($value->getRows() as $item) {
                $iYear = substr($item[0], 0, 4);
                $iMonth = substr($item[0], 4, 2);
                $iDay = substr($item[0], 6, 2);
                $date = $iYear . "-" . $iMonth . "-" . $iDay . " 00:00:00";
                $qry .= '(' . $userID . ',"' . $date . '",' . $key . "," . $item[1] . ',' . $item[7] . ',' . $item[8] . ',' . $item[8] . ',' . $item[2] . ',' . $item[6] . ',' . $item[5] . ',' . $item[3] . ',' . $item[4] . ',' . $item[4] . '), ';
            }
            // remove trailing ),
            $qry = substr($qry, 0, -2);
            $qry .= ";";

            $results = $db->exe($qry, null);
        }
    }
}

function checkIfEmpty($array)
{
    unset($array[0]);
    foreach ($array as $item) {
        if ($item != 0 && $item != "0" && $item != "0.0") {
            return false;
        }
    }
    return true;
}

?>
