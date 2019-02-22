<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use FacebookAds\Api;
use FacebookAds\Object\AdUser;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\AdsInsightsFields;
use FacebookAds\Object\Ad;
use FacebookAds\Object\Fields\AdSetFields;
use FacebookAds\Object\AdCampaign;
use FacebookAds\Object\Fields\AdFields;
use FacebookAds\Object\Fields;
use FacebookAds\Object\Fields\AdImageFields;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdSet;
use FacebookAds\Object\AdCreative;
use FacebookAds\Object\Fields\AdCreativeFields;
use FacebookAds\Object\Fields\AdCreativePhotoDataFields;
use FacebookAds\Object\AdCreativeLinkData;
use FacebookAds\Object\Fields\AdCreativeLinkDataFields;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\Page;
use FacebookAds\Object\Fields\AdPreviewFields;
use FacebookAds\Object\Values\AdPreviewAdFormatValues;
use FacebookAds\Object\AdVideo;


$facebookAppID = "456797558007270";
$facebookAppSecret = "c69f7f97677d5852875a23045305cc8e";
$facebook_access_token = "EAAGfdHgt5eYBAMcXZB5zdsmU1h3rG3RAPzM7VjZBu89J2r5RuqkXF1GHqpibZCfshBbDPIQDyUu4zZAT9DoADR3tfegAZAWltVz9g4Qnm7eqrBsD4bQPU1eVsNFbunZBBSo3wzPiOfswYGdbR8m3Wgh6bWRpcPYfyelkyYglYuQg5spJZCaoufL";

// Init PHP Sessions
session_start();

$fb = new Facebook([
    'app_id' => $facebookAppID,
    'app_secret' => $facebookAppSecret,
    'default_graph_version' => 'v2.9',
]);


$response = $fb->get('/act_119662315288281/insights', $facebook_access_token);
$result = json_decode($response->getBody());

print_r($result);
exit();

Api::init(
    $facebookAppID,
    $facebookAppSecret,
    $facebook_access_token
);

?>
  <div id="fbdata"></div> <?php

$account = new AdAccount('act_119662315288281');


$params = array(

    'date_preset' => 'last_28d',


    'thumbnail_width' => 200,
    'thumbnail_height' => 150,
    'level' => 'campaign',
    'limit' => '15'

);
//         arr_metrics[7] = {'Clicks': 'clicks', 'Impressions': 'impressions', 'Amount Spent': 'amountSpent', 'Average CPC': 'avgCPC', 'CTR': 'ctr', 'Page Likes': 'pageLikes', 'Post Likes': 'postLikes', 'Website Conversions': 'webSiteConversions', 'Cost Per Page Like': 'costPerPageLike', 'Cost Per Post Like': 'costPerPostLike', 'Cost Per Website Conversion': 'costPerWebsiteConversion'};
$fields = array(
    AdsInsightsFields::CAMPAIGN_NAME,
    AdsInsightsFields::CAMPAIGN_ID,
    AdsInsightsFields::IMPRESSIONS,
    AdsInsightsFields::CLICKS,
    AdsInsightsFields::REACH,
    AdsInsightsFields::SPEND,
    AdsInsightsFields::CPM,
    AdsInsightsFields::CPC,
    AdsInsightsFields::ACTIONS,
);

$field = array(
    AdCreativeFields::TITLE,
    AdCreativeFields::THUMBNAIL_URL,
    AdCreativeFields::BODY,
);

$params1 = array(
    'time_range' => array(
        'since' => "2017-04-01",
        'until' => "2017-07-01",
    ),
    'thumbnail_width' => 200,
    'thumbnail_height' => 150,
    'level' => 'ad',
    'limit' => '5'
);

$adcreatives = $account->getAdCreatives($field, $params1);
?>
  <table class="fbtable">
  <tr>
    <th>Title</th>
    <th>Ad Image</th>
    <th>Ad Body</th>
  </tr>
<?php
foreach ($adcreatives as $t2) {

    echo "<tr>
        <td>$t2->title</td>
      <td><img src='$t2->thumbnail_url'/></td>
      <td>$t2->body</td>
    </tr>";
}

$insights = $account->getInsights($fields, $params); ?>

  <table class="fbtable">
    <tr>
      <th>Campaign ID</th>
      <th>Campaign Name</th>
      <th>Impressions</th>
      <th>Clicks</th>
      <th>Reach</th>
      <th>Spend</th>
      <th>Total Actions</th>
      <th>CPM</th>
      <th>CPC</th>
    </tr>

<?php

foreach ($insights as $i) {
    $impress = number_format((float)$i->impressions);
    $reach = number_format((float)$i->reach);
    $totalAction = number_format((float)$i->actions);
    $cpc = number_format($i->cpc, 2, '.', '');
    $cpm = number_format($i->cpm, 2, '.', '');
    echo "<tr class='fbtable'>
      <td>$i->campaign_id</td>
      <td>$i->campaign_name</td>
      <td>$impress</td>
      <td>$i->clicks</td>
      <td>$reach</td>
      <td>$i->spend</td>
      <td>$totalAction</td>
      <td>$cpm</td>
      <td>$cpc</td>
    </tr>";
}


?>