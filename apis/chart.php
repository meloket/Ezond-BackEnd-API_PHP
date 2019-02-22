<?php // content="text/plain; charset=utf-8"
require_once('../jpgraph/src/jpgraph.php');
require_once('../jpgraph/src/jpgraph_line.php');
require_once '../config.php';

$dashboardID = "";
if (isset($_GET['id'])) $dashboardID = $_GET['id'];

// Setup the graph
$graph = new Graph(350, 60);

$graph->SetScale("textlin");

$graph->xaxis->Hide(); // Hide xaxis 
$graph->yaxis->Hide(); // Hide y-axis

$caseNum = 0;

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
    else
        $__curr_value += $datay1[$i];
}
if ($__curr_value > $__old_value) $caseNum = 0;
else $caseNum = 1;

// Create the first line
$p1 = new LinePlot($datay1);
$graph->Add($p1);

if ($caseNum % 2 == 0)
    $p1->SetColor("#0000ff");
else
    $p1->SetColor("#ff0000");

// Output line
$graph->Stroke();

?>

