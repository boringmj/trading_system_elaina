<?php

namespace app\elaina\model;
use app\elaina\model\Token;
use base\Model;
use AdminService\Exception;
class Time extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='ssd_elaina_time';

    /**
     * 记录用户时长信息
     * 
     * @access public
     * @param string $nid 用户NetID
     * @return array
     * @throws Exception
     */
    public function recordTime(string $token,int $necklace_time): array {
        $Token = new Token();
        $token_info = $Token->getTokenInfo($token);
        $time = time();
        $time_diff = $time - strtotime($token_info['effect_time']);
        $date = date('Y-m-d H:i:s',$time);
        if ($time_diff > 644){
            $Token->updateTokenInfo($token,$time_diff); 
            throw new Exception("两次活跃时差过大");
            
        }
        //获取旧的时间信息 
        $time_info = $this->where('net_id', $token_info['net_id'])->find();
        if (empty($time_info))
            throw new Exception("找不到时长信息");
        $arr = array(
            'game_time'=>$time_info['game_time'] + floor($time_diff/60),
            'record_time'=>$date
        );
        //上传项链时长小于10分钟,允许更新时长
        if($necklace_time < 644){
            $arr['necklace_time'] = $time_info['necklace_time'] +  
           floor($necklace_time/60) ;
        }
        $this->where('net_id', $token_info['net_id'])->update($arr);
        // 返回时长信息
        return $arr;
    }
    /**
     * 获取用户时长信息
     * 
     * @access public
     * @param string $nid 用户NetID
     * @return array
     * @throws Exception
     */
    public function getTime(string $nid){
        $time_info = $this->where('net_id', $nid)->find();
        if (empty($time_info))
            throw new Exception("找不到时长信息");
        return $time_info;
    }
}