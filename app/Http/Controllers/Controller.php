<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\BM\consts\Response as BMResponse;
use App\BM\utils\Log as BMLog;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public static function error($code, $msg = '', $redirect = '', $debugMsg = null){
        $err = '';
        if(empty($msg) && isset(BMResponse::ERROR_MESSAGES[$code])){
            $err = BMResponse::ERROR_MESSAGES[$code];
        }else{
            $err = $msg;
        }

        if(env('APP_DEBUG') && isset($debugMsg)){
            $err = $debugMsg;
        }

        BMLog::error(
            [
                'message' => '['. \Route::current()->getActionName(). ']'. $msg,
            ]
        );
        $res = array('err_code' => $code, 'err_msg' => $err, 'success' => false, 'data'=> null);
        if($redirect != ''){
            $res['redirect'] = $redirect;
        }

        $type = Input::get('type');
        if($type == 'jsonp'){
            return Response::json($res)->setCallback(Input::get('callback'));
        }
        return Response::json($res);
    }
}
