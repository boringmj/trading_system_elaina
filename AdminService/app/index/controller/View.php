<?php

namespace app\index\controller;

use AdminService\App;
use base\Controller;
use app\index\model\Bank;
use AdminService\Exception;
use AdminService\model\User;
use AdminService\model\Money;
use AdminService\model\Token;

use function AdminService\common\view;

class View extends Controller {

    public function shop() {
        return view('shop');
    }

    public function index() {
        // 检查用户是否登录
        $token=$this->param('token_user');
        $uuid=$this->param('uuid_user');
        if($token==null||$uuid==null) {
            $this->header('Location','/index/view/login');
            return '';
        }
        try {
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
            $new_money_list = array_merge($money_list['to'],$money_list['from']);
            usort($new_money_list,function($a,$b) {
                if($a['create_time']===$b['create_time'])
                    return 0;
                return ($a['create_time']>$b['create_time'])?-1:1;;
            });
            // 构造交易记录列表(转入)
            $count=1;
            $currency_name=App::getClass('Config')::get('app.config.all.view.currency_name');
            foreach($new_money_list as $money) {
                $type="转出";
                if($user_info['uuid']===$money['uuid'])
                    $type="转入";
                $list.="
                    <tr>
                        <td>{$count}</td>
                        <td>{$money['money']} {$currency_name}</td>
                        <td>".htmlspecialchars($money['remark']??'')."</td>
                        <td>".date('Y-m-d H:i:s',$money['create_time'])."</td>
                        <td>{$type}</td>
                    </tr>
                ";
                if($count===App::getClass('Config')::get('app.config.all.view.list_max'))
                    break;
                $count++;
            }
            // 构造交易记录列表(转出)
            // foreach($money_list['from'] as $money) {
            //     $list.="
            //         <tr>
            //             <td>{$money['money']} {$currency_name}</td>
            //             <td>".htmlspecialchars($money['remark']??'')."</td>
            //             <td>".date('Y-m-d H:i:s',$money['create_time'])."</td>
            //             <td>转出</td>
            //         </tr>
            //     ";
            // }
            // 构造表头
            $list="
                <table class='table table-bordered'>
                    <thead>
                        <tr>
                            <th>序号</th>
                            <th>{$currency_name}</th>
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
            $money=0;
            $diff_time=time()-$bank_info['save_date'];
            $date_min=App::getClass('Config')::get('app.config.all.bank.interest.date_min')*60*60*24;
            $rate=App::getClass('Config')::get('app.config.all.bank.interest.rate');
            if($diff_time>=$date_min&&$bank_info['save_date']!==null) {
                // 计算利息
                $money=((int)((($diff_time / 86400) * $rate ) * $bank_info['base_money'] * 100)) / 100;
            }
            $money_expected=((int)((($diff_time / 86400) * $rate ) * $bank_info['base_money'] * 100)) / 100;
            return view(array(
                'title'=>'我的信息',
                'username'=>htmlspecialchars($user_info['username']??''),
                'nickname'=>htmlspecialchars($user_info['nickname']??''),
                'money'=>$user_info['money'],
                'bank_money'=>$bank_info['money'],
                'bank_base_money'=>$bank_info['base_money'],
                'bank_wait_save_money'=>$bank_info['wait_save_money'],
                'bank_wait_take_money'=>$bank_info['wait_take_money'],
                'bank_save_date'=>$bank_info['save_date']?date('Y-m-d H:i:s',$bank_info['save_date']):'无',
                'bank_take_date'=>$bank_info['take_date']?date('Y-m-d H:i:s',$bank_info['take_date']):'无',
                'list'=>$list,
                'event_money'=>$user_info['event_money'],
                'bank_money_tow'=>$money,
                'bank_money_tow_expected'=>$money_expected,
                'money_total'=>$user_info['money']+$bank_info['money']+$money,
                'currency_name'=>$currency_name,
            ));
        } catch(Exception $e) {
            $this->header('Location','/index/view/login');
            return $e->getMessage();
        }
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
        try {
            $Token=new Token();
            $token_info=$Token->getTokenInfo($token);
            if($token_info==null||$token_info['uuid']!=$uuid) {
                $this->header('Location','/index/view/login');
                return '';
            }
            // 预留管理员的uuid
            $admin_uuid_list=App::getClass('Config')::get('app.config.all.bank.admin_uuid');
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
                $date_min=App::getClass('Config')::get('app.config.all.bank.interest.date_min')*60*60*24;
                if($diff_time>=$date_min&&$user['save_date']!==null) {
                    // 计算利息
                    $rate=App::getClass('Config')::get('app.config.all.bank.interest.rate');
                    $money=((int)((($diff_time / 86400) * $rate ) * $user['base_money'] * 100)) / 100;
                }
                $app.='<tr>';
                $app.='<td>'.htmlspecialchars($user['qq']??'').'</td>';
                $app.='<td>'.htmlspecialchars($user['nickname']??'').'</td>';
                //$app.='<td>'.$user['money'].'</td>';
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
                      <!--  <th>余额</th> -->
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
            $User=new User();
            $user_info=$User->getUserInfoByUUID(App::getClass('Config')::get('app.config.all.system.uuid.bank'));
            $currency_name=App::getClass('Config')::get('app.config.all.view.currency_name');
            return view(array(
                'title'=>'存取信息',
                'app'=>$app,
                'bank'=>$user_info['money'],
                'currency_name'=>$currency_name
            ));
        } catch(Exception $e) {
            $this->header('Location','/index/view/login');
            return $e->getMessage();
        }
    }

    public function login() {
        // 处理用户登录
        $username=$this->param('username','');
        $password=$this->param('password','');
        $goto=$this->param('goto','/index/view/index');
        $goto=urldecode($goto);
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
                'token_user'=>$user_info['token'],
                'uuid_user'=>$user_info['uuid']
            ));
            $this->cookie(array(
                'token'=>array(
                    'value'=>$user_info['token'],
                    'path'=>'/'
                ),
                'uuid'=>array(
                    'value'=>$user_info['uuid'],
                    'path'=>'/'
                )
            ));
            // 设置返回头
            $this->header('Location',$goto);
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