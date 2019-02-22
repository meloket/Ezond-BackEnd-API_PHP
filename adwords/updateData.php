<?php
ini_set('max_execution_time', 100000);

require_once(__DIR__ . '/init.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../Mysql.php');
require_once(__DIR__ . '/../vendor/autoload.php');

// $clientId = "126030751623-d2pf3ii5ibhqg3evm228tsgb7c1lh3gm.apps.googleusercontent.com";
// $clientSecret = "m7CDYpGVHdPY3MLB4MOfXtSv";

$clientId = "903205507988-o43d395e1d0i5nrdo5h6o8va5p617f2k.apps.googleusercontent.com";
$clientSecret = "w65KeNUmVDTUhJMwF1WzjV-Q";

// $devToken = "tQ27uP9JkLXZs5hlGsupVA";

$devToken = "c6fWiBgJrYC58qHfFCqWnA";
$userAgent = "EzondMarkettingApp";

$db = new Mysql();

$user_networks = $db->select("SELECT * FROM users_networks WHERE networkID = 2");
$callbackUrl = SITE_URL . "adwords/updateData.php";

if (count($user_networks) == 0 || !$user_networks) {
    die("No networks");
}

foreach ($user_networks as $dbUser) {
    $user = new AdWordsUser();
    $user->setUserAgent($userAgent);
    $user->SetDeveloperToken($devToken);

    $OAuthDetails = array(
        "client_id" => $clientId,
        "client_secret" => $clientSecret,
        "access_token" => $dbUser["access_token"],
        "access_type" => "offline",
        "refresh_token" => $dbUser["refresh_token"]
    );

    $user->SetOAuth2Info($OAuthDetails);
    $oauthHandler = $user->GetDefaultOAuth2Handler();

    $t = $oauthHandler->GetOrRefreshAccessToken($OAuthDetails);

    // var_dump($t);
    // die();
    $user->SetOAuth2Info($t);
    $accounts = explode(',', $dbUser['viewID']);

    foreach ($accounts as $account) {
        $user->SetClientCustomerId($account);
        getAndSaveAccountData($db, $dbUser['userID'], $user, $account);
    }
}

function getAndSaveAccountData($db, $userID, $user, $id)
{
    $options = array('version' => 'v201605');
    $options['includeZeroImpressions'] = true;
    $options['skipColumnHeader'] = true;
    $options['skipReportHeader'] = true;
    $options['skipReportSummary'] = true;
    // store data into database
    $today = date("Ymd");
    var_dump("today is " . $today);
    $yesterday = date('Ymd', strtotime('-14 days'));

    $reportQuery = "SELECT Date, Cost, Conversions, CostPerConversion, ConversionRate FROM ACCOUNT_PERFORMANCE_REPORT DURING $yesterday, $today";

    $reportUtils = new ReportUtils();
    $data = $reportUtils->DownloadReportWithAwql($reportQuery, null, $user, "CSV", $options);
    $rows = [];
    // var_dump($data);
    // return;
    // break every new line
    $temp = explode("\n", $data);
    for ($i = 0; $i < count($temp); $i++) {
        $rows[] = explode(",", $temp[$i]);
    }

    var_dump($rows);
    $qry = "INSERT INTO users_data_adwords (userID, saveTime, viewID, conversions, conversionRate, cost, cpc) VALUES ";
    foreach ($rows as $item) {
        if ($item[0] != "") {
            $date = $item[0] . " 00:00:00";
            $qry .= '(' . $userID . ',"' . $date . '",' . $id . "," . $item[2] . ',' . str_replace("%", "", $item[4]) . ',' . $item[1] . ',' . $item[3] . '), ';
        }
    }
    // remove trailing ),
    $qry = substr($qry, 0, -2);
    $qry .= ";";

    $results = $db->exe($qry, null);
}

?>
