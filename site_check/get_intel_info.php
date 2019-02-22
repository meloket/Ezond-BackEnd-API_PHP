<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

$dashboardIdx = 0;
if (isset($_GET["idx"])) $dashboardIdx = $_GET["idx"];
if (($dashboardIdx == "undefined") || ($dashboardIdx == 0)) {
    $ret = new stdClass();
    $ret->error = 1;
    echo json_encode($ret);
    exit();
}

require_once '../config.php';

function __get_site_domain_name($__site_url)
{
    $__site_url = strtolower($__site_url);
    $__site_url = str_replace("https://", "", $__site_url);
    $__site_url = str_replace("http://", "", $__site_url);
    $__site_url = str_replace("https:", "", $__site_url);
    $__site_url = str_replace("http:", "", $__site_url);
    $__site_url = str_replace("\/", "", $__site_url);
    $__site_url = str_replace("/", "", $__site_url);

    return $__site_url;
}

function __get_review_obj($__site_url)
{
    global $db;

    $sql = "SELECT * FROM `website_check_reviews` WHERE `webSiteURL` = :webSiteURL ORDER BY `reviewDate` DESC LIMIT 1";

    $result = $db->select($sql, ['webSiteURL' => $__site_url]);
    $reviewObj = false;
    if (count($result) > 0) {
        if (isset($result[0]["webSiteURL"]))
            $reviewObj = $result[0];
    }
    return $reviewObj;
}

$siteURL = "";

$sql = "SELECT `description`, `keywords` FROM `dashboards` WHERE `id` = :id";
$result = $db->select($sql, ['id' => $dashboardIdx]);
$dashboardId = 0;
$keywords_arr = array();
if (count($result) > 0) {
    $row = $result[0];
    if (isset($row["description"])) {
        $description = $row["description"];
        if ($description != "") {
            $descr_obj = json_decode($description);
            if (isset($descr_obj->url)) {
                $this_url = __get_site_domain_name($descr_obj->url);
                $arr_return = array($this_url);
            }
        }
    }
    if (isset($row["keywords"])) {
        $keywords = $row["keywords"];
        if ($keywords != "") {
            $keywords = str_replace(chr(10), '', $keywords);
            $keywords_arr = json_decode($keywords);
        }
    }
}

$arr_people_also_ask = array();

if (count($keywords_arr) > 0) {
    $sql = "SELECT `questionString` FROM `people_also_ask` WHERE `keyword` = 'MyText'";
    $data = [];

    for ($i = 0; $i < count($keywords_arr); $i++) {
        $key = 'keyword_' . $i;
        $sql .= " OR keyword = :" . $key;
        $data[$key] = $keywords_arr[$i];
    }

    $result = $db->select($sql, $data);
    for ($i = 0; $i < count($result); $i++) {
        if (isset($result[$i]["questionString"])) {
            $__temp_str = $result[$i]["questionString"];
            $__temp_str_arr = json_decode($__temp_str);
            for ($j = 0; $j < count($__temp_str_arr); $j++)
                array_push($arr_people_also_ask, $__temp_str_arr[$j]);
        }
    }
    while (count($arr_people_also_ask) > 7) {
        $__rnd = rand(0, count($arr_people_also_ask) - 1);
        unset($arr_people_also_ask[$__rnd]);
    }
}

if (count($arr_return) > 0) $siteURL = $arr_return[0];
else {
    $ret = new stdClass();
    $ret->error = 1;
    echo json_encode($ret);
    exit();
}

$reviewObj = __get_review_obj($siteURL);

$__metric_value = $reviewObj["ssl_check_value"];
$__metric_value = str_replace("\r", "", $__metric_value);
$__metric_value = str_replace("\n", "", $__metric_value);

$ssl_check_obj = json_decode($__metric_value);

$ssl_info = new stdClass();
$ssl_info->server_address = "";
$ssl_info->server_status = 1;
$ssl_info->cert_correctness = "";
$ssl_info->cert_status = 1;
$ssl_info->cert_expiration = "";
$ssl_info->cert_expire_status = 1;
$ssl_info->cert_host_correctness = "";
$ssl_info->cert_host_status = 1;

if ($ssl_check_obj) {
    $ssl_info->server_address = $ssl_check_obj->host_resolve . "<br>" . $ssl_check_obj->server_type;
    $ssl_info->server_status = ($ssl_check_obj->host_resolve_status ? 0 : 1);
    $ssl_info->cert_correctness = $ssl_check_obj->browser_support;
    $ssl_info->cert_status = ($ssl_check_obj->browser_support_status ? 0 : 1);
    $ssl_info->cert_expiration = "";
    $ssl_expiration_date = $ssl_check_obj->expire_date;
    if (strtotime($ssl_expiration_date) >= strtotime(date("Y-m-d"))) {
        $ssl_info->cert_expiration = "The certificate will expire in " . ((strtotime($ssl_expiration_date) - strtotime(date("Y-m-d"))) / 86400) . " days.";
    }
    $ssl_info->cert_expire_status = ($ssl_check_obj->remain_date_status ? 0 : 1);
    $ssl_info->cert_host_correctness = $ssl_check_obj->host_in_cert;
    $ssl_info->cert_host_status = ($ssl_check_obj->validation_status ? 0 : 1);
}
$essential_info = new stdClass();
$essential_info->sitemap_info = "XML Sitemap Not Present";
$essential_info->sitemap_status = 1;
if ($reviewObj["sitmapCheck"] == 200) {
    $essential_info->sitemap_info = "XML Sitemap Present";
    $essential_info->sitemap_status = 0;
}
$essential_info->safe_info = "Google Safe Check : Issues Detected";
$essential_info->safe_status = 1;
if ($reviewObj["safeBrowsing"] == 0) {
    $essential_info->safe_info = "Google Safe Check : Site is Safe";
    $essential_info->safe_status = 0;
}
$essential_info->load_info = "Site Loading Slow";
$essential_info->load_status = 1;
if ($reviewObj["desktopPageSpeed"] >= 80) {
    $essential_info->load_info = "Site Loading High";
    $essential_info->load_status = 0;
} else if ($reviewObj["desktopPageSpeed"] >= 60) {
    $essential_info->load_info = "Site Loading Medium";
    $essential_info->load_status = 2;
}

$essential_info->analytics_info = "Analytics Not Installed";
$essential_info->analytics_status = 1;

$sql = "SELECT count(*) cn FROM `users_networks` 
                              WHERE `dashboardID` = :dashboardID 
                                AND `networkID` = :networkID 
                                AND `defaultCheck` = 1";
$data = [
    'dashboardID' => $dashboardIdx,
    'networkID' => 1
];

$result = $db->select($sql, $data);
if (count($result) > 0) {
    if (isset($result[0]["cn"])) {
        if ($result[0]["cn"] > 0) {
            $essential_info->analytics_info = "Analytics Installed";
            $essential_info->analytics_status = 0;
        }
    }
}

$essential_info->console_info = "Search Console Not Installed";
$essential_info->console_status = 1;

$data['networkID'] = 3;
$result = $db->select($sql, $data);
if (count($result) > 0) {
    if (isset($result[0]["cn"])) {
        if ($result[0]["cn"] > 0) {
            $essential_info->console_info = "Search Console Installed";
            $essential_info->console_status = 0;
        }
    }
}

$internal_external_links = json_decode($reviewObj["internal_external_links"]);
$__in_array_temp = $internal_external_links->internal;
$__out_array_temp = $internal_external_links->external;
$__broken_array_temp = $internal_external_links->broken;
$links = array();
foreach ($__in_array_temp as $count => $proc_obj) {
    $new_link = new stdClass();
    $new_link->type = "Internal Links";
    if ($proc_obj->follow_type == "dofollow")
        $new_link->follow = "Dofollow";
    else
        $new_link->follow = "Nofollow";
    $new_link->url = $proc_obj->href;
    $new_link->text = $proc_obj->href;
    if ($proc_obj->text != "") $new_link->text = $proc_obj->text;
    if (strlen($new_link->text) > 40) $new_link->text = substr($new_link->text, 0, 40);

    array_push($links, $new_link);
}
foreach ($__out_array_temp as $count => $proc_obj) {
    $new_link = new stdClass();
    $new_link->type = "External Links";
    if ($proc_obj->follow_type == "dofollow")
        $new_link->follow = "Dofollow";
    else
        $new_link->follow = "Nofollow";
    $new_link->url = $proc_obj->href;
    $new_link->text = $proc_obj->href;
    if ($proc_obj->text != "") $new_link->text = $proc_obj->text;
    if (strlen($new_link->text) > 40) $new_link->text = substr($new_link->text, 0, 40);

    array_push($links, $new_link);
}
$broken_links = array();
for ($i = 0; $i < count($__broken_array_temp); $i++) {
    $new_link = new stdClass();
    $new_link->url = $__broken_array_temp[$i];
    array_push($broken_links, $new_link);
}

$link_analysis = new stdClass();
$link_analysis->inpage_status = (count($links) > 0 ? 0 : 1);
$link_analysis->broken_status = (count($broken_links) > 0 ? 1 : 0);
$link_analysis->links = $links;
$link_analysis->broken_links = $broken_links;

$page_speed = new stdClass();
$page_speed->site = $siteURL;
$page_speed->site_score = "slow";
$page_speed->site_status = 1;

if ($reviewObj["desktopPageSpeed"] >= 80) {
    $page_speed->site_score = "high";
    $page_speed->site_status = 0;
} else if ($reviewObj["desktopPageSpeed"] >= 60) {
    $page_speed->site_score = "medium";
    $page_speed->site_status = 2;
}
$page_speed->desktop = $reviewObj["desktopPageSpeed"];
$page_speed->mobile = $reviewObj["mobilePageSpeed"];
$page_speed->page_size = $reviewObj["pageSize"];
$page_speed->page_size_status = 1;
if ($page_speed->page_size <= 320) $page_speed->page_size_status = 0;
$page_speed->load_time = $reviewObj["desktopPageSpeed"];
$page_speed->load_time_status = 1;
if ($page_speed->load_time < 3) $page_speed->load_time_status = 0;

$privacy = new stdClass();
$privacy->email_info = "Email address has been found in plain text!";
$privacy->email_status = 1;
if ($reviewObj["emailCount"] == 0) {
    $privacy->email_info = "Good, no email address has been found in plain text.";
    $privacy->email_status = 0;
}
$privacy->safe_info = "The website is blacklisted and not safe to use.";
$privacy->safe_status = 1;
if ($reviewObj["safeBrowsing2"] == 204) {
    $privacy->safe_info = "The website is not blacklisted and look safe to use.";
    $privacy->safe_status = 0;
}
$privacy->privacy_status = 1;
if (($privacy->email_status == 0) && ($privacy->safe_status == 0)) {
    $privacy->privacy_status = 0;
} else if (($privacy->email_status == 0) || ($privacy->safe_status == 0)) {
    $privacy->privacy_status = 2;
}

$social = new stdClass();
$social->facebook = number_format($reviewObj["social_fb_count"]);
$social->google = number_format($reviewObj["social_plus_count"]);
$social->stumble = number_format($reviewObj["social_stumble_count"]);
$social->linkedin = number_format($reviewObj["social_linkedin_count"]);
$social->social_status = 0;

$docTypeCheck = json_decode($reviewObj["docTypeCheck"]);
$technology = new stdClass();
$technology->encoding_info = "Oh no, language/character encoding is not specified!";
$technology->encoding_status = 1;
if ($docTypeCheck->docCheck) {
    $technology->encoding_info = "Great, language/character encoding is specified: " . $reviewObj["charterSet"];
    $technology->encoding_status = 0;
}
$technology->doctype_info = "HTML doctype declaration is missing or is syntactically invalid!";
$technology->doctype_status = 1;
if ($docTypeCheck->docType) {
    $technology->doctype_info = "Your Web Page doctype is " . $docTypeCheck->docType;
    $technology->doctype_status = 0;
}
$technology->w3c_info = "W3C not validated";
$technology->w3c_status = 1;
if ($docTypeCheck->__w3c_check_data == 1) {
    $technology->w3c_info = "Yes, W3C Validated";
    $technology->w3c_status = 0;
}

$mobile_obj = new stdClass();
$mobile_obj->phone_screen = $reviewObj["mobileScreenData"];
if (substr($mobile_obj->phone_screen, 0, 4) != "<img") $mobile_obj->phone_screen = "";
$mobile_obj->tablet_screen = $reviewObj["desktopScreenData"];
if (substr($mobile_obj->tablet_screen, 0, 4) != "<img") $mobile_obj->tablet_screen = "";
$mobile_obj->mobile_info = "Oh No! WebSite is not mobile-friendly.";
$mobile_obj->mobile_status = 1;
if ($reviewObj["isMobileFriendly"] == 1) {
    $mobile_obj->mobile_info = "WebSite appears to be optimised for viewing on a mobile phone or a tablet.";
    $mobile_obj->mobile_status = 0;
}

$keywords_rank = '{"report_date":"21 August 2017","keywords":[{"keyword":"Google","rank":"1","change":"1 UP","change_status":0},{"keyword":"glow","rank":"2","change":"1 UP","change_status":0},{"keyword":"Analytics","rank":"3","change":"1 UP","change_status":0},{"keyword":"console","rank":"4","change":"3 DOWN","change_status":1},{"keyword":"adwords","rank":"5","change":"1 UP","change_status":0},{"keyword":"server","rank":"6","change":"1 DOWN","change_status":1}]}';

$competitors = '[{"url":"competitor.com","rank":"1st","traffic":"4000 Visit per Month","source":"Adwords Facebook buyads.com","analysis":""},{"url":"www.google.com","rank":"2nd","traffic":"3000 Visit per Month","source":"Adwords Facebook buyads.com","analysis":""},{"url":"linkedin.com","rank":"3rd","traffic":"400 Visit per Month","source":"Adwords Facebook buyads.com","analysis":""},{"url":"www.facebook.com","rank":"4th","traffic":"200 Visit per Month","source":"Adwords Facebook buyads.com","analysis":""}]';

$site_keywords = $arr_people_also_ask;

$linksto = '{"total":254,"links":[{"url":"http://www.google.com/","count":12},{"url":"http://www.facebook.com/","count":2},{"url":"http://www.linkein.com/","count":1},{"url":"http://www.google.com/","count":12},{"url":"http://www.facebook.com/","count":2},{"url":"http://www.linkein.com/","count":1}],"contents":[{"url":"http://www.google.com/","count":12},{"url":"http://www.facebook.com/","count":2},{"url":"http://www.linkein.com/","count":1},{"url":"http://www.google.com/","count":12},{"url":"http://www.facebook.com/","count":2},{"url":"http://www.linkein.com/","count":1}]}';

$ret = new stdClass();
$ret->ssl_info = $ssl_info;
$ret->essential_info = $essential_info;
$ret->site_keywords = $site_keywords;
$ret->keywords_rank = json_decode($keywords_rank);
$ret->mobile = $mobile_obj;
$ret->competitors = json_decode($competitors);
$ret->page_speed = $page_speed;
$ret->privacy = $privacy;
$ret->social = $social;
$ret->technology = $technology;
$ret->linksto = json_decode($linksto);
$ret->link_analysis = $link_analysis;
$ret->error = 0;

echo json_encode($ret);

?>