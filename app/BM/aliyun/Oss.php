<?php
namespace App\BM\aliyun;

use \Datetime;
use OSS\OssClient;
use OSS\Core\OssException;
use App\BM\utils\Log;

include_once 'aliyun-php-sdk-core/Config.php';
use Sts\Request\V20150401\AssumeRoleRequest;

define('ACCESS_KEY_ID', config('banma.aliyun.oss.access_key_id'));
define('ACCESS_KEY_SECRET', config('banma.aliyun.oss.access_key_secret'));

define('ROLE_ARN', config('banma.aliyun.oss.role_arn'));
define('DURATION', config('banma.aliyun.oss.sts_duration'));

define('PUBLIC_END_POINT', config('banma.aliyun.oss.public.end_point'));
define('PUBLIC_BUCKET', config('banma.aliyun.oss.public.bucket'));
define('PUBLIC_HOST', config('banma.aliyun.oss.public.host'));
define('PUBLIC_REGION', config('banma.aliyun.oss.public.region'));
define('PUBLIC_DIR', config('banma.aliyun.oss.public.dir'));

define('PRIVATE_END_POINT', config('banma.aliyun.oss.private.end_point'));
define('PRIVATE_BUCKET', config('banma.aliyun.oss.private.bucket'));
define('PRIVATE_HOST', config('banma.aliyun.oss.private.host'));
define('PRIVATE_REGION', config('banma.aliyun.oss.private.region'));

class Oss {

    private static function gmt_iso8601($time) {
        $dtStr = date("c", $time);
        $mydatetime = new DateTime($dtStr);
        $expiration = $mydatetime->format(DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }

    public static function auth(string $dir, string $baseURL = null) {
        // $id= 'LTAIoHRORdNzwb2u';          // 请填写您的AccessKeyId。
        // $key= 'gLhZZxnFgtFNZtPGNUxdmf2wKFO8jp';     // 请填写您的AccessKeySecret。
        $id = ACCESS_KEY_ID;
        $key = ACCESS_KEY_SECRET;
        // $host的格式为 bucketname.endpoint，请替换为您的真实信息。
        $host = PUBLIC_HOST;
        // $callbackUrl为上传回调服务器的URL，请将下面的IP和Port配置为您自己的真实URL信息。
        $callbackUrl = '';

        $callback_param = array('callbackUrl'=>$callbackUrl,
            'callbackBody'=>'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType'=>"application/x-www-form-urlencoded");
        $callback_string = json_encode($callback_param);

        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = 600;  //设置该policy超时时间是10分钟. 即这个policy过了这个有效时间，将不能访问。
        $end = $now + $expire;
        $expiration = self::gmt_iso8601($end);


        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition;

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $start = array(0=>'starts-with', 1=>'$key', 2=>$dir);
        $conditions[] = $start;


        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = array();
        $response['accessid'] = $id;
        $response['host'] = $host;
        $response['end_point'] = PUBLIC_END_POINT;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64_callback_body;
        $response['dir'] = $dir;  // 这个参数是设置用户上传文件时指定的前缀。
        $response['bucket'] = PUBLIC_BUCKET;
        $response['base_url'] = $baseURL;

        return $response;
    }

    public static function sts(string $clientName){
        $clientName = str_replace(' ', '_', $clientName);
        $profile = \DefaultProfile::getProfile(PUBLIC_REGION, ACCESS_KEY_ID, ACCESS_KEY_SECRET);
        $client = new \DefaultAcsClient($profile);

        $policy = <<<POLICY
        {
          "Statement": [
            {
              "Action": [
                "oss:Put*",
                "sts:AssumeRole"
              ],
              "Effect": "Allow",
              "Resource": "*"
            }
          ],
          "Version": "1"
        }
POLICY;

        $request = new AssumeRoleRequest();
        $request->setRoleSessionName($clientName);
        $request->setRoleArn(ROLE_ARN);
        $request->setPolicy($policy);
        $request->setDurationSeconds(DURATION);

        try{
            $response = $client->getAcsResponse($request);
            $credentials = $response->Credentials;
            $credentials->EndPoint = PUBLIC_END_POINT;
            $credentials->Bucket = PUBLIC_BUCKET;
            $credentials->Host = PUBLIC_HOST;
            $credentials->Dir = PUBLIC_DIR;
            return $credentials;
        }catch (\ServerException $e){
            Log::error($e->getMessage());
            return $e->getMessage();
        }catch (\ClientException $e){
            Log::error($e->getMessage());
            return $e->getMessage();
        }
    }

    public static function putObject($fileName, $content) {
        $object = $fileName;
        try{
            $ossClient = new OSSClient(ACCESS_KEY_ID, ACCESS_KEY_SECRET, PUBLIC_END_POINT);
            $data = $ossClient->putObject(PUBLIC_BUCKET, $object, $content);
            return $data;
        }catch(OSSException $e){
            Log::error($e->getMessage());
            return false;
        }
    }

    public static function getPrivateObjectUrl($fileName, $timeout = 10){
        try{
            $ossClient = new OssClient(ACCESS_KEY_ID, ACCESS_KEY_SECRET, PRIVATE_END_POINT);
            $data = $ossClient->signUrl(PRIVATE_BUCKET, $fileName, $timeout);
            return $data;
        }catch(OssException $e){
            Log::error($e->getMessage());
            return false;
        }
    }

    public static function uploadFile($path, $fileName) {
        $object = $fileName;
        try{
            $ossClient = new OSSClient(ACCESS_KEY_ID, ACCESS_KEY_SECRET, PUBLIC_END_POINT);
            $data = $ossClient->uploadFile(PUBLIC_BUCKET, $object, $path);
            return $data;
        }catch(OSSException $e){
            Log::error($e->getMessage());
            return false;
        }
    }
}
