<?php

namespace AdminService\model;

use base\Model;
use AdminService\Config;
use AdminService\Exception;
use AdminService\model\Token;

class User extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='trading_system_elaina_user';

    /**
     * 将密码加密
     * 
     * @access private
     * @param string $password
     * @return string
     */
    private function encryptPassword(string $password):string {
        return md5(Config::get('app.config.all.user.salt').$password);
    }

    /**
     * 用户登录
     * 
     * @access public
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $nickname 昵称
     * @return array
     * @throws Exception
     */
    public function login(string $username,string $password,?string $nickname): array {
        // 处理用户登录
        $password=$this->encryptPassword($password);
        $user_info=$this->where('username',$username)->where('password',$password)->find(array('uuid','status'));
        if(empty($user_info))
            throw new Exception("用户名或密码错误");
        if($user_info['status']!==1)
            throw new Exception("用户状态异常");
        $uuid=$user_info['uuid'];
        // 更新用户信息
        if($nickname!==null) {
            $this->where('uuid',$uuid)->update(array(
                'nickname'=>$nickname
            ));
        }
        // 生成令牌
        $Token=new Token();
        $token=$Token->createToken($uuid);
        return array(
            'uuid'=>$uuid,
            'token'=>$token
        );
    }

    /**
     * 用户注册
     * 
     * @access public
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $nickname 昵称
     * @param string $qq QQ号
     * @param string $money 余额
     * @return array
     * @throws Exception
     */
    public function register(string $username,string $password,string $nickname='',string $qq='',int $money=0): string {
        // 处理用户注册
        $password=$this->encryptPassword($password);
        // 生成UUID
        $uuid=\AdminService\common\uuid(true);
        // 判断用户名是否已存在
        $user_info=$this->where('username',$username)->find();
        if(!empty($user_info))
            throw new Exception("用户名已存在");
        // 保存用户信息
        $this->insert(array(
            'username'=>$username,
            'password'=>$password,
            'uuid'=>$uuid,
            'nickname'=>$nickname,
            'qq'=>$qq,
            'money'=>$money,
            'create_time'=>time()
        ));
        return $uuid;
    }

    /**
     * 通过UUID获取用户信息(需要用户状态正常)
     * 
     * @access public
     * @param string $uuid
     * @return array
     * @throws Exception
     */
    public function getUserInfoByUUID(string $uuid): array {
        $user_info=$this->where('uuid',$uuid)->find();
        if(empty($user_info))
            throw new Exception("用户不存在");
        if($user_info['status']!==1)
            throw new Exception("用户状态异常");
        return $user_info;
    }

}