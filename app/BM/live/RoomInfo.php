<?php
namespace App\BM\live;

class RoomInfo
{
    // 房间ID
    public $roomId;

    // 成员列表 array
    public $members = [];

    // 创建时间 int
    public $createTime;

    // 结束时间 int
    public $closeTime;

    // 房间标题
    public $title;

    // 房间唯一标识 uuid
    public $uuid;

    // 其它信息
    public $data;

    // 房主
    public $owner;
}