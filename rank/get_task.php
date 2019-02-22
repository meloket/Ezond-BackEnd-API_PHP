<?php

if (!empty($_GET['id'])) {
    $campaign_id = $_GET['id'];
} else {
    echo "Please Enter the Campaign ID";
    die;
}

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
    $client = new RestClient('https://api.dataforseo.com/', null, $user, $pass);
} catch (RestClientException $e) {
    echo "\n";
    print "HTTP code: {$e->getHttpCode()}\n";
    print "Error code: {$e->getCode()}\n";
    print "Message: {$e->getMessage()}\n";
    print  $e->getTraceAsString();
    echo "\n";
    exit();
}

$se_name = "google.co.nz";
$se_language = "English";

$camp_data = "select * from dashboards where id=$campaign_id";

if ($result = mysqli_query($conn, $camp_data)) {
    echo "Getting Task by Keyword <br>";
    while ($row = mysqli_fetch_assoc($result)) {
        $location = '';
        $url = '';
        $desc = $row['description'];
        if (!empty($desc)) {
            $desc = json_decode($desc);
            $location = $desc->location;
            $url = $desc->url;
        } else {
            echo "Please provide location and URL";
            die;
        }

        if (empty($location)) {
            $location = "New Zealand";
        }

        if (!empty($row['keywords'])) {
            $keyword = $row['keywords'];
            if (strpos($keyword, ',') !== false) {
                $keys = explode(",", $keyword);

            } else {
                $keys[0] = $keyword;
            }
        } else {
            echo "Please provide Keyword";
            die;
        }

        foreach ($keys as $key) {

            $post_array = array();
            //$my_unq_id = mt_rand(0,30000000); //your unique ID. will be returned with all results
            $key = trim($key);
            $post_array[$campaign_id] = array(


                "priority" => 1,
                "site" => $url,
                "se_name" => $se_name,
                "se_language" => $se_language,
                "loc_name_canonical" => $location,
                "key" => mb_convert_encoding($key, "UTF-8")
            );


            try {
                $task_post_result = $client->post('v2/rnk_tasks_post', array('data' => $post_array));
                echo "Request for task ID sent <br>";
                $post_array = array();
            } catch (RestClientException $e) {
                echo "\n";
                print "HTTP code: {$e->getHttpCode()}\n";
                print "Error code: {$e->getCode()}\n";
                print "Message: {$e->getMessage()}\n";
                print  $e->getTraceAsString();
                echo "\n";
            }

        }

    }
}


?>
