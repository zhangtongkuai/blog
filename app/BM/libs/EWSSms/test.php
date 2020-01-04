<?php
require __DIR__ . "/../../vendor/autoload.php";
require __DIR__ . '/Agent.php';

$agent = new \App\Libs\EWSSms\Agent([
    'account' => 'N9721214',
    'password' => 'wlNgxn4Bj',
    'host' => 'smssh1.253.com'
]);
$agent->sendContentSms(13164952325, "大家好，我是白租的iPhone 7 Plus，您的验证码是 2324。【白租】");
var_dump($agent->result());