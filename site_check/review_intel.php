<?php
require_once "library/functions.php";
require_once "library/safe_browser_helper.php";
require_once "library/page_speed_insight_helper.php";
require_once "library/alexa_helper.php";
require_once "library/compressiontest_helper.php";
require_once "library/google_index_helper.php";
require_once "library/host_info_helper2.php";
require_once "library/socialcount.php";
require_once "library/simple_html_dom.php";
require_once "library/kd.php";

$url = "www.jd.com";

if (isset($_GET['url'])) $url = $_GET['url'];

$__site_source_data = "";
$__site_dom_data = "";

$__meta_obj = get_meta_info($url);
print_r($__meta_obj);
echo "<br>";

$__doctype_obj = __get_doc_type();
print_r($__doctype_obj);
echo "<br>";

$__w3c_check_data = __check_w3c_validate($url);
echo $__w3c_check_data;
echo "<br>";

$__internal_external_links = __get_internal_external_links($url);
print_r($__internal_external_links);
echo "<br>";

$__ssl_infos = new stdClass();

echo '<h1>SSL / HTTPS</h1>';
$ssl_check_value = ssl_check($url);

echo "Host Resolve : " . $__ssl_infos->host_resolve . "<br>";
echo "Host Resolve Status: " . ($__ssl_infos->host_resolve_status ? "TRUE" : "FALSE") . "<br>";
echo "Server Type : " . $__ssl_infos->server_type . "<br>";
echo "Browser Support : " . $__ssl_infos->browser_support . "<br>";
echo "Browser Support Status: " . ($__ssl_infos->browser_support_status ? "TRUE" : "FALSE") . "<br>";
echo "Certification Expiration Date : " . $__ssl_infos->expire_date . "<br>";
echo "Certification Remain Dates : " . $__ssl_infos->remain_date . "<br>";
echo "Certification Remain Status: " . ($__ssl_infos->remain_date_status ? "TRUE" : "FALSE") . "<br>";
echo "Certification Validation Check : " . $__ssl_infos->host_in_cert . "<br>";
echo "Certification Validation Status: " . ($__ssl_infos->validation_status ? "TRUE" : "FALSE") . "<br>";

echo '<h1>Essentials</h1>';

$sitemapLink = "http://" . $url . '/sitemap.xml';
$httpCode = getHttpCode($sitemapLink);
$__google_safety_check = 0;
$__google_safe_check = check_site_safety($url);
$desktopScore = pageSpeedInsightChecker("http://" . $url, 'desktop', true);
if (intval($desktopScore) < 50) {
    $desktopSpeed = "Slow";
} elseif (intval($desktopScore) < 79) {
    $desktopSpeed = "Medium";
} else {
    $desktopSpeed = "Fast";
}
$__arr_google_safety = array("No available data", "No unsafe content found", "This site is unsafe", "Some pages on this site are unsafe", "Check a specific URL", "This site hosts files that are not commonly downloaded", "No available data");

echo "XML Sitemap Check : XML Sitemap " . (($httpCode == '404') ? 'Not ' : '') . "Present <br>";
echo "Google Safe Check : " . (($__google_safety_check == 2 || $__google_safety_check == 3) ? "Issues Detected, " . $__arr_google_safety[$__google_safety_check] : " Safety") . "<br>";
echo "Site Loading Check : Site Loading " . $desktopSpeed . "<br>";

echo '<h1>Social Presence</h1>';

$count_obj = new socialCount($url);
echo "Facebook: " . $count_obj->getFb() . "<br>";
echo "Google Plus One: " . $count_obj->getPlusones() . "<br>";
echo "StumbleUpon: " . $count_obj->getStumble() . "<br>";
echo "LinkedIn: " . $count_obj->getLinkedin() . "<br>";


echo '<h1>Privacy</h1>';
echo safeBrowsing($url, true);

exit();

?>