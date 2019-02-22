<?php
$servername = "188.166.231.250";
$username = "root";
$password = "treecat21tub";
$dbname = "ezond";

$user = 'anshu.kumar@webynxa.com';
$pass = 'dYwX273VdqHW2mnc';
// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . mysqli_connect_error());
}

require('RestClient.php');

try {
    echo "Getting Task ID <br>";
    //Instead of 'login' and 'password' use your credentials from https://my.dataforseo.com/login
    $client = new RestClient('https://api.dataforseo.com', null, $user, $pass);
} catch (RestClientException $e) {
    echo "\n";
    print "HTTP code: {$e->getHttpCode()}\n";
    print "Error code: {$e->getCode()}\n";
    print "Message: {$e->getMessage()}\n";
    print  $e->getTraceAsString();
    echo "\n";
}
try {
    //GET /v2/rnk_tasks_get
    $task_get_result = $client->get('v2/rnk_tasks_get');
    //file_put_contents("test.txt",serialize($task_get_result));
    //$task_get_result = unserialize(file_get_contents("test.txt"));
    print_r("<pre>");
    print_r($task_get_result);
    die;
    if (count($task_get_result) > 0) {
        //print_r("<pre>");print_r($task_get_result);die;
        $results = $task_get_result['results'];
        foreach ($results['organic'] as $single) {
            //print_r($single);die;

            $dashboard_id = $single['post_id'];
            $keyword = $single['post_key'];
            $post_id = $single['post_id'];
            $task_id = $single['task_id'];
            if (!empty($single['result_position'])) {
                $rank = $single['result_position'];
            } else {
                $rank = 0;
            }

            $rank_data = json_encode($single);
            //$change         = $single[''];
            $response_date = $single['result_datetime'];
            $date = date('Y-m-d H:i:s');

            $l_rank = "select rank from data_seo where id in (select max(id) from data_seo where keyword='$keyword' group by keyword)";
            $l_result = mysqli_query($conn, $l_rank);
            $change = '';
            while ($last_rank_data = mysqli_fetch_assoc($l_result)) {
                if (!empty($last_rank_data)) {
                    $last_rank = $last_rank_data['rank'];
                    if ($last_rank > $rank) {
                        $change = $last_rank - $rank . " Down";
                    } else {
                        $change = $rank - $last_rank . " Up";
                    }
                } else {
                    $change = '';
                }

            }
            //print_r($rank);die;
            $sql = "INSERT INTO data_seo (dashboard_id, keyword, post_id, task_id, rank, rank_data, `change`, response_date,date)VALUES($dashboard_id,'$keyword',$post_id,$task_id,$rank,'$rank_data','$change','$response_date','$date')";
            //print_r($sql);die;
            if (mysqli_query($conn, $sql)) {
                echo "Rank Insterted <br>";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }

        }
    } else {
        echo "No Rank Get";
        die;
    }


} catch (RestClientException $e) {
    echo "\n";
    print "HTTP code: {$e->getHttpCode()}\n";
    print "Error code: {$e->getCode()}\n";
    print "Message: {$e->getMessage()}\n";
    print  $e->getTraceAsString();
    echo "\n";
}


?>