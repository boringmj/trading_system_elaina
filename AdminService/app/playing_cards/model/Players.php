<?php

namespace app\playing_cards\model;

use base\Model;
use AdminService\Exception;
use AdminService\model\Token;
use app\playing_cards\model\Room;

class Players extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='trading_system_elaina_playing_cards_players';

    /**
     * 玩家加入房间
     * 
     * @param string $token 用户Token
     * @param string $room_uuid 房间UUID
     * @return array
     */
    public function joinRoom(string $token,string $room_uuid): array {
        // 验证用户Token
        $Token=new Token();
        $token_info=$Token->getTokenInfo($token);
        // 获取玩家uuid
        $uuid=$token_info['uuid'];
        // 获取房间信息
        $Room=new Room();
        $room_info=$Room->getRoomInfo($room_uuid);
        // 判断玩家是否已经在房间中
        $player_info=$this->where('uuid',$uuid)->where('rmid',$room_uuid)->find();
        if(!empty($player_info))
            throw new Exception('玩家已经在房间中');
        // 判断房间是否已经满员
        if(count($room_info['players'])>=$room_info['max_player'])
            throw new Exception('房间已经满员');
        // 加入房间
        $this->insert(array(
            'uuid'=>$uuid,
            'rmid'=>$room_uuid,
            'serial'=>count($room_info['players'])+1,
            'create_time'=>time()
        ));
        // 重新获取房间信息
        $room_info=$Room->getRoomInfo($room_uuid);
        return $room_info;
    }

    /**
     * 通过房间ID获取玩家列表
     * 
     * @param string $room_uuid 房间ID
     * @return array
     * @throws Exception
     */
    public function getPlayersListByRoomUUID(string $room_uuid): array {
        $players_list=$this->where('rmid',$room_uuid)->select();
        return $players_list;
    }

    /**
     * 通过玩家Token获取玩家加入的房间列表
     * 
     * @param string $token 玩家Token
     * @return array
     * @throws Exception
     */
    public function getPlayersRoomListByToken(string $token): array {
        // 验证用户Token
        $Token=new Token();
        $token_info=$Token->getTokenInfo($token);
        // 获取玩家uuid
        $uuid=$token_info['uuid'];
        // 获取玩家加入的房间列表
        $players_room_list=$this->where('uuid',$uuid)->select();
        return $players_room_list;
    }

}