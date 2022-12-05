<?php

namespace app\index\controller;

use base\Controller;
use AdminService\Config;
use AdminService\Exception;
use AdminService\model\User;

use function AdminService\common\json;

class Login extends Controller {

    public function index() {
        // 获取用户名和密码
        $username=$this->param('username','');
        $password=$this->param('password','');
        $nickname=$this->param('nickname',null);
        try {
            // 实例化模型
            $User=new User();
            // 登录
            $user_info=$User->login($username,$password,$nickname);
            // 设置cookie信息
            $this->cookie(array(
                'token'=>$user_info['token'],
                'uuid'=>$user_info['uuid']
            ));
            return json(1,'登录成功',array(
                'token'=>$user_info['token'],'uuid'=>$user_info['uuid']
            ));
        } catch(Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    public function bind() {
        // 获取用户名和密码
        $username=$this->param('username','');
        $password=$this->param('password','');
        $nickname=$this->param('nickname','');
        $money=$this->param('money',0);
        $qq=$this->param('qq','');
        $sign=$this->param('sign','');
        try {
            // 验证参数
            if(!preg_match(Config::get('app.config.all.user.register.rule.username'),$username))
                throw new Exception("用户名不合法");
            if(!preg_match(Config::get('app.config.all.user.register.rule.password'),$password))
                throw new Exception("密码不合法");
            // 验证签名
            $token=Config::get('app.config.all.user.register.bind_token');
            $sign_array=array(
                'username'=>$username,
                'password'=>$password,
                'nickname'=>$nickname,
                'money'=>$money,
                'qq'=>$qq,
                'token'=>$token
            );
            $server_sign=\AdminService\common\sign($sign_array);
            if($sign!==$server_sign)
                throw new Exception("签名错误");
                // 实例化模型
                $User=new User();
                // 注册
                $uuid=$User->register($username,$password,$nickname,$qq,$money);
                return json(1,'绑定成功',array(
                    'uuid'=>$uuid
                ));
        } catch(Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

}

?>