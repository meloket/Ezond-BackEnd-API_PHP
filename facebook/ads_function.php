<?php
require_once(__DIR__ . '/../vendor/autoload.php');

use FacebookAds\Api;
use FacebookAds\Object\User;
use FacebookAds\Object\Campaign;

use Facebook\Facebook;
use Facebook\FacebookRequest;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Fields\AdAccountFields;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\Fields\AdSetFields;

$fldName_Origin = array("Campaign", "Ad Set", "Ad", "Clicks", "Impressions", "Reach", "Amount Spent", "Average CPC", "Unique CTR");
$fldName_Origin2 = array("Unique Link Clicks", "Website Conversions", "Cost Per Website Conversion", "Page Likes", "Post Likes", "Cost Per Page Like", "Cost Per Post Like", "Cost per Unique Link Click");

$fldName = array('campaign_name', 'adset_name', 'ad_name', 'clicks', 'impressions', 'reach', 'spend', 'cpc', 'unique_clicks', 'unique_ctr');
$fldName2 = array('link_click', 'offsite_conversion', 'offsite_conversion', 'page_engagement', 'post_engagement', 'page_engagement', 'post_engagement', 'link_click');
$fldName3 = array('unique_actions', 'actions', 'cost_per_action_type', 'actions', 'actions', 'cost_per_action_type', 'cost_per_action_type', 'cost_per_action_type');

function initApiVersion()
{
    $api = $api = Api::instance();
    $api->setDefaultGraphVersion('3.0');
}

function correct_number($_num, $signCheck = true)
{
    $_num = str_replace(",", "", $_num);
    if (strpos($_num, "%") === false) {
        $_num = number_format($_num, 3);
        if (substr($_num, -4) == ".000") $_num = substr($_num, 0, strpos($_num, ".000"));
        if (strpos($_num, ".") !== false) {
            if (substr($_num, -1) == "0") $_num = substr($_num, 0, strlen($_num) - 1);
        }
    } else
        $_num = round($_num, 3) . ($signCheck ? "%" : "");
    return $_num;
}

function get_data_from_array($arr_value, $fld_name)
{
    for ($i = 0; $i < count($arr_value); $i++) {
        if ($arr_value[$i]->action_type == $fld_name)
            return $arr_value[$i]->value;
    }
    return 0;
}

function GetMetricChartData($ret, $data)
{
    global $fldName_Origin, $fldName_Origin2, $fldName, $fldName2, $fldName3;

    for ($i = 3; $i < count($fldName_Origin); $i++) {
        $_fld_name = $fldName_Origin[$i] . "_Chart";
        $$_fld_name = "Date," . $fldName_Origin[$i] . "\n";
    }

    for ($i = 0; $i < count($fldName_Origin2); $i++) {
        $_fld_name = $fldName_Origin2[$i] . "_Chart";
        $$_fld_name = "Date," . $fldName_Origin[$i] . "\n";
    }

    for ($k = 0; $k < count($data); $k++) {
        $date_val = $data[$k]->date_start;

        for ($i = 3; $i < count($fldName_Origin); $i++) {
            $_fld_name = $fldName_Origin[$i] . "_Chart";

            $record = $data[$k];
            $_fld_name2 = $fldName[$i];
            if (isset($record->$_fld_name2)) if ($record->$_fld_name2 != "") {
                $fld_value = $record->$_fld_name2;
                //if(is_numeric($fld_value)) $fld_value = correct_number(number_format($fld_value, 3));
                $$_fld_name .= $date_val . "," . $fld_value . "\n";
            }
        }

        for ($i = 0; $i < count($fldName_Origin2); $i++) {
            $_fld_name = $fldName_Origin2[$i] . "_Chart";

            $_fld_name2 = $fldName3[$i];
            if (isset($data[$k]->$_fld_name2)) {
                $record = $data[$k]->$_fld_name2;
                //$fld_value = correct_number(number_format(get_data_from_array($record, $fldName2[$i]), 3));
                $fld_value = get_data_from_array($record, $fldName2[$i]);
                $$_fld_name .= $date_val . "," . $fld_value . "\n";
            }
        }
    }

    for ($i = 3; $i < count($fldName_Origin); $i++) {
        $_fld_name = $fldName_Origin[$i] . "_Chart";
        $ret->$_fld_name = $$_fld_name;
    }

    for ($i = 0; $i < count($fldName_Origin2); $i++) {
        $_fld_name = $fldName_Origin2[$i] . "_Chart";
        $ret->$_fld_name = $$_fld_name;
    }
    return $ret;
}

function GetMainMetricData($data)
{
    global $fldName_Origin, $fldName_Origin2, $fldName, $fldName2, $fldName3;

    $ret = new stdClass();

    for ($i = 0; $i < count($fldName_Origin); $i++) {
        $_fld_name = $fldName_Origin[$i];
        $ret->$_fld_name = 0;
        if (count($data) > 0) {
            $record = $data[0];
            $_fld_name2 = $fldName[$i];
            if (isset($record->$_fld_name2)) if ($record->$_fld_name2 != "") {
                $ret->$_fld_name = $record->$_fld_name2;
                if (is_numeric($ret->$_fld_name)) $ret->$_fld_name = correct_number(number_format($record->$_fld_name2, 3));
            }
            if ($ret->$_fld_name == null) $ret->$_fld_name = 0;
        }
    }

    for ($i = 0; $i < count($fldName_Origin2); $i++) {
        $_fld_name = $fldName_Origin2[$i];
        $ret->$_fld_name = 0;
        $_fld_name2 = $fldName3[$i];
        if (count($data) > 0) {
            $record = $data[0]->$_fld_name2;
            $ret->$_fld_name = correct_number(number_format(get_data_from_array($record, $fldName2[$i]), 3));
        }
    }
    return $ret;
}

function GetResultMetricData($data)
{
    global $fldName_Origin, $fldName_Origin2, $fldName, $fldName2, $fldName3;

    $ressult = array();

    for ($k = 0; $k < count($data); $k++) {

        $ret = new stdClass();

        for ($i = 0; $i < count($fldName_Origin); $i++) {
            $_fld_name = $fldName_Origin[$i];
            $ret->$_fld_name = 0;

            $record = $data[$k];
            $_fld_name2 = $fldName[$i];
            if (isset($record->$_fld_name2)) if ($record->$_fld_name2 != "") {
                $ret->$_fld_name = $record->$_fld_name2;
                if (is_numeric($ret->$_fld_name)) $ret->$_fld_name = correct_number(number_format($record->$_fld_name2, 3));
            }
        }

        for ($i = 0; $i < count($fldName_Origin2); $i++) {
            $_fld_name = $fldName_Origin2[$i];
            $ret->$_fld_name = 0;
            $_fld_name2 = $fldName3[$i];

            $record = $data[$k]->$_fld_name2;
            $ret->$_fld_name = correct_number(number_format(get_data_from_array($record, $fldName2[$i]), 3));
        }

        array_push($ressult, $ret);
    }

    return $ressult;
}

function GetCampaigns($account)
{

    $arr_campaign = array("All Campaigns");
    try {
        $campaigns = json_decode($account->getCampaigns(array(CampaignFields::NAME))->getResponse()->getBody())->data;
        for ($i = 0; $i < count($campaigns); $i++)
            array_push($arr_campaign, $campaigns[$i]->name);
    } catch (FacebookAds\Http\Exception\AuthorizationException $e) {
    }

    return $arr_campaign;
}

function GetAdGroups($account)
{

    $arr_adgroup = array("All Ad Sets");
    try {
        $adgroups = json_decode($account->getAdSets(array(AdSetFields::NAME))->getResponse()->getBody())->data;
        for ($i = 0; $i < count($adgroups); $i++)
            array_push($arr_adgroup, $adgroups[$i]->name);
    } catch (FacebookAds\Http\Exception\AuthorizationException $e) {
    }
    return $arr_adgroup;
}

function GetMetricDatas($account, $start_date, $end_date, $level_param, $daily_check = false, $filter = null)
{


    $insight_fields = array(
        'campaign_name',
        'campaign_id',
        'adset_name',
        'adset_id',
        'ad_name',
        'ad_id',
        'clicks',
        'impressions',
        'reach',
        'spend',
        'cpc',
        'cpm',
        'cpp',
        'ctr',
        'unique_clicks',
        'unique_ctr',
        'cost_per_unique_click',
        'actions',
        'cost_per_action_type',
        'unique_actions',
        // 'website_clicks',
        'website_ctr'
    );

    if ($daily_check) {
        $insight_params = array(
            'level' => $level_param,
            'time_increment' => 1,
            'time_range' => array(
                'since' => $start_date,
                'until' => $end_date
            )
        );
    } else {
        $insight_params = array(
            'level' => $level_param,
            'time_range' => array(
                'since' => $start_date,
                'until' => $end_date
            )
        );
    }
    if (isset($filter)) {
        $filter_arr = array();
        if (isset($filter->campaignName)) {
            $filter_obj = new stdClass();
            $filter_obj->field = "campaign.name";
            $filter_obj->operator = "EQUAL";
            $filter_obj->value = $filter->campaignName;
            array_push($filter_arr, $filter_obj);
        }
        if (isset($filter->groupName)) {
            $filter_obj = new stdClass();
            $filter_obj->field = "adset.name";
            $filter_obj->operator = "EQUAL";
            $filter_obj->value = $filter->groupName;
            array_push($filter_arr, $filter_obj);
        }

        $insight_params["filtering"] = $filter_arr;
    }
    try {
        $insights = json_decode($account->getInsights($insight_fields, $insight_params)->getResponse()->getBody());
        return $insights->data;
    } catch (FacebookAds\Http\Exception\AuthorizationException $e) {
        echo "<pre>";
        print_r($e->getMessage());
        echo "</pre>";
    }
    return array();
}

?>
