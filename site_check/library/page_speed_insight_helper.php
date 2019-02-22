<?php

/*
* @author Balaji
* @name Turbo Website Reviewer - PHP Script
* @copyright © 2017 ProThemes.Biz
*
*/

function pageSpeedInsightChecker($url,$type='desktop',$screenshot=false){
    
    $pageSpeedInsightUrl = $desktopUrl = $mobileUrl = $score = $jsonData = '';
    
    $apiKey = 'AIzaSyB2O18subSyz9Zy7fBaeRq-ZfNNecDcBfI';
    $url = urldecode($url);
    
    if($screenshot)
        $screenshot = 'true';
    else
        $screenshot = 'false';
    
    $mobileUrl = 'https://www.googleapis.com/pagespeedonline/v3beta1/runPagespeed?key='.$apiKey.'&screenshot='.$screenshot.'&snapshots='.$screenshot.'&locale=en_US&url='.$url.'&strategy=mobile&filter_third_party_resources=false&callback=_callbacks_._P4fpvilxHvmf';
    
    $desktopUrl = 'https://www.googleapis.com/pagespeedonline/v3beta1/runPagespeed?key='.$apiKey.'&screenshot='.$screenshot.'&snapshots='.$screenshot.'&locale=en_US&url='.$url.'&strategy=desktop&filter_third_party_resources=false&callback=_callbacks_._w09qWHxHR1wK';
    
    if($type == 'desktop')
        $pageSpeedInsightUrl = $desktopUrl;
    else if($type == 'mobile')
        $pageSpeedInsightUrl = $mobileUrl;
    else
        stop('Unkown Page Speed Insight Checker Error!');
        
    $jsonData = curlGET($pageSpeedInsightUrl);
    
    if($jsonData != ''){
        if($type == 'mobile')
            $jsonData = getCenterText('P4fpvilxHvmf(',');',$jsonData);
        else
             $jsonData = getCenterText('w09qWHxHR1wK(',');',$jsonData);
        
        $jsonData = json_decode($jsonData);
        
        if(isset($jsonData->{'ruleGroups'}->{'SPEED'}->{'score'}))
            $score = $jsonData->{'ruleGroups'}->{'SPEED'}->{'score'};
        else
            $score = '0';
    }else{
        //Error
        $score = '0';
    }
    
    return $score;

}

?>