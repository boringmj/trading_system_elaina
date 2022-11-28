<?php

namespace app\playing_cards\model;

use base\Model;
use AdminService\Exception;
use AdminService\model\Token;
use app\playing_cards\model\Players;

class Room extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='trading_system_elaina_playing_cards_room';

    /**
     * 创建房间
     * 
     * @param bool $is_public 是否公开
     * @return string
     * @throws Exception
     */
    public function createRoom(bool $is_public=true): string {
        // 创建房间
        $room_uuid=\AdminService\common\uuid(true);
        $room_info=array(
            'rmid'=>$room_uuid,
            'public'=>(int)$is_public,
            'create_time'=>time(),
        );
        $this->insert($room_info);
        return $room_uuid;
    }

    /**
     * 获取房间信息
     * 
     * @param string $room_uuid 房间ID
     * @return array
     * @throws Exception
     */
    public function getRoomInfo(string $room_uuid): array {
        $room_info=array();
        $room_info=$this->where('rmid',$room_uuid)->find();
        if(empty($room_info))
            throw new Exception('房间不存在');
        $Player=new Players();
        $room_info['players']=$Player->getPlayersListByRoomUUID($room_uuid);
        return $room_info;
    }

    /**
     * 加入房间
     * 
     * @param string $token 用户Token
     * @param int $id 房间ID
     * @return array
     */
    public function joinRoom(string $token,?int $id=null): array {
        $room_uuid='';
        // 如果房间ID为空,则视为加入公开房间(如果没有则创建),否则视为加入私有房间
        if($id===null) {
            // 先寻找是否还有公开的房间
            $room_info=$this->where('public',1)->where('vacancy',1,'>=')->find();
            if(empty($room_info)) {
                // 没有公开的房间,则创建一个公开的房间
                $room_uuid=$this->createRoom(true);
            } else {
                // 有公开的房间,则加入该房间
                $room_uuid=$room_info['rmid'];
            }
        }else{
            // 寻找id对应的room_uuid
            $room_info=$this->where('id',$id)->where('public',0)->find();
            if(empty($room_info))
                throw new Exception('私有房间不存在');
            $room_uuid=$room_info['rmid'];
        }
        // 加入房间
        $Player=new Players();
        return $Player->joinRoom($token,$room_uuid);
    }

}