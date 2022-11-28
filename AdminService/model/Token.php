<?php

namespace AdminService\model;

use base\Model;
use AdminService\Config;
use AdminService\Exception;
use AdminService\model\User;

class Token extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='trading_system_elaina_user_token';

    /**
     * 为用户创建一个新令牌
     * 
     * @access public
     * @param string $uuid 用户ID
     * @param string $expire 令牌过期时间
     * @return string
     * @throws Exception
     */
    public function createToken(string $uuid,string $expire=null): string {
        // 检查用户真实性(防止无意义的令牌生成)
        if(Config::get('app.config.all.user.token.check',false)||Config::get('app.config.all.user.check',false)) {
            $User=new User();
            if(empty($User->getUserInfoByUUID($uuid)))
                throw new Exception("用户不存在");
        }
        // 生成令牌
        $token=\AdminService\common\uuid(true);
        $timestamp=time();
        $expire=$timestamp+($expire??Config::get('app.config.all.user.token.expire',3600));
        // 判断是否需要删除旧令牌
        if(!Config::get('app.config.all.user.token.allow_multiple',false))
            $this->where('uuid',$uuid)->delete();
        // 保存新令牌
        $this->insert(array(
            'uuid'=>$uuid,
            'token'=>$token,
            'create_time'=>$timestamp,
            'expire_time'=>$expire
        ));
        return $token;
    }

    /**
     * 获取用户信息
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
        if(empty($token_info)||$token_info['expire_time']<time())
            throw new Exception('令牌已过期');
        // 判断是否需要强制验证用户真实性
        if(Config::get('app.config.all.user.check',false)) {
            $User=new User();
            if(empty($User->getUserInfoByUUID($token_info['uuid'])))
                throw new Exception("用户不存在");
        }
        // 判断是否允许续签令牌
        if(Config::get('app.config.all.user.token.allow_renew',false)) {
            $this->where('token',$token)->update(array(
                'expire_time'=>time()+Config::get('app.config.all.user.token.expire',3600)
            ));
        }
        return $token_info;
    }

}