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

    $filename = "http://".$site;

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
            
            $screenData = str_replace("_","/",$jsonData['screenshot']['data']);
            $screenData = str_replace("-","+",$screenData);
                    
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

    $filename = "http://".$site;

    if($sourceData){

    } else {
        //Get Data of the URL
        $sourceData = getMyData($filename);
    } 
        
    //Check Empty Source Data
    if($sourceData == '') return false;

    //Load Dom Data
    $domData = load_html($sourceData);

    $mobileComCheck = false;
    
    foreach($domData->find('iframe') as $iframe)
        $mobileComCheck = true;
    
    foreach($domData->find('object') as $embedded)
        $mobileComCheck = true;
    
    foreach($domData->find('embed') as $embedded)
        $mobileComCheck = true;
    
    //Clean up memory
    $domData->clear();
    $domData = null;
    
    return $mobileComCheck;
}

function missing_img_alt($site, $sourceData = ""){
    $meta_obj = new stdClass();
    $meta_obj->imageWithOutAltTag = 0;
    $meta_obj->imageCount = 0;
    $meta_obj->imgArr = array();
    $meta_obj->url = $site;

    $filename = "http://".$site;

    if($sourceData){

    } else {
        //Get Data of the URL
        $sourceData = getMyData($filename);
    }    
        
    //Check Empty Source Data
    if($sourceData == '') return $meta_obj;

    //Load Dom Data
    $domData = load_html($sourceData);

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

    //Clean up memory
    $domData->clear();
    $domData = null;
    
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

function get_meta_info($site) {
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
    $meta_obj->pageSize = strlen($sourceData);
    $meta_obj->timeTaken = round($timeEnd - $timeStart, 2);

    //Fix Meta Uppercase Problem
    $html = str_ireplace(array("Title","TITLE"),"title",$sourceData);
    $html = str_ireplace(array("Description","DESCRIPTION"),"description",$html);
    $html = str_ireplace(array("Keywords","KEYWORDS"),"keywords",$html);
    $html = str_ireplace(array("Content","CONTENT"),"content",$html);  
    $html = str_ireplace(array("Meta","META"),"meta",$html);  
    $html = str_ireplace(array("Name","NAME"),"name",$html);      
        
    //Check Empty Source Data
    if($sourceData == '') return $meta_obj;

    $meta_obj->sourceData = $sourceData;
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