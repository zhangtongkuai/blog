<?php
/**
 * Created by PhpStorm.
 * User: abellee
 * Date: 2019-01-19
 * Time: 17:04
 */

namespace App\BM\tencent;

use App\BM\utils\Log;
use App\BM\utils\Time;
use GuzzleHttp\Client;

define('WECHAT_APP_APP_ID', config('app.debug') ? config('banma.wechat.dev.app_id') : config('banma.wechat.production.app_id'));
define('WECHAT_APP_APP_SECRET', config('app.debug') ? config('banma.wechat.dev.app_secret') : config('banma.wechat.production.app_secret'));
//define('WECHAT_APP_APP_ID', config('banma.wechat.production.app_id'));
//define('WECHAT_APP_APP_SECRET', config('banma.wechat.production.app_secret'));

class Wechat
{
    public static function getAccessToken($code){
        $client = new Client();
        $response = $client->request('GET', 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . WECHAT_APP_APP_ID . '&secret=' . WECHAT_APP_APP_SECRET . '&code=' . $code . '&grant_type=authorization_code');
        $data = $response->getBody();
        $data = json_decode($data, true);
        if(isset($data['access_token'])){
            $data['timestamp'] = Time::nowTimestamp();
        }else{
            Log::error([
                'err_msg' => 'get accessToken failed',
                'params' => $data
            ]);
        }
        return $data;
    }

    public static function refreshToken($accessToken, $refreshToken){
        $client = new Client();
        $response = $client->request('GET', 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=' . WECHAT_APP_APP_ID . '&grant_type=refresh_token&refresh_token=' . $refreshToken);
        $data = $response->getBody();
        $data = json_decode($data, true);
        if(isset($data['access_token'])) {
            $data['timestamp'] = Time::nowTimestamp();
        }else{
            Log::error([
                'err_msg' => 'get accessToken failed',
                'params' => $data
            ]);
        }
        return $data;
    }

    public static function isAccessTokenExpired($expiresIn, $timestamp){
        $now = Time::nowTimestamp();
        if($now >= $expiresIn + $timestamp){
            return true;
        }
        return false;
    }

    /**
     * 微信官方给出的refreshToken的时效性为30天，这里使用25天作为限制
     * @param $expiresIn
     * @param $timestamp
     */
    public static function canRefresh($expiresIn, $timestamp){
        $now = Time::nowTimestamp();
        if($now >= $expiresIn + $timestamp && $now <= 25 * 24 * 60 * 60 + $timestamp){
            return true;
        }
        return false;
    }

    public static function getUserInfo($code){
        $data = self::getAccessToken($code);
        if(isset($data['access_token'])){
            $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $data['access_token'] . '&openid=' . $data['openid'];
            $client = new Client();
            $response = $client->request('GET', $url);
            $userInfo = json_decode($response->getBody(), true);
            $userInfo = array_merge($userInfo, $data);
            if(isset($userInfo['headimgurl'])){
                $arr = explode('/', $userInfo['headimgurl']);
                $arr[count($arr) - 1] = '0';
                $userInfo['headimgurl'] = join('/', $arr);
            }
            return $userInfo;
        }
        return null;
    }
}