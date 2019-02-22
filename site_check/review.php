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

$url = "www.jd.com";

if (isset($_GET['url'])) $url = $_GET['url'];

// Safe Browsing
echo '<h1>Safe Browsing</h1>';
echo safeBrowsing($url, true);

// PageSpeed Insights (Desktop)
echo '<h1>PageSpeed Insights (Desktop)</h1>';
$desktopScore = pageSpeedInsightChecker("http://" . $url, 'desktop', true);

if (intval($desktopScore) < 50) {
    $desktopSpeed = "Slow";
} elseif (intval($desktopScore) < 79) {
    $desktopSpeed = "Medium";
} else {
    $desktopSpeed = "Fast";
}

$desktopDescription = "<b>" . $desktopScore . " / 100</b><br><b>" . $url . "</b> desktop website speed is " . $desktopSpeed . ". Page speed is important for both search engines and visitors end.";

$desktopMsg = <<< EOT
	<script>var desktopPageSpeed = new Gauge({
		renderTo  : 'desktopPageSpeed',
		width     : 250,
		height    : 250,
		glow      : true,
		units     : 'Speed',
	    title       : 'Desktop',
	    minValue    : 0,
	    maxValue    : 100,
	    majorTicks  : ['0','20','40','60','80','100'],
	    minorTicks  : 5,
	    strokeTicks : true,
	    valueFormat : {
	        int : 2,
	        dec : 0,
	        text : '%'
	    },
	    valueBox: {
	        rectStart: '#888',
	        rectEnd: '#666',
	        background: '#CFCFCF'
	    },
	    valueText: {
	        foreground: '#CFCFCF'
	    },
		highlights : [{
			from  : 0,
			to    : 40,
			color : '#EFEFEF'
		},{
			from  : 40,
			to    : 60,
			color : 'LightSalmon'
		}, {
			from  : 60,
			to    : 80,
			color : 'Khaki'
		}, {
			from  : 80,
			to    : 100,
			color : 'PaleGreen'
		}],
		animation : {
			delay : 10,
			duration: 300,
			fn : 'bounce'
		}
	});

	desktopPageSpeed.onready = function() {
	    desktopPageSpeed.setValue($desktopScore);
	};


	desktopPageSpeed.draw();</script>
EOT;
echo '<script type="text/javascript" src="js/pagespeed.min.js"></script>';
echo '<canvas id="desktopPageSpeed"></canvas>' . $desktopMsg;
echo $desktopDescription;

// PageSpeed Insights (Mobile)
echo '<h1>PageSpeed Insights (Mobile)</h1>';
$mobileScore = pageSpeedInsightChecker("http://" . $url, 'mobile', true);

if (intval($mobileScore) < 50) {
    $mobileSpeed = "Slow";
} elseif (intval($mobileScore) < 79) {
    $mobileSpeed = "Medium";
} else {
    $mobileSpeed = "Fast";
}

$mobileDescription = "<b>" . $mobileScore . " / 100</b><br><b>" . $url . "</b> mobile website speed is " . $mobileSpeed . ". Page speed is important for both search engines and visitors end.";

$mobileMsg = <<< EOT
	<script>var mobilePageSpeed = new Gauge({
		renderTo  : 'mobilePageSpeed',
		width     : 250,
		height    : 250,
		glow      : true,
		units     : 'Speed',
	    title       : 'Mobile',
	    minValue    : 0,
	    maxValue    : 100,
	    majorTicks  : ['0','20','40','60','80','100'],
	    minorTicks  : 5,
	    strokeTicks : true,
	    valueFormat : {
	        int : 2,
	        dec : 0,
	        text : '%'
	    },
	    valueBox: {
	        rectStart: '#888',
	        rectEnd: '#666',
	        background: '#CFCFCF'
	    },
	    valueText: {
	        foreground: '#CFCFCF'
	    },
		highlights : [{
			from  : 0,
			to    : 40,
			color : '#EFEFEF'
		},{
			from  : 40,
			to    : 60,
			color : 'LightSalmon'
		}, {
			from  : 60,
			to    : 80,
			color : 'Khaki'
		}, {
			from  : 80,
			to    : 100,
			color : 'PaleGreen'
		}],
		animation : {
			delay : 10,
			duration: 300,
			fn : 'bounce'
		}
	});

	mobilePageSpeed.onready = function() {
	    mobilePageSpeed.setValue($mobileScore);
	};


	mobilePageSpeed.draw();</script>
EOT;

echo '<canvas id="mobilePageSpeed"></canvas>' . $mobileMsg;
echo $mobileDescription;

//	Alexa Global Rank
echo '<h1>Alexa Global Rank</h1>';
$traffic_rank = alexaRank($url);
echo number_format($traffic_rank[0] * 1) . "th most visited website in the World.<br>";
echo number_format($traffic_rank[2] * 1) . "th most visited website in the " . $traffic_rank[1] . ".";

print_r(alexaExtended($url));
// WHOIS Data	
print_r(whois_info($url));
print_r(host_info($url)); // Server IP, Server Location, Hosting Service Provider
// Favicon  ==>	https://www.google.com/s2/favicons?domain=www.google.com
// Facebook Likes Count, PlusOne Count, StumbleUpon Count, LinkedIn Count, Delicious Count, PInterest Count, Tweet Count
$count_obj = new socialCount($url);
echo sprintf("LinkedIn: %s, Delicious: %s, Google Plus: %s, Stumble: %s, PInterest: %s, Facebook: %s, Tweet: %s", $count_obj->getLinkedin(), $count_obj->getDelicious(), $count_obj->getPlusones(), $count_obj->getStumble(), $count_obj->getPinterest(), implode(" <=> ", $count_obj->getFb()), $count_obj->getTweets());

// Indexed Pages Count (Google)
echo '<h1>Indexed Pages Count</h1>';
$index_page_count = googleIndex($url);
$index_count = Trim(str_replace(',', '', $index_page_count));

if (intval($index_count) < 50) {
    $indexProgress = 'danger';
} elseif (intval($index_count) < 200) {
    $indexProgress = 'warning';
} else {
    $indexProgress = 'success';
}
echo 'Indexed pages in search engines : ' . number_format($index_count) . ' Page(s), Status: ( ' . $indexProgress . ' ) ';

$meta_infos = get_meta_info($url);

// Meta Title
echo '<h1>Meta Title</h1>';
echo $meta_infos->meta_title;

// Meta Description
echo '<h1>Meta Description</h1>';
echo $meta_infos->meta_description;

// Meta Keywords
echo '<h1>Meta Keywords</h1>';
echo $meta_infos->meta_keywords;

// Google Preview
echo '<h1>Google Preview</h1>';
google_preview($meta_infos);

// Missing Image Alt Attribute
echo '<h1>Missing Image Alt Attribute</h1>';
$missing_info = missing_img_alt($url, $meta_infos->sourceData);
echo 'We found ' . $missing_info->imageCount . ' images on this web page.<br>';
echo (($missing_info->imageWithOutAltTag == 0) ? 'No' : $missing_info->imageWithOutAltTag) . ' ALT attributes are empty or missing.';
for ($i = 0; $i < count($missing_info->imgArr); $i++) {
    echo "<br>" . $missing_info->imgArr[$i];
}

// Keywords Cloud, Keyword Consistency
$keyword_data = keywords_cloud($url, $meta_infos->sourceData);

echo '<h1>Keywords Cloud</h1>';
echo '<ul class="keywordsTags">';
if ($keyword_data) echo $keyword_data->keyData;
echo '</ul>';

echo '<h1>Keyword Consistency</h1>';
if ($keyword_data) echo $keyword_data->keywordConsistencyString;

$__ssl_infos = new stdClass();

// SSL Checker
echo '<h1>SSL Check</h1>';
$ssl_check_value = ssl_check($url);
if ($ssl_check_value) {
    echo "SSL Check Success!, The certificate will expire in " . $ssl_check_value . " days.";
} else {
    echo "SSL Check Failed";
}

echo "<br>";
print_r($__ssl_infos);
echo "<br>";

// XML Sitemap
echo '<h1>XML Sitemap</h1>';
$sitemapLink = "http://" . $url . '/sitemap.xml';
$httpCode = getHttpCode($sitemapLink);
echo(($httpCode == '404') ? 'Oh no, XML Sitemap file not found!' : 'Good, you have XML Sitemap file!');
echo '<br><a href="' . $sitemapLink . '" title="XML Sitemap Link" rel="nofollow" target="_blank">' . $sitemapLink . '</a>';

// Page Size
echo '<h1>Page Size</h1>';
$pageSize = __size_as_kb($meta_infos->pageSize);
if ($pageSize) {
    echo(($pageSize > 320) ? "Page Size is so large<br>" : "");
    echo "" . $pageSize . " KB (World Wide Web average is 320 KB)";
}

// Web Site Load Time
echo '<h1>Web Site Load Time</h1>';
$timeTaken = $meta_infos->timeTaken;
if ($timeTaken) {
    echo(($timeTaken >= 1) ? "Web Site Load Time is so long<br>" : "");
    echo "" . $timeTaken . " second(s)";
}

// Mobile Compatibility
echo '<h1>Mobile Compatibility</h1>';
$compatibility_check = mobile_compatibility($url, $meta_infos->sourceData);
if ($compatibility_check) echo 'Bad, embedded objects detected.<br>Embedded Objects such as Flash, Silverlight or Java. It should only be used for specific enhancements.';
else echo 'Perfect, no embedded objects detected.';

// Mobile Friendliness
echo '<h1>Mobile Friendliness</h1>';
$ret = mobile_friendly($url);

if ($ret->error == 0) {
    echo(($ret->isMobileFriendly) ? 'Awesome! This page is mobile-friendly!<br>' : 'Oh No! This page is not mobile-friendly.<br>');
    echo 'Your mobile friendly score is ' . $ret->mobileScore . '/100';
} else {
    echo 'Something went wrong!';
}

// Mobile Preview Screenshot
echo '<h1>Mobile Preview Screenshot</h1>';

if ($ret->error == 0) {
    if ($ret->screenData != "") echo $ret->screenData;
} else {
    echo 'No Screenshot available!';
}

?>
<style type="text/css">
  .googlePreview {
    border: 4px solid #f1f1f1;
    padding: 12px 10px;
    border-radius: 4px;
  }

  .googlePreview p:first-child {
    color: #00e;
    font-size: 16px;
    margin-bottom: 2px;
    overflow-x: hidden;
    text-decoration: underline;
    white-space: nowrap;
  }

  .googlePreview p:nth-child(2) {
    color: #00802a;
    font-size: 14px;
  }

  .googlePreview p {
    color: #444;
    font-family: helvetica, arial, sans-serif;
    font-size: 13px;
    margin: 0 0 5px;
  }

  .bold {
    font-weight: 700;
  }

  .keywordsTags li {
    background-color: #ecebeb;
    border-radius: 4px;
    color: #333;
    display: inline-block;
    font-size: 12px;
    list-style-type: none;
    margin: 0 12px 12px 0;
  }

  .keywordsTags .keyword {
    padding: 0 6px;
  }

  .keywordsTags .number {
    background: #4693d5 none repeat scroll 0 0;
    border-radius: 0 3px 3px 0;
    color: #fff;
    display: inline-block;
    padding: 0 6px;
  }
</style>