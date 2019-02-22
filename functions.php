<?php

function getImageNameForUser($userId, $preview, $prefix = '') {
    $suffix = $preview ? '_2.jpg' : '.jpg';

    return $prefix . $userId . $suffix;
}

function getPhotoNamePrefixes() {
    return [
        'photo/',
        'photo/agency_',
    ];
}

function generatePathToSerpResponseFile($dashboardId, $day, $taskId) {
    if($dashboardId && $day && $taskId) {

        return 'serp_responses/' . $dashboardId . '/' . $day . '_' . $taskId . '.json';
    }

    return null;
}

function formatDate($date) {
    return formatTime(strtotime($date));
}

function formatTime($time) {
    return date('Y-m-d', $time);
}
