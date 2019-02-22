<?php
function Lookup_GoogleSafeBrowsing_v4($url)
{
    $data = '{
      "client": {
        "clientId": "ezond",
        "clientVersion": "1.0"
      },
      "threatInfo": {
        "threatTypes":      ["MALWARE", "SOCIAL_ENGINEERING", "THREAT_TYPE_UNSPECIFIED", "UNWANTED_SOFTWARE", "POTENTIALLY_HARMFUL_APPLICATION"],
        "platformTypes":    ["LINUX"],
        "threatEntryTypes": ["URL"],
        "threatEntries": [
          {"url": "'.$url.'"}
        ]
      }
    }';
 
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://safebrowsing.googleapis.com/v4/threatMatches:find?key=AIzaSyB2O18subSyz9Zy7fBaeRq-ZfNNecDcBfI");
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", 'Content-Length: ' . strlen($data)));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    //$response = (array) json_decode(curl_exec($ch), true);
    curl_close ($ch);
    print_r($response);
    //return ($response['matches'][0]['threatType']) ? true : false;
}

/*

0	No available data
1	No unsafe content found
2	This site is unsafe
3	Some pages on this site are unsafe
4	Check a specific URL
5	This site hosts files that are not commonly downloaded
6	No available data

*/

function check_site_safety($url){
	$response = @file_get_contents("https://www.google.com/transparencyreport/api/v3/safebrowsing/status?site=".$url);
	$arr_resp = explode(",", $response);
	if(count($arr_resp) > 0)
		$response = $arr_resp[1];
	else
		$response = "0";
	if(($response == 2) || ($response == 3))
		return true;
	return false;
}

echo check_site_safety("gumblar.cn");

echo check_site_safety("https://marinereach.com/");
echo check_site_safety("google.com");
echo check_site_safety("ezond.com");
?>
