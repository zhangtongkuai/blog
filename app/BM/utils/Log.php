<?php
namespace App\BM\utils;

use Carbon\Carbon;
use Storage;
use Jenssegers\Agent\Agent;

class Log {
    public static function error($error){
        self::formatLog("Error", $error, self::get_caller_info());
    }

    public static function info($info){
        self::formatLog("Info", $info, self::get_caller_info());
    }

    public static function custom($logName, $str)
    {
        Storage::append($logName, $str);
    }

    private static function formatLog($type, $info, $caller){
        $agent = new Agent();
        if(is_object($info)){
            $info = json_decode($info, true);
        }else{
            $info = [$info];
        }
        $info['platform'] = $agent->platform();
        $info['device'] = $agent->device();
        $info['browser'] = $agent->browser();

        $str = "\n";
        $str .= "------------------------------  " . $type . "  ------------------------------\n";
        $str .= "| Time		:	" . Carbon::now()->format('Y-m-d H:i:s') . "\n";
        $str .= "| Class	:	" . $caller['class'] . "\n";
        $str .= "| Func		:	" . $caller['func'] . "\n";
        $str .= "| Line		:	" . $caller['line'] . "\n";
        $str .= "| File		:	" . $caller['file'] . "\n";
        $str .= "| Content	:	\n| " . json_encode($info, JSON_UNESCAPED_UNICODE) . "\n";
        $str .= "---------------------------------------------------------------------";
        if($type == 'Error'){
            Storage::append('logs/error/error_' . Carbon::today()->format('Y-m-d') . '.log', $str);
        }else if($type == 'Info'){
            Storage::append('logs/info/info_' . Carbon::today()->format('Y-m-d') . '.log', $str);
        }
    }

    private static function get_caller_info() {
        $file = '';
        $func = '';
        $class = '';
        $line = -1;
        $trace = debug_backtrace();
        if (isset($trace[2])) {
            $file = $trace[1]['file'];
            $line = $trace[1]['line'];
            $func = $trace[2]['function'];
            if ((substr($func, 0, 7) == 'include') || (substr($func, 0, 7) == 'require')) {
                $func = '';
            }
        } else if (isset($trace[1])) {
            $file = $trace[1]['file'];
            $line = $trace[1]['line'];
            $func = '';
        }
        if (isset($trace[3]['class'])) {
            $class = $trace[3]['class'];
            $func = $trace[3]['function'];
            $file = $trace[2]['file'];
        } else if (isset($trace[2]['class'])) {
            $class = $trace[2]['class'];
            $func = $trace[2]['function'];
            $file = $trace[1]['file'];
        }
        // if ($file != '') $file = basename($file);
        return ["file" => $file, "func" => $func, "class" => $class, "line" => $line];
    }
}
