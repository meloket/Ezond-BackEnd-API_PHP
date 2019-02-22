<?php

/*
* @author Balaji
* @name: Turbo Website Reviewer
* @copyright © 2017 ProThemes.Biz
*
*/

class socialCount
{
    private $url;
    function __construct($url){
        $url = clean_with_www($url);
        $this->url = urlencode($url);
        $this->timeout = 20;
    }
    
    function getDataCurl($url) {
        global $_SERVER;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if(isset($_SERVER['HTTP_USER_AGENT'])) curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $response = curl_exec($ch);
        if (curl_error($ch))
        {
            return '0';
        }
        return $response;
    }
    
    function getLinkedin() {
        $lurl = 'http://' . $this->url;
        $json_string = getMyData("http://www.linkedin.com/countserv/count/share?url=$lurl&format=json");
        $json = json_decode($json_string, true);
        return isset($json['count']) ? intval($json['count']) : 0;
    }
    
    function getDelicious() {
        $json_string = $this->getDataCurl('http://feeds.delicious.com/v2/json/urlinfo/data?url=' .
            $this->url);
        $json = json_decode($json_string, true);
        return isset($json[0]['total_posts']) ? intval($json[0]['total_posts']) : 0;
    }
    
    function getPlusones() {
        return 0;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://clients6.google.com/rpc");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"http://' .
            rawurldecode($this->url) .
            '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        $response = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($response, true);
        return isset($json[0]['result']['metadata']['globalCounts']['count']) ? intval($json[0]['result']['metadata']['globalCounts']['count']) :
            0;
    }

    function getStumble() {
        $json_string = $this->getDataCurl('http://www.stumbleupon.com/services/1.01/badge.getinfo?url=' .
            $this->url);
        $json = json_decode($json_string, true);
        return isset($json['result']['views']) ? intval($json['result']['views']) : 0;
    }

    function getPinterest() {
        $purl = 'http://' . $this->url;
        $purl = sprintf('http://api.pinterest.com/v1/urls/count.json?url=%s', $purl);
        $response = $this->getDataCurl($purl);
        $response = str_replace(array('(', ')'), '', $response);
        $response = str_replace("receiveCount", '', $response);
        if (!$json = json_decode($response, true))
            return 0;
        return isset($json['count']) ? (int)$json['count'] : 0;
    }

    function getFb() {

        $this->url = str_replace('www.','',$this->url);
        //$json_string = $this->getDataCurl('http://demo.atozseotools.com/fb/' .            $this->url);
        $json_string = $this->getDataCurl('https://graph.facebook.com/?fields=share&ids=http://'.$this->url.',https://'.$this->url);
        $json = json_decode($json_string, true);

        $share = 0;
        $fld1 = 'http://'.$this->url;
        $fld2 = 'https://'.$this->url;

        if($json[$fld1]["share"]["share_count"]) $share += $json[$fld1]["share"]["share_count"];
        if($json[$fld2]["share"]["share_count"]) $share += $json[$fld2]["share"]["share_count"];

        return $share;
        /*
        $share_count = $json[1];
        $like_count = $json[0];
        $comment_count = $json[2];
        
        $val = array($share_count, $like_count, $comment_count);
        return $val;
        */
    }
    
    function getTweets() {
        $this->url = str_replace('www.','',$this->url);
        $json_string = $this->getDataCurl('http://api.prothemes.biz/tweets.php?site=http://www.' .
            $this->url);
        $json = Trim($json_string);
        return $json;
    }
}

?>