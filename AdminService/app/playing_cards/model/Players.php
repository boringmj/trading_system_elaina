<?php

namespace app\playing_cards\model;

use base\Model;
use AdminService\App;
use AdminService\Exception;
use AdminService\model\Token;
use app\playing_cards\model\Room;

class Players extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='ssd_playing_cards_players';

    /**
     * 玩家加入房间
     * 
     * @access public
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
            return $room_info;
        // 判断房间是否已经满员
        if(count($room_info['players'])>=$room_info['max_player'])
            throw new Exception('房间已经满员');
        // 加入房间
        $this->insert(array(
            'uuid'=>$uuid,
            'rmid'=>$room_uuid,
            'serial'=>count($room_info['players'])+1,
            'create_time'=>time(),
            'update_time'=>time()
        ));
        // 减少房间剩余座位
        $Room->where('rmid',$room_uuid)->update(array(
            'vacancy'=>$room_info['vacancy']-1
        ));
        // 重新获取房间信息
        $room_info=$Room->getRoomInfo($room_uuid);
        return $room_info;
    }

    /**
     * 玩家离开房间
     * 
     * @access public
     * @param string $token 用户Token
     * @param string $room_uuid 房间UUID
     * @return array
     * @throws Exception
     */
    public function leaveRoom(string $token,string $room_uuid): array {
        // 验证用户Token
        $Token=new Token();
        $token_info=$Token->getTokenInfo($token);
        // 获取玩家uuid
        $uuid=$token_info['uuid'];
        // 判断玩家是否已经在房间中
        $player_info=$this->where('uuid',$uuid)->where('rmid',$room_uuid)->find();
        if(empty($player_info))
            throw new Exception('玩家不在房间中');
        // 获取房间信息
        $Room=new Room();
        $room_info=$Room->getRoomInfo($room_uuid);
        // 判断房间是否已经开始
        if($room_info['status']===1)
            throw new Exception('房间已经开始');
        if($room_info['status']>=2)
            return $room_info;
        // 离开房间
        $this->where('uuid',$uuid)->where('rmid',$room_uuid)->delete();
        // 增加房间剩余座位
        $Room->where('rmid',$room_uuid)->update(array(
            'vacancy'=>$room_info['vacancy']+1
        ));
        // 重新获取房间信息
        $room_info=$Room->getRoomInfo($room_uuid);
        return $room_info;
    }

    /**
     * 通过房间ID获取玩家列表
     * 
     * @access public
     * @param string $room_uuid 房间ID
     * @return array
     * @throws Exception
     */
    public function getPlayersListByRoomUUID(string $room_uuid): array {
        $players_list=$this->where('rmid',$room_uuid)->select();
        return $players_list;
    }

    /**
     * 通过玩家Token获取玩家加入的房间列表(准备中和游戏中的)
     * 
     * @access public
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
        $players_room_list=$this->where('uuid',$uuid)->where('status',1,'<=')->select(array('rmid','serial','status'));
        return $players_room_list;
    }

    /**
     * 出牌
     * 
     * @access public
     * @param string $token 玩家Token
     * @param string $room_uuid 房间UUID
     * @param string $cards 出牌列表
     * @return bool
     * @throws Exception
     */
    public function playCards(string $token,string $room_uuid,string $cards): bool {
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
        if(empty($player_info))
            throw new Exception('玩家不在房间中');
        // 判断房间是否已经开始
        if($room_info['status']!=1)
            throw new Exception('房间状态异常');
        // 判断玩家是否轮到出牌
        if($room_info['current_player']!==$player_info['serial'])
            throw new Exception('还未轮到您出牌');
        // 判断玩家出牌是否合法
        $surplus_cards=$this->checkPlayCards(
            $player_info['cards'],$cards,
            ($room_info['previous_player']===$player_info['serial']?null:$room_info['previous_cards'])
        );
        $this->playCardsEvent($Room,$room_info,$player_info,$room_uuid,$uuid,$surplus_cards,$cards);
        return true;
    }

    /**
     * 出牌触发事件
     * 
     * @access public
     * @param Room $Room 房间模型
     * @param array $room_info 房间信息
     * @param array $player_info 玩家信息
     * @param string $room_uuid 房间UUID
     * @param string $uuid 玩家UUID
     * @param array $surplus_cards 剩余牌信息
     * @param string $cards 出牌
     * @param bool $is_pass 是否更新玩家状态
     * @return void
     * @throws Exception
     */
    public function playCardsEvent(Room $Room,array $room_info,array $player_info,string $room_uuid,string $uuid,array $surplus_cards,string $cards,bool $is_pass=true) {
        // 记录玩家出牌
        $data=array(
            'cards'=>$surplus_cards['cards'],
            'cards_played'=>$cards,
            'cards_count'=>$surplus_cards['cards_count']
        );
        if($is_pass)
            $data['update_time']=time();
        $this->where('uuid',$uuid)->where('rmid',$room_uuid)->update($data);
        // 记录房间出牌信息(如果玩家出的牌不是pass则记录)
        if($cards!=='pass')
            $Room->where('rmid',$room_uuid)->update(array(
                'previous_player'=>$player_info['serial'],
                'previous_cards'=>$cards
            ));
        // 更新到下一个玩家
        $Room->where('rmid',$room_uuid)->update(array(
            'current_player'=>($player_info['serial']<$room_info['max_player']?$player_info['serial']+1:1),
            'timeouts'=>time()+61,
            'update_time'=>time()
        ));
        // 判断玩家是否已经出完牌
        if($surplus_cards['cards_count']<=0&&$cards!=='pass') {
            // 当前玩家出完牌的事件
            // 记录排名
            $ranking=empty($room_info['ranking'])?$player_info['serial']:($room_info['ranking'].$player_info['serial']);
            $Room->where('rmid',$room_uuid)->update(array(
                'ranking'=>$ranking,
                'update_time'=>time()
            ));
            // 更改当前玩家状态为2(已完成游戏),前提是当前玩家在线
            $this->where('uuid',$uuid)->where('rmid',$room_uuid)->where('status',0)->update(array(
                'status'=>2,
                'update_time'=>time()
            ));
            $ranking=str_split($ranking);
            // 判断牌局是否结束
            if($this->isOver($room_uuid,$ranking)) {
                // 变更房间状态为2(结算中)
                $Room->where('rmid',$room_uuid)->where('status','1')->update(array(
                    'status'=>2,
                    'update_time'=>time()
                ));
                // 更改所有玩家状态为2(已完成游戏),前提是当前玩家在线
                $this->where('rmid',$room_uuid)->where('status',0)->update(array(
                    'status'=>2,
                    'update_time'=>time()
                ));
            }
        }
    }

   /**
     * 判断牌局是否结束
     * 
     * @access private
     * @param string $room_uuid 房间UUID
     * @param array $ranking 排名
     * @return bool
     * @throws Exception
     */
    private function isOver(string $room_uuid,array $ranking): bool {
        // 获取玩家信息
        $player_info=$this->where('rmid',$room_uuid)->where('group',1)->select(array('serial','group'));
        $spade=0;
        $count=count($ranking);
        foreach ($player_info as $value) {
            for($i=0;$i<$count;$i++) {
                if($value["serial"]===(int)$ranking[$i]) {
                    $spade+=1;
                    break;
                }
            }
        }
        if(count($player_info) == $spade ) return true;
        elseif(($count -$spade ) ==2 ) return true;
        return false;
    }

    /**
     * 解析牌型
     * 
     * @access private
     * @param array $poker 牌列表
     * @return array
     * @throws Exception
     */
    private function getPokerInfo(array $poker) {
        $poker_len = count($poker);
        if($poker_len != 5){//小于4,判断牌数字是否相同
            $max_flower="A";
            for($i=0;$i<$poker_len;++$i){
                if($poker[0][1] != $poker[$i][1]){
                    return array("type" => 0);
                }
                $max_flower=max($poker[$i][0],$max_flower);
            }
            return array("type" => $poker_len,"number"=>$poker[0][1],"flower"=>$max_flower);
        }else{
            $number=array();
            $flower=array();
            for ($i=0; $i<$poker_len; $i++) { 
                array_push($flower,$poker[$i][0]);
                array_push($number,$poker[$i][1]);
            }
            arsort($number);
            arsort($flower);
            //开始判断顺子
            //"AJAIAHAGAFAEADACAB"
            $sz_str="JIHGFEDCBA";//顺子的顺序456789 10 J Q K
            $number_str=implode("",$number);//组合成字符串,待会直接比较
            $start_pos=strpos($sz_str,$number_str);
            $sz=true;
            if($start_pos === false){//最大的牌的牌不在这个队列,不是顺子
                App::get('Log')->write(
                    'Error({error_code}): {message} | data: {data}',
                    array(
                        'message'=>"顺子",
                        'error_code'=>-1,
                        'data'=>$number_str.$sz_str
                    )
                );
                $sz=false;
            }else{
                if($number_str != substr($sz_str,$start_pos,5)){
                    $sz=false;//顺子中截取五个字符串,如果不相等就不是顺子
                }
            }
            //开始判断同花
            $th=true;
            for($i=0;$i<5;++$i){
                if($flower[0] != $flower[$i]){
                    $th=false;
                    break;
                }
            }
            //开始判断三带二
            $max_number=0;
            $sde=false;
            if(($number[0]==$number[2]) && ($number[3]==$number[4]) ){
                $max_number=$number[2];
                //按顺序排序,如果第一张牌数字和第三张牌数字相等,且第四第五张牌相等,那么就是三带二,最大的数字为第一张牌的数字
                $sde=true;
            }elseif(($number[4]==$number[2] && ($number[1] != $number[4]))){
                $max_number=$number[2];
                //按顺序排序,如果第三张牌数字和第五张牌数字相等,且第一第二张牌相等,那么就是三带二,最大的数字为第五张牌的数字
                $sde=true;
            }
            //开始判断四带一
            $sdy=false;
            if($number[0]==$number[3] ){
                $max_number=$number[0];
                //14相等,最大为1
                $sdy=true;
            }elseif($number[4]==$number[1] ){
                $max_number=$number[4];
                //25相等,最大为5
                $sdy=true;
            }
            if($th && $sz){//同花且顺子,为同花顺分值55
            return array("type"=>55,"number"=>$number[4],"flower"=>$flower[3] );
            }elseif($sdy){//四带一,分值54
                return array("type"=>54,"number"=>$max_number,"flower"=>$flower[0] );//花色没用
            }elseif($sde){//三带二,分值53
                return array("type"=>53,"number"=>$max_number,"flower"=>$flower[0] );//花色没用
            }elseif($th){//同花,数字最大为数字排序后第一个数字,花色最大为花色排序后第一个数字
            return array("type"=>52,"number"=>$number[0],"flower"=>$flower[0] );
            }elseif($sz){//顺子,数字最大为数字排序后第一个数字,花色最大为最大数字的花色,由于排序后失去对应关系,需要再遍历一遍寻找
                $hs=1;
                for ($i=0; $i<4; $i++) { 
                    if($poker[$i][1] === $number[0]){
                        $hs=$poker[$i][0] ;
                        break;
                    }
                }
                return array("type"=>51,"number"=>$number[0],"flower"=>$hs );
            }
            else{
                return array("type"=>0);
            }
        }
    }

    /**
     * 计算剩余手牌
     * 
     * @access private
     * @param array $own_poker 当前手牌
     * @param array $play_poker 打出的牌
     * @return array
     */
    private function getLeftOverPoker(array $own_poker,array $play_poker) {
        $new_poker=array_diff($own_poker,$play_poker);
        $surplus_cards=implode($new_poker);
        $surplus_cards_count=strlen($surplus_cards)/2;
        // 返回玩家剩余手牌
        return array(
            'cards'=>$surplus_cards,
            'cards_count'=>$surplus_cards_count
        );
    }
    
    /**
     * 判断出牌是否合法
     * 
     * @access public
     * @param string $cards 玩家手牌
     * @param string $play_cards 本次出牌
     * @param string $previous_cards 上家出牌
     * @return array
     * @throws Exception
     */
    public function checkPlayCards(string $cards,string $play_cards,?string $previous_cards=null): array {
        // 判断是否为pass
        if($play_cards==='pass') {
            if($previous_cards===null)
                throw new Exception('您必须出牌');
            $cards_count=strlen($cards)/2;
            return array(
                'cards'=>$cards,
                'cards_count'=>$cards_count
            );
        }
        // 判断牌是否可以解析
        if(strlen($play_cards)%2!=0||strlen($cards)==0)
            throw new Exception('无法解析牌');
        $previous_poker=str_split($previous_cards??'00',2);
        $play_poker=str_split($play_cards,2);
        $own_poker=str_split($cards,2);
        // 判断玩家手牌是否包含出牌
        if(count($play_poker) != count(array_unique($play_poker)))
            throw new Exception('严禁出现重复牌');
        foreach ($play_poker as $value)
            if(!in_array($value,$own_poker))
                throw new Exception('牌中出现玩家未拥有的牌');
        // 判断出牌是否合法
        $previous_info=$this->getPokerInfo($previous_poker);
        $current_info=$this->getPokerInfo($play_poker);
        //类型为0视为不合法,不符合规则
        if($current_info['type']===0)
            throw new Exception('出的牌不符合规则');
        //上一家为自己,且手牌符合规则可以直接出
        if($previous_cards===null) {
            if(in_array("AA",$own_poker) && !in_array("AA",$play_poker))
            throw new Exception('第一次出的牌必须含有方块四');
            // 返回玩家剩余手牌
            return $this->getLeftOverPoker($own_poker,$play_poker);
        }
        //类型不等且小于5,不符合出牌规则
        if($current_info['type']!=$previous_info['type']&&($previous_info['type']<5 || $current_info['type'] < 5 ))
            throw new Exception('和上家的牌型不一致');
        // 定义一个flags
        switch($current_info['type']) {
            case 1:
            case 2:
                // 一二张,先比数字,再比花色
                if($current_info['number'] === $previous_info['number']) {
                    if($current_info['flower']<$previous_info['flower'])
                        throw new Exception("花色比上家小");
                } elseif($current_info['number']<$previous_info['number'])
                    throw new Exception("数字比上家小");
                break;
            case 3:
            case 4:
                // 三四张,直接比数字
                if($current_info['number']<$previous_info['number'])
                    throw new Exception("数字比上家小");
                break;     
            default:
                // 五张
                if($current_info['type']>$previous_info['type']) {
                    // 大牌可以直接出
                    break;
                }
                if($current_info['type']<$previous_info['type']){
                    // 小于就出不了了
                    throw new Exception("类型比上家小");
                }
                //相等分特殊情况
                switch($current_info['type']) {
                    case 51:
                        if($current_info['flower']<$previous_info['flower'])
                            throw new Exception("花色比上家小");
                        break;
                    case 52:
                    case 53:
                    case 54:
                        if($current_info['number']<$previous_info['number'])
                            throw new Exception("数字比上家小");
                        break;
                    case 55:
                        if($current_info['flower']<$previous_info['flower'])
                            throw new Exception("花色比上家小");
                        elseif($current_info['flower']===$previous_info['flower'])
                            if($current_info['number']<$previous_info['number'])
                                throw new Exception("数字比上家小");
                }                    
        }
        // 返回玩家剩余手牌
        return $this->getLeftOverPoker($own_poker,$play_poker);
    }

}