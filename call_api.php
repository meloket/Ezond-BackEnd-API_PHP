<?php
require_once(__DIR__ . '/config.php');

$arrApiCaption = array("Google Analystics", "Google Adwords", "Google Search Console", "Google Sheets", "Facebook Ads", "Facebook", "Twitter", "Youtube", "Linkedln", "Mail Chimp", "Campaign Monitor", "CallRail");

$arrApiURL = array(
    SITE_URL . "google/auth.php?method=analytics",
    SITE_URL . "google/auth.php?method=ads",
    SITE_URL . "google/auth.php?method=console",
    SITE_URL . "google/auth.php?method=sheet",
    SITE_URL . "facebook/test_login.php?method=ads",
    SITE_URL . "facebook/test_login.php?method=facebook",
    "Twitter",
    SITE_URL . "google/auth.php?method=youtube",
    "Linkedln",
    SITE_URL . "mailchimp/auth.php?method=mailchimp",
    "Campaign Monitor", "CallRail");

for ($i = 0; $i < count($arrApiCaption); $i++) {
    if (strpos($arrApiURL[$i], "http") !== false)
        echo "<a href='" . file_get_contents($arrApiURL[$i] . "&userID=test") . "' target='_blank'>" . $arrApiCaption[$i] . "</a>";
    else
        echo "<a>" . $arrApiCaption[$i] . "</a>";
    echo "<br><br>";
}
?>