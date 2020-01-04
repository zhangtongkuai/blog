<?php
namespace App\BM\live;

use App\BM\utils\Log;
use App\BM\utils\Time;
use App\BM\utils\Utils;
use Illuminate\Support\Facades\Cache;

define('TAG', 'live_tag');
define('ID', 'autocrement_id_key_name');
define('ROOM_KEY_PREFIX', 'room_');
define('ROOMS', 'rooms');
define('HEART_BEAT_TIMEOUT', 1);

class Room
{
    public static function createRoom(string $title, Member $member, array $otherRoomData){
        $now = Time::nowTimestamp();
        $member->status = Status::ONLINE;
        $member->enterTime = $now;
        $member->lastHeartBeatTime = $now;
        $member->cameraType = CameraType::HIDE;
        $member->audioStatus = AudioStatus::SILENCE;
        $member->role = Role::OWNER;

        $rooms = self::getRooms();
        foreach($rooms as $k => $room){
            $members = $room['members'];
            foreach($members as $mk => $mem){
                if($mem['id'] == $member->id){
                    $roomKey = self::getRoomKey($room['roomId']);
                    $rooms[$k]['members'][$mk] = $member;
                    self::updateRooms($rooms);
                    $roomInfo = Cache::tags([TAG])->get($roomKey);
                    if(isset($roomInfo) && isset($roomInfo['members'])){
                        $roomInfo = json_decode($roomInfo, true);
                        $roomInfo['owner'] = $member;
                        foreach($roomInfo['members'] as $mmk => $mem0){
                            if($mem0['id'] == $member->id){
                                $roomInfo['members'][$mmk] = $member;
                                break;
                            }
                        }
                        Cache::tags([TAG])->forever($roomKey, json_encode($roomInfo));
                        $ri = new RoomInfo();
                        foreach($roomInfo as $kk => $vv){
                            $ri->$kk = $vv;
                        }
                        return $ri;
                    }
                }
            }
        }

        $roomInfo = new RoomInfo();
        array_push($roomInfo->members, $member);
        $roomInfo->owner = $member;

        $roomId = Cache::increment(ID, 1);
        $roomInfo->roomId = $roomId;
        $roomInfo->createTime = $now;
        $roomInfo->uuid = Utils::uuid();
        $roomInfo->title = $title;
        $roomInfo->data = $otherRoomData;

        Cache::tags([TAG])->forever(self::getRoomKey($roomId), json_encode($roomInfo));

        $arr = [
            'roomId' => $roomId,
            'members' => [
                $member
            ]
        ];
        array_push($rooms, $arr);
        self::updateRooms($rooms);

        return $roomInfo;
    }

    public static function hasRoom(int $roomId){
        return Cache::tags([TAG])->has(self::getRoomKey($roomId));
    }

    /**
     * @param bool $bool 上报房间创建结果，如果为false，删除缓存中房间数据
     */
    public static function reportRoom(int $roomId, bool $bool){
        $roomInfo = Cache::tags([TAG])->get(ROOM_KEY_PREFIX . $roomId);
        if(isset($roomInfo)){
            if(!$bool){
                Cache::tags([TAG])->forget(self::getRoomKey($roomId));
            }
        }

        $rooms = self::getRooms();
        foreach($rooms as $k => $room){
            if($room['roomId'] == $roomId){
                array_splice($rooms, $k, 1);
                break;
            }
        }
    }

    /**
     * 获取房间列表
     * @param array $memberIds
     */
    public static function getRoomList(array $memberIds){
        $roomIds = [];
        foreach($memberIds as $memberId){
            $roomId = self::getRoomIdByMemberId($memberId);
            if($roomId){
                array_push($roomIds, $roomId);
            }
        }

        $rooms = [];
        if(count($roomIds)){
            foreach($roomIds as $roomId){
                $roomData = Cache::tags([TAG])->get(self::getRoomKey($roomId));
                if(isset($roomData)){
                    $roomData = json_decode($roomData, true);
                    // 判断心跳
                    $now = Time::nowTimestamp();
                    if(isset($roomData['members'])){
                        foreach($roomData['members'] as $mk => $member){
                            if(isset($member['lastHeartBeatTime']) && $now - $member['lastHeartBeatTime'] >= HEART_BEAT_TIMEOUT * 60){
                                unset($roomData['members'][$mk]);
                            }
                        }

                        if(count($roomData['members'])){
                            $roomInfo = new RoomInfo();
                            foreach($roomData as $key => $value){
                                $roomInfo->$key = $value;
                            }
                            array_push($rooms, $roomInfo);
                        }
                    }
                }
            }
        }
        return $rooms;
    }

    public static function getRoomMembers(int $roomId){
        $rooms = self::getRooms();
        $members = [];
        $now = Time::nowTimestamp();
        foreach($rooms as $k => $room){
            foreach($room['members'] as $mk => $member){
                if(isset($member['lastHeartBeatTime']) && $now - $member['lastHeartBeatTime'] >= HEART_BEAT_TIMEOUT * 60){
                    unset($rooms[$k]['members'][$mk]);
                }
            }

            if($room['roomId'] == $roomId){
                $members = $rooms[$k]['members'];
            }

            if(count($room['members']) == 0){
                unset($rooms[$k]);
            }
        }
        self::updateRooms($rooms);
        return $members;
    }

    /**
     * 成功返回true 否则false
     * @param int $roomId
     * @param Member $member
     * @return bool|mixed
     */
    public static function enterRoom(int $roomId, Member $member){
        if(self::isInRoom($roomId, $member->id)){
            return null;
        }
        $timestamp = Time::nowTimestamp();

        if($member->role == Role::OWNER){
            $member->status = Status::ONLINE;
            $member->enterTime = $timestamp;
            $member->lastHeartBeatTime = $timestamp;
            $member->cameraType = CameraType::FRONT;
            $member->audioStatus = AudioStatus::NORMAL;
            $member->role = Role::OWNER;
        }else{
            $member->enterTime = $timestamp;
            $member->status = Status::ONLINE;
            $member->lastHeartBeatTime = $timestamp;
        }

        // 更新rooms里的成员信息
        $rooms = self::getRooms();
        foreach($rooms as $rk => $room){
            if($room['roomId'] == $roomId){
                array_push($rooms[$rk]['members'], $member);
                if($member->role == Role::OWNER){
                    $rooms[$rk]['owner'] = $member;
                }
            }
        }
        self::updateRooms($rooms);

        // 更新对应room里的成员信息
        $roomKey = self::getRoomKey($roomId);
        $roomData = Cache::tags([TAG])->get($roomKey);
        if(isset($roomData)){
            $roomData = json_decode($roomData, true);
            array_push($roomData['members'], $member);
            if($member->role == Role::OWNER){
                $rooms[$rk]['owner'] = $member;
            }
        }
        Cache::tags([TAG])->forever($roomKey, json_encode($roomData));
        return $roomData;
    }

    public static function isInRoom(int $roomId, $memberId){
        $rooms = self::getRooms();
        foreach($rooms as $rk => $room){
            if($room['roomId'] == $roomId){
                $members = $room['member'];
                foreach($members as $member){
                    if($member['id'] == $memberId){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function getRoomByRoomId($roomId){
        $roomKey = self::getRoomKey($roomId);
        $roomData = Cache::tags([TAG])->get($roomKey);
        return $roomData;
    }

    public static function heartBeat(int $roomId, $memberId){
        self::updateMemberWithKeyValue($roomId, $memberId, 'lastHeartBeatTime', Time::nowTimestamp());
    }

    public static function closeRoom($roomId){
        $roomKey = self::getRoomKey($roomId);
        $roomData = Cache::tags([TAG])->get($roomKey);
        $rooms = self::getRooms();
        foreach($rooms as $rk => $room){
            if($room['roomId'] == $roomId){
                unset($rooms[$rk]);
                break;
            }
        }
        self::updateRooms($rooms);
        return $roomData;
    }

    public static function exitRoom(int $roomId, $memberId){
        // 更新rooms里的成员信息
        $rooms = self::getRooms();
        foreach($rooms as $rk => $room){
            if($room['roomId'] == $roomId){
                $members = $room['members'];
                $bool = false;
                foreach($members as $mk => $member){
                    if($member['id'] == $memberId){
                        array_splice($rooms[$rk]['members'], $mk , 1);
                        $bool = true;
                        break;
                    }
                }
                if($bool){
                    break;
                }
            }
        }
        self::updateRooms($rooms);

        // 更新对应room里的成员信息
        $roomKey = self::getRoomKey($roomId);
        $roomData = Cache::tags([TAG])->get($roomKey);
        if(isset($roomData)){
            $roomData = json_decode($roomData, true);
            $members = $room['members'];
            foreach($members as $k => $member){
                if($member['id'] == $memberId){
                    array_splice($roomData['members'], $k, 1);
//                    if($member['role'] == Role::OWNER){
//                        $roomData['owner'] = null;
//                    }
                    break;
                }
            }
        }
        Cache::tags([TAG])->forever($roomKey, json_encode($roomData));
        return Cache::tags([TAG])->get($roomKey);
    }

    public static function switchCamera(int $roomId, $memberId, $cameraType){
        self::updateMemberWithKeyValue($roomId, $memberId, 'cameraType', $cameraType);
    }

    public static function switchMic(int $roomId, $memberId, $audioStatus){
        self::updateMemberWithKeyValue($roomId, $memberId, 'audioStatus', $audioStatus);
    }

    public static function getRoomsByMemberId($memberId){
        $rooms = self::getRooms();
        $arr = [];
        foreach($rooms as $room){
            $members = $room['members'];
            foreach($members as $member){
                if($member['id'] == $memberId){
                    $arr[] = $room;
                }
            }
        }
        return $arr;
    }

    /**
     * 调试之用，没事别调
     */
    public static function flush(){
        Cache::tags([TAG])->flush();
    }

    private static function getRoomIdByMemberId($memberId){
        $rooms = self::getRooms();
        $ids = [];
        foreach($rooms as $room){
            $members = $room['members'];
            foreach($members as $member){
                if($member['id'] == $memberId){
                    $ids[] = $room['roomId'];
                }
            }
        }
        sort($ids);
        if(count($ids)){
            return $ids[count($ids) - 1];
        }
        return 0;
    }

    private static function updateMemberWithKeyValue($roomId, $memberId, $key, $value){
        // 更新rooms里的成员信息
        $rooms = self::getRooms();
        foreach($rooms as $rk => $room){
            if($room['roomId'] == $roomId){
                $members = $room['members'];
                $bool = false;
                foreach($members as $mk => $member){
                    if($member['id'] == $memberId){
                        $rooms[$rk]['members'][$mk][$key] = $value;
                        $bool = true;
                        break;
                    }
                }
                if($bool){
                    break;
                }
            }
        }
        self::updateRooms($rooms);

        // 更新对应room里的成员信息
        $roomKey = self::getRoomKey($roomId);
        $roomData = Cache::tags([TAG])->get($roomKey);
        if(isset($roomData)){
            $roomData = json_decode($roomData, true);
            $members = $roomData['members'];
            foreach($members as $mk => $member){
                if($member['id'] == $memberId){
                    $roomData['members'][$mk][$key] = $value;
                    if($member['role'] == Role::OWNER){
                        $roomData['owner'][$key] = $value;
                    }
                    break;
                }
            }
        }
        Cache::tags([TAG])->forever($roomKey, json_encode($roomData));
    }

    private static function getRoomKey(int $roomId){
        return ROOM_KEY_PREFIX . $roomId;
    }

    private static function getRooms(){
        $data = Cache::tags([TAG])->get(ROOMS);
        if(isset($data)){
            return json_decode($data, true);
        }
        return [];
    }

    private static function updateRooms(array $data){
        Cache::tags([TAG])->forever(ROOMS, json_encode($data));
    }
}