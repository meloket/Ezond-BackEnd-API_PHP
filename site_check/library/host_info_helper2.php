<?php

/*
* @author Balaji
* @name Turbo Website Reviewer - PHP Script
* @copyright © 2017 ProThemes.Biz
*
*/

function host_info($site) {
    
    $ch = curl_init('http://www.iplocationfinder.com/' . clean_url($site));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT,
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    $data = curl_exec($ch);
    preg_match('~ISP.*<~', $data, $isp);
    preg_match('~Country.*<~', $data, $country);
    preg_match('~IP:.*<~', $data, $ip);

    $country = explode(':', strip_tags($country[0]));
    $country = trim(str_replace('Hide your IP address and Location here', '', $country[1]));
    if ($country == '')
        $country = 'Not Available';

    $isp = explode(':', strip_tags($isp[0]));
    $isp = trim($isp[1]);
    if ($isp == '')
        $isp = 'Not Available';

    $ip = $ip[0];
    $ip = trim(str_replace(array(
        'IP:',
        '<',
        '/label>',
        '/th>td>',
        '/td>'), '', $ip));
    if ($ip == '')
        $ip = 'Not Available';
    return array($ip,$country,$isp);
}

function whois_info($site) {
    
    $data = file_get_contents('https://www.whois.com/whois/' . $site);
    $str_pos = strpos($data, '<pre class="df-raw" id="registrarData">');
    $data = substr($data, $str_pos + strlen('<pre class="df-raw" id="registrarData">'));
    $str_pos = strpos($data, '</pre>');
    $data = substr($data, 0, $str_pos);
    $data = str_replace("\n", "<br>", $data);
    return $data;
}

function keywords_cloud($site, $sourceData = ""){
    global $__site_source_data;

    $filename = "http://".$site;

    if($__site_source_data) $sourceData = $__site_source_data;

    if($sourceData){

    } else {
        //Get Data of the URL
        $sourceData = getMyData($filename);
    }

    //Fix Meta Uppercase Problem
    $html = str_ireplace(array("Title","TITLE"),"title",$sourceData);
    $html = str_ireplace(array("Description","DESCRIPTION"),"description",$html);
    $html = str_ireplace(array("Keywords","KEYWORDS"),"keywords",$html);
    $html = str_ireplace(array("Content","CONTENT"),"content",$html);  
    $html = str_ireplace(array("Meta","META"),"meta",$html);  
    $html = str_ireplace(array("Name","NAME"),"name",$html);      
        
    //Check Empty Source Data
    if($sourceData == '') return false;
    $title = $description = $keywords = '';
    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $nodes = $doc->getElementsByTagName('title');
    if($nodes->length > 0) $title = $nodes->item(0)->nodeValue;
    $metas = $doc->getElementsByTagName('meta');

    for ($i = 0; $i < $metas->length; $i++){
        $meta = $metas->item($i);
        if($meta->getAttribute('name') == 'description')
           $description = $meta->getAttribute('content');
        if($meta->getAttribute('name') == 'keywords')
            $keywords = $meta->getAttribute('content');
    }

    $obj = new KD();
    $obj->domain = $site;
    $obj->domainData = $sourceData;
    $resdata = $obj->result(); 
    $keyData = '';
    $blockChars = $blockWords = $outArr = array();
    $keyCount = 0;
    
    foreach($resdata as $outData){
        if(isset($outData['keyword'])){
        $outData['keyword'] = Trim($outData['keyword']);
        if($outData['keyword'] != null || $outData['keyword'] != "") {
            
            $blockChars = array('~','=','+','?',':','_','[',']','"','.','!','@','#','$','%','^','&','*','(',')','<','>','{','}','|','\\','/',',');
            $blockWords = array('and', 'is', 'was', 'to', 'into', 'with', 'without', 'than', 'then', 'that', 'these', 'this', 'their', 'them', 'from', 'your', 'able', 'which', 'when', 'what', 'who');
            $blockCharsBol = false;
            foreach($blockChars as $blockChar){
                if(str_contains($outData['keyword'],$blockChar))
                {
                    $blockCharsBol = true;
                    break;
                }
            }
    
            if (!preg_match('/[0-9]+/', $outData['keyword'])){
                if(!$blockCharsBol){
                 if (!in_array($outData['keyword'], $blockWords)) {
                    if($keyCount == 15)
                        break;
                    $outArr[] = array($outData['keyword'], $outData['count'], $outData['percent']);
                    $keyData .= '<li><span class="keyword">'.$outData['keyword'].'</span><span class="number">'.$outData['count'].'</span></li>';
                    $keyCount++;
                 }
                }
            }   
         }
         }
    }

    $ret = new stdClass();
    $ret->outArr = $outArr;
    $ret->keyData = $keyData;

    $outCount = count($outArr);

    //Get H1 to H6 Tags
    $tags = array ('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
    $h1Count = $h2Count = $h3Count = $h4Count = $h5Count = $h6Count = 0;
    $elementListData = $texts = array ();
    $hideCount = 0;
    $hideClass = $headStr = '';
    
    foreach($tags as $tag) {
          $elementList = $doc->getElementsByTagName($tag);
          foreach($elementList as $element){
             if($hideCount == 3)
                $hideClass = 'hideTr hideTr1';
             $headContent = strip_tags($element->textContent);
             $texts[$element->tagName][] = $headContent;
             if(strlen($headContent) >= 100)
                $headStr.= '<tr class="'.$hideClass.'"> <td>&lt;'.strtoupper($element->tagName).'&gt; <b>'.truncate($headContent, 20, 100).'</b> &lt;/'.strtoupper($element->tagName).'&gt;</td> </tr>';
             else
                $headStr.= '<tr class="'.$hideClass.'"> <td>&lt;'.strtoupper($element->tagName).'&gt; <b>'.$headContent.'</b> &lt;/'.strtoupper($element->tagName).'&gt;</td> </tr>';
             $elementListData[$tag][] = array(strtoupper($element->tagName),$headContent);
             $hideCount++;
          }
    }
    
    $hideClass = $keywordConsistencyTitle = $keywordConsistencyDes = $keywordConsistencyH = $keywordConsistencyData = '';
    
    $hideCount = 1;
    $keywordConsistencyScore = 0;
    
    foreach($outArr as $outKey){
        if(str_contains($title, $outKey[0], true)){
            $keywordConsistencyTitle = "YES";
            $keywordConsistencyScore++;
        }else{
            $keywordConsistencyTitle = "NO";
        }
       
        if(str_contains($description, $outKey[0], true)){
            $keywordConsistencyDes = "YES";
            $keywordConsistencyScore++;
        }else{
            $keywordConsistencyDes = "NO";
        } 
        
        $keywordConsistencyH = "NO";
        
        foreach($texts as $htags){
            foreach($htags as $htag){
                if(str_contains($htag, $outKey[0], true)){
                    $keywordConsistencyH = "YES";
                    break 2;
                }
            }
        }
            
        if($hideCount == 5)
            $hideClass = 'hideTr hideTr3';
                
        $keywordConsistencyData .= '<tr class="'.$hideClass.'"> 
                <td>'.$outKey[0].'</td> 
                <td>'.$outKey[1].'</td> 
                <td>'.$keywordConsistencyTitle.'</td>
                <td>'.$keywordConsistencyDes.'</td>
                <td>'.$keywordConsistencyH.'</td>   
                </tr>';
        $hideCount++;
    }
    
    if($keywordConsistencyScore == 0)
        $keywordConsistencyClass = 'errorBox';
    elseif($keywordConsistencyScore < 4)
        $keywordConsistencyClass = 'improveBox';
    else
        $keywordConsistencyClass = 'passedBox';
        
    $ret->keywordConsistencyString = '<div class="'.$keywordConsistencyClass.'">
    <div class="msgBox">       
        <table class="table table-striped table-responsive">
            <thead>
                <tr>
                    <th>Keywords</th>
                    <th>Freq</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>&lt;H&gt;</th>
                </tr>
            </thead>
            <tbody>
                '.$keywordConsistencyData.'
           </tbody>
        </table>';

    return $ret;
}

function desktop_screenshot($site) {
    
    $url = "http://".$site;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/pagespeedonline/v1/runPagespeed?screenshot=true&url=' . $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1000);

    $data = curl_exec($ch);
    curl_close($ch);
        
    $jsonData = json_decode($data,true);

    $ret = new stdClass();
    $ret->error = 0;
    $ret->screenData = "";

    if($jsonData != null || $jsonData == ""){
        $screenData = "";
        if(isset($jsonData['screenshot'])){
            $screenData = str_replace("_","/",$jsonData['screenshot']['data']);
            $screenData = str_replace("-","+",$screenData);
        }
        if($screenData == '')
            $screenData = '';
        else
            $screenData  = '<img src="data:image/jpeg;base64,'.$screenData.'" />';
        $ret->screenData = $screenData;
    }else{
        $ret->error = 1;
    }
    
    return $ret;
}
function mobile_friendly($site) {
    $isMobileFriendlyMsg = '';
    $mobileClass = $mobileScreenClass = 'lowImpactBox';

    $mobileCheckMsg = 'Mobile Friendliness refers to the usability aspects of your mobile website, which Google uses as a ranking signal in mobile search results. <br>';
    $mobileScreenClassMsg = 'The number of people using the Mobile Web is huge; over 75 percent of consumers have access to smartphones. <br> Your website should look nice on the most popular mobile devices.<br>Tip: Use an analytics tool to track mobile usage of your website.<br>';
    
    $url = "http://".$site;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/pagespeedonline/v3beta1/mobileReady?url=' . $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1000);

    $data = curl_exec($ch);
    curl_close($ch);
        
    $jsonData = json_decode($data,true);

    $ret = new stdClass();
    $ret->isMobileFriendly = false;
    $ret->mobileScore = 0;
    $ret->screenData = "";
    $ret->error = 0;
    
    $lang['AN116'] = 'Awesome! This page is mobile-friendly!';
    $lang['AN118'] = 'Oh No! This page is not mobile-friendly.';
    if($jsonData != null || $jsonData == ""){
        if(isset($jsonData['ruleGroups'])){
            $mobileScoreData = Trim($jsonData['ruleGroups']['USABILITY']['score']);
            $mobileScore = ($mobileScoreData == '' ? 0 : $mobileScoreData);
            
            $isMobileFriendly = Trim($jsonData['ruleGroups']['USABILITY']['pass']);
            $isMobileFriendly = filter_var($isMobileFriendly, FILTER_VALIDATE_BOOLEAN);

            $screenData = "";
            if(isset($jsonData['screenshot'])){
                $screenData = str_replace("_","/",$jsonData['screenshot']['data']);
                $screenData = str_replace("-","+",$screenData);
            }
                    
            if($screenData == '')
                $mobileScreenData = '';
            else
                $mobileScreenData  = '<img src="data:image/jpeg;base64,'.$screenData.'" />';

            $ret->isMobileFriendly = $isMobileFriendly;
            $ret->mobileScore = $mobileScore;
            $ret->screenData = $mobileScreenData;
        } else {
            $ret->error = 1;    
        }
    }else{
        $ret->error = 1;
    }
    
    return $ret;
}

function mobile_compatibility($site, $sourceData = ""){
    global $__site_source_data, $__site_dom_data;

    $filename = "http://".$site;

    if($__site_source_data) $sourceData = $__site_source_data;
    if($sourceData){

    } else {
        //Get Data of the URL
        $sourceData = getMyData($filename);
    } 
        
    //Check Empty Source Data
    if($sourceData == '') return false;

    //Load Dom Data
    if($__site_dom_data) $domData = $__site_dom_data;
    else {
        $domData = load_html($sourceData);
        $__site_dom_data = $domData;
    }

    $mobileComCheck = false;
    
    foreach($domData->find('iframe') as $iframe)
        $mobileComCheck = true;
    
    foreach($domData->find('object') as $embedded)
        $mobileComCheck = true;
    
    foreach($domData->find('embed') as $embedded)
        $mobileComCheck = true;
    
    /*
    //Clean up memory
    $domData->clear();
    $domData = null;*/
    
    return $mobileComCheck;
}

function missing_img_alt($site, $sourceData = ""){
    global $__site_source_data, $__site_dom_data;

    $meta_obj = new stdClass();
    $meta_obj->imageWithOutAltTag = 0;
    $meta_obj->imageCount = 0;
    $meta_obj->imgArr = array();
    $meta_obj->url = $site;

    $filename = "http://".$site;

    if($__site_source_data) $sourceData = $__site_source_data;
    if($sourceData){

    } else {
        //Get Data of the URL
        $sourceData = getMyData($filename);
    }    
        
    //Check Empty Source Data
    if($sourceData == '') return $meta_obj;

    //Load Dom Data
    if($__site_dom_data) {
      $domData = $__site_dom_data;  
    } else {
        $domData = load_html($sourceData);
        $__site_dom_data = $domData;
    }
    if(!isset($domData)){
        $domData = load_html($sourceData);
        $__site_dom_data = $domData;
    }

    //Image without "alt" tag
    $imageCount = 0;
    $imageWithOutAltTag = 0;
    $hideClass = $imageWithOutAltTagData = '';
    $imgArr = array();
    
    foreach($domData->find('img') as $imgData){
        if(Trim($imgData->getAttribute('src')) != ""){
            //Valid Image
            $imageCount++;
            if(Trim($imgData->getAttribute('alt')) == ""){
                //Without "alt" tag!
                if($imageWithOutAltTag == 3)
                    $hideClass = 'hideTr hideTr2';
                $imageWithOutAltTagData .= '<tr class="'.$hideClass.'"> <td>'.Trim($imgData->getAttribute('src')).'</td> </tr>';
                $imgArr[] = Trim($imgData->getAttribute('src'));
                $imageWithOutAltTag++;
            }
        }
    }

    $meta_obj->imageWithOutAltTag = $imageWithOutAltTag;
    $meta_obj->imageCount = $imageCount;
    $meta_obj->imgArr = $imgArr;

    /*
    //Clean up memory
    $domData->clear();
    $domData = null;*/
    
    return $meta_obj;
}

function __get_values($__str_data, $__pre_pattern, $__post_pattern) {
    $__pos = strpos($__str_data, $__pre_pattern);
    if($__pos !== false){
        $__str_data = substr($__str_data, $__pos + strlen($__pre_pattern));
        $__pos = strpos($__str_data, $__post_pattern);
        if($__pos !== false) {
            return substr($__str_data, 0, $__pos);
        } else 
            return false;
    } else
    return false;
}

function __get_until_values($__str_data, $__post_pattern){
    $__pos = strpos($__str_data, $__post_pattern);
    if($__pos !== false)
        return substr($__str_data, 0, $__pos);
    return false;    
}

function ssl_check($site) {
    global $__ssl_infos;

    $__ssl_infos = new stdClass();
    $filename = "https://www.sslshopper.com/assets/snippets/sslshopper/ajax/ajax_check_ssl.php?hostname=".$site;
    //Get Data of the URL
    $sourceData = getMyData($filename);

    $__ssl_infos->error = 1;
    $__ssl_infos->ssl_check = false;
    $__ssl_infos->host_resolve = "";
    $__ssl_infos->server_type = "";
    $__ssl_infos->browser_support = "";
    $__ssl_infos->host_in_cert = "";
    $__ssl_infos->host_resolve_status = false;
    $__ssl_infos->browser_support_status = false;
    $__ssl_infos->remain_date_status = false;
    $__ssl_infos->validation_status = false;
    $__ssl_infos->expire_date = "";
    $__ssl_infos->remain_date = "";


    if($sourceData == '') return false;

    $__ssl_infos->error = 0;
    //$__ssl_infos->ssl_info = $sourceData;

    $sourceData = str_replace("<H3>", "<h3>", $sourceData);
    $sourceData = str_replace("</H3>", "</h3>", $sourceData);
    $__arr_h3 = explode("<h3>", $sourceData);

    if(count($__arr_h3) > 1) {
        $__ssl_infos->host_resolve = __get_until_values($__arr_h3[1], "</h3>");
        $__ssl_infos->host_resolve_status = (strpos($__ssl_infos->host_resolve, "does not resolve") === false);
    }
    if(count($__arr_h3) > 2) $__ssl_infos->server_type = __get_until_values($__arr_h3[2], "</h3>");
    if(count($__arr_h3) > 3) {
        $__ssl_infos->browser_support = __get_until_values($__arr_h3[3], "</h3>");
        $__ssl_infos->browser_support_status = (strpos($__ssl_infos->browser_support, "all the correct intermediate certificates are installed") !== false);
    }
    if(count($__arr_h3) > 5) {
        $__ssl_infos->host_in_cert = __get_until_values($__arr_h3[count($__arr_h3) - 1], "<");
        $__ssl_infos->validation_status = (strpos($__ssl_infos->host_in_cert, "is correctly listed in the certificate") !== false);
    }

    $__remain_date = __get_values($sourceData, 'The certificate will expire in ', 'day');
    if($__remain_date) {
        $__ssl_infos->remain_date = $__remain_date;
        $__ssl_infos->expire_date = date("Y-m-d", strtotime(date("Y-m-d")) + 86400 * ($__remain_date ? $__remain_date : 0));
        $__ssl_infos->remain_date_status = true;
    }

    if(strpos($sourceData, " class='failed'") !== false) return false;

    $__ssl_infos->ssl_check = true;

    return __get_values($sourceData, 'The certificate will expire in ', 'day');
}

function __get_doc_type() {
    global $__site_source_data;

    $sourceData = $__site_source_data;

    $anCheck = false;
    $docCheck = false;
    $docType = "";
    
    $doctypes = array(
        'HTML 5' => '<!DOCTYPE html>',
        'HTML 4.01 Strict' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
        'HTML 4.01 Transitional' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
        'HTML 4.01 Frameset' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
        'XHTML 1.0 Strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
        'XHTML 1.0 Transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'XHTML 1.0 Frameset' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
        'XHTML 1.1' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
    );
    
    if (preg_match("/\bua-\d{4,9}-\d{1,4}\b/i", $sourceData)){
        $anCheck = true;
    }

    $patternCode = "<!DOCTYPE[^>]*>";
    preg_match("#{$patternCode}#is", $sourceData, $matches);
    if(!isset($matches[0])){
    }else{
        $docType = array_search(strtolower(preg_replace('/\s+/', ' ', Trim($matches[0]))), array_map('strtolower', $doctypes));
        $docCheck = true;
    }

    $ret_obj = new stdClass();
    $ret_obj->anCheck = $anCheck;
    $ret_obj->docCheck = $docCheck;
    $ret_obj->docType = $docType;
    return $ret_obj;
}

function __check_w3c_validate($__site_url) {
    $w3DataCheck = 0;
    
    $w3Data = curlGET('https://validator.w3.org/nu/?doc=http%3A%2F%2F'.$__site_url.'%2F');
    //echo $w3Data;
    if($w3Data != ''){
        if(str_contains($w3Data,'document validates')){
            $w3DataCheck = 1;
        }else{
           $w3DataCheck = 2;
        }
    } else {
        $w3DataCheck = 3;
    }
    return $w3DataCheck;
}

function __get_internal_external_links($my_url_host) {
    global $__site_source_data, $__site_dom_data;
    
    $my_url = 'http://'.clean_url(raino_trim($my_url_host));
    $my_url_parse = parse_url($my_url);
    $inputHost = $my_url_parse['scheme'] . "://" . $my_url_parse['host'];

    $sourceData = "";
    if($__site_source_data) $sourceData = $__site_source_data;

    if($__site_dom_data) $domData = $__site_dom_data;
    else {
        $domData = load_html($sourceData);
        $__site_dom_data = $domData;
    }

    $urlRewritingClass= $urlRewritingMsg = $linkUnderScoreMsg = $linkUnderScoreClass = $hideMe = $inPageData = $inPageMsg = '';
    
    //Define Variables
    $ex_data_arr = $ex_data = array();
    $t_count = $i_links = $e_links = $i_nofollow = $e_nofollow = 0;
    $int_data = array();
    $ext_data = array();
    
    //URL Rewriting
    $urlRewriting = true;
    $webFormats = array('html', 'htm', 'xhtml', 'xht', 'mhtml', 'mht','asp', 'aspx','cgi', 
    'ihtml', 'jsp', 'las','pl', 'php', 'php3', 'phtml', 'shtml');
    
    //Underscore on URL's
    $linkUnderScore = false;
    if(isset($domData) && ($domData)){
        foreach($domData->find("a") as $href) {
            if(!in_array($href->href, $ex_data_arr)) {
                if(substr($href->href, 0, 1) != "" && $href->href != "#") {
                    $ex_data_arr[] = $href->href;
                    $ex_data[] = array(
                        'href' => $href->href,
                        'rel' => $href->rel,
                        'innertext' => Trim(strip_tags($href->plaintext))
                    );
                }
            }
        }
    }
    
    
    //Internal Links
    foreach ($ex_data as $count => $link) {
        $t_count++;
        $parse_urls = parse_url($link['href']);
        $type = strtolower($link['rel']);
        $myIntHost = $path = '';
        if(isset($parse_urls['path']))
            $path = $parse_urls['path'];
            
        if(isset($parse_urls['host']))
            $myIntHost = $parse_urls['host'];
        
        if ($myIntHost == $my_url_host || $myIntHost == "www." . $my_url_host) {
            $i_links++;
            
            $int_data[$i_links]['inorout'] = "Internal";
            $int_data[$i_links]['href'] = $link['href'];
            $int_data[$i_links]['text'] = $link['innertext'];
            
            if(mb_strpos($link['href'], "_") !== false)
                $linkUnderScore = true;
            
            $dotStr = $exStr = '';
            $exStr = explode('.',$path);
            $dotStr = Trim(end($exStr));
            if($dotStr != $path){
                if(in_array($dotStr,$webFormats))
                    $urlRewriting = false;
            }
            
            if ($type == 'dofollow' || ($type != 'dofollow' && $type != 'nofollow'))
                $int_data[$i_links]['follow_type'] = "dofollow";
    
            if ($type == 'nofollow'){
                $i_nofollow++;
                $int_data[$i_links]['follow_type'] = "nofollow";
            }
            
        } elseif ((substr($link['href'], 0, 2) != "//") && (substr($link['href'], 0, 1) == "/")) {
            $i_links++;
            $int_data[$i_links]['inorout'] = "Internal";
            $int_data[$i_links]['href'] = $inputHost.$link['href'];
            $int_data[$i_links]['text'] = $link['innertext'];
            
            if(mb_strpos($link['href'], "_") !== false)
                $linkUnderScore = true;
                
            $dotStr = $exStr = '';
            $exStr = explode('.',$path);
            $dotStr = Trim(end($exStr));
            if($dotStr != $path){
                if(in_array($dotStr,$webFormats))
                    $urlRewriting = false;
            }
            
            if ($type == 'dofollow' || ($type != 'dofollow' && $type != 'nofollow'))
                $int_data[$i_links]['follow_type'] = "dofollow";
                
            if ($type == 'nofollow') {
                $i_nofollow++;
                $int_data[$i_links]['follow_type'] = "nofollow";
            }
        } else{
                if(substr($link['href'], 0, 7) != "http://" && substr($link['href'], 0, 8) != "https://" &&
                substr($link['href'], 0, 2) != "//" && substr($link['href'], 0, 1) != "/" && substr($link['href'], 0, 1) != "#"
                && substr($link['href'], 0, 2) != "//" && substr($link['href'], 0, 6) != "mailto" && substr($link['href'], 0, 10) != "javascript") { 
                
                    $i_links++;
                    $int_data[$i_links]['inorout'] = "Internal";
                    $int_data[$i_links]['href'] = $inputHost.'/'.$link['href'];
                    $int_data[$i_links]['text'] = $link['innertext'];
                    if(mb_strpos($link['href'], "_") !== false)
                        $linkUnderScore = true;
                    
                    $dotStr = $exStr = '';
                    $exStr = explode('.',$path);
                    $dotStr = Trim(end($exStr));
                    if($dotStr != $path){
                        if(in_array($dotStr,$webFormats))
                            $urlRewriting = false;
                    }
                    
                    if ($type == 'dofollow' || ($type != 'dofollow' && $type != 'nofollow'))
                        $int_data[$i_links]['follow_type'] = "dofollow";
                        
                    if ($type == 'nofollow') {
                        $i_nofollow++;
                        $int_data[$i_links]['follow_type'] = "nofollow";
                    }
                }
            }
    }
    
    //External Links
    foreach ($ex_data as $count => $link)
    {
        $parse_urls = parse_url($link['href']);
        $type = strtolower($link['rel']);
        
        if ($parse_urls !== false && isset($parse_urls['host']) && $parse_urls['host'] !=
            $my_url_host && $parse_urls['host'] != "www." . $my_url_host) {
            $e_links++;
            $ext_data[$e_links]['inorout'] = "External";
            $ext_data[$e_links]['href'] = $link['href'];
            $ext_data[$e_links]['text'] = $link['innertext'];
            if ($type == 'dofollow' || ($type != 'dofollow' && $type != 'nofollow'))
                $ext_data[$e_links]['follow_type'] = "dofollow";
            if ($type == 'nofollow') {
                $e_nofollow++;
                $ext_data[$e_links]['follow_type'] = "nofollow";
            }
        } elseif ((substr($link['href'], 0, 2) == "//") && (substr($link['href'], 0, 1) != "/")) {
            $e_links++;
            $ext_data[$e_links]['inorout'] = "External";
            $ext_data[$e_links]['href'] = $link['href'];
            $ext_data[$e_links]['text'] = $link['innertext'];
            if ($type == 'dofollow' || ($type != 'dofollow' && $type != 'nofollow'))
                $ext_data[$e_links]['follow_type'] = "dofollow";
            if ($type == 'nofollow') {
                $e_nofollow++;
                $ext_data[$e_links]['follow_type'] = "nofollow";
            }
        }
    }
    
    /*
    //Clean up memory
    $domData->clear();
    $domData = null;
    */

    $ret_obj = new stdClass();
    $ret_obj->internal = $int_data;
    $ret_obj->external = $ext_data;

    $bLinks = array();
    
    foreach($int_data as $internal_link){
        $iLink = Trim($internal_link['href']);
        if(substr($iLink, 0, 2) == "//") {
            $iLink = 'http:' . $iLink;
        }
        elseif(substr($iLink, 0, 1) == "/") {
            $iLink = $inputHost . $iLink;
        }
        $httpCode = getHttpCode($iLink);
        
        if($httpCode == 404){
            $bLinks[] = $iLink;
        }
    }
    
    foreach($ext_data as $external_link){
        $eLink = Trim($external_link['href']);        
        $httpCode = getHttpCode($eLink);
        
        if($httpCode == 404){
            $bLinks[] = $eLink;
        }
    }

    $ret_obj->broken = $bLinks;

    return $ret_obj;
}

function get_meta_info($site) {
    global $__site_source_data;

    $timeStart = microtime(true);
    $meta_obj = new stdClass();
    $meta_obj->meta_title = "";
    $meta_obj->meta_description = "";
    $meta_obj->meta_keywords = "";
    $meta_obj->url = $site;
    $meta_obj->sourceData = "";

    $filename = "http://".$site;

    //Get Data of the URL
    $sourceData = getMyData($filename);
    $timeEnd = microtime(true);
    
    $__site_source_data = $sourceData;

    $meta_obj->pageSize = strlen($sourceData);
    $meta_obj->timeTaken = round($timeEnd - $timeStart, 2);
    $meta_obj->emailCount = 0;
    $meta_obj->meta_title = "";
    $meta_obj->meta_description = "";
    $meta_obj->meta_keywords = "";
    $meta_obj->charterSet = "";

    //Fix Meta Uppercase Problem
    $html = str_ireplace(array("Title","TITLE"),"title",$sourceData);
    $html = str_ireplace(array("Description","DESCRIPTION"),"description",$html);
    $html = str_ireplace(array("Keywords","KEYWORDS"),"keywords",$html);
    $html = str_ireplace(array("Content","CONTENT"),"content",$html);  
    $html = str_ireplace(array("Meta","META"),"meta",$html);  
    $html = str_ireplace(array("Name","NAME"),"name",$html);      
        
    //Check Empty Source Data
    if($sourceData == '') return $meta_obj;

    $meta_obj->sourceData = "";
    //Meta Data
    $title = $description = $keywords = '';
    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $nodes = $doc->getElementsByTagName('title');
    if($nodes->length > 0) $title = $nodes->item(0)->nodeValue;
    $metas = $doc->getElementsByTagName('meta');

    for ($i = 0; $i < $metas->length; $i++){
        $meta = $metas->item($i);
        if(($title == "")&&($meta->getAttribute('name') == 'title'))
           $title = $meta->getAttribute('content');
        if($meta->getAttribute('name') == 'description')
           $description = $meta->getAttribute('content');
        if($meta->getAttribute('name') == 'keywords')
            $keywords = $meta->getAttribute('content');
    }

    preg_match_all("/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/", $sourceData, $matches, PREG_SET_ORDER);

    $meta_obj->emailCount = count($matches);

    $charterSetPattern = '<meta[^>]+charset=[\'"]?(.*?)[\'"]?[\/\s>]';
    preg_match("#{$charterSetPattern}#is", $sourceData, $matches);
   
    if(isset($matches[1])) 
        $meta_obj->charterSet = Trim(mb_strtoupper($matches[1]));

    $meta_obj->meta_title = $title;
    $meta_obj->meta_description = $description;
    $meta_obj->meta_keywords = $keywords;

    return $meta_obj;
}

function google_preview($__meta_obj, $__check_echo = true)
{
    $__preview_str = '<div class="googlePreview"><p>'.$__meta_obj->meta_title.'</p><p><span class="bold">'.$__meta_obj->url.'</span>/</p><p>'.$__meta_obj->meta_description.'</p></div>';
    if($__check_echo) echo $__preview_str;
    return $__preview_str;
}

?>