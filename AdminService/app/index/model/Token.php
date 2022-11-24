<?php

namespace app\index\model;

use base\Model;
use AdminService\Config;
use AdminService\Exception;
use app\index\model\User;

class Token extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='trading_system_elaina_user_token';

    /**
     * 错误信息
     * @var array
     */
    public array $error_info=array();

    /**
     * 为用户创建一个新令牌
     * 
     * @access public
     * @param int $uuid 用户ID
     * @param string $expire 令牌过期时间
     * @return string
     */
    public function createToken(int $uuid,string $expire=null): string {
        // 检查用户真实性(防止无意义的令牌生成)
        if(Config::get('app.config.index.user.token.check',false))
        {
            $User=new User();
            if(empty($User->getUserInfoByUUID($uuid)))
            {
                $this->error_info['createToken']=$User->error_info['getUserInfoByUUID'];
                return '';
            }
        }
        // 生成令牌
        $token=\AdminService\common\uuid(true);
        $timestamp=time();
        $expire=$timestamp+($expire??Config::get('app.config.index.user.token.expire',3600));
        try {
            // 判断是否需要删除旧令牌
            if(Config::get('app.config.index.user.token.allow_multiple',false))
                $this->where('uuid',$uuid)->delete();
            // 保存新令牌
            $this->insert(array(
                'uuid'=>$uuid,
                'token'=>$token,
                'create_time'=>$timestamp,
                'expire_time'=>$expire
            ));
        } catch (Exception $e) {
            $this->error_info['createToken']=$e->getMessage();
            return '';
        }
        return $token;
    }
}