<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

$user_id = $_GET['user_id'] ?? 0;
$dashboardId = $_GET['dashboardId'] ?? 0;
$msgContent = $_POST['msgContent'] ?? '';
$usor = $_POST['userNotifi'] ?? '';

if ($user_id == 0 || $dashboardId == 0 || $msgContent == "") {
    exit();
}

function __correct_message($__str_source)
{
    $__pos_compare = strlen($__str_source);
    while (strrpos(substr($__str_source, 0, $__pos_compare), "http") !== false) {
        $__pos = strrpos(substr($__str_source, 0, $__pos_compare), "http");
        $__pos_last = strpos($__str_source, " ", $__pos);
        if ($__pos_last === false) $str_url = substr($__str_source, $__pos);
        else $str_url = substr($__str_source, $__pos, $__pos_last - $__pos);
        $str_link_url = '<a href="' . $str_url . '" target="_blank" rel="noopener">' . $str_url . '</a>';

        if ($__pos_last !== false) $__str_source = substr($__str_source, 0, $__pos) . $str_link_url . substr($__str_source, $__pos_last);
        else $__str_source = substr($__str_source, 0, $__pos) . $str_link_url;
        $__pos_compare = $__pos - 1;
        if ($__pos_compare < 4) break;
    }

    $__reg_str = '([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9._-]+)';
    preg_match_all($__reg_str, $__str_source, $matches, PREG_OFFSET_CAPTURE);
    $__arr_matches = $matches[0];
    for ($i = count($__arr_matches) - 1; $i >= 0; $i--) {
        $__match_pattern = $__arr_matches[$i][0];
        $__match_pos = $__arr_matches[$i][1];
        $__match_pos_end = $__match_pos + strlen($__arr_matches[$i][0]);
        $str_link_url = '<a href="mailto:' . $__match_pattern . '">' . $__match_pattern . '</a>';
        $__str_source = substr($__str_source, 0, $__match_pos) . $str_link_url . substr($__str_source, $__match_pos_end);
    }

    return $__str_source;
}

$arrMessage = explode("\r\n", $msgContent);

for ($i = 0; $i < count($arrMessage); $i++) {
    $arrMessage[$i] = __correct_message($arrMessage[$i]);
}

$msgContent = __replace_single_quote(implode("<br><br>", $arrMessage));

if ($usor != "undefined") {
    $noticeSql = "INSERT INTO user_users_notices 
                        (userIdx, noticeDate, noticeContent, noticeType, dashboardIdx, noticeTitle) 
                        VALUES (?, NOW(), 'You was remindered in message', 0, ?, ?)";
    $noticesData = [
        $user_id,
        $dashboardId,
        $msgContent
    ];
    $sth = $db->prepare($noticeSql);
    $sth->execute($noticesData);
}

$table = 'user_actions';
$insertData = [
    'userIdx' => $user_id,
    'actionTime' => date("Y-m-d H:i:s"),
    'actionContent' => $msgContent,
    'actionType' => 0,
    'dashboardId' => $dashboardId,
    'actionDetail' => ''
];

$db->insert($table, $insertData);
$db->exe('UPDATE `dashboards` SET `actionCount` = actionCount + 1 WHERE `id`= :id', ['id' => $dashboardId]);

$ret = new stdClass();
$ret->error = 0;
echo json_encode($ret);
?>