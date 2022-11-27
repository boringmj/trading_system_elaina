<?php

namespace app\index\controller;

use base\Controller;
use AdminService\Config;
use AdminService\model\User;

use function AdminService\common\json;

class Login extends Controller {

    public function index() {
        // 获取用户名和密码
        $username=$this->param('username','');
        $password=$this->param('password','');
        // 实例化模型
        $User=new User();
        // 登录
        $user_info=$User->login($username,$password);
        // 验证登录结果
        if(empty($user_info))
            return json(array('code'=>-1,'msg'=>$User->error_info['login']));
        // 设置cookie信息
        $this->cookie(array(
            'token'=>$user_info['token'],
            'uuid'=>$user_info['uuid']
        ));
        return json(array('code'=>1,'msg'=>'登录成功','data'=>array(
            'token'=>$user_info['token'],
            'uuid'=>$user_info['uuid']
        )));
    }

    public function bind() {
        // 获取用户名和密码
        $username=$this->param('username','');
        $password=$this->param('password','');
        $sign=$this->param('sign','');
        // 验证参数
        if(!preg_match(Config::get('app.config.index.user.register.rule.username'),$username))
            return json(array('code'=>-1,'msg'=>'用户名不合法'));
        if(!preg_match(Config::get('app.config.index.user.register.rule.password'),$password))
            return json(array('code'=>-1,'msg'=>'密码不合法'));
        // 验证签名
        $token=Config::get('app.config.index.user.register.bind_token');
        $sign_array=array(
            'username'=>$username,
            'password'=>$password,
            'token'=>$token
        );
        $server_sign=\AdminService\common\sign($sign_array);
        if($sign!==$server_sign)
            return json(array('code'=>-1,'msg'=>'签名不合法'));
        // 实例化模型
        $User=new User();
        // 注册
        $uuid=$User->register($username,$password);
        if($uuid==='')
            return json(array('code'=>-1,'msg'=>$User->error_info['register']));
        return json(array('code'=>1,'msg'=>'绑定成功','uuid'=>$uuid));
    }

}

?>