<?php

namespace app\elaina\model;

use base\Model;
use AdminService\Exception;
use app\elaina\model\Token;

class User extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='ssd_elaina_user';

    /**
     * 用户登录
     * 
     * @access public
     * @param string $kid 克雷ID
     * @param string $nid netID
     * @param string $nickname 昵称
     * @return array
     * @throws Exception
     */
    public function login(string $kid,string $nid,string $nickname='',): string {
        // 处理用户登录
        $user_info=$this->where('net_id',$nid)->find(array('net_id'));
        if (empty($user_info))
            return $this->register($kid, $nid, $nickname);
        
        $ip=\AdminService\common\ipaddress();
        $time = date('Y-m-d H:i:s',time());
        $net_id=$user_info['net_id'];
        $this->where('net_id',$net_id)->update(array(
                "klei_id"=>$kid,
                'current_name'=>$nickname,
                'login_time'=>$time,
                'login_ipaddress'=>$ip
            ));
        // 生成令牌
        $Token=new Token();
        return $Token->createToken($kid,$nid);
    }


    /**
     * 用户注册
     * 
     * @access public
     * @param string $kid 克雷ID
     * @param string $nid netID
     * @param string $nickname 昵称
     * @return string
     * @throws Exception
     */
    public function register(string $kid,string $nid,string $nickname='',): string {
        if(strcmp(substr($nid, 0, 3),"OU_") == 0){
            $game_platform="steam";
        }else{
            $game_platform="wegame";
        }
        $ip=\AdminService\common\ipaddress();
        $time = date('Y-m-d H:i:s',time());
        // 保存用户信息
        $this->insert(array(
            'klei_id'=>$kid,
            'net_id'=>$nid,
            'game_platform'=>$game_platform,
            'register_name'=>$nickname,
            'current_name'=>$nickname,
            'register_time'=>$time,
            'login_time'=>$time,
            'login_ipaddress'=>$ip
        ));
        $Token = new Token();
        return $Token->createToken($kid,$nid);
    }

    /**
     * 通过NetID获取用户信息(需要用户状态正常)
     * 
     * @access public
     * @param string $nid
     * @return array
     * @throws Exception
     */
    public function getUserInfoByNetID(string $nid): array {
        $user_info=$this->where('net_id',$nid)->find();
        if(empty($user_info))
            throw new Exception("用户不存在");
        return $user_info;
    }

}