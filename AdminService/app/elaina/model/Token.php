<?php

namespace app\elaina\model;

use base\Model;
use AdminService\Config;
use AdminService\Exception;
use app\elaina\model\User;

class Token extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='ssd_elaina_token';

    /**
     * 为用户创建一个新令牌
     * 
     * @access public
     * @param string $nid 用户ID
     * @return string
     * @throws Exception
     */
    public function createToken(string $kid,string $nid): string {
        // 检查用户真实性(防止无意义的令牌生成)
        if(Config::get('app.config.all.user.token.check',false)||Config::get('app.config.all.user.check',false)) {
            $User=new User();
            if(empty($User->getUserInfoByNetID($nid)))
                throw new Exception("用户不存在");
        }
        // 生成令牌
        $token=\AdminService\common\uuid(true);
        // 判断是否需要删除旧令牌
        $this->where('net_id',$nid)->delete();
        // 保存新令牌
        $time = date('Y-m-d H:i:s',time());
        $this->insert(array(
            'klei_id'=>$kid,
            'net_id'=>$nid,
            'token'=>$token,
            'expire_time'=>3600,
            'effect_time'=>$time,

        ));
        return $token;
    }

    /**
     * 获取Token信息
     * 
     * @access public
     * @param string $token 用户令牌
     * @return array
     * @throws Exception
     */
    public function getTokenInfo(string $token): array {
        // 获取用户信息
        $token_info=array();
        $token_info=$this->where('token',$token)->find();
        // 检查令牌是否过期
        if(empty($token_info))
            throw new Exception('令牌不存在,请重新登录');
        // 判断是否需要强制验证用户真实性
        if(Config::get('app.config.all.user.check',false)) {
            $User=new User();
            if(empty($User->getUserInfoByNetID($token_info['net_id'])))
                throw new Exception("用户不存在");
        }
        // 判断是否允许续签令牌
        // if(Config::get('app.config.all.user.token.allow_renew',false)) {
        //     $this->where('token',$token)->update(array(
        //         'expire_time'=>time()+Config::get('app.config.all.user.token.expire',3600)
        //     ));
        // }
        return $token_info;
    }
    public function updateTokenInfo(string $token,int $diff_time):void
    {
        $token_info=array();
        $token_info=$this->where('token',$token)->find();
        // 检查令牌是否过期
        if(empty($token_info))
            throw new Exception('令牌不存在,请重新登录');
        
        $time = date('Y-m-d H:i:s',time());
        $this->where('token',$token)->update(array(
                        'effect_time'=>$time,
                        'expire_time' =>$token_info['expire_time'] - $diff_time
                    ));
    }

}