<?php

namespace app\elaina\model;

use base\Model;
use AdminService\Config;
use AdminService\Exception;
use AdminService\model\Token;

class User extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='ssd_elaina_user';

    /**
     * 校验net_id
     * 
     * @access private
     * @param string $nid
     * @return bool
     */
    private function checkNetId(string $nid):bool{
        if(strlen($nid) != 20){
            return true;
        }
        if(substr($nid,1,2)!="U_" || !is_numeric(substr($nid,3,17))  ){
        return true;
        }
        return false;
    }
    /**
     * 校验klei_id
     * 
     * @access private
     * @param string $nid
     * @return bool
     */
    private function checkKleiId(string $kid):bool{
        if(strlen($kid) != 11){
            return true;
        }
        if(substr($kid,0,3) != "KU_"){
            return true;
        }
        return false;
    }

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
        //校验net_id
        if($this->checkNetId($nid))
            throw new Exception("net_id不正确");
        // 校验klei_id
        if(!empty($kid)){
            if($this->checkKleiId($kid))
            throw new Exception("klei_id不正确");
        }
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
        // // 校验nei_id
        // if($this->checkNetId($nid))
        //     throw new Exception("net_id不正确");
        // // 校验klei_id
        // if(!empty($kid)){
        //     if($this->checkKleiId($kid))
        //     throw new Exception("klei_id不正确");
        // }
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
            'register_date'=>$time,
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