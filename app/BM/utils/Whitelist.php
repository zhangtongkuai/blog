<?php
namespace App\BM\utils;

class Whitelist {
    // 验证地址是否在白名单中
    public static function check($url){
        $urlInfo = parse_url($url);
        if (!isset($urlInfo['host'])) {
            return false;
        }

        $callbackWhiteList = config('banma.system.whitelist');

        if (empty($callbackWhiteList)) {
            return false;
        }

        return in_array($urlInfo['host'], json_decode($callbackWhiteList, true));
    }
}