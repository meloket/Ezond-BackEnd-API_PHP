<?php
ini_set('max_execution_time', 100000);

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../Mysql.php');

$db = new Mysql();

$user_networks = $db->select("SELECT * FROM users_networks WHERE networkID = 1");
$redirect_uri = SITE_URL . "googleanalytics/updateData.php";

if (count($user_networks) == 0 || !$user_networks) {
    die("No users to update");
}

foreach ($user_networks as $user) {
    $client = new Google_Client();
    $client->setApplicationName("EzondMarkettingApp");
    $client->setAccessType("offline");
    $client->setClientId('126030751623-d2pf3ii5ibhqg3evm228tsgb7c1lh3gm.apps.googleusercontent.com');
    $client->setClientSecret('m7CDYpGVHdPY3MLB4MOfXtSv');
    $client->setDeveloperKey('AIzaSyAg7islKFa3gl1-PwAZVMac0Z4k4pHiSC4');
    $client->setRedirectUri($redirect_uri);
    $client->addScope("https://www.googleapis.com/auth/analytics.readonly");
    $client->setAccessToken($user["access_token"]);

    if ($client->isAccessTokenExpired()) {
        $newToken = $client->fetchAccessTokenWithRefreshToken($user["refresh_token"]);
        if (isset($newToken["error"])) {
            // error with network, delete from database
            $qry = "DELETE FROM users_networks WHERE id = " . $user["id"];
            $db->exe($qry, null);
            $qry = "DELETE FROM users_data_google_analytics WHERE viewID IN (" + $user["viewID"] + ") ";
            $db->exe($qry, null);
        } else {
            // set new access token (update in database)
            $qry = "UPDATE users_networks SET access_token = " + json_encode($newToken) + " WHERE id = " . $user["id"];
            $db->exe($qry, null);
            $client->setAccessToken($newToken);
        }
    }

    $analytics = new Google_Service_Analytics($client);
    $profile = getAccountDetails($analytics);

    if (!$profile) {
        // delete network from db
        $qry = "DELETE FROM users_networks WHERE id = " . $user["id"];
        $db->exe($qry, null);
        $qry = "DELETE FROM users_data_google_analytics WHERE viewID IN (" + $user["viewID"] + ") ";
        $db->exe($qry, null);
        continue;
    } else {
        $results = getDaysData($analytics, $profile["viewID"]);
        saveDaysData($results, $user["userID"], $db);
    }
}

echo "Updated " . count($user_networks);

function getAccountDetails(&$analytics)
{
    $results = [];

    // Get the list of accounts for the authorized user.
    try {
        $accounts = $analytics->management_accounts->listManagementAccounts();
    } catch (Exception $exc) {
        return false;
    }

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

function getDaysData(&$analytics, $profileId)
{

    $views = explode(",", $profileId);
    $results = [];

    foreach ($views as $view) {
        $optParams = array(
            'dimensions' => 'ga:date');
        // Calls the Core Reporting API and queries for the number of sessions
        // for the last seven days.
        $results[$view] = $analytics->data_ga->get(
            'ga:' . $view,
            'today', // start date
            'today', // end date
            'ga:sessions,ga:bounces,ga:newUsers,ga:timeOnPage,ga:exits,ga:entrances,ga:pageviews,ga:uniquePageviews',
            $optParams);
    }

    return $results;
}

function saveDaysData(&$results, $userID, $db)
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

?>