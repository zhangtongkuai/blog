<?php
namespace App\BM\libs\DHTCSms;

/**
 * 大汉三通 短信接口
 * @package app\libs\DHTCSms
 * @property string $account 账号
 * @property string $password 密码
 * @property string $sign 签名
 */
class Agent extends \App\Toplan\PhpSms\Agent implements \App\Toplan\PhpSms\ContentSms
{

    const URL_SEND_CONTENT = "http://www.dh3t.com/json/sms/Submit";

    /**
     * @param array|string $to
     * @param string $content
     */
    public function sendContentSms($to, $content)
    {
        if(!is_array($to)){
            $to = [$to];
        }

        $data = [
            'account' => $this->account,
            'password' => strlen($this->password) !== 32 ? md5($this->password) : $this->password,
            'msgid' => null,
            'phones' => implode(',', array_map('intval', $to)),
            'content' => $content,
            'sign' => $this->sign,
            'subcode' => null,
            'sendtime' => null
        ];

        $res = $this->curlPostJson(self::URL_SEND_CONTENT, json_encode($data, JSON_UNESCAPED_UNICODE));

        if(!$res['request']) {
            $this->result(self::SUCCESS, false);
            $this->result(self::CODE, 0);
            $this->result(self::INFO, '网络请求失败');
            return;
        }

        $result = json_decode($res['response'], true);

        if(!is_array($result) || !isset($result['result'])) {
            $this->result(self::SUCCESS, false);
            $this->result(self::CODE, -1);
            $this->result(self::INFO, "未知错误");
        } else if((int)$result['result'] === 0) {
            //@todo: 多条信息没有办法返回单条状态
            if(count($to) < 2 && isset($result['data']) && is_array($result['data'])) {
                $this->result(self::SUCCESS, (int)$result['data'][0]['status'] === 0);
                $this->result(self::CODE, (int)$result['data'][0]['status']);
                $this->result(self::INFO, $result['data'][0]['desc']);
            } else {
                $this->result(self::SUCCESS, true);
                $this->result(self::CODE, 0);
            }
        } else {
            $this->result(self::SUCCESS, false);
            $this->result(self::CODE, $result['result']);
            $this->result(self::INFO, $result['desc']);
        }
    }

    public function curlPostJson($url, $json, array $opts = [])
    {
        $options = [
            CURLOPT_POST    => true,
            CURLOPT_URL     => $url,
        ];
        foreach ($opts as $key => $value) {
            if ($key !== CURLOPT_POST && $key !== CURLOPT_URL) {
                $options[$key] = $value;
            }
        }

        $options[CURLOPT_POSTFIELDS] = $json;

        return self::curl($options);
    }



    public function getErrorMessage($code)
    {
        $map = [
            0 => "提交成功",
            1 => "账号无效",
            2 => "密码错误",
            3 => "msgid太长，不得超过64位",
            4 => "错误号码/限制运营商号码",
            5 => "手机号码个数超过最大限制",
            6 => "短信内容超过最大限制",
            7 => "扩展子号码无效",
            8 => "定时时间格式错误",
            14 => "手机号码为空",
            19 => "用户被禁发或禁用",
            20 => "IP鉴权失败",
            21 => "短信内容为空",
            22 => "数据包大小不匹配",
            23 => "taskid太长",
            24 => "无可用号码",
            25 => "批量提交短信数超过最大限制",
            98 => "系统正忙",
            99 => "消息格式错误",
        ];

        return isset($map[(int)$code]) ? $map[(int)$code] : '未知错误';
    }

}