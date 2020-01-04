<?php
/**
 * Created by PhpStorm.
 * User: abellee
 * Date: 2019-02-21
 * Time: 00:56
 */

namespace App\BM\tencent;


use App\BM\utils\Time;
use App\BM\utils\Utils;
use Vod\Model\VodUploadRequest;
use Vod\VodUploadClient;

class Vod
{
    public static function getTempKey(string $type = 'mp4', bool $needAudio = false){
        $secretId = config('banma.tencent.vod.secret_id');
        $secretKey = config('banma.tencent.vod.secret_key');

        $current = time();
        $expired = $current + config('banma.tencent.vod.expiration');
        $args = [
            'secretId' => $secretId,
            'currentTimeStamp' => $current,
            'expireTime' => $expired,
            'random' => rand(),
            'vodSubAppId' => config('app.debug') ? config('banma.tencent.vod.dev.sub_app_id') : 0
        ];

        if($type == "mp4"){
            $args['procedure'] = 'process_hls';
            // 如果需要采样 加上  . ',10,10)'
//            $args['procedure'] = 'QCVB_SimpleProcessFile({210,220,230,240' . ($needAudio ? ',1010,1020' : '') . '},' . (config('app.debug') ? config('banma.tencent.vod.dev.watermark_id') : config('banma.tencent.vod.production.watermark_id')) . ',10,10)';
        }

        $original = http_build_query($args);
        $signature = base64_encode(hash_hmac('SHA1', $original, $secretKey, true).$original);
        return $signature;
    }

    /**
     * @param string $url
     * @param int $exper 表示试看时长，不填或填0表示不试看
     * @param int $rlimit 最多允许多少个IP的终端播放，不填表示不限制
     */
    public static function getPlayableURL(string $url, int $exper = 0, int $rlimit = 0){
        $expiration = config('banma.tencent.vod.playable_url_expiration');
        $key = config('app.debug') ? config('banma.tencent.vod.dev.key') : config('banma.tencent.vod.production.key');

        $components = parse_url($url);
        if(!isset($components['path'])){
            return '';
        }
        $path = $components['path'];
        $arr = explode('/', $path);
        unset($arr[3]);

        $us = Utils::genRandString(10, false);
        $t = base_convert(Time::nowTimestamp() + $expiration, 10, 16);
        $dir = join('/', $arr) . '/';

        $sign = md5($key . $dir . $t . ($exper > 0 ? $exper : '') . ($rlimit > 0 ? $rlimit : '') . $us);
        return "$url?t=$t" . ($exper > 0 ? "&exper=$exper" : '') . ($rlimit > 0 ? "&rlimit=$rlimit" : '') . "&us=$us&sign=$sign";
    }

    /**
     * @param string $fullPath
     * @param string $fileName
     * @param bool $addWatermark
     * @return \Exception|\Vod\Model\VodUploadResponse 只需要使用 $response->FileId, $response->CoverUrl, $response->MediaUrl 这三个即可
     */
    public static function uploadFile(string $fullPath, string $fileName, bool $addWatermark, bool $needAudio = false, string $coverPath = null){
        $client = new VodUploadClient(config('banma.tencent.vod.secret_id'), config('banma.tencent.vod.secret_key'));
        $request = new VodUploadRequest();
        $request->MediaFilePath = $fullPath;
        $request->MediaName = $fileName;
        $request->Procedure = 'process_hls';
//        $request->Procedure = 'QCVB_SimpleProcessFile({210,220,230,240' . ($needAudio ? ',1010,1020' : '') . '}' . ($addWatermark ? ',' . (config('app.debug') ? config('banma.tencent.vod.dev.watermark_id') : config('banma.tencent.vod.production.watermark_id')) : ',10,10)');
        if(isset($coverPath)){
            $request->CoverFilePath = $coverPath;
        }
        if(config('app.debug'))
            $request->SubAppId = config('banma.tencent.vod.dev.sub_app_id');

        try{
            $response = $client->upload(config('banma.tencent.vod.region'), $request);
            return $response;
        }catch (\Exception $e){
            return $e;
        }
    }
}
