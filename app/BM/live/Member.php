<?php

namespace App\BM\live;

class Member
{
    // 成员ID
    public $id;

    // 成员角色 Role
    public $role;

    // 状态 Status
    public $status;

    // 进入时间 int
    public $enterTime;

    // 离开时间 int
    public $exitTime;

    // 最后一次心跳时间 int
    public $lastHeartBeatTime;

    // 摄像头类型
    public $cameraType;

    // 是否禁音
    public $audioStatus;

    // 其它信息
    public $data;
}