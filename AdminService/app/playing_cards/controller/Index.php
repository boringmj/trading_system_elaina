<?php

namespace app\playing_cards\controller;

use base\Controller;
use app\playing_cards\model\Room;
use app\playing_cards\model\Players;

use function AdminService\common\json;

class Index extends Controller {

    public function create_room() {
        // 获取参数
        $token=$this->param('token','');
        try {
            // 创建房间
            $Room=new Room();
            $room_uuid=$Room->createRoom(false);
            // 加入房间
            $Players=new Players();
            $room_info=$Players->joinRoom($token,$room_uuid);
            // 返回结果
            return json(1,'创建房间成功',$room_info);
        } catch (\Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    public function join_room() {
        // 获取参数
        $token=$this->param('token','');
        $room_id=$this->param('room_id');
        try {
            // 加入房间
            $Room=new Room();
            return json(1,'加入房间成功',$Room->joinRoom($token,$room_id));
        } catch (\Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

}

?>