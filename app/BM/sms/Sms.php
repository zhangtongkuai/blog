<?php
namespace App\BM\sms;

use App\Jobs\BMSendSms;
use App\Toplan\PhpSms\Sms as PhpSms;

class Sms {

    public static function send(Data $data) {
        $res = self::sendAsync($data);
        $result = [
            "success" => $res["success"]
        ];
        if(!$res["success"]){
            $result["result"] = $res["logs"]["result"];
        }
        return $result;
    }

    /**
     * @param $to
     * @param $data [ "code" => $code, "minutes" => $minutes ]
     * @param $content
     * @return mixed
     */
    public static function sendAsync(Data $data) {
        $content = $data->content;
        $result = PhpSms::make()->to($data->to)->template($data->templates)->data($data->data)->content("${content}")->send();
        return $result;
    }

    /**
     * 登录/注册短信
     * @param type 0为登录 1为注册
     */
    public static function sendCodeForLoginOrRegist(string $tel, string $code, int $type) {
        $minutes = config('banma.sms.code_timeout') / 60;
        $data = [
            'code' => $code,
            'minutes' => $minutes
        ];
        $content = sprintf(config('sms_templates.LOGIN_REGIST'), $code, $type == 0 ? '登录' : '注册', $minutes);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 重新绑定手机
     */
    public static function sendCodeForRebindTel(string $tel, string $code) {
        $minutes = config('banma.sms.code_timeout') / 60;
        $data = [
            'code' => $code,
            'minutes' => $minutes
        ];
        $content = sprintf(config('sms_templates.REBIND_TEL'), $code, $minutes);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 重新绑定手机时，验证原手机号
     */
    public static function sendRebindCodeForOriginTel(string $tel, string $code) {
        $minutes = config('banma.sms.code_timeout') / 60;
        $data = [
            'code' => $code,
            'minutes' => $minutes
        ];
        $content = sprintf(config('sms_templates.REBIND_TEL_VERIFY_OLD'), $code, $minutes);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 设置密码
     */
    public static function sendCodeForSetPassword(string $tel, string $code) {
        $minutes = config('banma.sms.code_timeout') / 60;
        $data = [
            'code' => $code,
            'minutes' => $minutes
        ];
        $content = sprintf(config('sms_templates.SET_PASSWORD'), $code, $minutes);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 教师帐号创建
     */
    public static function sendTeacherAccount(string $tel, string $name, string $password) {
        $data = [];
        $content = sprintf(config('sms_templates.CREATE_TEACHER_ACCOUNT'), $name, $tel, $password);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 补课申请提交成功
     */
    public static function submitBuKeRequestSuccess(string $tel, string $studentName, string $originTime, string $expectTime) {
        $data = [];
        $content = sprintf(config('sms_templates.BUKE_SUBMIT_SUCCESS'), $studentName, $originTime, $expectTime);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 补课申请审核通过
     */
    public static function buKeRequestGranted(string $tel, string $studentName, string $originTime, string $expectTime) {
        $data = [];
        $content = sprintf(config('sms_templates.BUKE_REQUEST_GRANTED'), $studentName, $originTime, $expectTime);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 补课申请审核不通过
     */
    public static function buKeRequestReject(string $tel, string $studentName, string $originTime, string $expectTime, string $reason) {
        $data = [];
        $content = sprintf(config('sms_templates.BUKE_REQUEST_REJECTED'), $studentName, $originTime, $expectTime, $reason);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 调课申请提交成功
     */
    public static function submitSwitchRequestSuccess(string $tel, string $studentName, string $originTime, string $expectTime) {
        $data = [];
        $content = sprintf(config('sms_templates.SWITCH_SUBMIT_SUCCESS'), $studentName, $originTime, $expectTime);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 调课申请审核通过
     */
    public static function switchGranted(string $tel, string $studentName, string $originTime, string $expectTime) {
        $data = [];
        $content = sprintf(config('sms_templates.SWITCH_REQUEST_GRANTED'), $studentName, $originTime, $expectTime);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 调课申请审核不通过
     */
    public static function switchReject(string $tel, string $studentName, string $originTime, string $expectTime, string $reason) {
        $data = [];
        $content = sprintf(config('sms_templates.SWITCH_REQUEST_REJECTED'), $studentName, $originTime, $expectTime, $reason);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 即将上课提醒
     * @param $leftTime 剩余时长
     */
    public static function courseRemindForTeacher(string $tel, string $name, string $leftTime) {
        $data = [];
        $content = sprintf(config('sms_templates.COURSE_REMIND_FOR_TEACHER'), $name, $leftTime);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    public static function courseRemindForStudent(string $tel, string $studentName, string $leftTime, string $courseName, string $teacherName) {
        $data = [];
        $content = sprintf(config('sms_templates.COURSE_REMIND_FOR_STUDENT'), $studentName, $leftTime, $courseName, $teacherName);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 即将缺课提醒
     * @param $leftTime 分钟
     */
    public static function courseWillMissed(string $tel, string $studentName, string $time, string $leftTime) {
        $data = [];
        $content = sprintf(config('sms_templates.COURSE_WILL_MISSED'), $studentName, $time, $leftTime);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 已缺课通知
     */
    public static function courseDidMissed(string $tel, string $studentName, string $time) {
        $data = [];
        $content = sprintf(config('sms_templates.COURSE_DID_MISSED'), $studentName, $time);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 加入课程通知
     * @param $day 为周一、周二、周三、周四、周五、周六、周日
     * @param $repeatType 0为每周 1为隔周
     */
    public static function addedIntoCourse(string $tel, string $studentName, string $courseName, string $teacherName, string $day, string $time,  int $repeatType) {
        $data = [];
        $content = sprintf(config('sms_templates.ADD_INTO_COURSE'), $studentName, $courseName, $teacherName, $repeatType == 0 ? '每周' : '每隔一周的', $day, $time);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 移出课程通知
     */
    public static function removedFromCourse(string $tel, string $studentName, string $courseName) {
        $data = [];
        $content = sprintf(config('sms_templates.REMOVED_FROM_COURSE'), $studentName, $courseName);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 作业被点评
     * @param time 提交作业的时间
     */
    public static function homeworkEvaluated(string $tel, string $studentName, string $time, string $courseName) {
        $data = [];
        $content = sprintf(config('sms_templates.HOMEWORK_EVALUATED'), $studentName, $time, $courseName);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 布置作业通知
     */
    public static function homeworkAssigned(string $tel, string $studentName, string $courseName) {
        $data = [];
        $content = sprintf(config('sms_templates.HOMEWORK_ASSIGNED'), $studentName, $courseName);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }

    /**
     * 招行钢琴比赛报名
     * @param type 0为登录 1为注册
     */
    public static function sendCodeForCMBCompetition(string $tel, string $code) {
        $minutes = config('banma.sms.code_timeout') / 60;
        $data = [
            'code' => $code,
            'minutes' => $minutes
        ];
        $content = sprintf(config('sms_templates.CMB_COMPETITION'), $code, $minutes);
        $data = new Data($tel, $data, $content, []);
        BMSendSms::dispatch($data);
    }
}