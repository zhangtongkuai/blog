<?php
namespace App\BM\libs\EWSSms;
use \App\Toplan\PhpSms\ContentSms;

/**
 * 253云通讯PaaS短信云接口
 *
 * @package app\libs\EWSSms
 * @property string $account 账号
 * @property string $password 密码
 * @property string $host 服务器主机域名
 */
class Agent extends \App\Toplan\PhpSms\Agent implements ContentSms
{
    public function sendContentSms($to, $content)
    {
        if(!is_array($to)){
            $to = [$to];
        }

        $data = [
            'account' => $this->account,
            'password' => $this->password,
            'msg' => urlencode($content),
            'phone' => implode(',', array_map('intval', $to)),
            'sendtime' => "",
            'report' => false,
            'extend' => "",
            'uid' => ""
        ];

        $url = 'http://' . $this->host . "/msg/send/json";
        $res = $this->curlPostJson($url, json_encode($data, JSON_UNESCAPED_UNICODE));

        if(!$res['request']) {
            $this->result(\Toplan\PhpSms\Agent::SUCCESS, false);
            $this->result(\Toplan\PhpSms\Agent::CODE, 0);
            $this->result(\Toplan\PhpSms\Agent::INFO, '网络请求失败');
            return;
        }

        $result = json_decode($res['response'], true);

        if(!is_array($result) || !isset($result['code'])) {
            $this->result(\Toplan\PhpSms\Agent::SUCCESS, false);
            $this->result(\Toplan\PhpSms\Agent::CODE, -1);
            $this->result(\Toplan\PhpSms\Agent::INFO, "未知错误");
        } else if((int)$result['code'] === 0) {
            $this->result(\Toplan\PhpSms\Agent::SUCCESS, true);
            $this->result(\Toplan\PhpSms\Agent::CODE, 0);
        } else {
            $this->result(\Toplan\PhpSms\Agent::SUCCESS, false);
            $this->result(\Toplan\PhpSms\Agent::CODE, $result['code']);
            $this->result(\Toplan\PhpSms\Agent::INFO, $result['errorMsg']);
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

        $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json; charset=utf-8'];
        $options[CURLOPT_POSTFIELDS] = $json;

        return self::curl($options);
    }

    public function getErrorMessage($code)
    {
        $map = [

            0 => "提交成功",
            101 => "无此用户",
            102 => "密码错",
            103 => "提交过快（提交速度超过流速限制）",
            104 => "系统忙（因平台侧原因，暂时无法处理提交的短信）",
            105 => "敏感短信（短信内容包含敏感词）",
            106 => "消息长度错（>536或<=0）",
            107 => "包含错误的手机号码",
            108 => "手机号码个数错（群发>50000或<=0）",
            109 => "无发送额度（该用户可用短信数已使用完）",
            110 => "不在发送时间内",
            113 => "扩展码格式错（非数字或者长度不对）",
            114 => "可用参数组个数错误（小于最小设定值或者大于1000）;变量参数组大于20个",
            116 => "签名不合法或未带签名（用户必须带签名的前提下）",
            117 => "IP地址认证错,请求调用的IP地址不是系统登记的IP地址",
            118 => "用户没有相应的发送权限（账号被禁止发送）",
            119 => "用户已过期",
            120 => "违反防盗用策略(日发送限制)",
            123 => "发送类型错误",
            124 => "白模板匹配错误",
            125 => "匹配驳回模板，提交失败",
            127 => "定时发送时间格式错误",
            128 => "内容编码失败",
            129 => "JSON格式错误",
            130 => "请求参数错误（缺少必填参数）",

        ];

        return isset($map[(int)$code]) ? $map[(int)$code] : '未知错误';
    }


}