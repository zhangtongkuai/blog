<?php
namespace App\BM\utils;

class Env{
    public static function isDebug(){
        return env("APP_DEBUG");
    }

    public static function isLocal() {
        return env('APP_ENV') === 'local' ?: false;
    }

    public static function isDev() {
        return env('APP_ENV') === 'dev' ?: false;
    }

    public static function isProd() {
        return env('APP_ENV') === 'prod' ?: false;
    }
}