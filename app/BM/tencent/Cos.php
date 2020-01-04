<?php
/**
 * Created by PhpStorm.
 * User: abellee
 * Date: 2019-01-24
 * Time: 15:11
 */

namespace App\BM\tencent;

use App\BM\utils\Time;

define('TENCENT_BUCKET_NAME', config('banma.tencent.cos.bucket'));
define('TENCENT_APP_ID', config('banma.tencent.cos.app_id'));
define('TENCENT_REGION', config('banma.tencent.cos.region'));
define('TENCENT_SECRET_ID', config('banma.tencent.cos.secret_id'));
define('TENCENT_SECRET_KEY', config('banma.tencent.cos.secret_key'));
define('TENCENT_COS_DURATION', config('banma.tencent.cos.timeout'));
define('TENCENT_COS_ACL_TIMEOUT', config('banma.tencent.cos.acl_timeout'));

class Cos
{
    private static $config = [
        'Url' => 'https://sts.api.qcloud.com/v2/index.php',
        'Domain' => 'sts.api.qcloud.com',
        'Proxy' => '',
        'SecretId' => TENCENT_SECRET_ID, // 固定密钥
        'SecretKey' => TENCENT_SECRET_KEY, // 固定密钥
        'Bucket' => TENCENT_BUCKET_NAME . '-' . TENCENT_APP_ID,
        'Region' => TENCENT_REGION,
        'AllowPrefix' => ''
    ];

    private static function _hex2bin($data) {
        $len = strlen($data);
        return pack("H" . $len, $data);
    }

    private static function json2str($obj, $notEncode = false) {
        ksort($obj);
        $arr = array();
        foreach ($obj as $key => $val) {
            array_push($arr, $key . '=' . ($notEncode ? $val : rawurlencode($val)));
        }
        return join('&', $arr);
    }

    private static function getSignature($opt, $key, $method) {
        $formatString = $method . self::$config['Domain'] . '/v2/index.php?' . self::json2str($opt, 1);
        $sign = hash_hmac('sha1', $formatString, $key);
        $sign = base64_encode(self::_hex2bin($sign));
        return $sign;
    }

    public static function getACLSignature(string $uri){
        $now = Time::now();
        $startTime = $now->timestamp;
        $endTime = $now->addMinutes(TENCENT_COS_ACL_TIMEOUT)->timestamp;


        $qSignTime = "$startTime;$endTime";
        $headerKeys = [];
        $urlParamKeys = [];

        // Signature计算所需参数
        // 目前只支持get方式获取
        $httpMethod = 'get';
        $httpURI = '/' . $uri;
        $httpParams = [];
        $httpHeaders = [];
        $joinParams = join('&', $httpParams);
        $joinHeaders = join('&', $httpHeaders);
        $httpString = "$httpMethod\n$httpURI\n$joinParams\n$joinHeaders\n";
        $sha1edHttpString = sha1($httpString);
        $stringToSign = "sha1\n$qSignTime\n$sha1edHttpString\n";
        $signKey = hash_hmac('sha1', $qSignTime, 'AiHQL5f1BidKq6TaR6WnxrqqiOWgdDSS');
        $signature = hash_hmac('sha1', $stringToSign, $signKey);

        $arr = [
            'q-sign-algorithm' => 'sha1',
            'q-ak' => TENCENT_SECRET_ID,
            'q-sign-time' => $qSignTime,
            'q-key-time' => $qSignTime,
            'q-header-list' => join(';', $headerKeys),
            'q-url-param-list' => join(';', $urlParamKeys),
            'q-signature' => $signature
        ];

        $tempArr = [];
        foreach ($arr as $key => $value){
            $tempArr[] = $key . '=' . $value;
        }
        return join('&', $tempArr);
    }

    public static function getTempKeys() {
        // 判断是否修改了 AllowPrefix
        if (self::$config['AllowPrefix'] === '_ALLOW_DIR_/*') {
            return array('error'=> '请修改 AllowPrefix 配置项，指定允许上传的路径前缀');
        }

        $policy = array(
            'version'=> '2.0',
            'statement'=> array(
                array(
                    'action'=> array(
                        // // 上传操作
                        'name/cos:*'
                    ),
                    'effect'=> 'allow',
                    'principal'=> array('qcs'=> array('*')),
                    'resource'=> '*'
                )
            )
        );
        $policyStr = str_replace('\\/', '/', json_encode($policy));
        $Action = 'GetFederationToken';
        $Nonce = rand(10000, 20000);
        $Timestamp = time() - 1;
        $Method = 'POST';
        $params = array(
            'Region'=> 'sh',
            'SecretId'=> TENCENT_SECRET_ID,
            'Timestamp'=> $Timestamp,
            'Nonce'=> $Nonce,
            'Action'=> $Action,
            'durationSeconds'=> TENCENT_COS_DURATION,
            'name'=> 'cos',
            'policy'=> urlencode($policyStr)
        );
        $params['Signature'] = self::getSignature($params, TENCENT_SECRET_KEY, $Method);
        $url = self::$config['Url'];
        $ch = curl_init($url);
        self::$config['Proxy'] && curl_setopt($ch, CURLOPT_PROXY, self::$config['Proxy']);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::json2str($params));
        $result = curl_exec($ch);
        if(curl_errno($ch)) $result = curl_error($ch);
        curl_close($ch);
        $result = json_decode($result, 1);

        if (isset($result['data'])) $result = $result['data'];

        $arr = [
            'sessionToken' => $result['credentials']['sessionToken'],
            'tmpSecretId' => $result['credentials']['tmpSecretId'],
            'tmpSecretKey' => $result['credentials']['tmpSecretKey'],
            'region' => TENCENT_REGION,
            'bucket' => TENCENT_BUCKET_NAME,
            'appId' => TENCENT_APP_ID,
            'expiredTime' => $result['expiredTime'],
            'now' => Time::nowTimestamp()
        ];
        return $arr;
    }
}