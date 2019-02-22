<?php

$api_key = '';
$account_id = '';


if (isset($_GET['refreshToken'])) $api_key = $_GET['refreshToken'];
if ($api_key == "") exit();


if (isset($_GET['viewID'])) $account_id = $_GET['viewID'];
if ($account_id == "") exit();

$date = new DateTime();
$date->modify('+1 day');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://" . $account_id . ".api.mailchimp.com/3.0/reports?before_send_time=" . $date->format('Y-m-d') . "&since_send_time=2017-08-01");
curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "anystring:" . $api_key . "-" . $account_id);

$json_data = curl_exec($ch);

$parsed_data = json_decode($json_data);
curl_close($ch);


/*
  Campaign
  Name, Emails Sent, Open Rate, Click Rate, Unsubscribed, Bounced
  campaign_title, emails_sent, opens->opens_rate, clicks->clicks_rate, unsubscribed, bounces->hard_bounces + bounces->soft_bounces

  Lists
  Name, Rating, Subscribers, Open Rate, Click Rate
  campaign_title, emails_sent, list_stats->sub_rate, list_stats->open_rate, list_stats->click_rate
*/
$arrResult = array();
$arrMails = $parsed_data->reports;
for ($i = 0; $i < count($arrMails); $i++) {
    $mailObj = array();
    $mailObj["Name"] = $arrMails[$i]->campaign_title;
    $mailObj["Emails Sent"] = $arrMails[$i]->emails_sent;
    $mailObj["Open Rate"] = ($arrMails[$i]->opens)->open_rate;
    $mailObj["Click Rate"] = $arrMails[$i]->clicks->click_rate;
    $mailObj["Unsubscribed"] = $arrMails[$i]->unsubscribed;
    $mailObj["Bounced"] = $arrMails[$i]->bounces->hard_bounces + $arrMails[$i]->bounces->soft_bounces;
    array_push($arrResult, $mailObj);
}
$ret = array();
$ret["Campaigns_result"] = $arrResult;

$arrResult1 = array();
$arrMails = $parsed_data->reports;
for ($i = 0; $i < count($arrMails); $i++) {
    $mailObj = array();
    $mailObj["Name"] = $arrMails[$i]->list_name;
    $mailObj["Rating"] = $arrMails[$i]->emails_sent;
    $mailObj["Subscribers"] = $arrMails[$i]->list_stats->sub_rate;
    $mailObj["Open Rate"] = $arrMails[$i]->list_stats->open_rate;
    $mailObj["Click Rate"] = $arrMails[$i]->list_stats->click_rate;
    array_push($arrResult1, $mailObj);
}
$ret["Lists_result"] = $arrResult1;
echo json_encode($ret);

?>
