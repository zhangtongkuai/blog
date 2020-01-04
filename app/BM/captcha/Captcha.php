<?php
namespace App\BM\captcha;

use App\BM\consts\CaptchaStatus;
use App\BM\utils\Log;
use Illuminate\Support\Facades\Cache;
use Webpatser\Uuid\Uuid;

class Captcha
{
    var $im = null;
    var $im_fullbg = null;
    var $im_bg = null;
    var $im_slide = null;
    var $bg_width = 720;
    var $bg_height = 450;
    var $mark_width = 117;
    var $mark_height = 117;
    var $bg_num = 1;
    var $_x = 0;
    var $_y = 0;
    var $times = '@2x';
    var $block_num = 1;
    var $block_index = 0;
    //容错象素 越大体验越好，越小破解难道越高
    var $_fault = 20;
    var $host = '';
    function __construct(string $host, int $t = 2){
        error_reporting(0);
        if($t == 1){
            $this->times = '';
        }else{
            $this->times = '@' . $t . 'x';
        }
        $this->host = $host;
        $this->block_index = mt_rand(0, $this->bg_num);
        $this->bg_width = 720 / (3 / $t);
        $this->bg_height = 450 / (3 / $t);
        $this->mark_width = 117 / (3 / $t);
        $this->mark_height = 117 / (3 / $t);
        if($this->block_index == 1){
            $this->mark_width = 75 / (3 / $t);
            $this->mark_height = 127 / (3 / $t);
        }
    }

    function make(){
        $this->_init();
        $this->_createSlide();
        $this->_createBg();
        $this->_merge();
        $content = $this->_imgout();
        $this->_destroy();
        return $content;
    }

    /**
     * @param int $movedX
     * @return int
     */
    public function check(int $movedX = 0){
        $vc = $_COOKIE['vc'];
        $realX = Cache::get("$vc");
        $checkTimes = Cache::get('check_times_' . $vc);
        Log::info("movedX: $movedX >>>>> realX: $realX >>>>>> vc: $vc");
        if($checkTimes >= config('banma.captcha.max_attempts')){
            Log::info('over limit');
            return CaptchaStatus::OVER_LIMIT;
        }
        if(Cache::has('check_times_' . $vc)){
            Cache::increment('check_times_' . $vc);
        }
        if(isset($realX)){
            if(abs($realX - $movedX) <= $this->_fault){
                Cache::put("$vc", 1, config('banma.captcha.invalid_in'));
                Log::info('success');
                return CaptchaStatus::SUCCESS;
            }
            return CaptchaStatus::FAILED;
        }
        Log::info('over time');
        return CaptchaStatus::OVER_TIME;
    }


    private function _init(){
        $bg = mt_rand(1, $this->bg_num);
        $file_bg = public_path('captcha/imgs/bg/' . $bg . $this->times . '.jpg');
        $this->im_fullbg = imagecreatefromjpeg($file_bg);
        $this->im_bg = imagecreatetruecolor($this->bg_width, $this->bg_height);
        imagecopy($this->im_bg,$this->im_fullbg,0,0,0,0,$this->bg_width, $this->bg_height);
        $this->im_slide = imagecreatetruecolor($this->mark_width, $this->bg_height);
        // 真实位置
        $this->_x = mt_rand(50, $this->bg_width - $this->mark_width - 1);
        $this->_y = mt_rand(0, $this->bg_height - $this->mark_height - 1);

//        $vc = $_COOKIE['vc'];
//        if(isset($vc)){
//            Cache::forget("$vc");
//            Cache::forget("check_times_$vc");
//        }

        $uuid = Uuid::generate();
        $vc = md5(md5(md5($uuid)));
        Log::info("vc is $vc and x is $this->_x");
        header('Set-Cookie: vc=' . $vc . '; path=/; domain=' . $this->host . '; HttpOnly; SameSite=Lax');
        Cache::put("$vc", $this->_x, config('banma.captcha.invalid_in'));
        Cache::put('check_times_' . $vc, 0, config('banma.captcha.invalid_in'));

        Log::info('check times in init: ' . Cache::get('check_times_' . $vc). '>>>>>>' . 'check_times_' . $vc);
    }

    private function _destroy(){
        imagedestroy($this->im);
        imagedestroy($this->im_fullbg);
        imagedestroy($this->im_bg);
        imagedestroy($this->im_slide);
    }

    private function _imgout(){
        $func = 'imagepng';
        $func($this->im, null, 7);
    }

    private function _merge(){
        $this->im = imagecreatetruecolor($this->bg_width, $this->bg_height * 3);
        $white = imagecolorallocate($this->im, 255, 255, 255);
        imagefill($this->im, 0, 0, $white);
        imagecolortransparent($this->im, $white);

        imagecopy($this->im, $this->im_bg, 0, 0, 0, 0, $this->bg_width, $this->bg_height);
        imagecopy($this->im, $this->im_slide, 0, $this->bg_height , 0, 0, $this->mark_width, $this->bg_height);
        imagecopy($this->im, $this->im_fullbg, 0, $this->bg_height * 2 , 0, 0, $this->bg_width, $this->bg_height);
    }

    private function _createBg(){
        $file_mark = public_path('captcha/imgs/block_' . $this->block_index . $this->times . '.png');
        $im = imagecreatefrompng($file_mark);

        header('Content-Type: image/png');
        imagecolortransparent($im,0);
        imagecopy($this->im_bg, $im, $this->_x, $this->_y  , 0  , 0 , $this->mark_width, $this->mark_height);
        imagedestroy($im);
    }

    private function _createSlide(){
        $file_mark = public_path('captcha/imgs/mask_' . $this->block_index . $this->times . '.png');;
        $img_mark = imagecreatefrompng($file_mark);
        imagecopy($this->im_slide, $this->im_fullbg,1, $this->_y + 1, $this->_x, $this->_y, $this->mark_width - 2, $this->mark_height - 2);
        imagecopy($this->im_slide, $img_mark,0, $this->_y , 0, 0, $this->mark_width, $this->mark_height);
        imagecolortransparent($this->im_slide,0);
        imagedestroy($img_mark);
    }
}