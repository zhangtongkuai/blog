<?php
namespace App\BM\thirdparty\wechat;

use App\BM\consts\Response;
use App\BM\utils\Log;

define('REFRESH_TOKEN_PATH', 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=APP_ID&grant_type=refresh_token&refresh_token=REFRESH_TOKEN');
define('APP_ID', 'wx440af5db63548e29');
define('APP_SECRET', '8651e6ce94d826e5558e79b121d114ce');
define('ACCESS_TOKEN_PATH', 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=APP_ID&secret=APP_SECRET&code=CODE&grant_type=authorization_code');
define('USER_INFO_PATH', 'https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN');

class Wechat{
    /**
	* $url: 请求链接
	* $data: array("key" => value ...)
	*/
	public static function post($url, $data)
	{
		$ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	    $output = curl_exec($ch);
	    curl_close($ch);
	    return $output;
	}

	public static function get($url)
	{
		$ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	    $output = curl_exec($ch);
	    curl_close($ch);
	    return $output;
	}

	/**
	* $len: 随机字符串长度
	*/
	public static function randomString($len = 16){
	    $t = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';
	    $n = strlen($t);
	    $r = '';
	    for ($i = 0; $i < $len; $i++){
	    	$r .= $t{rand(0, $n - 1)};
	    }
	    return $r;
	}

	public static function sos($wechat_result){
		if(!$wechat_result || !isset($wechat_result['errcode'])){
			return ['errcode' => Response::GO_DIE, 'wechat_result' => $wechat_result];
		}
		switch($wechat_result['errcode']){
			case Response::INVALID_ACCESS_TOKEN:
			case Response::ACCESS_TOKEN_EXPIRED:
				if(!isset($wechat_result['refresh_token'])){
					return ['errcode' => Response::GO_DIE, 'wechat_result' => $wechat_result];
				}
				$result = self::refreshAccessToken($wechat_result['refresh_token']);
				if(isset($result['errcode'])){
					return ['errcode' => Response::GO_DIE, 'data' => $result, 'wechat_result' => $wechat_result];
				}
				return ['errcode' => Response::I_AM_NO_NAME, 'data' => $result];
			case Response::INVALID_REFRESH_TOKEN:
			case Response::INVALID_CODE:
			case Response::CODE_EXPIRED:
			case Response::REFRESH_TOKEN_EXPIRED:
			case Response::INVALID_OPENID:
				return ['errcode' => Response::NOT_UR_BUSINESS, 'data' => Response::RELOGIN_WITH_ERROR, 'wechat_result' => $wechat_result];
			default:
				return ['errcode' => Response::GO_DIE, 'wechat_result' => $wechat_result];
		}
	}
	/**
	 * 刷新访问令牌
	 * @param  [type] $refreshToken [description]
	 * @return [type]               [description]
	 */
	public static function refreshAccessToken($refreshToken){
		$path = str_replace('REFRESH_TOKEN', $refreshToken, REFRESH_TOKEN_PATH);
		$path = str_replace('APP_ID', APP_SECRET, $path);
		$result = self::get($path);
		$result = json_decode($result, true);
		return $result;
	}
	/**
	 * 获取访问令牌
	 * @param  [type] $code [description]
	 * @return [type]       [description]
	 */
	public static function getAccessToken($code)
	{
	    $path = str_replace('APP_ID', APP_ID, ACCESS_TOKEN_PATH);
	    $path = str_replace('APP_SECRET', APP_SECRET, $path);
	    $path = str_replace('CODE', $code, $path);
	    $result = self::get($path);
	    $result = json_decode($result, true);
	    return $result;
	}
	/**
	 * 登录
	 * @param  [type] $code [description]
	 * @return [type]       [description]
	 */
	public static function login($code)
	{
		Log::info('start get access token');
		$result = self::getAccessToken($code);
	    if(!isset($result['errcode'])){
	    	Log::info('start get user info');
	    	$userInfo = self::getUserInfo($result['access_token'], $result['openid']);
	    	$userInfo['access_token'] = $result['access_token'];
	    	$userInfo['refresh_token'] = $result['refresh_token'];
	    	return $userInfo;
	    }
	    return $result;
	}

	/**
	 * 用户信息
	 * @param  [type] $access_token [description]
	 * @param  [type] $open_id      [description]
	 * @return [type]               [description]
	 */
	public static function getUserInfo($access_token, $open_id){
		$path = str_replace('ACCESS_TOKEN', $access_token, 'USER_INFO_PATH');
		$path = str_replace('OPENID', $open_id, $path);
		$result = self::get($path);
	    $result = json_decode($result, true);
	    return $result;
	}

	/**
	 * 用于微信公众号
	 */
	public static function getConfig(){
		if(file_exists('wx.json')){
			$config = json_decode(file_get_contents('wx.json'), true);
			$appId = $config['appid'];
			$appSec = $config['appsecret'];
			$expireTime = $config['expire_time'];
			$cacheFile = $config['cachefile'];
			$debug = $config['debug'];
			$ignore = $config['ignore'];
			$custom = $config['custom'];

			$apis = array(
				'onMenuShareTimeline',
				'onMenuShareAppMessage',
				'onMenuShareQQ',
				'onMenuShareWeibo',
				'startRecord',
				'stopRecord',
				'onVoiceRecordEnd',
				'playVoice',
				'pauseVoice',
				'stopVoice',
				'onVoicePlayEnd',
				'uploadVoice',
				'downloadVoice',
				'chooseImage',
				'previewImage',
				'uploadImage',
				'downloadImage',
				'translateVoice',
				'getNetworkType',
				'openLocation',
				'getLocation',
				'hideOptionMenu',
				'showOptionMenu',
				'hideMenuItems',
				'showMenuItems',
				'hideAllNonBaseMenuItem',
				'showAllNonBaseMenuItem',
				'closeWindow',
				'scanQRCode',
				'chooseWXPay',
				'openProductSpecificView',
				'addCard',
				'chooseCard',
				'openCard'
			);

			foreach($ignore as $value){
				$index = array_search($value, $apis);
				array_splice($apis, $index, 1);
			}

			$accessToken = self::getAccessTokenGZ($cacheFile['access_token'], $appId, $appSec, $expireTime);
			$ticket = self::getTicket($cacheFile['ticket'], $accessToken, $expireTime);

    		$from = $_SERVER['HTTP_REFERER'];
			$pathArr = explode('#', $from);
			$from = $pathArr[0];
			$timestamp = time();
			$randomStr = self::randomString();

			$arr = array('jsapi_ticket=' . $ticket, 'noncestr=' . $randomStr, 'timestamp=' . $timestamp, 'url=' . $from);
		    sort($arr);
		    $str = $arr[0] . '&' . $arr[1] . '&' . $arr[2] . '&' . $arr[3];
		    $signature = sha1($str);

		    $config = array(
		    	'debug'			=> $debug,
		    	'appId'			=> $appId,
		    	'timestamp'		=> $timestamp,
		    	'nonceStr'		=> $randomStr,
		    	'signature'		=> $signature,
		    	'jsApiList'		=> $apis
		    );

		    if($debug){
		    	$config['ticket'] = $ticket;
		    	$config['token'] = $accessToken;
		    	$config['rawString'] = $str;
		    	$config['url'] = $from;
		    }

		    $resStr = '';
		    if($custom && sizeof($custom) > 0){
		    	foreach($custom as $key=>$val){
		    		$resStr .= 'window.' . $key . '="' . $val . '";';
		    	}
		    }
		    // return $config;
		    $resStr .= 'wx.config(' . json_encode($config) . ')';
			return  $resStr;
		}else{
			echo 'wx.json is not exists!';
			exit();
		}
	}

	/**
	* !important: 请确保ticketCacheFile可写
	*/
	public static function getTicket($ticketCacheFile, $accessToken, $expireTime)
	{
		if(file_exists($ticketCacheFile)){
			$info = json_decode(file_get_contents($ticketCacheFile), true);
			if($info['ticket'] && $info['expire_time'] > time()){
		        return $info['ticket'];
		    }
		}
	    $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $accessToken . '&type=jsapi';
	    $result = self::get($url);
	    $data = json_decode($result, true);
	    $ticket = $data['ticket'];
	    if($ticket){
	        $arr = array();
	        $arr['ticket'] = $ticket;
	        $arr['expire_time'] = time() + $expireTime;
	        file_put_contents($ticketCacheFile, json_encode($arr));
	    }
	    return $data['ticket'];
	}

	/**
	* !important: 请确保accessTokenCacheFile可写
	*/
	public static function getAccessTokenGZH($accessTokenCacheFile, $appId, $appSecret, $expireTime)
	{
		if(file_exists($accessTokenCacheFile)){
			$info = json_decode(file_get_contents($accessTokenCacheFile), true);
			if($info['access_token'] && $info['expire_time'] > time()){
		        return $info['access_token'];
		    }
		}
	    $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appId . '&secret=' . $appSecret;
	    $result = self::get($url);
	    $data = json_decode($result, true);
	    $accessToken = $data['access_token'];
	    if($accessToken){
	        $arr = array();
	        $arr['access_token'] = $accessToken;
	        $arr['expire_time'] = time() + $expireTime;
	        file_put_contents($accessTokenCacheFile, json_encode($arr));
	        return $accessToken;
	    }
	    else return '';
	}
}