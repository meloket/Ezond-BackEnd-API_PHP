<?php
require_once '../config.php';

function _in_logit_POST($id_message, $data)
{
    @file_put_contents("./logs/postback_url_example.log", PHP_EOL . date("Y-m-d H:i:s") . ": " . $id_message . PHP_EOL . "---------" . PHP_EOL . print_r($data, true) . PHP_EOL . "---------", FILE_APPEND);
}

$rawData = file_get_contents('php://input');
$_search_pos = strpos($rawData, "results%5Bextra%5D%5Bpeople_also_ask%5D");
if ($_search_pos !== false) {
    $rawData_1 = substr($rawData, $_search_pos);
    $data = array();
    parse_str($rawData_1, $data);
    $result = $data["results"]["extra"]["people_also_ask"][0];

    if (isset($result) && (count($result) > 0)) {
        $rawData_2 = substr($rawData, 0, 2000);
        $data = array();
        parse_str($rawData_2, $data);
        $keyword = $_POST["results"]["organic"][0]["post_key"];

        $table = 'people_also_ask';

        $db->delete('DELETE FROM `' . $table . '` WHERE `keyword` = :keyword', ['keyword' => $keyword]);
        $insertData = [
            'keyword' => $keyword,
            'questionString' => json_encode($result),
        ];
        $db->insert($table, $insertData);
    }
}

?>
