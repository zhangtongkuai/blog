<?php
namespace App\BM\utils;


use Carbon\Carbon;

class Time {
    public static function now(){
        return Carbon::now();
    }

    public static function noSecondString($timeStr){
        return Carbon::parse($timeStr)->format("Y-m-d H:i");
    }

    public static function format($timeStr, $formatStr)
    {
        return Carbon::parse($timeStr)->format($formatStr);
    }

    public static function nowDateString(){
        return Carbon::now()->format("Y-m-d");
    }

    public static function nowNoSecondString()
    {
        return Carbon::now()->format("Y-m-d H:i");
    }

    public static function nowSecondString()
    {
        return Carbon::now()->format("Y-m-d H:i:s");
    }

    public static function nowTimestamp()
    {
        return Carbon::now()->timestamp;
    }

    public static function parse($timeStr)
    {
        return Carbon::parse($timeStr);
    }

    public static function millisecondsOfNow(){
        return round(microtime(true) * 1000);
    }

    public static function micro(){
        $now = Carbon::now();
        return (int)(round(microtime(true) * 1000) . $now->micro);
    }
}