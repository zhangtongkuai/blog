<?php
require __DIR__ . "/../../vendor/autoload.php";
require __DIR__ . '/Agent.php';

$agent = new \app\libs\DHTCSms\Agent([
    'account' => 'dh85841',
    'password' => '66WA8x0Q',
    'sign' => '【白租】'
]);
$agent->sendContentSms(13164952325, "测试接口短信。");
var_dump($agent->result());