<?php
namespace App\BM\email;

class Email {
    public static function send(Data $data){
        $res = self::sendAsync($data);
        $result = [
            "success" => true
        ];
        if($res !== true){
            $result["result"] = $res;
        }
        return $result;
    }

    /**
     * @param $to
     * @param $data [ "code" => $code, "minutes" => $minutes ]
     * @param $content
     * @return mixed
     */
    public static function sendAsync(Data $data){
        try{
            Mail::raw($data->content, function($message) use($data) {
                $message->from($data->from)->to($data->to)->subject($data->title);
            });
        }catch(\Exception $e){
            return $e->getMessage();
        }
        return true;
    }
}