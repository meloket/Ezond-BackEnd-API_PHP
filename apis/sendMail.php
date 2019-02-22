<?php
require_once "../Mailer/Mail.php";

$from = '<ezond.agency.analytics@gmail.com>';
$to = '<mark@webscientists.net>';
$subject = 'Ezond Agency Analytics';
$body = "Hi,\n\nHe assigned task to you";

$headers = array(
    'From' => $from,
    'To' => $to,
    'Subject' => $subject
);

$smtp = Mail::factory('smtp', array(
    'host' => 'ssl://smtp.gmail.com',
    'port' => '465',
    'auth' => true,
    'username' => 'ali.texa7890@gmail.com',
    'password' => '1234guraud!'
));

$mail = $smtp->send($to, $headers, $body);

if (PEAR::isError($mail)) {
    echo('<p>' . $mail->getMessage() . '</p>');
} else {
    echo('<p>Message successfully sent!</p>');
}
?>