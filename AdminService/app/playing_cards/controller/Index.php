<?php

namespace app\playing_cards\controller;

use base\Controller;
use AdminService\Request;
use AdminService\Exception;
use app\playing_cards\model\Room;
use app\playing_cards\model\Players;

use function AdminService\common\json;

class Index extends Controller {

    public function create_room() {
        // 获取参数
        $token=Request::getPost('token','');
        try {
            // 创建房间
            $Room=new Room();
            $room_uuid=$Room->createRoom(false);
            // 加入房间
            $Players=new Players();
            $room_info=$Players->joinRoom($token,$room_uuid);
            // 返回结果
            return json(1,'创建房间成功',array(
                'rmid'=>$room_uuid,
                'id'=>$room_info['id']
            ));
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    public function join_room() {
        // 获取参数
        $token=Request::getPost('token','');
        $room_id=Request::getPost('room_id');
        try {
            // 加入房间
            $Room=new Room();
            $room_info=$Room->joinRoom($token,$room_id);
            return json(1,'加入房间成功',array(
                'rmid'=>$room_info['rmid']
            ));
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    public function leave_room() {
        // 获取参数
        $token=Request::getPost('token','');
        $rmid=Request::getPost('rmid','');
        try {
            // 离开房间
            $Room=new Players();
            $Room->leaveRoom($token,$rmid);
            return json(1,'已离开房间');
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    public function get_info() {
        // 获取参数
        $token=Request::getPost('token','');
        try {
            // 获取信息
            $Players=new Players();
            return json(1,'获取信息成功',$Players->getPlayersRoomListByToken($token));
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    public function heartbeat() {
        // 获取参数
        $token=Request::getPost('token','');
        $rmid=Request::getPost('rmid','');
        try {
            // 获取信息
            $Room=new Room();
            return json(1,'获取成功',$Room->heartbeat($token,$rmid));
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    public function play_cards() {
        // 获取参数
        $token=Request::getPost('token','');
        $rmid=Request::getPost('rmid','');
        $cards=Request::getPost('cards','');
        try {
            // 获取信息
            $Players=new Players();
            return json(1,'已出牌',$Players->playCards($token,$rmid,$cards));
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    // 监控接口
    public function monitor() {
        try {
            // 获取信息
            $Room=new Room();
            $Room->monitor();
            // 把商城的监控也挂上
            $Mall=new \app\mall\model\Mall();
            $Mall->monitor();
            return json(1,'ok');
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    } 

}

?>