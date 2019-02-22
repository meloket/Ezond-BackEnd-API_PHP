<?php
require_once "library/functions.php";
require_once "library/safe_browser_helper.php";
require_once "library/page_speed_insight_helper.php";
require_once "library/alexa_helper.php";
require_once "library/compressiontest_helper.php";
require_once "library/google_index_helper.php";
require_once "library/host_info_helper.php";
require_once "library/socialcount.php";
require_once "library/simple_html_dom.php";
require_once "library/kd.php";

require_once '../config.php';

$site_info = new stdClass();
$site_info->error = 1;

$url = "";
if (isset($_GET['url'])) $url = $_GET['url'];
if ($url == "") {
    echo json_encode($site_info);
    exit();
}

$site_info->error = 0;

$site_info->safeBrowsing = json_decode(safeBrowsing($url, true));

if (($site_info->safeBrowsing->status == 401) || ($site_info->safeBrowsing->status == 501))
    $site_info->error = 1;

if ($site_info->error == 1) {
    echo json_encode($site_info);
    exit();
}

$desktopScore = pageSpeedInsightChecker("http://" . $url, 'desktop', true);
if (intval($desktopScore) < 50) {
    $desktopSpeed = "Slow";
} elseif (intval($desktopScore) < 79) {
    $desktopSpeed = "Medium";
} else {
    $desktopSpeed = "Fast";
}
$desktopDescription = "<b>" . $desktopScore . " / 100</b><br><b>" . $url . "</b> desktop website speed is " . $desktopSpeed . ". Page speed is important for both search engines and visitors end.";

$__temp_obj = new stdClass();
$__temp_obj->desktopScore = $desktopScore;
$__temp_obj->desktopSpeed = $desktopSpeed;
$__temp_obj->desktopDescription = $desktopDescription;

$site_info->desktopPageSpeed = $__temp_obj;

$mobileScore = pageSpeedInsightChecker("http://" . $url, 'mobile', true);
if (intval($mobileScore) < 50) {
    $mobileSpeed = "Slow";
} elseif (intval($mobileScore) < 79) {
    $mobileSpeed = "Medium";
} else {
    $mobileSpeed = "Fast";
}
$mobileDescription = "<b>" . $mobileScore . " / 100</b><br><b>" . $url . "</b> mobile website speed is " . $mobileSpeed . ". Page speed is important for both search engines and visitors end.";

$__temp_obj = new stdClass();
$__temp_obj->mobileScore = $mobileScore;
$__temp_obj->mobileSpeed = $mobileSpeed;
$__temp_obj->mobileDescription = $mobileDescription;

$site_info->mobilePageSpeed = $__temp_obj;

$traffic_rank = alexaRank($url);

$__temp_obj = new stdClass();
$__temp_obj->worldRank = number_format($traffic_rank[0] * 1);
$__temp_obj->regionRank = number_format($traffic_rank[2] * 1);
$__temp_obj->regionName = $traffic_rank[1];
$__temp_obj->worldDescription = number_format($traffic_rank[0] * 1) . "th most visited website in the World.<br>";
$__temp_obj->regionDescription = number_format($traffic_rank[2] * 1) . "th most visited website in the " . $traffic_rank[1] . ".";

$site_info->alexaRank = $__temp_obj;

$index_page_count = googleIndex($url);
$index_count = Trim(str_replace(',', '', $index_page_count));

if (intval($index_count) < 50) {
    $indexProgress = 'danger';
} elseif (intval($index_count) < 200) {
    $indexProgress = 'warning';
} else {
    $indexProgress = 'success';
}

$__temp_obj = new stdClass();
$__temp_obj->indexCount = $index_count;
$__temp_obj->indexProgress = $indexProgress;
$__temp_obj->indexDescription = 'Indexed pages in search engines : ' . number_format($index_count) . ' Page(s), Status: ( ' . $indexProgress . ' ) ';

$site_info->indexPageInfo = $__temp_obj;

$meta_infos = get_meta_info($url);

$__temp_obj = new stdClass();

$__temp_obj->meta_title = $meta_infos->meta_title;
$__temp_obj->meta_description = $meta_infos->meta_description;
$__temp_obj->meta_keywords = $meta_infos->meta_keywords;

$site_info->metaInfo = $__temp_obj;

$site_info->googlePreview = google_preview($meta_infos, false);

$missing_info = missing_img_alt($url, $meta_infos->sourceData);
$missing_info->missDescription = 'We found ' . $missing_info->imageCount . ' images on this web page.<br>' . (($missing_info->imageWithOutAltTag == 0) ? 'No' : $missing_info->imageWithOutAltTag) . ' ALT attributes are empty or missing.';

$site_info->missingImageInfo = $missing_info;

$keyword_data = keywords_cloud($url, $meta_infos->sourceData);

$site_info->keywordCloud = $keyword_data->keyData;
$site_info->keywordConsistencyString = $keyword_data->keywordConsistencyString;

$__temp_obj = new stdClass();

$__ssl_infos = new stdClass();

$ssl_check_value = ssl_check($url);
if ($ssl_check_value) {
    $__temp_obj->sslCheck = true;
    $__temp_obj->sslExpirationDate = date("Y-m-d", strtotime(date("Y-m-d")) + 86400 * $ssl_check_value);
    $__temp_obj->sslDescription = "SSL Check Success!, The certificate will expire in " . $ssl_check_value . " days.";
} else {
    $__temp_obj->sslCheck = false;
    $__temp_obj->sslExpirationDate = "0000-00-00";
    $__temp_obj->sslDescription = "SSL Check Failed";
}

$site_info->sslCheck = $__temp_obj;

$__temp_obj = new stdClass();

$sitemapLink = "http://" . $url . '/sitemap.xml';
$httpCode = getHttpCode($sitemapLink);
$__temp_obj->sitemapDescription = (($httpCode == '404') ? 'Oh no, XML Sitemap file not found!' : 'Good, you have XML Sitemap file!') . '<br><a href="' . $sitemapLink . '" title="XML Sitemap Link" rel="nofollow" target="_blank">' . $sitemapLink . '</a>';
$__temp_obj->sitemapLink = $sitemapLink;
$__temp_obj->httpCode = $httpCode;

$site_info->sitmapCheck = $__temp_obj;

$pageSize = __size_as_kb($meta_infos->pageSize);
$__temp_obj = new stdClass();
$__temp_obj->pageSize = 0;
if ($pageSize) {
    $__temp_obj->pageSize = $pageSize;
    $__temp_obj->pageSizeDescription = (($pageSize > 320) ? "Page Size is so large<br>" : "") . $pageSize . " KB (World Wide Web average is 320 KB)";
}
$site_info->pageSize = $__temp_obj;

$__temp_obj = new stdClass();
$timeTaken = $meta_infos->timeTaken;
$__temp_obj->timeTaken = 0;
if ($timeTaken) {
    $__temp_obj->timeTaken = $timeTaken;
    $__temp_obj->timeTakenDescription = (($timeTaken >= 1) ? "Web Site Load Time is so long<br>" : "") . $timeTaken . " second(s)";
}
$site_info->timeTaken = $__temp_obj;

$__temp_obj = new stdClass();
$compatibility_check = mobile_compatibility($url, $meta_infos->sourceData);
$__temp_obj->compatibility_check = (!$compatibility_check);
if ($compatibility_check) $__temp_obj->compatibility_check_description = 'Bad, embedded objects detected.<br>Embedded Objects such as Flash, Silverlight or Java. It should only be used for specific enhancements.';
else $__temp_obj->compatibility_check_description = 'Perfect, no embedded objects detected.';
$site_info->compatibility_check = $__temp_obj;

$__temp_obj = new stdClass();

$ret = mobile_friendly($url);
$__temp_obj->mobileScore = 0;
$__temp_obj->isMobileFriendly = false;
if ($ret->error == 0) {
    $__temp_obj->mobileScore = $ret->mobileScore;
    $__temp_obj->isMobileFriendly = $ret->isMobileFriendly;
    $__temp_obj->mobileFriendlyDescription = (($ret->isMobileFriendly) ? 'Awesome! This page is mobile-friendly!<br>' : 'Oh No! This page is not mobile-friendly.<br>') . 'Your mobile friendly score is ' . $ret->mobileScore . '/100';
} else {
    $__temp_obj->mobileFriendlyDescription = 'Something went wrong!';
}
$site_info->isMobileFriendly = $__temp_obj;
$site_info->mobileScreenData = "No Screenshot available!";

if ($ret->error == 0) {
    if ($ret->screenData != "") $site_info->mobileScreenData = $ret->screenData;
}

function __insert_review_to_db($__site_info)
{
    global $db, $url;

    $insertData = [
        'webSiteURL' => $url,
        'reviewDate' => date("Y-m-d"),
        'safeBrowsing' => $__site_info->safeBrowsing->status,
        'desktopPageSpeed' => $__site_info->desktopPageSpeed->desktopScore,
        'mobilePageSpeed' => $__site_info->mobilePageSpeed->mobileScore,
        'alexaRank' => $__site_info->alexaRank->worldRank,
        'regionName' => $__site_info->alexaRank->regionName,
        'regionRank' => $__site_info->alexaRank->regionRank,
        'indexCount' => $__site_info->indexPageInfo->indexCount,
        'meta_title' => $__site_info->metaInfo->meta_title,
        'meta_description' => $__site_info->metaInfo->meta_description,
        'meta_keywords' => $__site_info->metaInfo->meta_keywords,
        'missingImage' => json_encode($__site_info->missingImageInfo),
        'keywordCloud' => $__site_info->keywordCloud,
        'keywordConsistencyString' => $__site_info->keywordConsistencyString,
        'sslCheck' => $__site_info->sslCheck->sslCheck,
        'sslExpirationDate' => $__site_info->sslCheck->sslExpirationDate,
        'sitmapCheck' => $__site_info->sitmapCheck->httpCode,
        'pageSize' => $__site_info->pageSize->pageSize,
        'timeTaken' => $__site_info->timeTaken->timeTaken,
        'compatibility_check' => $__site_info->compatibility_check->compatibility_check,
        'mobileScore' => $__site_info->isMobileFriendly->mobileScore,
        'isMobileFriendly' => $__site_info->isMobileFriendly->isMobileFriendly,
        'mobileScreenData' => $__site_info->mobileScreenData,
    ];

    $db->insert('website_check_reviews', $insertData);
}

__insert_review_to_db($site_info);
exit();
?>