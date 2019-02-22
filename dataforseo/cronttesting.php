<?php

require_once '../config.php';
require_once '../Mysql.php';

$db = new Mysql();

$id = intval(date('is'));
echo $id;

$db->exec("INSERT INTO rank_tracking (keyword, dashboard_id, post_id, task_id) VALUES ('darkina', 777, " . $id . ", 9999)");

?>