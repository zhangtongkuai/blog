<?php
namespace App\BM\utils;

class Text {
    public static function truncateText($txt, $len)
    {
        if(mb_strlen($txt, "utf8") > $len){
            return mb_substr($txt, 0, $len) . "...";
        }
        return $txt;
    }

    public static function hideLeadingFrom(string $str, int $length, string $symbol = '*'){
        $s = mb_substr($str, $length);
        return sprintf('%\'' . $symbol . ($length + strlen($s)) . 's', $s);
    }

    public static function hideTrailingFrom(string $str, int $length, string $symbol = '*'){
        $s = mb_substr($str, 0, -$length);
        return $s . sprintf('%\'' . $symbol . $length . 's', '');
    }

    public static function hideCenterFrom(string $str, int $start, int $length, string $symbol = '*'){
        $s = mb_substr($str, $start + $length);
        return sprintf(mb_substr($str, 0, $start) . '%\'' . $symbol . (strlen($s) + $length) . 's', $s);
    }
}