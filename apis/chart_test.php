<?php // content="text/plain; charset=utf-8"
require_once('../jpgraph/SVGGraph/SVGGraph.php');
require_once '../config.php';

$dashboardID = "";
if (isset($_GET['id'])) $dashboardID = $_GET['id'];

$w = 500;
$h = 500;

if (isset($_GET['w'])) $w = $_GET['w'];
if (isset($_GET['h'])) $h = $_GET['h'];

$datay1 = array(0, 0, 0, 0, 0, 0, 0);

$curr_data = $db->select("SELECT `chartValue` FROM `dashboards` WHERE `id` = :id", ['id' => $dashboardID]);
if (count($curr_data) > 0) {
    if (isset($curr_data[0]["chartValue"])) {
        if ($curr_data[0]["chartValue"] != "")
            $datay1 = explode(",", $curr_data[0]["chartValue"]);
    }
}

$__old_value = 0;
$__curr_value = 0;

$__mid_point = (count($datay1) - count($datay1) % 2) / 2;

for ($i = 0; $i < count($datay1); $i++) {
    if ($i < $__mid_point)
        $__old_value += $datay1[$i];
    else if ($i >= count($datay1) - $__mid_point)
        $__curr_value += $datay1[$i];
}
if ($__curr_value > $__old_value) $caseNum = 0;
else $caseNum = 1;

if ($caseNum % 2 == 0)
    $line_color = "#43ea3b";
else
    $line_color = "#ff0000";

$settings = array(
    'show_grid' => false,
    'show_axes' => false,
    'back_colour' => '#f00:0',
    'stroke_colour' => $line_color,
    'marker_size' => 0,
    'back_stroke_width' => 0,
    'show_tooltips' => false,
    'pad_top' => 0,
    'pad_bottom' => 0,
    'pad_left' => 0,
    'pad_right' => 0,

);

$graph = new SVGGraph($w, $h, $settings);
$graph->Values($datay1);
$graph->Render('LineGraph');

?>

