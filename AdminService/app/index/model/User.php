<?php

namespace app\index\model;

use base\Model;
use AdminService\Config;
use AdminService\Exception;
use app\index\model\Token;

class User extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='trading_system_elaina_user';

    /**
     * 错误信息
     * @var array
     */
    public array $error_info=array();

    /**
     * 将密码加密
     * 
     * @access private
     * @param string $password
     * @return string
     */
    private function encryptPassword(string $password):string {
        return md5(Config::get('app.config.index.user.salt').$password);
    }

    /**
     * 用户登录
     * 
     * @access public
     * @param string $username 用户名
     * @param string $password 密码
     * @return array
     */
    public function login(string $username,string $password): array {
        // 处理用户登录
        $password=$this->encryptPassword($password);
        try {
            $uuid=$this->where('username',$username)->where('password',$password)->find('uuid');
            // 生成令牌
            $Token=new Token();
            $token=$Token->createToken($uuid);
            if($token==='') {
                $this->error_info['login']=$Token->error_info['createToken'];
                return array();
            }
            return array(
                'uuid'=>$uuid,
                'token'=>$token
            );
        } catch(Exception $e) {
            $this->error_info['login']=$e->getMessage();
            return array();
        }
    }

    /**
     * 用户注册
     * 
     * @access public
     * @param string $username 用户名
     * @param string $password 密码
     * @return array
     */
    public function register(string $username,string $password): string {
        // 处理用户注册
        $password=$this->encryptPassword($password);
        // 生成UUID
        $uuid=\AdminService\common\uuid(true);
        try {
           
            // 保存用户信息
            $this->insert(array(
                'username'=>$username,
                'password'=>$password,
                'uuid'=>$uuid,
                'create_time'=>time()
            ));
        } catch (Exception $e) {
            $this->error_info['register']=$e->getMessage();
            return '';
        }
        return $uuid;
    }

    /**
     * 通过UUID获取用户信息
     * 
     * @access public
     * @param string $uuid
     * @return array
     */
    public function getUserInfoByUUID(string $uuid): array {
        $user_info=$this->where('uuid',$uuid)->find();
        if(empty($user_info)) {
            $this->error_info['getUserInfoByUUID']='用户不存在';
            return array();
        }
        return $user_info;
    }

}