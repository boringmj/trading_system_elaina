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
     * @access public
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
     * @access public
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
     * @access public
     * @param string $token 用户Token
     * @param int $id 房间ID
     * @return array
     */
    public function joinRoom(string $token,?int $id=null): array {
        $room_uuid='';
        // 如果房间ID为空,则视为加入公开房间(如果没有则创建),否则视为加入私有房间
        if($id===null) {
            // 先寻找是否还有公开的房间(准备中的房间)
            $room_info=$this->where('public',1)->where('status',0)->where('vacancy',1,'>=')->find();
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
            // 判断房间是否已满
            if($room_info['vacancy']<=0)
                throw new Exception('房间已满');
            // 判断房间是否已开始
            if($room_info['status']!=0)
                throw new Exception('房间已开始');
            $room_uuid=$room_info['rmid'];
        }
        // 加入房间
        $Player=new Players();
        return $Player->joinRoom($token,$room_uuid);
    }

    /**
     * 退出房间
     * 
     * @access public
     * @param string $token 用户Token
     * @param string $room_uuid 房间ID
     * @return array
     * @throws Exception
     */
    public function quitRoom(string $token,string $room_uuid): array {
        $Player=new Players();
        return $Player->leaveRoom($token,$room_uuid);
    }

    /**
     * 心跳检测
     * 
     * @access public
     * @param string $token 用户Token
     * @param string $room_uuid 房间ID
     * @return array
     * @throws Exception
     */
    public function heartbeat(string $token,string $room_uuid): array {
        $return_data=array();
        // 通过token获取用户信息
        $Token=new Token();
        $user_info=$Token->getTokenInfo($token);
        // 通过$room_uuid获取房间信息
        $room_info=$this->getRoomInfo($room_uuid);
        // 判断房间是否已经结束
        if($room_info['status']===3)
            throw new Exception('房间已结束');
        // 判断房间是否已经封存
        if($room_info['status']===4)
            throw new Exception('房间已封存');
        // 判断房间是否还在准备中
        if($room_info['status']===0) {
            // 判断房间玩家是否已经到齐
            if($room_info['vacancy']===0) {
                // 房间玩家已经到齐,则开始游戏
                $this->startGame($room_uuid);
                // 重新获取房间信息
                $room_info=$this->getRoomInfo($room_uuid);
            }
        }
        $is_in_room=false;
        foreach($room_info['players'] as $key=>$value) {
            $return_data['players']=array();
            $return_data['players'][$key]=array(
                'nickname'=>$value['nickname'],
                'serial'=>$value['serial'],
                'status'=>$value['status'],
                'cards_count'=>$value['cards_count'],
                'cards_played'=>$value['cards_played'],
            );
            // 公开房间需要匿名玩家的信息
            if($room_info['public']==1)
                $return_data['players'][$key]['nickname']='';
            // 如果是自己,则返回自己的信息
            if($value['uuid']==$user_info['uuid']) {
                $return_data['players'][$key]['nickname']=$value['nickname'];
                $return_data['players'][$key]['cards']=$value['cards'];
                $is_in_room=true;
            }
        }
        if(!$is_in_room)
            throw new Exception('用户不在房间中');
        // 刷新房间更新时间
        $this->where('rmid',$room_uuid)->update(array('update_time'=>time()));
        // 返回结果
        $return_data=array(
            'status'=>$room_info['status'],
            'timeout'=>$room_info['timeouts']-time()-1,
            'current_player'=>$room_info['current_player'],
            'previous_player'=>$room_info['previous_player']
        );
        return $return_data;
    }

    /**
     * 游戏开始
     * 
     * @access private
     * @param string $room_uuid 房间ID
     * @return void
     * @throws Exception
     */
    private function startGame(string $room_uuid): void {
        // 变更房间状态为游戏中
        $this->where('rmid',$room_uuid)->update(array('status'=>1));
        // 重置玩家序号,重新分配序号(座位号)
        $Player=new Players();
        $players=$Player->where('room_uuid',$room_uuid)->select();
        $serial=1;
        foreach($players as $value) {
            $Player->where('uuid',$value['uuid'])->where('rmid',$room_uuid)->update(array('serial'=>$serial));
            $serial++;
        }
        // 重置玩家状态
        $Player->where('room_uuid',$room_uuid)->update(array('status'=>0));
        // 重置玩家上一次出牌信息
        $Player->where('room_uuid',$room_uuid)->update(array('cards_played'=>''));
        // 发牌
        $this->dealCards($room_uuid);
    }

    /**
     * 发牌
     * 
     * @access private
     * @param string $room_uuid 房间ID
     * @return void
     */
    private function dealCards(string $room_uuid): void {
        // 在这里实现发牌
        $char=str_split("ABCDEFGHIJKLM");
        $pokerlist=array();
        for ($i=0;$i<4;$i++) { 
            for ($j=0;$j<13;$j++) { 
                array_push($pokerlist,$char[$i].$char[$j]);
            }
        }
        //打乱顺序
        shuffle($pokerlist);
        //开始发牌
        $pokergroup=array(
            array("poker"=>"","group"=>0,"firstplay"=>false),
            array("poker"=>"","group"=>0,"firstplay"=>false),
            array("poker"=>"","group"=>0,"firstplay"=>false),
            array("poker"=>"","group"=>0,"firstplay"=>false)
        );
        for ($i=0;$i<13;$i++) { 
            for ($j=0;$j<4;$j++) { 
                $poker=array_pop($pokerlist);
                if($poker==="AA"){//方块四
                    $pokergroup[$j]["firstplay"]=true;
                }elseif($poker==="DM"||$poker==="DK"){
                    $pokergroup[$j]["group"]=1;
                }
                $pokergroup[$j]["poker"].=$poker;
            }
        }
        $Player=new Players();
        $players=$Player->where('rmid',$room_uuid)->select();
        $n=0;
        foreach($players as $value) {
            // 这里是玩家的手牌
            $card=$pokergroup[$n]["poker"];
            $card_count=strlen($card)/2;
            $group=$pokergroup[$n]["group"];
            // 这里是保存玩家手牌的代码
            $Player->where('rmid',$room_uuid)->where('uuid',$value['uuid'])->update(array(
                'cards'=>$card,
                'group'=>$group,
                'cards_count'=>$card_count,
                'update_time'=>time()
            ));
            // 如果该玩家持有方块4,则将其设置为当前玩家
            if($pokergroup[$n]["firstplayer"])
                $this->where('rmid',$room_uuid)->update(array(
                    'current_player'=>$value['serial'],
                    'previous_player'=>$value['serial'],
                ));
            $n++;
        }
    }

}