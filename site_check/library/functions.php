<?php

/*
 * @author Balaji
 * @name: Rainbow PHP Framework
 * @copyright � 2017 ProThemes.Biz
 *
 */

function isValidUsername($str){
    return !preg_match('/[^A-Za-z0-9.#\\-$]/', $str);
}

function isValidEmail($email){
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isValidSite($site) {
    return !preg_match('/^[a-z0-9\-]+\.[a-z]{2,100}(\.[a-z]{2,14})?$/i', $site);
}

function isValidIPv4($ip){
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) 
        return true;
    return false;
}

function isValidIPv6($ip){
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
        return true;
    return false;
}

function str_contains($data, $searchString, $ignoreCase = false){
    if ($ignoreCase)
    {
        $data = strtolower($data);
        $searchString = strtolower($searchString);
    }
    $needlePos = strpos($data, $searchString);
    return ($needlePos === false ? false : ($needlePos + 1));
}

function raino_trim($str){
    $str = Trim(htmlspecialchars($str));
    return $str;
}

function randomPassword(){
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 9; $i++)
    {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}

function escapeMe($con,$data){
     return mysqli_real_escape_string($con, $data);
}

function escapeTrim($con,$data){
     $data = Trim(htmlspecialchars($data));
     return mysqli_real_escape_string($con, $data);
}

function roundSize($size){
    $i = 0;
    $iec = array(
        "B",
        "Kb",
        "Mb",
        "Gb",
        "Tb");
    while (($size / 1024) > 1)
    {
        $size = $size / 1024;
        $i++;
    }
    return (round($size, 1) . " " . $iec[$i]);
}

function encrypt($value,$secret){
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $val = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $secret, $value, MCRYPT_MODE_ECB, $iv);
    return base64_encode($val);
}

function decrypt($value,$secret){
    $value = base64_decode($value);
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $secret, $value, MCRYPT_MODE_ECB, $iv);
}

function truncate($input, $maxWords, $maxChars){
    $words = preg_split('/\s+/', $input);
    $words = array_slice($words, 0, $maxWords);
    $words = array_reverse($words);

    $chars = 0;
    $truncated = array();

    while(count($words) > 0)
    {
        $fragment = trim(array_pop($words));
        $chars += strlen($fragment);

        if($chars > $maxChars) break;

        $truncated[] = $fragment;
    }

    $result = implode($truncated, ' ');

    return $result . ($input == $result ? '' : '...');
}

function strInt($input) {
    $output = null;
    $inputlen = strlen($input);
    $randkey = rand(1, 9);
 
    $i = 0;
    while ($i < $inputlen){
        $inputchr[$i] = (ord($input[$i]) - $randkey);
        $i++;
    }
    
    $output = implode('.', $inputchr) . '.' . (ord($randkey)+50);
    return $output;
}

function intStr($input) {
  $output = null;
  $input_count = strlen($input);
 
  $dec = explode(".", $input);
  $x = count($dec);
  $y = $x-1;
 
  $calc = $dec[$y]-50;
  $randkey = chr($calc);
 
  $i = 0;
 
  while ($i < $y) {
 
    $array[$i] = $dec[$i]+$randkey;
    $output .= chr($array[$i]);
 
    $i++;
  };
  return $output;
}

function makeUrlFriendly($input){
        $output = preg_replace("/\s+/" , "_" , raino_trim($input));
        $output = preg_replace("/\W+/" , "" , $output);
        $output = preg_replace("/_/" , "-" , $output);
        return strtolower($output);
}


function rgb2hex(array $rgb=array(0,0,0)){
    $hex = '#';
    $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);
    return $hex;
}


function hex2rgb($hex){
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    $rgb ="$r,$g,$b";
    return $rgb;
}

function getFrameworkVersion() {
    return '1.1.3';
}

function getServerMemoryUsage(){
    $memory_usage = 'Not Available';
    $free = shell_exec('free');
    if(!nullCheck($free)){
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        $memory_usage = round($mem[2] / $mem[1] * 100);
    }
    return $memory_usage;
}

function getServerCpuUsage() {
    if (function_exists('sys_getloadavg')){
        $load = sys_getloadavg();
        return $load[0];
    }else {
        return 'Not Available';
    }
}

function clean_url($site) {
    $site = strtolower($site);
    $site = str_replace(array(
        'http://',
        'https://',
        'www.'), '', $site);
    return $site;
}

function clean_with_www($site) {
    $site = strtolower($site);
    $site = str_replace(array(
        'http://',
        'https://'), '', $site);
    return $site;
}

function getTimeZone(){
    return date_default_timezone_get();
}

function setTimeZone($value) {
    date_default_timezone_set($value);
    return true;
}

function getDaysOnThisMonth($month = 5, $year = '2015'){
  if ($month < 1 OR $month > 12)
  {
	  return 0;
  }

  if ( ! is_numeric($year) OR strlen($year) != 4)
  {
	  $year = date('Y');
  }

  if ($month == 2)
  {
	  if ($year % 400 == 0 OR ($year % 4 == 0 AND $year % 100 != 0))
	  {
		  return 29;
	  }
  }

  $days_in_month	= array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
  return $days_in_month[$month - 1];
}
 
function getDomainName($site){
    $site = clean_url($site);
    $site = parse_url('http://'.trim($site));
    $host = $site['host'];
    return $host;
}

function getUserIP(){
    $ip = '127.0.0.1';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])){
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    if(filter_var($ip, FILTER_VALIDATE_IP)) 
        return $ip;
    else
        return '';
}

function getUA(){
    return raino_trim($_SERVER ['HTTP_USER_AGENT']);
}

function getUserLang($default='en'){
	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$langs = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);

		foreach ($langs as $value){
			$choice=substr($value,0,2);
            return $choice;
		}
	} 
	return $default;
}

function delDir($dir) {
    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file)
    {
        (is_dir("$dir/$file")) ? delDir("$dir/$file") : unlink("$dir/$file");
    }
    rmdir($dir);
    return 1;
}

function delFile($file){
    return unlink($file);
}

function getCenterText($str1,$str2,$data){
    $data = explode($str1,$data);
    $data = explode($str2,$data[1]);
    return Trim($data[0]);
}

function nullCheck($str){
    $str = strtolower($str);
    if($str == 'none' || $str == 'null' || $str == 'n/a' || $str == '' || $str == null)
        return true;
    else
        return false;
}

function copyDir($src,$dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                copyDir($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                if(!copy($src . '/' . $file,$dst . '/' . $file)){
                    //Error - File Copy Failed!
                }
            }
        }
    }
    closedir($dir);
}

function fixSpecialChar($plainTxt){
    return mb_convert_encoding($plainTxt, 'UTF-8', 'UTF-8');
}

function getLastID($con,$table) {
    $table = escapeTrim($con,$table);
    $query = "SELECT @last_id := MAX(id) FROM $table";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_array($result);
    $last_id = $row['@last_id := MAX(id)'];
    return $last_id;
}

function getMyData($site){
    return @file_get_contents($site);
}

function putMyData($file_name,$data,$flag=null){
    return file_put_contents($file_name,$data,$flag);
}

function baseURL($atRoot=FALSE, $atCore=FALSE, $parse=FALSE){
        if (isset($_SERVER['HTTP_HOST'])) {
            $http = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
            $hostname = $_SERVER['HTTP_HOST'];
            $dir =  str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

            $core = preg_split('@/@', str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(dirname(__FILE__))), NULL, PREG_SPLIT_NO_EMPTY);
            $core = $core[0];

            $tmplt = $atRoot ? ($atCore ? "%s://%s/%s/" : "%s://%s/") : ($atCore ? "%s://%s/%s/" : "%s://%s%s");
            $end = $atRoot ? ($atCore ? $core : $hostname) : ($atCore ? $core : $dir);
            $base_url = sprintf( $tmplt, $http, $hostname, $end );
        }
        else $base_url = 'http://localhost/';

        if ($parse) {
            $base_url = parse_url($base_url);
            if (isset($base_url['path'])) if ($base_url['path'] == '/') $base_url['path'] = '';
        }

        return $base_url;
}

function passwordHash($str){
    $hash=md5(crypt(Md5($str),HASH_CODE));
    return $hash;
}

function redirectTo($path){
    header('Location: '. $path);
    exit();
}

function redirectToWithMeta($path,$sec=1){
    header('Location: '. $path);
    echo '<meta http-equiv="refresh" content="'.$sec.';url='.$path.'">';
    exit();
}

function array_map_recursive($callback, $array) {
    foreach ($array as $key => $value) {
        if (is_array($array[$key])) {
            $array[$key] = array_map_recursive($callback, $array[$key]);
        }
        else {
            $array[$key] = call_user_func($callback, $array[$key]);
        }
    }
    return $array;
}

function metaRefresh($path=null,$sec=1,$exit=false){
    if($path!=null)
    echo '<meta http-equiv="refresh" content="'.$sec.';url='.$path.'">';
    else
    echo '<meta http-equiv="refresh" content="'.$sec.'">';
    if($exit)
    exit();
    else
    return true;
}

function stop($msg=null,$disMsg=true,$logMsg=true){
    if(ERR_R){
        if($logMsg){
            if($msg != null){
                $msgWithDate = '['. date('d-M-Y H:i:s') . ' ' . getTimeZone() .']' . " App Notice:  " . $msg . " | Request From ". getUserIP();
                $errFile = LOG_DIR.ERR_R_FILE;
                putMyData($errFile,$msgWithDate."\r\n\n",FILE_APPEND);
            }
        }
    }
    if($disMsg)
        die("$msg"); 
    else
        die();
}

function writeLog($msg=null){
    if(ERR_R){
            if($msg != null){
                $msgWithDate = '['. date('d-M-Y H:i:s') . ' ' . getTimeZone() .']' . " App Notice:  " . $msg . " | Request From ". getUserIP();
                $errFile = LOG_DIR.ERR_R_FILE;
                putMyData($errFile,$msgWithDate."\r\n\n",FILE_APPEND);
            }
    }
}

function curlPOST($url,$post_data,$ref_url = "http://www.google.com/",$agent = "Mozilla/5.0 (Windows NT 6.3; WOW64; rv:49.0) Gecko/20100101 Firefox/49.0"){
    $cookie=tempnam("/tmp","CURLCOOKIE");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/html; charset=utf-8","Accept: */*"));
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_REFERER, $ref_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data);
    $html=curl_exec($ch);
    curl_close($ch);
    return $html;
}

function curlGET($url,$ref_url = "http://www.google.com/",$agent = "Mozilla/5.0 (Windows NT 6.3; WOW64; rv:49.0) Gecko/20100101 Firefox/49.0"){
    $cookie=tempnam("/tmp","CURLCOOKIE");
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 100);
	curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/html; charset=utf-8","Accept: */*"));
    curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt($ch, CURLOPT_REFERER, $ref_url);
	$html=curl_exec($ch);
    curl_close($ch);
    return $html;
}

function simpleCurlGET($url){
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	$html=curl_exec($ch);
    curl_close($ch);
    return $html;
}

function getHeaders($site) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $site);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:49.0) Gecko/20100101 Firefox/49.0');
    $headers=curl_exec($ch);
    curl_close($ch);
    return $headers;
}

function getHttpCode($site,$followRedirect=true) {
    $ch = curl_init($site);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirect);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:49.0) Gecko/20100101 Firefox/49.0');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);    
    curl_close($ch);
    return $httpCode;
}

function getHeader($myheader) {
  if (isset($_SERVER[$myheader])) {
    return $_SERVER[$myheader];
  } else {
    if(function_exists('apache_request_headers') ) {
    $headers = apache_request_headers();
    if (isset($headers[$myheader])) {
      return $headers[$myheader];
    }
    }
  }
  return '';
}

function createZip($source,$des,$filename) {
    $filename = str_replace(".zip","",$filename);
    $zip = new ZipArchive();
    $zip->open($des.$filename.".zip", ZipArchive::CREATE);
    if (is_dir($source) === true){
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($files as $file){
        if (is_dir($file) === true){
            
        }else if (is_file($file) === true){
            $zip->addFromString(str_replace($source . '/', '', $file), getMyData($file));
        }
    }
    }
    $zip->close();
    return true;
}

function extractZip($sourceFile,$desPath){
    $zip = new ZipArchive;
    $res = $zip->open($sourceFile);
    if ($res === TRUE) {
        $zip->extractTo($desPath);
        $zip->close();
        return true;
    } else {
        return false;
    }
}

function __size_as_kb($yoursize) {
    $size_kb = round($yoursize/1024);
    return $size_kb;
}

?>