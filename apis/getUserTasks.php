<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once '../config.php';

if (empty($_GET['user_id'])) {
    exit;
}
$user_id = $_GET['user_id'];

function __get_date_str($__date, $__basis_date)
{
    if (!isset($__date)) return "";
    if ($__date == "") return "";
    if ($__date == $__basis_date) return "Today";
    if (strtotime($__date) - strtotime($__basis_date) == 86400) return "Tomorrow";
    return date("j F", strtotime($__date));
}

function __check_date_str($__date, $__basis_date)
{
    if (!isset($__date)) return 2;
    if ($__date == "") return 2;
    if ($__date == $__basis_date) return 0;
    if (strtotime($__date) < strtotime($__basis_date)) return 2;
    return 1;
}

$ret = new stdClass();
$ret->today_tasks = array();
$ret->future_tasks = array();
$ret->other_tasks = array();

$sql = "SELECT actionDetail, filePath, taskProgress, dashboardId, company_name, CURRENT_DATE AS cur_date 
              FROM user_actions a 
                  INNER JOIN dashboards b 
                          ON a.dashboardId = b.id 
                            WHERE actionType = 2 
                              AND taskAssigner = :taskAssigner 
                              AND (((filePath IS NOT NULL AND filePath <> '') AND date(filePath)>=CURRENT_DATE) 
                              OR ((filePath IS NULL OR filePath = '' OR date(filePath) < CURRENT_DATE) AND taskProgress = 0)) 
                              ORDER BY (filePath IS NULL OR filePath = ''), filePath, actionIdx";

$result = $db->select($sql, ['taskAssigner' => $user_id]);

for ($i = 0; $i < count($result); $i++) {
    $row = $result[$i];

    if (isset($row["actionDetail"])) {
        if ($row["actionDetail"] == "") continue;

        $obj = new stdClass();
        $obj->task_title = $row["actionDetail"];
        $obj->task_date = __get_date_str($row["filePath"], $row["cur_date"]);
        $obj->dash_id = $row["dashboardId"];
        $obj->dash_name = $row["company_name"];
        $obj->task_progress = $row["taskProgress"];

        $case_num = __check_date_str($row["filePath"], $row["cur_date"]);
        if ($case_num == 0) {
            array_push($ret->today_tasks, $obj);
        } else if ($case_num == 1) {
            array_push($ret->future_tasks, $obj);
        } else {
            array_push($ret->other_tasks, $obj);
        }
    }

}

echo json_encode($ret);
?>