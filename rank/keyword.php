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


// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . mysqli_connect_error());
}
require('RestClient.php');
//You can download this file from here https://api.dataforseo.com/_examples/php/_php_RestClient.zip

try {
    //Instead of 'login' and 'password' use your credentials from https://my.dataforseo.com/login
    $client = new RestClient('https://api.dataforseo.com/', null, 'anshu.kumar@webynxa.com', 'dYwX273VdqHW2mnc');

    $camp_data = "select * from dashboards where id=$campaign_id";

    if ($result = mysqli_query($conn, $camp_data)) {
        echo "Getting Task by Keyword <br>";
        while ($row = mysqli_fetch_assoc($result)) {
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
                $key = trim($key);
                $post_array[] = array(
                    "language" => "en",
                    "loc_name_canonical" => "New Zealand",
                    "key" => $key
                );
            }
        }
    }
    // $post_array[] = array(
    // "language" => "en",
    // "loc_name_canonical"=> "New Zealand",
    // "key" => 'Web Design Tauranga'
    // );
    // $post_array[] = array(
    // "language" => "en",
    // "loc_name_canonical"=> "New Zealand",
    // "key" => 'tauranga web design'
    // );
    // $post_array[] = array(
    // "language" => "en",
    // "loc_name_canonical"=> "New Zealand",
    // "key" => 'google adwords management tauranga'
    // );

    //print_r("<pre>");print_r($post_array);die;
    $current_date = date('Y-m-d H:i:s');
    $sv_post_result = $client->post('v2/kwrd_sv', array('data' => $post_array));
    //print_r("<pre>");print_r($sv_post_result);die;
    foreach ($sv_post_result['results'] as $single_data) {

        $data = 'Not Avaliable';
        $cmp = 0;
        $cpc = 0;
        $sv = 0;

        if (!empty($single_data['ms'])) {
            $data = json_encode($single_data['ms']);
            $cmp = $single_data['cmp'];
            $cpc = $single_data['cpc'];
            $sv = $single_data['sv'];

        }

        $key = $single_data['key'];
        if (!empty($single_data['ms'][0]['count'])) {
            $last_month_count = $single_data['ms'][0]['count'];
        } else {
            $last_month_count = 0;
        }

        $sql = "INSERT INTO keyword_seo (dashboard_id,keyword, cmp, cpc,sv,last_month_count,data,date)VALUES($campaign_id,'$key',$cmp,$cpc,$sv,$last_month_count,'$data', '$current_date')";

        if (mysqli_query($conn, $sql)) {
            echo "New record created successfully <br>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

    }
    echo "Done.";

} catch (RestClientException $e) {
    echo "\n";
    print "HTTP code: {$e->getHttpCode()}\n";
    print "Error code: {$e->getCode()}\n";
    print "Message: {$e->getMessage()}\n";
    print  $e->getTraceAsString();
    echo "\n";
    exit();
}

$client = null;
?>
