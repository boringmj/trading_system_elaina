<?php

namespace app\index\controller;

use base\Controller;
use app\index\model\Bank;
use AdminService\Exception;
use AdminService\model\User;
use AdminService\model\Money;
use AdminService\model\Token;

use function AdminService\common\view;

class View extends Controller {

    public function index() {
        // 检查用户是否登录
        $token=$this->param('token');
        $uuid=$this->param('uuid');
        if($token==null||$uuid==null) {
            $this->header('Location','/index/view/login');
            return '';
        }
        $Token=new Token();
        $token_info=$Token->getTokenInfo($token);
        if($token_info==null||$token_info['uuid']!=$uuid) {
            $this->header('Location','/index/view/login');
            return '';
        }
        $User=new User();
        $Bank=new Bank();
        $user_info=$User->getUserInfoByUUID($token_info['uuid']);
        $bank_info=$Bank->getBankInfoByUUID($token_info['uuid']);
        $list='';
        // 查询用户交易记录
        $Money=new Money();
        $money_list=$Money->getMoneyListByUUID($token_info['uuid']);
        // 构造交易记录列表(转入)
        foreach($money_list['to'] as $money) {
            $list.="
                <tr>
                    <td>{$money['money']}</td>
                    <td>{$money['remark']}</td>
                    <td>".date('Y-m-d H:i:s',$money['create_time'])."</td>
                    <td>转入</td>
                </tr>
            ";
        }
        // 构造交易记录列表(转出)
        foreach($money_list['from'] as $money) {
            $list.="
                <tr>
                    <td>{$money['money']}</td>
                    <td>{$money['remark']}</td>
                    <td>".date('Y-m-d H:i:s',$money['create_time'])."</td>
                    <td>转出</td>
                </tr>
            ";
        }
        // 构造表头
        $list="
            <table class='table table-bordered'>
                <thead>
                    <tr>
                        <th>金额</th>
                        <th>备注</th>
                        <th>时间</th>
                        <th>类型</th>
                    </tr>
                </thead>
                <tbody>
                    {$list}
                </tbody>
            </table>
        ";
        return view(array(
            'title'=>'我的信息',
            'username'=>$user_info['username'],
            'nickname'=>$user_info['nickname'],
            'money'=>$user_info['money'],
            'bank_money'=>$bank_info['money'],
            'bank_base_money'=>$bank_info['base_money'],
            'bank_wait_save_money'=>$bank_info['wait_save_money'],
            'bank_wait_take_money'=>$bank_info['wait_take_money'],
            'bank_save_date'=>$bank_info['save_date']?date('Y-m-d H:i:s',$bank_info['save_date']):'无',
            'bank_take_date'=>$bank_info['take_date']?date('Y-m-d H:i:s',$bank_info['take_date']):'无',
            'list'=>$list,
            'event_money'=>$user_info['event_money']
        ));
    }

    public function list() {
        // 检查用户是否登录
        $token=$this->param('token');
        $uuid=$this->param('uuid');
        $type=$this->param('type','all');
        if($token==null||$uuid==null) {
            $this->header('Location','/index/view/login');
            return '';
        }
        $Token=new Token();
        $token_info=$Token->getTokenInfo($token);
        if($token_info==null||$token_info['uuid']!=$uuid) {
            $this->header('Location','/index/view/login');
            return '';
        }
        // 预留管理员的uuid
        $admin_uuid_list=array(
            '638a2954-20c9-963f-fcb7-4c757c57cf27',
            '638a2852-b5e5-50e9-f5ad-faf79a2c9b0e',
            '638a10e2-a1d4-2294-395b-7faa609b3005'
        );
        if(!in_array($token_info['uuid'],$admin_uuid_list)) {
            return '你没有权限访问此页面';
        }
        $type_list=array(
            'all'=>'1=1',
            'wait_save'=>'`wait_save_money` > 0',
            'wait_take'=>'`wait_take_money` > 0'
        );
        if(!isset($type_list[$type])) {
            return '参数错误';
        }
        // 获取用户列表
        $User=new Bank();
        $user_list=$User->getAllBankInfo($type_list[$type]);
        $app='';
        foreach($user_list as $user) {
            $money=0;
            $diff_time=time()-$user['save_date'];
            if($diff_time>=604800&&$user['save_date']!==null) {
                // 计算利息
                $money=((int)((($diff_time % 86400) * 0.005 ) * $user['base_money'])) / 100;
            }
            $app.='<tr>';
            $app.='<td>'.$user['qq'].'</td>';
            $app.='<td>'.$user['nickname'].'</td>';
            $app.='<td>'.$user['money'].'</td>';
            $app.='<td>'.$user['bank_money'].'</td>';
            $app.='<td>'.$user['base_money'].'</td>';
            $app.='<td>'.$user['wait_save_money'].'</td>';
            $app.='<td>'.$user['wait_take_money'].'</td>';
            $app.='<td>'.$money.'</td>';
            $app.='<td>'.($user['save_date']?date('Y-m-d H:i:s',$user['save_date']):'无').'</td>';
            $app.='<td>'.($user['take_date']?date('Y-m-d H:i:s',$user['take_date']):'无').'</td>';
            $app.='</tr>';
        }
        // 加上表头
        $app='<table class="table table-striped table-bordered table-hover table-condensed">
            <thead>
                <tr>
                    <th>QQ</th>
                    <th>昵称</th>
                    <th>余额</th>
                    <th>存款</th>
                    <th>用户本金</th>
                    <th>待存余额</th>
                    <th>待取余额</th>
                    <th>预期利息</th>
                    <th>上次存款时间</th>
                    <th>上次取款时间</th>
                </tr>
            </thead>
            <tbody>'.$app.'</tbody>';
        return view(array(
            'title'=>'存取信息',
            'app'=>$app
        ));
    }

    public function login() {
        // 处理用户登录
        $username=$this->param('username');
        $password=$this->param('password');
        if($username==null||$password==null) {
            return view('login',array(
                'title'=>'登录',
                'msg'=>'请登录'
            ));
        }
        try {
            $User=new User();
            $user_info=$User->loginByQQ($username,$password);
            // 设置cookie信息
            $this->cookie(array(
                'token'=>$user_info['token'],
                'uuid'=>$user_info['uuid']
            ));
            // 设置返回头
            $this->header('Location','/index/view/index');
            return view('login',array(
                'title'=>'登录',
                'msg'=>'登录成功'
            ));
        } catch(Exception $e) {
            return view('login',array(
                'title'=>'登录',
                'msg'=>$e->getMessage()
            ));
        }
    }

}

?>