<?php

namespace app\playing_cards\model;

use base\Model;
use AdminService\Exception;
use AdminService\model\Token;
use AdminService\model\User;
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
            'update_time'=>time()
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
        // 判断房间是否已经封存
        if($room_info['status']===3)
            throw new Exception('房间已封存');
        // 判断房间是否已经流局
        if($room_info['status']===4)
            throw new Exception('房间已流局');
        // 先更新房间信息
        $this->roomUpdateEvent($room_info);
        // 重新获取房间信息
        $room_info=$this->getRoomInfo($room_uuid);
        $is_in_room=false;
        $return_data['players']=array();
        foreach($room_info['players'] as $key=>$value) {
            // 通过玩家uuid获取玩家信息
            $User=new User();
            $user_info_temp=$User->getUserInfoByUUID($value['uuid']);
            $value['nickname']=$user_info_temp['nickname'];
            $return_data['players'][$key]=array(
                'nickname'=>$value['nickname'],
                'serial'=>$value['serial'],
                'status'=>$value['status'],
                'cards_count'=>$value['cards_count'],
                'cards_played'=>($value['cards_played']==='pass'?['pass']:str_split($value['cards_played']??'',2)),
            );
            // 公开房间需要匿名玩家的信息
            if($room_info['public']===1&&$room_info['status']===0) {
                $return_data['players'][$key]['nickname']='';
            // 如果是自己,则返回自己的信息
            if($value['uuid']===$user_info['uuid']) {
                $return_data['players'][$key]['nickname']=$value['nickname'];
                $return_data['players'][$key]['cards']=($value['cards']==='pass'?['pass']:str_split($value['cards']??'',2));
                // 同时需要记录自己的座位号
                $return_data['serial']=$value['serial'];
                $is_in_room=true;
            }
            // 如果进入结算阶段,则需要返回其他玩家的手牌
            if($room_info['status']===2) {
                // 手牌
                $return_data['players'][$key]['cards']=($value['cards']==='pass'?['pass']:str_split($value['cards']??'',2));
                // 分组
                $return_data['players'][$key]['group']=$value['group'];
            }
        }
        if(!$is_in_room)
            throw new Exception('用户不在房间中');
        // 刷新房间更新时间
        $this->where('rmid',$room_uuid)->where('status','1','<=')->update(array(
            'update_time'=>time()
        ));
        // 刷新玩家更新时间
        $Player=new Players();
        $Player->where('uuid',$user_info['uuid'])->where('status','1','<=')->where('rmid',$room_uuid)->update(array(
            'update_time'=>time(),
            'status'=>0,
        ));
        // 返回结果
        $ranking=empty($room_info['ranking'])?'':$room_info['ranking'];
        $ranking=str_split($ranking);
        $return_data_temp=array(
            'status'=>$room_info['status'],
            'timeout'=>$room_info['timeouts']-time()-1,
            'current_player'=>$room_info['current_player'],
            'previous_player'=>$room_info['previous_player'],
            'ranking'=>$ranking
        );
        // 如果timeout小于0,则显示为0
        if($return_data_temp['timeout']<0)
            $return_data_temp['timeout']=0;
        // 合并数组
        $return_data=array_merge($return_data,$return_data_temp);
        return $return_data;
    }

    /**
     * 房间更新事件
     * 
     * @access private
     * @param array $room_info 房间信息
     * @return void
     * @throws Exception
     */
    private function roomUpdateEvent(array $room_info): void {
        // 判断房间状态
        switch($room_info['status']) {
            case 0:
                // 准备中
                // 将30秒内无心跳的玩家踢出房间
                $Player=new Players();
                $timeout_list=$Player->where('rmid',$room_info['rmid'])->where('update_time',time()-30,'<')->select();
                // 删除对应的玩家并恢复房间空位
                foreach($timeout_list as $value) {
                   /*  $Player->where('uuid',$value['uuid'])->where('rmid',$room_info['rmid'])->delete();
                    $vacancy=$room_info['vacancy']+1;
                    $this->where('rmid',$room_info['rmid'])->update(array('vacancy'=>$vacancy)); */
                }
                // 判断房间玩家是否已经到齐,房间玩家到齐,则开始游戏
                if($room_info['vacancy']===0)
                    $this->startGame($room_info['rmid']);
                break;
            case 1:
                // 游戏中
                // 设置心跳超时的游戏中的玩家为离线状态
                $Player=new Players();
                $Player->where('rmid',$room_info['rmid'])->where('status',0)->where('update_time',time()-60,'<')->update(array('status'=>1));
                // 判断当前出牌的玩家是否已经超时(离线状态,已经完成的玩家和没有手牌的玩家默认为超时)
                $current_player_info=$Player->where('rmid',$room_info['rmid'])->where('serial',$room_info['current_player'])->find();
                if(empty($current_player_info))
                    throw new Exception('当前出牌玩家不存在');
                // 如果当前出牌玩家已经完成且上轮出牌玩家还是当前出牌玩家,则该玩家的下一位玩家设置为当前出牌玩家且将上轮出牌玩家设置为下个玩家
                if(($current_player_info['cards_count']===0||$current_player_info['status']===2)&&$room_info['previous_player']===$current_player_info['serial']) {
                    $next=($current_player_info['serial']<$room_info['max_player'])?($current_player_info['serial']+1):1;
                    $this->where('rmid',$room_info['rmid'])->update(array(
                        'current_player'=>$next,
                        'previous_player'=>$next,
                        'timeouts'=>time()+61,
                        'update_time'=>time()
                    ));
                }
                if($current_player_info['status']!==0||$room_info['timeouts']<time()||$current_player_info['cards_count']===0) {
                    // 判断是否允许pass
                    $cards='';
                    if($room_info['previous_player']===$current_player_info['serial']&&$current_player_info['cards_count']>0) {
                        // 判断手牌中是否有方块4(AA),如果有则打出,否则打出手牌最开始的一张
                        $cards_array=str_split($current_player_info['cards'],2);
                        if(in_array('AA',$cards_array)) {
                            $cards='AA';
                            $cards_array=array_diff($cards_array,['AA']);
                        } else {
                            $cards=$cards_array[0];
                            $cards_array=array_diff($cards_array,[$cards]);
                        }
                        // 将数组转换为字符串
                        $current_player_info['cards']=implode('',$cards_array);
                    } else
                        $cards='pass';
                    $surplus_cards=array(
                        'cards'=>$current_player_info['cards'],
                        'cards_count'=>strlen($current_player_info['cards'])/2
                    );
                    $Player->playCardsEvent($this,$room_info,$current_player_info,$room_info['rmid'],$current_player_info['uuid'],$surplus_cards,$cards,false);
                }
                break;
            case 2:
                // 结算中
                break;
        }
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
        $players=$Player->where('rmid',$room_uuid)->select();
        $serial=1;
        foreach($players as $value) {
            $Player->where('uuid',$value['uuid'])->where('rmid',$room_uuid)->update(array('serial'=>$serial));
            $serial++;
        }
        // 重置玩家状态
        $Player->where('rmid',$room_uuid)->update(array('status'=>0));
        // 重置玩家上一次出牌信息
        $Player->where('rmid',$room_uuid)->update(array('cards_played'=>''));
        // 发牌
        $this->dealCards($room_uuid);
        // 重置当前房间出牌超时时间
        $this->where('rmid',$room_uuid)->update(array('timeouts'=>time()+61));
    }

    /**
     * 发牌
     * 
     * @access private
     * @
     * @param string $room_uuid 房间ID
     * @return void
     */
    private function dealCards(string $room_uuid): void {
        // 在这里实现发牌
        $char=str_split("ABCDEFGHIJKLM");
        $pokerlist=array();
        for($i=0;$i<4;$i++) { 
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
        for($i=0;$i<13;$i++) { 
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
            if($pokergroup[$n]["firstplay"])
                $this->where('rmid',$room_uuid)->update(array(
                    'current_player'=>$value['serial'],
                    'previous_player'=>$value['serial'],
                ));
            $n++;
        }
    }

    /**
     * 监控
     * 
     * @access public
     * @return void
     * @throws Exception
     */
    public function monitor() {
        // 将状态为游戏中的房间设置为流局
        $this->where('status',1)->where('update_time',time()-600,'<')->update(array('status'=>4));
        // 将状态为结算中的房间设置为封存
        $this->where('status',2)->where('update_time',time()-600,'<')->update(array('status'=>3));
    }

}