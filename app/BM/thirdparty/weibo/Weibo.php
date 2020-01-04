<?php
namespace App\BM\thirdparty\weibo;

use App\BM\utils\Log;

define('WB_USER_INFO', 'https://api.weibo.com/2/users/show.json');

class Weibo {
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

	public static function getUserInfo($access_token, $wb_uid){
		$path = WB_USER_INFO . '?access_token=' . $access_token . '&uid=' . $wb_uid;
		$result = self::get($path);
		$result = json_decode($result, true);
		if(isset($result['error_code'])){
			$result['ea_note'] = 'error occured';
			Log::error($result);
		}
		return $result;
	}
}